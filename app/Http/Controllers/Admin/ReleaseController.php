<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ReleaseController extends Controller
{
    /**
     * Display a paginated list of releases.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->authorize('viewAny', Release::class);

        $releases = Release::query()
            ->where('user_id', $user->id)
            ->with(['project:id,name,github_repo,default_branch'])
            ->orderByDesc('generated_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.releases.index', ['releases' => $releases]);
    }

    /**
     * Show a single release and its changelog/commits.
     */
    public function show(Request $request, Release $release): View
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->authorize('view', $release);

        $release->load([
            'project:id,name,github_repo,default_branch',
            'changelogs' => fn ($query) => $query
                ->orderBy('position'),
            'commits' => fn ($query) => $query
                ->orderBy('authored_at'),
        ]);

        return view('admin.releases.show', ['release' => $release]);
    }
}
