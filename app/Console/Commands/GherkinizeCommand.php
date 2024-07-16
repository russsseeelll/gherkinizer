<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Agents\GPTAgent;
use App\Models\Input;
use App\Models\Output;
use App\Models\Prompt;
use App\Models\SystemInfo;
use App\Jobs\ProcessGherkinRequest;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;
use function Laravel\Prompts\select;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class GherkinizeCommand extends Command
{
    protected $signature = 'app:gherkinize';
    protected $description = 'Generate Gherkin user stories from AI-processed feature requests';
    private GPTAgent $gptAgent;

    // constructor for the GherkinizeCommand class.
    // this initializes a new instance of the GherkinizeCommand,
    // assigning a "GPTAgent" instance to handle AI conversation.
    public function __construct(GPTAgent $gptAgent)
    {
        parent::__construct();
        $this->gptAgent = $gptAgent;
    }

    // calling everything back to this main handle for readability.
    public function handle()
    {
        $this->info('Welcome to the Gherkin User Story Generator.');
        $startTime = Carbon::now();

        list($system, $title, $description) = $this->promptFeatureDetails();

        $input = Input::create([
            'system' => $system,
            'title' => $title,
            'description' => $description,
            'additional_inputs' => [],
        ]);

        $this->queueConversationStep($input->id, 'initialPrompt');

        $this->continueConversation($input->id);

        if ($this->generateAndSaveUserStories($input->id)) {
            $endTime = Carbon::now();
            $totalDuration = $endTime->diffInSeconds($startTime);
            $this->info("User Stories Generation Completed in {$totalDuration} seconds.");
        }
    }

    private function promptFeatureDetails(): array
    {
        $systems = SystemInfo::all()->pluck('name')->toArray();

        $system = select(
            label: 'If applicable, select the system you want a feature added to:',
            options: $systems,
            hint: 'Choose the system relevant to your feature request.'
        );

        $title = text(
            'Enter a short title for your feature request:',
            required: true,
            hint: 'Example: "Monthly Transactions Report Button"'
        );

        $description = textarea(
            label: 'Feature Description',
            required: true,
            placeholder: 'Describe the feature with details about the functionality or behavior you expect.',
            hint: 'Be specific. For instance, explain what the new button should do, where it should be located, and what the report should include.'
        );

        return [$system, $title, $description];
    }

    private function queueConversationStep($inputId, $step): void
    {
        ProcessGherkinRequest::dispatch($inputId, $step);
        $this->info('Processing the request... Please wait.');
    }

    private function continueConversation($inputId): void
    {
        while (true) {
            $latestOutput = $this->waitForJobCompletion($inputId);

            if ($latestOutput) {
                $this->info($latestOutput->answers[0]);
                $userResponse = text(
                    label: 'Add more details or clarify your input. Type "COMPLETE" to finish or provide more details about your feature request:',
                    required: true,
                    hint: 'Type your response or "COMPLETE" if no more details are needed.'
                );

                if (trim($userResponse) === 'COMPLETE') {
                    break;
                }

                $input = Input::find($inputId);
                $additionalInputs = $input->additional_inputs;
                $additionalInputs[] = $userResponse;
                $input->update(['additional_inputs' => $additionalInputs]);

                $this->queueConversationStep($inputId, 'nextQuestionPrompt');
            }
        }
    }

    private function waitForJobCompletion($inputId)
    {
        $timeout = 60; // timeout if job doesnt come back ....
        $startTime = Carbon::now();

        while (Carbon::now()->diffInSeconds($startTime) < $timeout) {
            $latestOutput = Output::where('input_id', $inputId)->latest()->first();
            if ($latestOutput && Carbon::parse($latestOutput->created_at)->greaterThan($startTime)) {
                return $latestOutput;
            }
            // polling the db every .5 seconds to see if the output is back from the AI
            usleep(500000);
        }

        $this->error("Timed out waiting for the job to complete.");
        return null;
    }

    private function generateAndSaveUserStories($inputId): bool
    {
        $input = Input::find($inputId);
        $conversation = [
            ['role' => 'user', 'content' => "System: {$input->system}\nTitle: {$input->title}\nDescription: {$input->description}"]
        ];

        foreach ($input->additional_inputs as $additional_input) {
            $conversation[] = ['role' => 'user', 'content' => $additional_input];
        }

        $userStoriesPrompt = Prompt::where('name', 'userStoriesPrompt')->first()->prompt;
        $details = array_map(
            fn($message) => "{$message['role']}: {$message['content']}",
            $conversation
        );
        $detailsString = implode("\n", $details);

        $userStoriesResponse = $this->gptAgent->ask($userStoriesPrompt . "\n" . $detailsString);

        if (!$userStoriesResponse) {
            $this->error('Failed to generate user stories.');
            return false;
        }

        $outputContent = $userStoriesResponse ?? 'Failed to extract user stories from the response.';

        if (strpos($outputContent, 'Failed to extract') !== false) {
            $this->error($outputContent);
            return false;
        }

        // Save the user stories in the result field of the latest output
        $latestOutput = Output::where('input_id', $inputId)->latest()->first();
        if ($latestOutput) {
            $latestOutput->update(['result' => $outputContent]);
        }

        $filename = 'user_stories_' . Carbon::now()->format('Y_m_d_H_i_s') . '.md';
        $output = "# User Stories\n\n" . $outputContent;
        Storage::disk('local')->put($filename, $output);
        $this->info("User stories have been saved to {$filename}");
        $this->line($output);

        return true;
    }
}
