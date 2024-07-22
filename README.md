# Gherkin-Style User Story Generator (PHP/Laravel Edition)

This Laravel web app helps in converting non-technical user feature requests into detailed Gherkin-style user stories. It uses the openAI GPT model to break down natural language feature requests into structured user stories in Gherkin syntax.


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

Copy the example environment file and open the .env file to add your GPT API key. 

```
cp .env.example .env
```

Migrate the database:

```
php artisan migrate:fresh                                  
php artisan db:seed --class=PromptSeeder
php artisan db:seed --class=SystemInfoSeeder
```


## Configuration

Before running the app, ensure the AI chatbot model is set correctly in your service provider configuration. Ensure the API key is set in the Env if required.

Add your own companies SystemInfo to the database / seeder for more accurate results.


## Features

- Supports GPT models.
- Converts natural language feature requests into Gherkin-style user stories.
- Saves user stories in a markdown file for easy access and documentation.

