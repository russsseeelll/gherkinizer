<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Input;
use App\Models\Output;
use App\Models\SystemInfo;
use App\Jobs\ProcessGherkinRequest;
use Carbon\Carbon;
use Parsedown;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\DeveloperMail;

class Gherkinize extends Component
{
    // declaring public properties
    public $system;
    public $title;
    public $description;
    public $userResponse;
    public $inputId;
    public $conversation = [];
    public $loading = false;
    public $completed = false;
    public $systems = [];
    public $userStories;
    public $selectedSystemDetails = null;

    public $interactionCount = 0;
    public $functionalRequirements = '';
    public $downloadLink = '';

    // defining form validation rules
    protected $rules = [
        'system' => 'required',
        'title' => 'required|string',
        'description' => 'required|string',
        'userResponse' => 'nullable|string',
    ];

    // component called when it's mounted
    public function mount()
    {
        // fetch all system names for selection
        $this->systems = SystemInfo::all()->pluck('name')->toArray();
    }

    // method called when property is updated in frontend
    public function updatedSystem($value)
    {
        // get details of selected system
        $this->selectedSystemDetails = SystemInfo::where('name', $value)->first();
    }

    // method to handle form submission
    public function submit()
    {
        // validate form inputs
        $this->validate();

        // handle initial form submission
        if (!$this->inputId) {
            // create a new input record
            $input = Input::create([
                'system' => $this->system,
                'title' => $this->title,
                'description' => $this->description,
                'additional_inputs' => [],
            ]);
            $this->inputId = $input->id;

            // ensure system details are loaded (mainly for debugging tbh)
            if (!$this->selectedSystemDetails) {
                $this->selectedSystemDetails = SystemInfo::where('name', $this->system)->first();
            }

            // queue the initial conversation step
            $this->queueConversationStep('initialPrompt', $input);
        } else {
            // append user response to conversation
            $this->conversation[] = "User: {$this->userResponse}";

            // update input with additional user responses
            $input = Input::find($this->inputId);
            $additionalInputs = $input->additional_inputs;
            $additionalInputs[] = $this->userResponse;
            $input->update(['additional_inputs' => $additionalInputs]);

            // clear user response and increment interaction count so user is not bombarded
            $this->clearInput();
            $this->interactionCount++;

            // check if interaction count reached threshold to call complete complete
            if ($this->interactionCount >= 3) {
                $this->complete();
            } else {
                // queue the next conversation step
                $this->queueConversationStep('nextQuestionPrompt', $input);
            }
        }
    }

    // method to queue conversation step for processing
    private function queueConversationStep($step, Input $input)
    {
        $this->loading = true;
        // build conversation string
        $conversation = $this->buildConversation($input);
        // dispatch job to process gherkin request
        ProcessGherkinRequest::dispatch($this->inputId, $step, $conversation, $this->selectedSystemDetails);
        // poll for latest output
        $this->pollLatestOutput();
    }

    // method to build conversation string from input and system details
    private function buildConversation(Input $input)
    {
        $conversation = "### Title: {$input->title}\n\n**Description:** {$input->description}\n\n";

        if ($this->selectedSystemDetails) {
            $systemInfo = "System Information:\n\n";
            $systemInfo .= "Name: {$this->selectedSystemDetails->name}\n";
            $systemInfo .= "Description: {$this->selectedSystemDetails->description}\n";
            $systemInfo .= "Stack: {$this->selectedSystemDetails->stack}\n";

            $features = is_array($this->selectedSystemDetails->features) ? $this->selectedSystemDetails->features : [];
            $systemInfo .= "Features: " . implode(', ', $features) . "\n\n";

            $conversation .= $systemInfo;
        }

        foreach ($input->additional_inputs as $additional_input) {
            $conversation .= "**User:** {$additional_input}\n\n";
        }

        foreach ($input->outputs as $output) {
            $conversation .= "**AI:** " . $output->answers[0] . "\n\n";
        }

        return $conversation;
    }

    // method to poll for the latest output
    public function pollLatestOutput()
    {
        $latestOutput = $this->waitForJobCompletion($this->inputId);

        if ($latestOutput) {
            $answers = json_decode($latestOutput->answers, true);
            if (isset($answers[0])) {
                $this->conversation[] = "AI: " . $answers[0];
                $this->userStories = $this->convertMarkdownToHtml($answers[0]);
            }
            $this->loading = false;
        } else {
            $this->dispatchBrowserEvent('notify', ['message' => 'Failed to get response. Please try again.']);
            $this->loading = false;
        }
    }

    // method to poll for functional requirements
    public function pollFunctionalRequirements()
    {
        $latestOutput = $this->waitForJobCompletion($this->inputId);

        if ($latestOutput) {
            $answers = json_decode($latestOutput->answers, true);
            if (isset($answers[0])) {
                $this->functionalRequirements = $this->convertMarkdownToHtml($answers[0]);
                ProcessGherkinRequest::dispatch($this->inputId, 'userStoriesPrompt', $this->buildConversation(Input::find($this->inputId)), $this->selectedSystemDetails);
                $this->pollUserStories();
            }
        } else {
            $this->dispatchBrowserEvent('notify', ['message' => 'Failed to get response. Please try again.']);
            $this->loading = false;
        }
    }

    // method to poll for user stories
    public function pollUserStories()
    {
        $latestOutput = $this->waitForJobCompletion($this->inputId);

        if ($latestOutput) {
            $answers = json_decode($latestOutput->answers, true);
            if (isset($answers[0])) {
                $this->userStories = $this->convertMarkdownToHtml($answers[0]);
            }
            $this->loading = false;
            $this->completed = true;
        } else {
            $this->dispatchBrowserEvent('notify', ['message' => 'Failed to get response. Please try again.']);
            $this->loading = false;
        }
    }

    // method to wait for job completion with a timeout
    private function waitForJobCompletion($inputId)
    {
        $timeout = 60; // timeout if job doesn't come back
        $startTime = Carbon::now();

        while (Carbon::now()->diffInSeconds($startTime) < $timeout) {
            $latestOutput = Output::where('input_id', $inputId)->latest()->first();
            if ($latestOutput && Carbon::parse($latestOutput->created_at)->greaterThan($startTime)) {
                return $latestOutput;
            }
            usleep(500000); // polling the db every .5 seconds
        }

        return null;
    }

    // method to generate download link for functional requirements and user stories
    public function generateDownloadLink()
    {
        // Generate the download link with functional requirements and user stories
        $this->downloadLink = route('download', [
            'functionalRequirements' => $this->functionalRequirements,
            'userStories' => $this->userStories
        ]);

        // Emit an event to notify the front-end
        $this->emit('linkGenerated');
    }


    // method to mark the process as complete
    public function complete()
    {
        $this->loading = true;
        ProcessGherkinRequest::dispatch($this->inputId, 'functionalRequirementsPrompt', $this->buildConversation(Input::find($this->inputId)), $this->selectedSystemDetails);
        $this->pollFunctionalRequirements();
        $this->loading = false;
    }

    // method to clear user response input field
    public function clearInput()
    {
        $this->userResponse = '';
    }

    // method to render the livewire component view
    public function render()
    {
        return view('livewire.gherkinize')
            ->layout('layouts.app');
    }

    // method to convert markdown to html
    public function convertMarkdownToHtml($markdown)
    {
        $parsedown = new Parsedown();
        return $parsedown->text($markdown);
    }

    // method to send email to developer
    public function emailToDeveloper()
    {
        Mail::to('developer@example.com')->send(new DeveloperMail($this->functionalRequirements, $this->userStories));

        $this->dispatch('notify', ['message' => 'Email sent to developer successfully.']);
    }
}
