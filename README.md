# Gherkin-Style User Story Generator (PHP/Laravel Edition)

This Laravel command line application helps in converting non-technical user feature requests into detailed Gherkin-style user stories. It uses the openAI GPT model to break down natural language feature requests into structured user stories in Gherkin syntax.

## Installation

To run this script, ensure that PHP and Composer are installed on your machine along with other Laravel dependencies.

1. **Clone the Repository:**

```
git clone https://github.com/russsseeelll/gherkinizer.git
cd gherkinizer
```

2. **Install Dependencies:**

This application requires several PHP libraries which can be installed using Composer.

```
composer install
```
3. **Environment Setup:**

Copy the example environment file and open the .env file to add your GPT API key. Then copy the systems.json file and edit it to match your own companies systems.

```
cp .env.example .env
cd public
cp systems.json.example systems.json
```


## Configuration

Before running the command, ensure the AI chatbot model is set correctly in your service provider configuration. Ensure the API key is set in the Env if required.

## Usage

To use this script:

1. **Run the Command:**

```
php artisan app:gherkinize
```

2. **Enter a Feature Request:**

When prompted, enter the non-IT user feature request. The script will then output the initial thoughts and the Gherkin-style user stories.

3. **User Stories Output:**

The generated user stories will be displayed in the console and saved to a markdown file named with the current date and time (e.g., user_stories_2024_01_01_12_00_00.md).

## Features

- Supports GPT models.
- Converts natural language feature requests into Gherkin-style user stories.
- Saves user stories in a markdown file for easy access and documentation.

