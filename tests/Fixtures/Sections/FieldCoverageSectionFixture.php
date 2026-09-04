<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Sections;

use Itineris\WpcBuilder\Fields\Checkbox;
use Itineris\WpcBuilder\Fields\CheckboxSwitch;
use Itineris\WpcBuilder\Fields\CheckboxToggle;
use Itineris\WpcBuilder\Fields\ColorPalette;
use Itineris\WpcBuilder\Fields\Custom;
use Itineris\WpcBuilder\Fields\Dimensions;
use Itineris\WpcBuilder\Fields\DropdownPages;
use Itineris\WpcBuilder\Fields\Editor;
use Itineris\WpcBuilder\Fields\Link;
use Itineris\WpcBuilder\Fields\Multicheck;
use Itineris\WpcBuilder\Fields\Number;
use Itineris\WpcBuilder\Fields\PostSelect;
use Itineris\WpcBuilder\Fields\Radio;
use Itineris\WpcBuilder\Fields\RadioButtonset;
use Itineris\WpcBuilder\Fields\Select;
use Itineris\WpcBuilder\Fields\Slider;
use Itineris\WpcBuilder\Fields\Textarea;
use Itineris\WpcBuilder\Fields\Toggle;
use Itineris\WpcBuilder\Fields\Url;
use Itineris\WpcBuilder\Sections\AbstractSection;

/**
 * One field per wpc-builder field type not already covered by
 * E2eSectionFixture (Text, Image, Color, Repeater). Exists purely so
 * tests/E2E/specs/field-coverage.spec.js can prove every control type in
 * src/Fields/ actually renders and saves against a real Customizer.
 *
 * Several fields render nothing without a choices/colors source
 * (Multicheck, Radio, RadioButtonset, Select, ColorPalette), so those are
 * given fixed choices here rather than left empty.
 */
final class FieldCoverageSectionFixture extends AbstractSection
{
    protected string $id = 'field_coverage';
    protected ?string $title = 'Field coverage';

    protected function fields(): array
    {
        return [
            Checkbox::make('checkbox_field')->setLabel('Checkbox'),
            CheckboxToggle::make('checkbox_toggle_field')->setLabel('Checkbox toggle'),
            CheckboxSwitch::make('checkbox_switch_field')->setLabel('Checkbox switch'),
            Toggle::make('toggle_field')->setLabel('Toggle'),
            ColorPalette::make('color_palette_field')
                ->setLabel('Colour palette')
                ->setColors(['#ff0000', '#0000ff']),
            Custom::make('custom_field')
                ->setLabel('Custom')
                ->setHtml('<p data-wpc-builder-e2e="custom_field">Custom field markup</p>'),
            Dimensions::make('dimensions_field')->setLabel('Dimensions'),
            DropdownPages::make('dropdown_pages_field')->setLabel('Dropdown pages'),
            Editor::make('editor_field')->setLabel('Editor'),
            Link::make('link_field')->setLabel('Link'),
            Multicheck::make('multicheck_field')
                ->setLabel('Multicheck')
                ->setChoices(['a' => 'A', 'b' => 'B']),
            Number::make('number_field')
                ->setLabel('Number')
                ->setMin(0)
                ->setMax(100),
            PostSelect::make('post_select_field')->setLabel('Post select'),
            Radio::make('radio_field')
                ->setLabel('Radio')
                ->setChoices(['a' => 'A', 'b' => 'B']),
            RadioButtonset::make('radio_buttonset_field')
                ->setLabel('Radio buttonset')
                ->setChoices(['a' => 'A', 'b' => 'B']),
            Select::make('select_field')
                ->setLabel('Select')
                ->setChoices(['a' => 'A', 'b' => 'B']),
            Slider::make('slider_field')->setLabel('Slider'),
            Textarea::make('textarea_field')->setLabel('Textarea'),
            Url::make('url_field')->setLabel('URL'),
        ];
    }
}
