<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use Illuminate\Http\Request;

final class ReleaseController extends Controller
{
    /**
     * Display a paginated list of releases.
     */
    public function index(Request $request)
    {
        $releases = Release::query()->orderByDesc('generated_at')->paginate(10)->withQueryString();

        return view('admin.releases.index', ['releases' => $releases]);
    }

    /**
     * Show a single release and its changelog/commits.
     */
    public function show(Release $release)
    {
        $release->load(['changelogs' => fn ($q) => $q->orderBy('position'), 'commits' => fn ($q) => $q->orderBy('authored_at')]);

        return view('admin.releases.show', ['release' => $release]);
    }
}
