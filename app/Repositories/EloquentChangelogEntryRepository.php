<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Changelog;
use App\Repositories\ChangelogEntryRepositoryInterface;

final class EloquentChangelogEntryRepository implements ChangelogEntryRepositoryInterface
{
    public function createEntry(array $data): Changelog
    {
        return Changelog::create($data);
    }
}
