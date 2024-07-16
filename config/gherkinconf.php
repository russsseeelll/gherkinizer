<?php

return [
    'gpt' => [
        'api_key' => env('GPT_API_KEY'),
        'model' => env('GPT_MODEL', 'gpt-4'),
        'base_url' => env('GPT_BASE_URL', 'https://api.openai.com/v1'),
    ],
];
