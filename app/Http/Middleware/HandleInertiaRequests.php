<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => Inertia::always(fn () => [
                'user' => $request->user()?->load('roles'),
            ]),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'can' => $request->user() ? [
                'manage_employees' => $request->user()->can('manage employees'),
                'view_payouts' => $request->user()->can('view payouts'),
                'create_payouts' => $request->user()->can('create payouts'),
                'edit_payouts' => $request->user()->can('edit payouts'),
                'delete_payouts' => $request->user()->can('delete payouts'),
                'change_status' => $request->user()->can('change payout status'),
            ] : [],
        ];
    }
}
