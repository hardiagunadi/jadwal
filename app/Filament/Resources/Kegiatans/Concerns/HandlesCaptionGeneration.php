<?php

namespace App\Filament\Resources\Kegiatans\Concerns;

use App\Models\CaptionGenerationLog;
use App\Services\CaptionGenerator;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Throwable;

trait HandlesCaptionGeneration
{
    public function generateCaption(): void
    {
        $state = $this->form->getState();

        $keywords = trim((string) ($state['caption_keywords'] ?? ''));
        $brandStyle = trim((string) ($state['caption_brand_style'] ?? ''));
        $maxLength = (int) ($state['caption_max_length'] ?? 0);

        if ($keywords === '' || $brandStyle === '' || $maxLength <= 0) {
            Notification::make()
                ->title('Lengkapi parameter caption terlebih dahulu')
                ->danger()
                ->send();

            return;
        }

        if (! config('openai.api_key')) {
            Notification::make()
                ->title('OPENAI_API_KEY belum dikonfigurasi')
                ->body('Tambahkan OPENAI_API_KEY di environment untuk mengaktifkan generator caption.')
                ->danger()
                ->send();

            $this->logCaptionGeneration($keywords, $brandStyle, $maxLength, status: 'failed', error: 'OPENAI_API_KEY belum dikonfigurasi.');

            return;
        }

        try {
            $caption = app(CaptionGenerator::class)
                ->generate($keywords, $brandStyle, $maxLength);

            $state['generated_caption'] = $caption;
            $this->form->fill($state);

            $this->logCaptionGeneration($keywords, $brandStyle, $maxLength, $caption);

            Notification::make()
                ->title('Caption berhasil dibuat')
                ->body($caption)
                ->success()
                ->persistent()
                ->send();
        } catch (Throwable $exception) {
            $this->logCaptionGeneration($keywords, $brandStyle, $maxLength, status: 'failed', error: $exception->getMessage());

            Notification::make()
                ->title('Gagal membuat caption')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function logCaptionGeneration(
        string $keywords,
        string $brandStyle,
        int $maxLength,
        ?string $caption = null,
        string $status = 'success',
        ?string $error = null,
    ): void {
        CaptionGenerationLog::create([
            'kegiatan_id' => $this->getCurrentKegiatanId(),
            'user_id' => Auth::id(),
            'keywords' => $keywords,
            'brand_style' => $brandStyle,
            'max_length' => $maxLength,
            'generated_caption' => $caption,
            'status' => $status,
            'error_message' => $error,
        ]);
    }

    protected function getCurrentKegiatanId(): ?int
    {
        if (! property_exists($this, 'record')) {
            return null;
        }

        return $this->record?->getKey();
    }
}
