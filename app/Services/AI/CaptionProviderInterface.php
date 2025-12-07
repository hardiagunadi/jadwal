<?php

namespace App\Services\AI;

interface CaptionProviderInterface
{
    public function generate(string $prompt): string;
}
