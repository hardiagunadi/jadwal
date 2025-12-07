<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class InstagramPublisher
{
    public function createMediaContainer(array $payload): array
    {
        $endpoint = $this->graphUrl(sprintf('%s/media', $this->userId()));

        $response = Http::asForm()->post($endpoint, array_merge($payload, [
            'access_token' => $this->accessToken(),
        ]));

        return $response->json();
    }

    public function uploadVideo(string $containerId, UploadedFile|string $filePath): array
    {
        $uploadId = $containerId;
        $url = sprintf('https://rupload.facebook.com/ig-api-upload/%s', $uploadId);
        $file = $filePath instanceof UploadedFile ? $filePath->getRealPath() : $filePath;
        $size = filesize($file);

        $response = Http::withHeaders([
            'Content-Type' => 'application/octet-stream',
            'Offset' => 0,
            'X-Entity-Name' => $uploadId,
            'X-Entity-Length' => $size,
            'X-Instagram-Rupload-Params' => json_encode([
                'name' => $uploadId,
                'upload_id' => $uploadId,
                'media_type' => 2,
            ]),
        ])->withBody(file_get_contents($file), 'application/octet-stream')->post($url);

        return $response->json();
    }

    public function publish(string $creationId): array
    {
        $endpoint = $this->graphUrl(sprintf('%s/media_publish', $this->userId()));

        $response = Http::asForm()->post($endpoint, [
            'creation_id' => $creationId,
            'access_token' => $this->accessToken(),
        ]);

        return $response->json();
    }

    public function graphUrl(string $path): string
    {
        $base = rtrim(config('services.instagram.graph_url', 'https://graph.facebook.com/v21.0'), '/');

        return $base . '/' . ltrim($path, '/');
    }

    protected function accessToken(): string
    {
        return config('services.instagram.access_token', '');
    }

    protected function userId(): string
    {
        return (string) config('services.instagram.ig_user_id', '');
    }
}
