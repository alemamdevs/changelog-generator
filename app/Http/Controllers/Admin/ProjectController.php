<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\Release;
use App\Models\WebhookDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->authorize('viewAny', Project::class);

        $this->syncProjectsFromExistingData((int) $user->id);

        $projectCount = Project::query()->ownedBy((int) $user->id)->count();
        $releaseCount = Release::query()->ownedBy((int) $user->id)->count();
        $latestRelease = Release::query()
            ->ownedBy((int) $user->id)
            ->latest('generated_at')
            ->first();

        $projects = Project::query()
            ->ownedBy((int) $user->id)
            ->withCount('releases')
            ->with('latestRelease')
            ->orderBy('github_repo')
            ->paginate(12)
            ->withQueryString();

        return view('admin.projects.index', [
            'projects' => $projects,
            'projectCount' => $projectCount,
            'releaseCount' => $releaseCount,
            'latestRelease' => $latestRelease,
        ]);
    }

    public function show(Request $request, Project $project): View
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->authorize('view', $project);

        $project->loadCount('releases');

        $latestRelease = $project->latestRelease()->first();

        $releases = $project->releases()
            ->orderByDesc('generated_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.projects.show', [
            'project' => $project,
            'releases' => $releases,
            'latestRelease' => $latestRelease,
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
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

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('status', 'Project has been added successfully.');
    }

    private function syncProjectsFromExistingData(int $userId): void
    {
        $releaseRepositories = Release::query()
            ->where('user_id', $userId)
            ->whereNotNull('repository_full_name')
            ->where('repository_full_name', '!=', '')
            ->distinct()
            ->pluck('repository_full_name');

        $webhookRepositories = WebhookDelivery::query()
            ->where('user_id', $userId)
            ->whereNotNull('repository_full_name')
            ->where('repository_full_name', '!=', '')
            ->distinct()
            ->pluck('repository_full_name');

        $repositories = $releaseRepositories
            ->merge($webhookRepositories)
            ->unique()
            ->values();

        if ($repositories->isEmpty()) {
            return;
        }

        $timestamp = now();

        Project::query()->upsert(
            $repositories
                ->map(function (string $repositoryFullName) use ($timestamp, $userId): array {
                    $webhookSecret = Str::random(64);

                    return [
                        'user_id' => $userId,
                        'name' => null,
                        'github_repo' => $repositoryFullName,
                        'repository_full_name' => $repositoryFullName,
                        'default_branch' => 'main',
                        'webhook_secret_ciphertext' => Crypt::encryptString($webhookSecret),
                        'webhook_secret_hash' => hash('sha256', $webhookSecret),
                        'is_active' => true,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                })
                ->all(),
            ['user_id', 'github_repo'],
            ['repository_full_name', 'webhook_secret_ciphertext', 'webhook_secret_hash', 'updated_at'],
        );
    }
}
