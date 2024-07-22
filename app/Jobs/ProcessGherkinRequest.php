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

    // declaring protected properties
    protected $inputId;
    protected $step;
    protected $conversation;
    protected $systemDetails;

    // constructor to initialize the job with provided data
    public function __construct($inputId, $step, $conversation, $systemDetails = null)
    {
        $this->inputId = $inputId;
        $this->step = $step;
        $this->conversation = $conversation;
        $this->systemDetails = $systemDetails;
    }

    // handle method that defines the job's logic
    public function handle(GPTClient $gptClient)
    {
        // fetch the prompt for the current step
        $prompt = Prompt::where('name', $this->step)->first()->prompt;
        // retrieve the input record
        $input = Input::find($this->inputId);
        // prepare main feature request string
        $mainFeatureRequest = "Title: {$input->title}\nDescription: {$input->description}";

        $systemInfo = '';
        if ($this->systemDetails) {
            $systemInfo = "System Information:\n\n";
            $systemInfo .= "Name: {$this->systemDetails->name}\n";
            $systemInfo .= "Description: {$this->systemDetails->description}\n";
            $systemInfo .= "Stack: {$this->systemDetails->stack}\n";

            $features = is_array($this->systemDetails->features) ? $this->systemDetails->features : [];
            $systemInfo .= "Features: " . implode(', ', $features) . "\n\n";
        }

        // send request to gpt client with context of prev conversation
        $response = $gptClient->chat([
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user', 'content' => "{$systemInfo}Main feature request:\n\n{$mainFeatureRequest}\n\nPrevious context:\n\n" . $this->conversation],
        ]);

        // process response from gpt client
        if ($response) {
            $aiMessage = $response['choices'][0]['message']['content'];
            // append ai response to conversation
            $this->conversation .= "\n\n**AI:** " . $aiMessage; // markdown
        }

        // create a new output record with the conversation and ai response
        Output::create([
            'input_id' => $this->inputId,
            'questions' => json_encode($this->conversation),
            'answers' => json_encode([$response['choices'][0]['message']['content']]),
            'result' => '',
        ]);
    }
}
