<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Services\InstagramPublisher;
use App\Models\InstagramPost;
use App\Models\User;
use App\Notifications\InstagramPostFailed;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class PublishInstagramPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public InstagramPost $post) {}

    public function handle(): void
    {
        $this->post->publish_attempted_at = now();
        $this->post->save();

        if ($this->isTokenExpired()) {
            $this->markFailed('Instagram access token has expired.');

            return;
        }

        try {
            $this->publish();
        } catch (Throwable $throwable) {
            $this->markFailed($throwable->getMessage());

            throw $throwable;
        }
    }

    private function publish(): void
    {
        // Implement actual Instagram publishing here. For now we simply mark the post as published.
        $this->post->forceFill([
            'status' => InstagramPost::STATUS_PUBLISHED,
            'published_at' => now(),
            'failure_reason' => null,
        ])->save();
    }

    private function markFailed(string $reason): void
    {
        $this->post->forceFill([
            'status' => InstagramPost::STATUS_FAILED,
            'failure_reason' => $reason,
        ])->save();

        $this->sendFailureNotifications($reason);
    }

    private function sendFailureNotifications(string $message): void
    {
        $users = User::whereNotNull('email')->get();

        Notification::send($users, new InstagramPostFailed($this->post, $message));

        FilamentNotification::make()
            ->title('Gagal mempublikasikan konten Instagram')
            ->body($message)
            ->danger()
            ->sendToDatabase($users);
    }

    private function tokenExpiresAt(): ?CarbonImmutable
    {
        $value = config('services.instagram.token_expires_at');

        return $value ? CarbonImmutable::parse($value) : null;
    }

    private function isTokenExpired(): bool
    {
        $expiresAt = $this->tokenExpiresAt();

        return $expiresAt !== null && $expiresAt->isPast();
    }
}
