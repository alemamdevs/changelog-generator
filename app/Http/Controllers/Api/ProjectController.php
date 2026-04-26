<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->authorize('viewAny', Project::class);

        $projects = Project::query()
            ->ownedBy((int) $user->id)
            ->withCount('releases')
            ->orderBy('github_repo')
            ->paginate(15)
            ->withQueryString();

        return response()->json($projects);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->authorize('view', $project);

        $project->load([
            'latestRelease',
            'releases' => fn ($query) => $query
                ->latest('generated_at')
                ->limit(20),
        ]);

        return response()->json([
            'project' => $project,
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->authorize('create', Project::class);

        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => $validated['name'] ?? null,
            'github_repo' => $validated['github_repo'],
            'repository_full_name' => $validated['github_repo'],
            'default_branch' => $validated['default_branch'],
            'webhook_secret' => Str::random(64),
            'is_active' => true,
        ]);

        return response()->json([
            'project' => $project,
        ], 201);
    }
}
