<?php

namespace App\Services;

use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;
use Throwable;

class CaptionGenerator
{
    public function generate(string $keywords, string $brandStyle, int $maxLength): string
    {
        $keywords = trim($keywords);
        $brandStyle = trim($brandStyle);
        $maxLength = max(1, $maxLength);

        $prompt = <<<PROMPT
Anda adalah copywriter media sosial pemerintah daerah. Gunakan bahasa Indonesia yang singkat, jelas, ramah, dan sesuai gaya brand berikut: "{$brandStyle}".
Buat caption dari kata kunci berikut: {$keywords}.
Batasi panjang caption maksimum {$maxLength} karakter. Hindari emoji berlebihan dan sertakan ajakan bertindak jika relevan.
PROMPT;

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Anda adalah asisten yang menulis caption media sosial singkat.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => max(50, (int) ceil($maxLength / 3)),
                'temperature' => 0.7,
            ]);
        } catch (Throwable $exception) {
            throw new RuntimeException('Gagal memanggil layanan OpenAI: ' . $exception->getMessage(), previous: $exception);
        }

        $caption = trim($response->choices[0]->message->content ?? '');

        if ($caption === '') {
            throw new RuntimeException('Model tidak mengembalikan caption.');
        }

        return Str::limit($caption, $maxLength, '');
    }
}
