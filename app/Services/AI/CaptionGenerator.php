<?php

namespace App\Services\AI;

use App\Enums\CaptionAiProvider;
class CaptionGenerator
{
    public function __construct(
        private readonly ?GeminiCaptionProvider $geminiCaptionProvider = null,
        private readonly ?OpenAICaptionProvider $openAICaptionProvider = null,
    ) {
    }

    public function generate(string $prompt, ?string $provider = null): string
    {
        $providerName = $provider ?? config('ai.default');
        $driver = $this->resolveProvider($providerName);

        return $driver->generate($prompt);
    }

    public function availableProviders(): array
    {
        return array_map(static fn (CaptionAiProvider $provider) => $provider->value, CaptionAiProvider::cases());
    }

    private function resolveProvider(?string $providerName): CaptionProviderInterface
    {
        $provider = $providerName !== null
            ? CaptionAiProvider::from($providerName)
            : CaptionAiProvider::Gemini;

        return match ($provider) {
            CaptionAiProvider::Gemini => $this->makeGeminiProvider(),
            CaptionAiProvider::OpenAI => $this->makeOpenAIProvider(),
        };
    }

    private function makeGeminiProvider(): CaptionProviderInterface
    {
        $settings = config('ai.providers.gemini');
        $apiKey = $settings['api_key'] ?? null;
        $model = $settings['model'] ?? null;

        return $this->geminiCaptionProvider ?? new GeminiCaptionProvider($apiKey, $model);
    }

    private function makeOpenAIProvider(): CaptionProviderInterface
    {
        $settings = config('ai.providers.openai');
        $apiKey = $settings['api_key'] ?? null;
        $model = $settings['model'] ?? null;

        return $this->openAICaptionProvider ?? new OpenAICaptionProvider($apiKey, $model);
    }
}
