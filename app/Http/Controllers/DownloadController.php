<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function download(Request $request)
    {
        $functionalRequirements = $request->input('functionalRequirements', '');
        $userStories = $request->input('userStories', '');

        $content = "Functional Requirements:\n\n" . $functionalRequirements . "\n\nUser Stories:\n\n" . $userStories;

        $fileName = 'requirements_and_user_stories.md';

        return response()->streamDownload(function() use ($content) {
            echo $content;
        }, $fileName);
    }
}
