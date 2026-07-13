<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(): Response
    {
        $search = request()->query('search', '');

        $employees = Employee::when($search, function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%");
        })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
                'created_at' => $employee->created_at->toDateString(),
            ]);

        return Inertia::render('employees/Index', [
            'employees' => $employees,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): RedirectResponse
    {
        // Not needed since we're using modals
        return redirect()->route('employees.index');
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        Employee::create([
            'name' => $request->validated('name'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee created successfully.']);

        return to_route('employees.index');
    }

    public function edit(Employee $employee): RedirectResponse
    {
        // Not needed since we're using modals
        return redirect()->route('employees.index');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->fill([
            'name' => $request->validated('name'),
        ]);

        $employee->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee updated successfully.']);

        return to_route('employees.index');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee deleted successfully.']);

        return to_route('employees.index');
    }
}
