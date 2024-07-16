<?php

namespace App\Agents;

use App\Services\GPTClient;

class GPTAgent extends BaseAgent
{
    private GPTClient $gptClient;

    public function __construct(GPTClient $gptClient)
    {
        $this->gptClient = $gptClient;
    }

    public function ask(string $question): string
    {
        $response = $this->gptClient->chat([
            ['role' => 'user', 'content' => $question],
        ]);

        return $response['choices'][0]['message']['content'] ?? '';
    }
}
