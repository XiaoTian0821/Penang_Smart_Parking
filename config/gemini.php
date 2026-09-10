<?php
/**
 * Gemini AI Configuration
 */
declare(strict_types=1);

function getGeminiConfig(): array {
    return [
        'api_key' => getenv('GEMINI_API_KEY') ?: '',
        'primary_model' => getenv('GEMINI_PRIMARY_MODEL') ?: 'gemini-2.0-flash',
        'fallback_model' => getenv('GEMINI_FALLBACK_MODEL') ?: 'gemini-1.5-flash',
        'api_url' => getenv('GEMINI_API_URL') ?: 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={key}',
        'timeout' => (int)(getenv('GEMINI_TIMEOUT') ?: 30),
        'confidence_threshold' => (float)(getenv('GEMINI_CONFIDENCE_THRESHOLD') ?: 0.75),
    ];
}
