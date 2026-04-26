<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

test('webhook configuration page is accessible and shows common endpoint', function (): void {
    config()->set('services.github.webhook_secret', 'configured');
    config()->set('services.gitlab.webhook_secret', 'configured');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.webhooks.configuration'));

    $response
        ->assertOk()
        ->assertSee('Webhook Configuration')
        ->assertSee(route('api.webhooks.push'), false)
        ->assertSee('id="githubWebhookSecret"', false)
        ->assertSee('id="gitlabWebhookSecret"', false)
        ->assertSee('data-copy-provider="github"', false)
        ->assertSee('data-generate-provider="gitlab"', false)
        ->assertSee('Configured');
});

test('configuration page generates and persists missing webhook secrets without redirect when requesting json', function (): void {
    $user = User::factory()->create();
    $envPath = storage_path('framework/testing/webhook-secrets.env');

    File::ensureDirectoryExists(dirname($envPath));
    File::put($envPath, "APP_NAME=Laravel\nGITHUB_WEBHOOK_SECRET=\n");

    config()->set('changelog.webhooks.env_file', $envPath);

    $response = $this
        ->actingAs($user)
        ->postJson(route('admin.webhooks.configuration.generate-secrets'), [
            'provider' => 'all',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('configured.github', true)
        ->assertJsonPath('configured.gitlab', true)
        ->assertJsonStructure([
            'ok',
            'generated',
            'message',
            'secrets' => ['github', 'gitlab'],
            'configured' => ['github', 'gitlab'],
        ]);

    $envContents = File::get($envPath);

    expect($envContents)->toContain('GITHUB_WEBHOOK_SECRET=')
        ->and($envContents)->toContain('GITLAB_WEBHOOK_SECRET=');

    preg_match('/^GITHUB_WEBHOOK_SECRET=(.*)$/m', $envContents, $githubMatches);
    preg_match('/^GITLAB_WEBHOOK_SECRET=(.*)$/m', $envContents, $gitlabMatches);

    expect(trim((string) ($githubMatches[1] ?? '')))->not->toBe('')
        ->and(trim((string) ($gitlabMatches[1] ?? '')))->not->toBe('');

    File::delete($envPath);
});
