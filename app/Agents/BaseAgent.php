<?php

namespace App\Agents;

abstract class BaseAgent
{
    abstract public function ask(string $question): string;
}
