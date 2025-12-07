<?php

namespace App\Services\AI;

use InvalidArgumentException;
use OpenAI;

class OpenAICaptionProvider implements CaptionProviderInterface
{
    public function __construct(private readonly ?string $apiKey, private readonly ?string $model)
    {
    }

    public function generate(string $prompt): string
    {
        if (empty($this->apiKey)) {
            throw new InvalidArgumentException('OpenAI API key is not configured.');
        }

        $client = OpenAI::client($this->apiKey);
        $modelName = $this->model ?: 'gpt-4o-mini';

        $response = $client->chat()->create([
            'model' => $modelName,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an assistant that writes concise, engaging social media captions in Indonesian.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return trim($response->choices[0]->message->content ?? '');
    }
}
