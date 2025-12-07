<?php

namespace App\Services;

use App\Models\PhotoTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;

class TemplateRenderer
{
    protected ImageManager $images;

    public function __construct()
    {
        $this->images = new ImageManager(new Driver());
    }

    public function render(PhotoTemplate $template, UploadedFile|string $photo, ?string $outputPath = null): string
    {
        $overlayConfig = $template->overlay_config ?? [];

        $basePath = Storage::disk('public')->path($template->base_image);
        $canvas = $this->images->read($basePath);

        $photoPath = $photo instanceof UploadedFile ? $photo->getRealPath() : $photo;
        $photoImage = $this->images->read($photoPath);

        $photoSlot = $overlayConfig['photo_slot'] ?? null;

        if (is_array($photoSlot)) {
            $targetWidth = (int) ($photoSlot['width'] ?? 0);
            $targetHeight = (int) ($photoSlot['height'] ?? 0);

            if ($targetWidth > 0 && $targetHeight > 0) {
                $photoImage->cover($targetWidth, $targetHeight);
            }

            $canvas->place(
                $photoImage,
                'top-left',
                (int) ($photoSlot['x'] ?? 0),
                (int) ($photoSlot['y'] ?? 0)
            );
        }

        foreach ($overlayConfig['logos'] ?? [] as $logo) {
            if (! isset($logo['path'])) {
                continue;
            }

            $logoPath = Storage::disk('public')->path($logo['path']);
            $logoImage = $this->images->read($logoPath);

            $logoWidth = (int) ($logo['width'] ?? 0);
            $logoHeight = (int) ($logo['height'] ?? 0);

            if ($logoWidth > 0 && $logoHeight > 0) {
                $logoImage->cover($logoWidth, $logoHeight);
            }

            $canvas->place(
                $logoImage,
                'top-left',
                (int) ($logo['x'] ?? 0),
                (int) ($logo['y'] ?? 0)
            );
        }

        foreach ($overlayConfig['texts'] ?? [] as $text) {
            if (! isset($text['label'], $text['x'], $text['y'])) {
                continue;
            }

            $canvas->text(
                $text['label'],
                (int) $text['x'],
                (int) $text['y'],
                function (FontFactory $font) use ($text): void {
                    if (! empty($text['font_path'])) {
                        $font->filename(Storage::disk('public')->path($text['font_path']));
                    }

                    $font->size((int) ($text['font_size'] ?? 32));
                    $font->color($text['color'] ?? '#000000');
                    $font->align($text['align'] ?? 'left');
                }
            );
        }

        $filename = $outputPath ?? 'rendered/'.Str::uuid().'.png';
        $storagePath = Storage::disk('public')->path($filename);

        if (! is_dir(dirname($storagePath))) {
            mkdir(dirname($storagePath), recursive: true);
        }

        $canvas->save($storagePath);

        return $filename;
    }
}
