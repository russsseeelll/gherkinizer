<div x-data="{ loading: false, submitLoading: false, completeLoading: false, completed: @entangle('completed'), functionalRequirements: @entangle('functionalRequirements'), userStories: @entangle('userStories') }">
    <h1 class="text-4xl font-extrabold mb-8 text-center">Gherkin User Story Generator</h1>

    <div id="loading-bar" class="loading-bar" x-show="loading"></div>

    <div x-data="{ completed: @entangle('completed'), functionalRequirements: @entangle('functionalRequirements'), userStories: @entangle('userStories'), downloadLink: @entangle('downloadLink') }">
        <template x-if="completed">
            <div class="bg-green-100 border border-green-400 text-green-800 px-6 py-4 rounded-lg relative shadow-md mb-8" role="alert">
                <strong class="font-bold text-lg">Success!</strong>
                <span class="block sm:inline mt-2">Your functional requirements and user stories have been successfully generated and saved.</span>
                <div class="markdown mt-4 bg-gray-50 p-4 rounded-lg text-gray-800 overflow-x-auto shadow-inner" x-html="functionalRequirements"></div>
                <div class="markdown mt-4 bg-gray-50 p-4 rounded-lg text-gray-800 overflow-x-auto shadow-inner" x-html="userStories"></div>
                <a :href="downloadLink" class="mt-4 inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-300" download>Download</a>
                <button x-on:click.prevent="$wire.emailToDeveloper()" class="mt-4 inline-block bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                    Email to Developer
                </button>
            </div>
        </template>
    </div>


    <template x-if="!completed">
        <form x-on:submit.prevent="submitLoading = true; loading = true; setTimeout(() => { document.getElementById('loading-bar').classList.add('loading'); }, 50); $wire.submit().then(() => { submitLoading = false; loading = false; document.getElementById('loading-bar').classList.remove('loading'); })" class="mb-8">
            <template x-if="!$wire.inputId">
                <div>
                    <div class="mb-6">
                        <label for="system" class="block text-gray-600 text-sm font-bold mb-2">System</label>
                        <select wire:model="system" id="system" class="shadow appearance-none border border-gray-300 rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" :disabled="loading || submitLoading || completeLoading">
                            <option value="">Select a system</option>
                            @foreach ($systems as $sys)
                                <option value="{{ $sys }}">{{ $sys }}</option>
                            @endforeach
                        </select>
                        @error('system') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6">
                        <label for="title" class="block text-gray-600 text-sm font-bold mb-2">Title</label>
                        <input type="text" wire:model="title" id="title" class="shadow appearance-none border border-gray-300 rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" :disabled="loading || submitLoading || completeLoading">
                        @error('title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6">
                        <label for="description" class="block text-gray-600 text-sm font-bold mb-2">Description</label>
                        <textarea wire:model="description" id="description" class="shadow appearance-none border border-gray-300 rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" :disabled="loading || submitLoading || completeLoading"></textarea>
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="bg-blue-300 hover:bg-blue-400 text-gray-800 font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline transition duration-300" :disabled="loading || submitLoading || completeLoading">
                        Submit
                        <div class="spinner" x-show="submitLoading"></div>
                    </button>
                </div>
            </template>

            <template x-if="$wire.inputId">
                <div>
                    <div class="mb-6">
                        <label for="system" class="block text-gray-600 text-sm font-bold mb-2">System</label>
                        <input type="text" value="{{ $system }}" disabled class="shadow appearance-none border border-gray-300 rounded w-full py-3 px-4 text-gray-600 leading-tight focus:outline-none focus:shadow-outline bg-gray-200">
                    </div>

                    <div class="mb-6">
                        <label for="title" class="block text-gray-600 text-sm font-bold mb-2">Title</label>
                        <input type="text" value="{{ $title }}" disabled class="shadow appearance-none border border-gray-300 rounded w-full py-3 px-4 text-gray-600 leading-tight focus:outline-none focus:shadow-outline bg-gray-200">
                    </div>

                    <div class="mb-6">
                        <label for="description" class="block text-gray-600 text-sm font-bold mb-2">Description</label>
                        <textarea disabled class="shadow appearance-none border border-gray-300 rounded w-full py-3 px-4 text-gray-600 leading-tight focus:outline-none focus:shadow-outline bg-gray-200">{{ $description }}</textarea>
                    </div>

                    <div class="mb-8">
                        @foreach ($conversation as $message)
                            <div class="bg-gray-200 p-4 rounded mb-4 text-gray-800">
                                <div class="markdown">{!! $this->convertMarkdownToHtml($message) !!}</div>
                            </div>
                        @endforeach

                        <div class="mb-6">
                            <label for="userResponse" class="block text-gray-600 text-sm font-bold mb-2">Your Response</label>
                            <input type="text" wire:model="userResponse" id="userResponse" class="shadow appearance-none border border-gray-300 rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" :disabled="loading || submitLoading || completeLoading">
                            @error('userResponse') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="bg-blue-300 hover:bg-blue-400 text-gray-800 font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline transition duration-300" :disabled="loading || submitLoading || completeLoading">
                            Submit
                            <div class="spinner" x-show="submitLoading"></div>
                        </button>
                        <button x-on:click.prevent="completeLoading = true; loading = true; setTimeout(() => { document.getElementById('loading-bar').classList.add('loading'); }, 50); $wire.complete().then(() => { completeLoading = false; loading = false; document.getElementById('loading-bar').classList.remove('loading'); })" class="bg-green-300 hover:bg-green-400 text-gray-800 font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline transition duration-300" :disabled="loading || submitLoading || completeLoading">
                            Complete
                            <div class="spinner" x-show="completeLoading"></div>
                        </button>
                    </div>
                </div>
            </template>
        </form>
    </template>
</div>


