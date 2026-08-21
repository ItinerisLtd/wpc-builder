<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Sections;

use Itineris\WpcBuilder\Fields\Image;
use Itineris\WpcBuilder\Sections\AbstractSection;

/**
 * One section with a single Image field, so a test can drive
 * Fields\Image::afterRegister() through a real register() call, the only
 * path that fires it.
 */
final class ImageSectionFixture extends AbstractSection
{
    protected string $id = 'image_section';
    protected ?string $title = 'Image';

    protected function fields(): array
    {
        return [Image::make('logo')];
    }
}
