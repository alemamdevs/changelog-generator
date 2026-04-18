<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('webhook configuration page is accessible and shows common endpoint', function (): void {
    config()->set('services.github.webhook_secret', 'configured');
    config()->set('services.gitlab.webhook_secret', 'configured');

    $response = $this->get(route('admin.webhooks.configuration'));

    $response
        ->assertOk()
        ->assertSee('Webhook Configuration')
        ->assertSee(route('api.webhooks.push'), false)
        ->assertSee('Configured');
});
