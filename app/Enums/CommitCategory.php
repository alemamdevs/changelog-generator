<?php

declare(strict_types=1);

namespace App\Enums;

enum CommitCategory: string
{
    case Feature = 'feature';
    case Fix = 'fix';
    case Refactor = 'refactor';
    case Chore = 'chore';
    case Docs = 'docs';
    case Breaking = 'breaking';

    public function heading(): string
    {
        return match ($this) {
            self::Breaking => 'Breaking Changes',
            self::Feature => 'Features',
            self::Fix => 'Fixes',
            self::Refactor => 'Refactors',
            self::Chore => 'Chores',
            self::Docs => 'Documentation',
        };
    }
}
