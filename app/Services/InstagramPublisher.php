<?php

namespace App\Services;

use App\Models\SocialAccount;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class InstagramPublisher
{
    protected string $graphBaseUrl;

    public function __construct()
    {
        $version = config('services.facebook.graph_version', 'v20.0');
        $this->graphBaseUrl = rtrim(config('services.facebook.graph_url', 'https://graph.facebook.com/'), '/') . '/' . $version . '/';
    }

    public function publishMedia(SocialAccount $account, string $mediaUrl, ?string $caption = null, bool $isVideo = false): array
    {
        $container = $this->createMediaContainer($account, $mediaUrl, $caption, $isVideo);
        $creationId = $container['id'] ?? null;

        if (! $creationId) {
            $this->recordFailure($account, 'publish_media', ['media_url' => $mediaUrl], $container, 'ID container tidak ditemukan.');

            throw new RuntimeException('Gagal membuat media container Instagram.');
        }

        $publishResponse = $this->publishMediaContainer($account, $creationId);
        $success = isset($publishResponse['id']);

        $this->recordLog(
            $account,
            'publish_media',
            [
                'media_url' => $mediaUrl,
                'caption' => $caption,
                'is_video' => $isVideo,
                'creation_id' => $creationId,
            ],
            $publishResponse,
            $success,
            $success ? null : ($publishResponse['error']['message'] ?? 'Gagal mem-publish media.')
        );

        if (! $success) {
            throw new RuntimeException('Gagal mem-publish media ke Instagram.');
        }

        return [
            'success' => $success,
            'creation_id' => $creationId,
            'response' => $publishResponse,
        ];
    }

    public function createMediaContainer(SocialAccount $account, string $mediaUrl, ?string $caption, bool $isVideo): array
    {
        $payload = [
            'access_token' => $account->page_access_token,
            'caption' => $caption,
        ];

        if ($isVideo) {
            $payload['media_type'] = 'VIDEO';
            $payload['video_url'] = $mediaUrl;
        } else {
            $payload['image_url'] = $mediaUrl;
        }

        $response = $this->http()->asForm()->post(
            $this->graphUrl("{$account->instagram_business_account_id}/media"),
            $payload
        );

        $data = $response->json();
        $success = $response->successful() && isset($data['id']);

        $this->recordLog(
            $account,
            'create_media_container',
            $payload,
            $data,
            $success,
            $success ? null : ($data['error']['message'] ?? $response->reason())
        );

        if (! $success) {
            $response->throw();
        }

        return $data;
    }

    public function publishMediaContainer(SocialAccount $account, string $creationId): array
    {
        $payload = [
            'creation_id' => $creationId,
            'access_token' => $account->page_access_token,
        ];

        $response = $this->http()->asForm()->post(
            $this->graphUrl("{$account->instagram_business_account_id}/media_publish"),
            $payload
        );

        $data = $response->json();
        $success = $response->successful() && isset($data['id']);

        $this->recordLog(
            $account,
            'publish_media_container',
            $payload,
            $data,
            $success,
            $success ? null : ($data['error']['message'] ?? $response->reason())
        );

        if (! $success) {
            $response->throw();
        }

        return $data;
    }

    public function testConnection(SocialAccount $account): array
    {
        $payload = [
            'fields' => 'id,username,name',
            'access_token' => $account->page_access_token,
        ];

        $response = $this->http()->get(
            $this->graphUrl($account->instagram_business_account_id),
            $payload
        );

        $data = $response->json();
        $success = $response->successful() && isset($data['id']);

        $this->recordLog(
            $account,
            'test_connection',
            $payload,
            $data,
            $success,
            $success ? null : ($data['error']['message'] ?? $response->reason())
        );

        if (! $success) {
            $response->throw();
        }

        return [
            'success' => $success,
            'data' => $data,
        ];
    }

    public function refreshToken(SocialAccount $account): array
    {
        $appId = config('services.facebook.app_id');
        $appSecret = config('services.facebook.app_secret');

        if (! $appId || ! $appSecret) {
            throw new RuntimeException('FACEBOOK_APP_ID atau FACEBOOK_APP_SECRET belum diatur.');
        }

        $payload = [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $account->page_access_token,
        ];

        $response = $this->http()->get(
            $this->graphUrl('oauth/access_token'),
            $payload
        );

        $data = $response->json();
        $success = $response->successful() && isset($data['access_token']);

        $this->recordLog(
            $account,
            'refresh_token',
            $payload,
            $data,
            $success,
            $success ? null : ($data['error']['message'] ?? $response->reason())
        );

        if (! $success) {
            $response->throw();
        }

        $account->page_access_token = $data['access_token'];
        $account->token_expires_at = isset($data['expires_in'])
            ? now()->addSeconds((int) $data['expires_in'])
            : $account->token_expires_at;
        $account->save();

        return [
            'success' => $success,
            'expires_in' => $data['expires_in'] ?? null,
            'access_token' => Str::mask($data['access_token'], '*', 8, 8),
        ];
    }

    protected function http(): PendingRequest
    {
        return Http::acceptJson();
    }

    protected function graphUrl(string $path): string
    {
        return $this->graphBaseUrl . ltrim($path, '/');
    }

    protected function recordLog(SocialAccount $account, string $action, array $request, array $response, bool $success, ?string $error): void
    {
        $account->appendRequestLog([
            'action' => $action,
            'status' => $success ? 'success' : 'failed',
            'request' => $account->maskSensitivePayload($request),
            'response' => $response,
            'error' => $error,
        ]);
    }

    protected function recordFailure(SocialAccount $account, string $action, array $request, array $response, string $error): void
    {
        $this->recordLog($account, $action, $request, $response, false, $error);
    }
}
