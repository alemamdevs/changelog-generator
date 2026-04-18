<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookDelivery;

final class WebhookDeliveryController extends Controller
{
    public function index()
    {
        $deliveries = WebhookDelivery::query()
            ->with('release:id,version')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.webhooks.index', [
            'deliveries' => $deliveries,
        ]);
    }

    public function show(WebhookDelivery $webhookDelivery)
    {
        $webhookDelivery->load(['release.commits', 'release.changelogs']);

        return view('admin.webhooks.show', [
            'delivery' => $webhookDelivery,
        ]);
    }
}
