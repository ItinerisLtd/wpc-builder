<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Sections;

use Itineris\WpcBuilder\Fields\Color;
use Itineris\WpcBuilder\Fields\Image;
use Itineris\WpcBuilder\Fields\Repeater;
use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Sections\AbstractSection;
use Itineris\WpcBuilder\Support\RowLabel;

use function Itineris\WpcBuilder\Tests\E2E\render_live_message;

/**
 * One field per browser/e2e scenario in tests/E2E/specs: a Repeater with
 * an image sub-field (row add/remove/reorder + its own media modal), a
 * standalone Image field (media modal), a Color field pre-seeded with an
 * rgba() value (docs/known-limitations.md's hand-traced picker limitation),
 * and a selective-refresh Text field (live preview).
 */
final class E2eSectionFixture extends AbstractSection
{
    protected string $id = 'e2e';
    protected ?string $title = 'E2E';

    protected function fields(): array
    {
        return [
            Repeater::make('gallery')
                ->setLabel('Gallery')
                ->setFields([
                    Text::make('caption')->setLabel('Caption'),
                    Image::make('photo')->setLabel('Photo'),
                ])
                ->setRowLabel(RowLabel::fromField('caption')),
            Image::make('hero_image')
                ->setLabel('Hero image'),
            Color::make('accent_color')
                ->setLabel('Accent colour')
                ->setDefaultValue('rgba(12,34,56,0.5)'),
            Text::make('live_message')
                ->setLabel('Live message')
                ->setDefaultValue('original')
                ->setPartialRefresh('[data-wpc-builder-e2e="live_message"]', render_live_message(...)),
        ];
    }
}
