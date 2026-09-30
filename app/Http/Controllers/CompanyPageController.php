<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyPageController extends Controller
{
    private function authorizeCompanyManagement(Request $request): void
    {
        abort_unless($request->user()->status === 'active' && in_array($request->user()->role, ['admin', 'super_admin'], true), 403);
    }

    public function show(Request $request, Company $company): View
    {
        $this->authorizeCompanyManagement($request);
        $company->loadCount(['projects', 'users', 'users as workers_count' => fn ($query) => $query->where('role', 'worker')]);

        return view('companies.show', compact('company'));
    }

    public function edit(Request $request, Company $company): View
    {
        $this->authorizeCompanyManagement($request);

        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->authorizeCompanyManagement($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'mobile' => ['required', 'string', 'max:20'],
            'contact_person' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        $company->update($validated);

        return redirect()->route('web.companies.show', $company)->with('success', 'Company updated successfully.');
    }

    public function index(): View
    {
        $companies = Company::query()
            ->withCount([
                'projects',
                'users',
                'users as workers_count' => fn ($query) => $query->where('role', 'worker'),
            ])
            ->latest()
            ->get()
            ->map(fn (Company $company): array => [
                'id' => $company->id,
                'show_url' => route('web.companies.show', $company),
                'edit_url' => route('web.companies.edit', $company),
                'company_code' => $company->company_code,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->mobile,
                'contact' => $company->contact_person,
                'address' => $company->address,
                'city' => $company->city,
                'state' => $company->state,
                'status' => $company->status,
                'status_label' => str($company->status)->title()->toString(),
                'created_at' => $company->created_at?->toDateString(),
                'created_date' => $company->created_at?->format('d M Y') ?? '-',
                'projects_count' => (int) $company->projects_count,
                'users_count' => (int) $company->users_count,
                'workers_count' => (int) $company->workers_count,
                'logo' => $this->logoType($company->name),
                'symbol' => $this->logoSymbol($company->name),
            ])
            ->values();

        return view('companies.index', [
            'companies' => $companies,
            'summary' => [
                'total' => $companies->count(),
                'active' => $companies->where('status', 'active')->count(),
                'inactive' => $companies->where('status', 'inactive')->count(),
                'projects' => $companies->sum('projects_count'),
                'workers' => $companies->sum('workers_count'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'contact_person' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
            'state' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:Active,Inactive,active,inactive'],
        ]);

        Company::create([
            'company_code' => $this->nextCompanyCode($validated['company_name']),
            'name' => $validated['company_name'],
            'contact_person' => $validated['contact_person'],
            'mobile' => $validated['phone'],
            'email' => $validated['email'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'status' => Str::lower($validated['status']),
        ]);

        return redirect()
            ->route('web.companies.index')
            ->with('status', 'Company registered successfully.');
    }

    private function logoType(string $name): string
    {
        $types = ['leaf', 'cross', 'sun', 'building', 'triangle', 'spiral', 'wave', 'initials'];

        return $types[crc32($name) % count($types)];
    }

    private function logoSymbol(string $name): string
    {
        $initials = collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->implode('');

        return $initials ?: 'CO';
    }

    private function nextCompanyCode(string $name): string
    {
        $prefix = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $name) ?: 'CO', 0, 3));
        $number = Company::count() + 1;

        do {
            $code = $prefix.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
            $number++;
        } while (Company::where('company_code', $code)->exists());

        return $code;
    }
}
