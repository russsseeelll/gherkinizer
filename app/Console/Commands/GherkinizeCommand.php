<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GPTClient;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;
use function Laravel\Prompts\spin;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class GherkinizeCommand extends Command
{
    protected $signature = "app:gherkinize";
    protected $description = "Generate Gherkin user stories from AI-processed feature requests";
    private GPTClient $gptClient;

    public function __construct(GPTClient $gptClient)
    {
        parent::__construct();
        $this->gptClient = $gptClient;
    }

    public function handle()
    {
        $this->info("Welcome to the Gherkin User Story Generator.");
        $startTime = Carbon::now();

        $featureRequest = text(
            "Enter a short title for your feature request:",
            required: true,
            hint: 'Example: "Monthly Transactions Report Button"'
        );

        $story = textarea(
            label: "Feature Description",
            required: true,
            placeholder: "Describe the feature with details about the functionality or behavior you expect.",
            hint: 'Be specific. For instance, explain what the new button should do, where it should be located, and what the report should include.'
        );

        $initialPrompt =
            "You are an AI assistant who is an expert at breaking down a non-IT users natural language feature requests for software applications and figuring out the individual parts of the request. You spend time thinking of both the 'happy path' features required and also edge-cases and error conditions which the user probably doesn't think about. The goal is to provide a detailed breakdown of the feature request so that it can then be turned into User Stories. You should not attempt to write the user stories yourself, but just provide the detailed breakdown of the feature request.";

        $initialResponse = spin(
            fn() => $this->gptClient->chat([
                ["role" => "system", "content" => $initialPrompt],
                ["role" => "user", "content" => $featureRequest, $story],
            ]),
            "Generating initial breakdown..."
        );

        if (!$initialResponse) {
            $this->error("Failed to connect to the AI API.");
            return;
        }

        $systemMessage =
            $initialResponse["choices"][0]["message"]["content"] ??
            "No initial response from AI.";

        $finishedAsking = false;
        $conversation = [["role" => "system", "content" => $systemMessage]];

        while (!$finishedAsking) {
            $latestSystemMessage = end($conversation)["content"];
            $this->info($latestSystemMessage);

            $userResponse = $this->ask(
                'Add more details or clarify your input. Type "COMPLETE" to finish or provide more details about your feature request:'
            );

            if (trim($userResponse) === "COMPLETE") {
                $finishedAsking = true;
                continue;
            }

            $conversation[] = ["role" => "user", "content" => $userResponse];

            $nextQuestionPrompt =
                "You are an AI assistant who is an expert at asking clarifying questions ONE AT A TIME about non-IT users natural language feature requests for software applications. You should ask questions to get more information about the feature request in order to help developers understand the exact nature of the request. Remember to only ask them one question at a time so they are not overwhelmed. The USER is non-technical so might not be able to answer a technically worded question. If you do not need to ask any more questions please reply with simply the word 'COMPLETE'.";

            $nextQuestionResponse = spin(
                fn() => $this->gptClient->chat([
                    ["role" => "system", "content" => $nextQuestionPrompt],
                    ["role" => "user", "content" => $userResponse],
                ]),
                "Analyzing your input..."
            );

            if (!$nextQuestionResponse) {
                $this->error(
                    "Failed to get further clarification from the AI API."
                );
                return;
            }

            $nextQuestion =
                $nextQuestionResponse["choices"][0]["message"]["content"] ??
                'Please specify more about your feature request or type "COMPLETE" if you are done.';
            $conversation[] = ["role" => "system", "content" => $nextQuestion];
        }

        $userStoriesPrompt =
            "You are an AI assistant skilled at interpreting user requests for software features. Your task is to analyze the provided conversation, extract the essential feature request, and translate it into a structured series of Gherkin Syntax User Stories. Ensure that these user stories are in correct Gherkin format and address all potential edge cases and error conditions that might not be evident from the user's initial request. Output each user story in Markdown format using Gherkin Syntax. No additional explanations are required, as the output will be used directly in another software tool.";

        $details = array_map(function ($message) {
            return "{$message["role"]}: {$message["content"]}";
        }, $conversation);
        $detailsString = implode("\n", $details);

        $userStoriesResponse = spin(
            fn() => $this->gptClient->chat([
                ["role" => "system", "content" => $userStoriesPrompt],
                ["role" => "user", "content" => $detailsString],
            ]),
            "Generating user stories..."
        );

        if (!$userStoriesResponse) {
            $this->error("Failed to generate user stories.");
            return;
        }

        $outputContent =
            $userStoriesResponse["choices"][0]["message"]["content"] ??
            "Failed to extract user stories from the response.";

        if (strpos($outputContent, "Failed to extract") !== false) {
            $this->error($outputContent);
            return;
        }

        $endTime = Carbon::now();
        $totalDuration = $endTime->diffInSeconds($startTime);
        $this->info(
            "User Stories Generation Completed in {$totalDuration} seconds."
        );
        $this->line($outputContent);

        $filename =
            "user_stories_" . Carbon::now()->format("Y_m_d_H_i_s") . ".md";
        $output = "# User Stories\n\n" . $outputContent;
        Storage::disk("local")->put($filename, $output);
        $this->info("User stories have been saved to {$filename}");
    }
}
