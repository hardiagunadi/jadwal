<?php

namespace App\Services\AI;

use GeminiAPI\Client;
use GeminiAPI\Resources\Parts\TextPart;
use InvalidArgumentException;

class GeminiCaptionProvider implements CaptionProviderInterface
{
    public function __construct(private readonly ?string $apiKey, private readonly ?string $model)
    {
    }

    public function generate(string $prompt): string
    {
        if (empty($this->apiKey)) {
            throw new InvalidArgumentException('Gemini API key is not configured.');
        }

        $client = new Client($this->apiKey);
        $modelName = $this->model ?: 'gemini-pro';

        $response = $client
            ->generativeModel($modelName)
            ->generateContent(new TextPart($prompt));

        return trim($response->text());
    }
}
