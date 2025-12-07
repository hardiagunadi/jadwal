<?php

namespace App\Enums;

enum CaptionAiProvider: string
{
    case Gemini = 'gemini';
    case OpenAI = 'openai';
}
