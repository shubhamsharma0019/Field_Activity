<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;

class UserPageController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth();

        $users = User::with('company')
            ->latest()
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'code' => 'USR' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
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
