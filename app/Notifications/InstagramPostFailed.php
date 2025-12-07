<?php

namespace App\Notifications;

use App\Models\InstagramPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InstagramPostFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly InstagramPost $post, private readonly string $reason) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Gagal mempublikasikan konten Instagram')
            ->line('Sistem gagal mempublikasikan konten Instagram terjadwal.')
            ->line('Alasan: ' . $this->reason)
            ->line('ID Konten: ' . $this->post->getKey())
            ->line('Status saat ini: ' . $this->post->status);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => (string) $this->post->getKey(),
            'status' => $this->post->status,
            'reason' => $this->reason,
        ];
    }
}
