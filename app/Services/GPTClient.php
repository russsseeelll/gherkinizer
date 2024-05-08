<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GPTClient
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.gpt.api_key');
    }

    public function chat($inputs)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/chat/completions", [
            'model' => 'gpt-3.5-turbo',
            'messages' => $inputs,
        ]);

        return $response->json();
    }
}
