<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Changelog;

interface ChangelogEntryRepositoryInterface
{
    public function createEntry(array $data): Changelog;
}
