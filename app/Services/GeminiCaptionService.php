<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GeminiCaptionService
{
    public function generate(string $keywords): ?string
    {
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            return null;
        }

        $prompt = 'Buat caption Instagram yang menarik, singkat, dan ramah dari kata kunci berikut: ' . $keywords;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->withQueryParameters([
            'key' => $apiKey,
        ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent', [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_NONE',
                ],
            ],
        ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        $candidates = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (! $candidates) {
            return null;
        }

        return Str::of($candidates)->trim()->toString();
    }
}
