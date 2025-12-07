<?php

namespace App\Services;

use App\Models\InstagramTemplate;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Interfaces\ImageInterface;

class ImageTemplateService
{
    public function applyTemplate(string $photoPath, InstagramTemplate $template): string
    {
        $image = $this->loadImage(Storage::disk('public')->path($photoPath));
        $frame = $this->loadImage(Storage::disk('public')->path($template->frame_path));

        $canvas = $this->prepareCanvas($template, $frame);
        $image = $this->fitToCanvas($image, $canvas->width(), $canvas->height());

        $canvas->place($image);
        $canvas->place($frame);

        $outputPath = 'instagram/processed/' . uniqid('framed_', true) . '.jpg';
        Storage::disk('public')->makeDirectory('instagram/processed');

        $canvas->save(Storage::disk('public')->path($outputPath), quality: 90);

        return $outputPath;
    }

    protected function prepareCanvas(InstagramTemplate $template, ImageInterface $frame): ImageInterface
    {
        $width = $template->canvas_width ?: $frame->width();
        $height = $template->canvas_height ?: $frame->height();

        return Image::canvas($width, $height);
    }

    protected function fitToCanvas(ImageInterface $image, int $width, int $height): ImageInterface
    {
        return $image->scaleDown(width: $width, height: $height)->cover($width, $height);
    }

    protected function loadImage(string $path): ImageInterface
    {
        return Image::read($path);
    }
}
