<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Services\InstagramPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishInstagramPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SocialAccount $socialAccount,
        public string $mediaUrl,
        public ?string $caption = null,
        public bool $isVideo = false,
    ) {
    }

    public function handle(InstagramPublisher $publisher): void
    {
        try {
            $result = $publisher->publishMedia(
                $this->socialAccount,
                $this->mediaUrl,
                $this->caption,
                $this->isVideo
            );

            $this->socialAccount->appendRequestLog([
                'action' => 'publish_media_job',
                'status' => ($result['success'] ?? false) ? 'success' : 'failed',
                'request' => $this->socialAccount->maskSensitivePayload([
                    'media_url' => $this->mediaUrl,
                    'caption' => $this->caption,
                    'is_video' => $this->isVideo,
                ]),
                'response' => $result,
                'error' => ($result['success'] ?? false) ? null : 'PublishInstagramPost tidak berhasil.',
            ]);
        } catch (Throwable $e) {
            Log::error('Gagal mem-publish ke Instagram', [
                'account_id' => $this->socialAccount->getKey(),
                'error' => $e->getMessage(),
            ]);

            $this->socialAccount->appendRequestLog([
                'action' => 'publish_media_job',
                'status' => 'failed',
                'request' => $this->socialAccount->maskSensitivePayload([
                    'media_url' => $this->mediaUrl,
                    'caption' => $this->caption,
                    'is_video' => $this->isVideo,
                ]),
                'response' => null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
