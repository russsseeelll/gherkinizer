<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeveloperMail extends Mailable
{
    use Queueable, SerializesModels;

    public $functionalRequirements;
    public $userStories;

    public function __construct($functionalRequirements, $userStories)
    {
        $this->functionalRequirements = $functionalRequirements;
        $this->userStories = $userStories;
    }

    public function build()
    {
        return $this->view('emails.developer')
            ->subject('Generated User Stories and Suggested Functional Requirements')
            ->with([
                'functionalRequirements' => $this->functionalRequirements,
                'userStories' => $this->userStories,
            ]);
    }
}
