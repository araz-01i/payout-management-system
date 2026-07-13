<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePayoutRequest;
use App\Http\Requests\UpdatePayoutRequest;
use App\Models\Employee;
use App\Models\Payout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PayoutController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');

        $allowedSorts = ['task', 'amount', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $payouts = Payout::with('employee')
            ->when($search, fn ($q) => $q->where('task', 'like', "%{$search}%"))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy($sortBy, $sortDir)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Payout $payout) => [
                'id' => $payout->id,
                'task' => $payout->task,
                'amount' => $payout->amount,
                'formatted_amount' => $payout->formatted_amount,
                'status' => $payout->status,
                'created_at' => $payout->created_at->toDateString(),
                'employee' => [
                    'id' => $payout->employee->id,
                    'name' => $payout->employee->name,
                ],
            ]);

        $user = $request->user();

        return Inertia::render('payouts/Index', [
            'payouts' => $payouts,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
            'employees' => Employee::orderBy('name')->get(['id', 'name']),
            'payoutCan' => [
                'create' => $user->can('create payouts'),
                'edit' => $user->can('edit payouts'),
                'delete' => $user->can('delete payouts'),
                'changeStatus' => $user->can('change payout status'),
            ],
        ]);
    }

    public function store(StorePayoutRequest $request): RedirectResponse
    {
        Payout::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payout created successfully.']);

        return to_route('payouts.index');
    }

    public function update(UpdatePayoutRequest $request, Payout $payout): RedirectResponse
    {
        $payout->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payout updated successfully.']);

        return to_route('payouts.index');
    }

    public function updateStatus(Request $request, Payout $payout): RedirectResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'completed'])],
        ]);

        $payout->update(['status' => $request->status]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payout status updated.']);

        return to_route('payouts.index');
    }

    public function destroy(Payout $payout): RedirectResponse
    {
        $payout->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payout deleted successfully.']);

        return to_route('payouts.index');
    }
}
