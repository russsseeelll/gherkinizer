<?php

namespace App\Jobs;

use App\Services\GPTClient;
use App\Models\Input;
use App\Models\Output;
use App\Models\Prompt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessGherkinRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inputId;
    protected $step;

    public function __construct($inputId, $step)
    {
        $this->inputId = $inputId;
        $this->step = $step;
    }

    public function handle(GPTClient $gptClient)
    {
        $input = Input::find($this->inputId);
        $conversation = $this->buildConversation($input);
        $prompt = Prompt::where('name', $this->step)->first()->prompt;

        $response = $gptClient->chat([
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user', 'content' => end($conversation)['content']],
        ]);

        if ($response) {
            $conversation[] = ['role' => 'system', 'content' => $response['choices'][0]['message']['content']];
        }

        Output::create([
            'input_id' => $this->inputId,
            'questions' => json_encode($conversation),
            'answers' => json_encode([$response['choices'][0]['message']['content']]),
            'result' => '',  // Keep result empty for now
        ]);
    }

    private function buildConversation(Input $input)
    {
        $conversation = [
            ['role' => 'user', 'content' => "System: {$input->system}\nTitle: {$input->title}\nDescription: {$input->description}"]
        ];

        foreach ($input->additional_inputs as $additional_input) {
            $conversation[] = ['role' => 'user', 'content' => $additional_input];
        }

        return $conversation;
    }
}
