<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GPTClient
{
    protected $apiKey;
    protected $baseUrl;
    protected $model;

    public function __construct()
    {
        $this->apiKey = config('gherkinconf.gpt.api_key');
        $this->baseUrl = config('gherkinconf.gpt.base_url');
        $this->model = config('gherkinconf.gpt.model');
    }

    public function chat($inputs)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/chat/completions", [
            'model' => $this->model,
            'messages' => $inputs,
        ]);

        return $response->json();
    }
}
