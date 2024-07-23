<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gherkin User Story Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    @livewireStyles

    <style>
        body {
            background: linear-gradient(135deg, #FFDEE9, #B5FFFC);
        }
        /* loading bar styles */
        .loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 0;
            height: 8px;
            background: #3490dc;
            z-index: 1031;
            transition: width 0.4s ease;
        }

        .loading {
            width: 100%;
        }

        /* loading spinner styles */
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: #3490dc;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        button[disabled] .spinner {
            display: inline-block;
            margin-left: 8px;
        }

        button .spinner {
            display: none;
        }

        pre {
            white-space: pre-wrap;       /* css3 */
            white-space: -moz-pre-wrap;  /* firefox */
            white-space: -o-pre-wrap;    /* opera 7 */
            word-wrap: break-word;       /* ie */
        }
    </style>
</head>
<body class="text-gray-800">
<div class="flex justify-center items-center min-h-screen p-6">
    <div class="bg-white p-8 rounded shadow-lg w-full max-w-3xl">
        {{ $slot }}
    </div>
</div>
@livewireScripts

<script>
    document.addEventListener('livewire:load', function () {
        Livewire.on('notify', message => {
            alert(message);
        });
    });

    function renderMarkdown() {
        document.querySelectorAll('.markdown').forEach(function(el) {
            el.innerHTML = marked(el.innerHTML);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderMarkdown(); // Initial render

        Livewire.hook('message.processed', (message, component) => {
            renderMarkdown();
        });
    });
</script>
</body>
</html>
