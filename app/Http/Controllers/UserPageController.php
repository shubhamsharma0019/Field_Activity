<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserPageController extends Controller
{
    private function authorizeUserManagement(Request $request, User $user): void
    {
        abort_unless($request->user()->status === 'active' && in_array($request->user()->role, ['admin', 'super_admin'], true), 403);
        abort_if($user->role === 'super_admin' && $request->user()->role !== 'super_admin', 403);
    }

    public function show(Request $request, User $user): View
    {
        $this->authorizeUserManagement($request, $user);
        $user->load('company')->loadCount(['assignments', 'activitySubmissions']);

        return view('users.show', compact('user'));
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorizeUserManagement($request, $user);
        $user->load('company');

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUserManagement($request, $user);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user)],
            'mobile' => ['required', 'regex:/^[0-9]{10}$/', Rule::unique('users', 'mobile')->ignore($user)],
            'status' => ['required', Rule::in($request->user()->is($user) ? ['active'] : ['active', 'inactive'])],
            'password' => ['nullable', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return redirect()->route('web.users.show', $user)->with('success', 'User updated successfully.');
    }

    public function index(): View
    {
        $monthStart = now()->startOfMonth();

        $users = User::with('company')
            ->latest()
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'show_url' => route('web.users.show', $user),
                'edit_url' => route('web.users.edit', $user),
                'code' => 'USR'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'role' => $user->role,
                'role_label' => str($user->role)->replace('_', ' ')->title()->toString(),
                'company_id' => $user->company_id,
                'company' => $user->company?->name ?? '-',
                'status' => $user->status,
                'status_label' => str($user->status)->title()->toString(),
                'created_at' => $user->created_at?->toDateString(),
                'created_date' => $user->created_at?->format('d M Y') ?? '-',
                'assignments_count' => $user->role === 'worker' ? $user->assignments()->count() : 0,
                'submissions_count' => $user->role === 'worker' ? $user->activitySubmissions()->count() : 0,
            ])
            ->values();

        $companies = Company::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Company $company): array => [
                'id' => $company->id,
                'name' => $company->name,
            ])
            ->values();

        return view('users.index', [
            'users' => $users,
            'companies' => $companies,
            'summary' => [
                'total' => $users->count(),
                'total_month' => User::where('created_at', '>=', $monthStart)->count(),
                'admins' => $users->whereIn('role', ['admin', 'super_admin'])->count(),
                'admins_month' => User::whereIn('role', ['admin', 'super_admin'])->where('created_at', '>=', $monthStart)->count(),
                'company_users' => $users->where('role', 'company')->count(),
                'company_users_month' => User::where('role', 'company')->where('created_at', '>=', $monthStart)->count(),
                'workers' => $users->where('role', 'worker')->count(),
                'workers_month' => User::where('role', 'worker')->where('created_at', '>=', $monthStart)->count(),
                'active_workers' => $users->where('role', 'worker')->where('status', 'active')->count(),
            ],
        ]);
    }
}
