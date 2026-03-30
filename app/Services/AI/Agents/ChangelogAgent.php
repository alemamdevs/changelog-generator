<?php

declare(strict_types=1);

namespace App\Services\AI\Agents;

use Laravel\Ai\Promptable;
use Laravel\Ai\Contracts\Agent;
use Stringable;

/**
 * Simple agent used to prompt the AI for changelog generation.
 */
final class ChangelogAgent implements Agent
{
    use Promptable;

    /**
     * Return human-readable instructions for the agent.
     */
    public function instructions(): Stringable|string
    {
        return 'You are a helpful assistant that converts a list of git commit messages into a concise, grouped changelog. Return the result as JSON with a top-level "sections" array where each section has "kind" and "items" (array of strings). Example: {"sections":[{"kind":"Bug Fixes","items":["Fixed X"]}]}';
    }
}
