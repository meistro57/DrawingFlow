<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerImportRequest;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\CustomerImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search', ''));
        $status = $request->string('status', 'all')->toString();
        $sort = $request->string('sort', 'latest')->toString();

        $sortMap = [
            'latest' => ['created_at', 'desc'],
            'oldest' => ['created_at', 'asc'],
            'name_asc' => ['name', 'asc'],
            'name_desc' => ['name', 'desc'],
        ];

        if (! array_key_exists($sort, $sortMap)) {
            $sort = 'latest';
        }

        [$sortColumn, $sortDirection] = $sortMap[$sort];

        $customers = Customer::query()
            ->withCount(['projects', 'drawingRequests'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status): void {
                $query->where('active', $status === 'active');
            })
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Create');
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function import(CustomerImportRequest $request, CustomerImportService $customerImportService): RedirectResponse
    {
        $result = $customerImportService->import($request->file('file'));

        $redirect = redirect()->route('customers.index')->with(
            'success',
            "Import completed. Created: {$result['created']}, Updated: {$result['updated']}, Skipped: {$result['skipped']}."
        );

        if ($result['errors'] !== []) {
            $preview = array_slice($result['errors'], 0, 5);
            $moreCount = count($result['errors']) - count($preview);
            $details = implode(' ', $preview);

            if ($moreCount > 0) {
                $details .= " (+{$moreCount} more)";
            }

            $redirect->with('error', "Import warnings: {$details}");
        }

        return $redirect;
    }

    public function show(Customer $customer): Response
    {
        $customer->load(['projects' => fn ($q) => $q->latest()->take(10)]);
        $customer->loadCount(['projects', 'drawingRequests', 'submittals']);

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
        ]);
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }
}
