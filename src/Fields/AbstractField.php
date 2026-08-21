<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Enums\OptionType;
use Itineris\WpcBuilder\Enums\Transport;
use Itineris\WpcBuilder\Support\ConditionEvaluator;
use Itineris\WpcBuilder\Support\RequiredWhenValidator;
use WP_Customize_Manager;
use WP_Customize_Setting;
use WP_Error;

use function __;
use function Itineris\WpcBuilder\Support\build_partial_refresh_args;
use function Itineris\WpcBuilder\Support\format_setting_id;
use function Itineris\WpcBuilder\Support\label_from_id;

abstract class AbstractField
{
    protected const string CONTROL_TYPE = 'text';

    /**
     * Fully-qualified WP_Customize_Control subclass, or null to let WordPress
     * resolve the control from the `type` argument.
     *
     * @var class-string<\WP_Customize_Control>|null
     */
    protected const CONTROL = null;

    private bool $registered = false;

    private bool $isSetUp = false;

    /** @var array<string, mixed>|null */
    private ?array $partialRefresh = null;

    protected ?string $label = null;
    protected ?string $description = null;
    protected mixed $defaultValue = null;
    protected int $priority = 10;
    protected ?Transport $transport = null;
    protected ?string $capability = null;
    protected ?OptionType $optionType = null;
    protected ?string $optionName = null;

    /** @var callable|string|null */
    protected $sanitizeCallback = null;

    /** @var callable|null */
    protected $activeCallback = null;

    /**
     * Condition rows; shape defined by the JS dependency engine, not
     * this package. Each row's `setting` key must be the real, formatted
     * setting id (matching settingId() below), not the bare field id:
     * Support\collect_visible_when_conditions() does not rewrite it. An
     * unresolvable setting id fails silently: the condition just passes
     * and the control stays visible.
     *
     * @var array<int, mixed>
     */
    protected array $visibleWhen = [];

    /**
     * Same condition-row shape as $visibleWhen above, evaluated
     * server-side by buildSettingArgs()'s composed validate_callback
     * rather than client-side. An unresolvable setting id also fails
     * open here (the condition is treated as passing), but because
     * "passes" means "the value is required" rather than "the control is
     * visible", failing open has the opposite practical effect: it makes
     * the field required rather than optional when a sibling setting
     * can't be resolved, erring toward stricter validation rather than a
     * silently-skippable field.
     *
     * @var array<int, mixed>
     */
    protected array $requiredWhen = [];

    /** @var array<string, string|int|null> */
    protected array $inputAttrs = [];

    final public function __construct(public readonly string $id)
    {
    }

    final public static function make(string $id): static
    {
        return new static($id);
    }

    final public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    final public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    final public function setDefaultValue(mixed $value): static
    {
        $this->defaultValue = $value;

        return $this;
    }

    final public function setPriority(int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    final public function setTransport(Transport $transport): static
    {
        $this->transport = $transport;

        return $this;
    }

    final public function setCapability(string $capability): static
    {
        $this->capability = $capability;

        return $this;
    }

    /**
     * Overrides Config::$optionType for this one field. Real themes mix
     * `theme_mod` and `option` fields inside a single section class, so
     * splitting such a theme across two `Customizer` instances isn't an
     * equivalent migration.
     *
     * Getting this wrong is silent and destructive: a field read from
     * the wrong storage back end comes back empty, and the first save
     * orphans the real row.
     */
    final public function setOptionType(OptionType $optionType): static
    {
        $this->optionType = $optionType;

        return $this;
    }

    /**
     * Overrides Config::$optionName for this one field. Only meaningful
     * under `OptionType::OPTION`, where a non-empty name makes the
     * setting id `"optionName[fieldId]"`. No known production caller
     * today, kept so all three storage knobs are overridable per
     * field, not just two.
     */
    final public function setOptionName(string $optionName): static
    {
        $this->optionName = $optionName;

        return $this;
    }

    /**
     * The Config this field resolves storage/capability against: the
     * Customizer-wide $config with any per-field override applied on
     * top. Public so callers outside the field get the same answer
     * without re-deriving it.
     */
    final public function effectiveConfig(Config $config): Config
    {
        if (null === $this->capability && null === $this->optionType && null === $this->optionName) {
            return $config;
        }

        return new Config(
            capability: $this->capability ?? $config->capability,
            optionType: $this->optionType ?? $config->optionType,
            optionName: $this->optionName ?? $config->optionName,
        );
    }

    final public function setSanitizeCallback(callable|string|null $callback): static
    {
        $this->sanitizeCallback = $callback;

        return $this;
    }

    /**
     * The explicitly set sanitize callback only (null when none was
     * set), not defaultSanitizeCallback(), so a caller can distinguish
     * "has its own override" from "using the built-in default". Needed
     * by Fields\Repeater to decide, for a sub-field, whether its own
     * override wins over the type-based sanitize dispatch.
     */
    final public function sanitizeCallback(): callable|string|null
    {
        return $this->sanitizeCallback;
    }

    /**
     * Accepts an array as well as a callable: a non-callable array is
     * treated as conditions, not a callback. Individually-callable
     * entries are stripped, and if anything is left at index 0 the whole
     * thing becomes `visibleWhen` and the callback is neutralised. Not
     * theoretical: this shape appears in real files across the estate.
     * `[$object, 'method']` and `['Class', 'method']` are still treated
     * as callables via `is_callable()`.
     *
     * The rule that `visibleWhen` wins and neutralises any callback is
     * applied in buildControlArgs() rather than here, so it holds
     * regardless of call order between setVisibleWhen() and
     * setActiveCallback().
     *
     * @param callable|array<int|string, mixed>|null $callback
     */
    final public function setActiveCallback(callable|array|null $callback): static
    {
        if (null === $callback || is_callable($callback)) {
            $this->activeCallback = $callback;

            return $this;
        }

        $this->activeCallback = null;

        $conditions = array_filter(
            $callback,
            static fn (mixed $row): bool => ! is_callable($row),
        );

        if (isset($conditions[0])) {
            $this->visibleWhen = array_values($conditions);
        }

        return $this;
    }

    /**
     * See the $visibleWhen property's docblock above for the row shape.
     * Notably, each row's `setting` must already be the real, formatted
     * setting/control id under option storage; this method does not
     * reformat it.
     *
     * @param array<int, mixed> $conditions
     */
    final public function setVisibleWhen(array $conditions): static
    {
        $this->visibleWhen = $conditions;

        return $this;
    }

    /**
     * See the $requiredWhen property's docblock above for the row shape
     * and fail-open behaviour. Composes with, and runs after, any
     * validate_callback the field's own settingArgs() already sets; see
     * composeRequiredWhenValidateCallback().
     *
     * @param array<int, mixed> $conditions
     */
    final public function setRequiredWhen(array $conditions): static
    {
        $this->requiredWhen = $conditions;

        return $this;
    }

    /**
     * @param array<string, string|int|null> $attrs
     */
    final public function setInputAttrs(array $attrs): static
    {
        $this->inputAttrs = $attrs;

        return $this;
    }

    /**
     * Registers a selective-refresh partial for this field's setting
     * inside register(), only when the active WP_Customize_Manager
     * actually has the selective-refresh component loaded (see that call
     * site). $containerInclusive maps straight through to
     * WP_Customize_Partial::$container_inclusive (bool, not string).
     * Configuring a partial refresh forces this field's resolved
     * transport to Transport::POST_MESSAGE unconditionally; see
     * resolveTransport().
     */
    final public function setPartialRefresh(
        string $selector,
        callable $renderCallback,
        ?bool $containerInclusive = null
    ): static {
        $this->partialRefresh = build_partial_refresh_args($selector, $renderCallback, $containerInclusive);

        return $this;
    }

    /**
     * Resolved against effectiveConfig(), not the raw $config, so a
     * field overriding setOptionName()/setOptionType() gets the setting
     * id its own storage implies.
     */
    final public function settingId(Config $config): string
    {
        return format_setting_id($this->id, $this->effectiveConfig($config));
    }

    final public function controlType(): string
    {
        return static::CONTROL_TYPE;
    }

    /**
     * The WP_Customize_Control subclass this field registers, or null
     * when WordPress resolves it from `type`. Read by
     * Customizer::register() to derive which control classes' assets a
     * site actually needs.
     *
     * @return class-string<\WP_Customize_Control>|null
     */
    final public function controlClass(): ?string
    {
        return static::CONTROL;
    }

    final public function label(): string
    {
        $this->label ??= label_from_id($this->id);

        return $this->label;
    }

    final public function description(): ?string
    {
        return $this->description;
    }

    final public function defaultValue(): mixed
    {
        return $this->defaultValue;
    }

    /**
     * @return array<string, mixed>
     */
    public function choices(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>|null
     */
    final public function partialRefresh(): ?array
    {
        return $this->partialRefresh;
    }

    /**
     * @return array<string, mixed>
     */
    final public function buildSettingArgs(Config $config): array
    {
        $this->runSetup();

        $effective = $this->effectiveConfig($config);

        $args = [
            'type' => $effective->optionType->value,
            'capability' => $effective->capability,
            'default' => $this->defaultValue,
            'transport' => $this->resolveTransport()->value,
            'sanitize_callback' => $this->sanitizeCallback ?? $this->defaultSanitizeCallback(),
            ...$this->settingArgs(),
        ];

        if ([] !== $this->requiredWhen && $this->registersSetting()) {
            $args['validate_callback'] = $this->composeRequiredWhenValidateCallback(
                is_callable($args['validate_callback'] ?? null) ? $args['validate_callback'] : null,
            );
        }

        return array_filter($args, static fn (mixed $value): bool => null !== $value);
    }

    /**
     * Unlike buildSettingArgs(), takes no Config: capability is enforced
     * on the setting, and core's check_capabilities() already defers to
     * the linked setting's check.
     *
     * @return array<string, mixed>
     */
    final public function buildControlArgs(string $sectionId): array
    {
        $this->runSetup();

        $args = [
            'section' => $sectionId,
            'type' => static::CONTROL_TYPE,
            'label' => $this->label(),
            'description' => $this->description,
            'priority' => $this->priority,
            'active_callback' => [] === $this->visibleWhen ? $this->activeCallback : null,
            'input_attrs' => [] === $this->inputAttrs ? null : $this->inputAttrs,
            'settings' => $this->registersSetting() ? null : [],
            ...$this->controlArgs(),
        ];

        return array_filter($args, static fn (mixed $value): bool => null !== $value);
    }

    /**
     * @return array<int, mixed>
     */
    final public function visibleWhen(): array
    {
        $this->runSetup();

        return $this->visibleWhen;
    }

    /**
     * @return array<int, mixed>
     */
    final public function requiredWhen(): array
    {
        $this->runSetup();

        return $this->requiredWhen;
    }

    final public function register(
        WP_Customize_Manager $customizer,
        string $sectionId,
        Config $config
    ): void {
        if ($this->registered) {
            return;
        }

        $settingId = $this->settingId($config);
        $settingArgs = $this->buildSettingArgs($config);

        if ($this->registersSetting()) {
            // @phpstan-ignore argument.type
            $customizer->add_setting($settingId, $settingArgs);
        }

        $controlClass = static::CONTROL;

        if (null === $controlClass) {
            // @phpstan-ignore argument.type
            $customizer->add_control($settingId, $this->buildControlArgs($sectionId));
        } else {
            $customizer->add_control(
                // @phpstan-ignore argument.type
                new $controlClass($customizer, $settingId, $this->buildControlArgs($sectionId)),
            );
        }

        if (
            null !== $this->partialRefresh
            && $this->registersSetting()
            && null !== $customizer->selective_refresh
            && Transport::POST_MESSAGE->value === $settingArgs['transport']
        ) {
            // @phpstan-ignore argument.type
            $customizer->selective_refresh->add_partial($settingId, $this->partialRefresh);
        }

        $this->registered = true;

        $this->afterRegister();
    }

    protected function defaultTransport(): Transport
    {
        return Transport::REFRESH;
    }

    /**
     * Whether register() calls add_setting() for this field at all. True
     * for every field but Fields\Custom.
     *
     * A display-only field that registers a setting whose
     * sanitize_callback returns null doesn't merely "write nothing", it
     * makes WordPress reject the entire changeset
     * (validate_setting_values() treats a null return as invalid, and
     * save_changeset_post() aborts the whole transaction). Registering
     * no setting removes the failure at the root: nothing named for the
     * field can enter a changeset, and the control still renders because
     * WP_Customize_Control has never required a setting.
     */
    protected function registersSetting(): bool
    {
        return true;
    }

    protected function defaultSanitizeCallback(): callable|string|null
    {
        return null;
    }

    /**
     * What counts as "blank" for setRequiredWhen()'s value_required
     * check. Deliberately not empty(): 0, '0', and false must stay valid
     * values for fields like Fields\Number/Fields\Toggle. Fields\Link and
     * Fields\Repeater override this for their own compound/multi-row
     * value shapes.
     */
    protected function isRequiredWhenValueBlank(mixed $value): bool
    {
        return null === $value || '' === $value || [] === $value;
    }

    /**
     * @return array<string, mixed>
     */
    protected function settingArgs(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function controlArgs(): array
    {
        return [];
    }

    protected function setup(): void
    {
    }

    /**
     * Called once, at the end of register(), only for a field wired into
     * a real WP_Customize_Manager. Never for a field merely exercised
     * via buildSettingArgs()/buildControlArgs() in isolation. The one
     * place besides register(), a sanitize callback, or a render method
     * that a field subclass may call WordPress functions from, e.g.
     * conditionally enqueuing a script only when this field type is in
     * use (see Fields\Editor).
     */
    protected function afterRegister(): void
    {
    }

    private function runSetup(): void
    {
        if ($this->isSetUp) {
            return;
        }

        $this->setup();

        $this->isSetUp = true;
    }

    /**
     * Wraps $inner (whatever validate_callback survived settingArgs(),
     * if any) so both run: $inner first, then the requiredWhen check
     * adds a value_required WP_Error on top of, never instead of,
     * whatever $inner already returned. WordPress calls a setting's
     * validate_callback as ($validity, $value, $setting), where $setting is
     * the WP_Customize_Setting instance itself, which is how a sibling
     * condition's value gets resolved via
     * RequiredWhenValidator::valueResolver($setting->manager).
     */
    private function composeRequiredWhenValidateCallback(?callable $inner): callable
    {
        return function (mixed $validity, mixed $value, WP_Customize_Setting $setting) use ($inner): mixed {
            $validity = null === $inner ? $validity : $inner($validity, $value, $setting);

            if (! $this->isRequiredWhenValueBlank($value)) {
                return $validity;
            }

            $required = ConditionEvaluator::passes(
                $this->requiredWhen,
                RequiredWhenValidator::valueResolver($setting->manager),
            );

            if (! $required) {
                return $validity;
            }

            $error = $validity instanceof WP_Error ? $validity : new WP_Error();
            $error->add('value_required', __('This field is required.', 'wpc-builder'));

            return $error;
        };
    }

    /**
     * Selective refresh functionally requires postMessage transport:
     * with transport: refresh, WordPress's own preview JS reloads the
     * whole iframe on every change and never sends the postMessage a
     * partial listens for, so a partial registered under
     * transport: refresh is dead weight. The resolved transport is
     * Transport::POST_MESSAGE whenever $this->partialRefresh is set,
     * regardless of setTransport() call order. A field type's own
     * settingArgs() override (e.g. Fields\Repeater) can still win by
     * returning its own 'transport' key.
     */
    private function resolveTransport(): Transport
    {
        if (null !== $this->partialRefresh) {
            return Transport::POST_MESSAGE;
        }

        return $this->transport ?? $this->defaultTransport();
    }
}
