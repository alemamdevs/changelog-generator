<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class WebhookDeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $deliveries = WebhookDelivery::query()
            ->where('user_id', $user->id)
            ->with('release:id,version')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.webhooks.index', [
            'deliveries' => $deliveries,
        ]);
    }

    public function show(Request $request, WebhookDelivery $webhookDelivery): View
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        abort_unless($webhookDelivery->user_id === $user->id, 404);

        $webhookDelivery->load(['release.commits', 'release.changelogs']);

        return view('admin.webhooks.show', [
            'delivery' => $webhookDelivery,
        ]);
    }
}
