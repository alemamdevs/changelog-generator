<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\Release;
use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $this->syncProjectsFromExistingData();

        $projects = Project::query()
            ->withCount('releases')
            ->with('latestRelease')
            ->orderBy('repository_full_name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.projects.index', [
            'projects' => $projects,
        ]);
    }

    public function show(Project $project): View
    {
        $releases = $project->releases()
            ->orderByDesc('generated_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.projects.show', [
            'project' => $project,
            'releases' => $releases,
        ]);
    }

    public function store(StoreProjectRequest $request)
    {
        $validated = $request->validated();

        $project = Project::query()->create([
            'name' => $validated['name'] ?? null,
            'repository_full_name' => $validated['repository_full_name'],
            'default_branch' => $validated['default_branch'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('status', 'Project has been added successfully.');
    }

    private function syncProjectsFromExistingData(): void
    {
        $releaseRepositories = Release::query()
            ->whereNotNull('repository_full_name')
            ->where('repository_full_name', '!=', '')
            ->distinct()
            ->pluck('repository_full_name');

        $webhookRepositories = WebhookDelivery::query()
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
                ->map(static fn (string $repositoryFullName): array => [
                    'repository_full_name' => $repositoryFullName,
                    'default_branch' => 'main',
                    'is_active' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->all(),
            ['repository_full_name'],
            ['updated_at'],
        );
    }
}
