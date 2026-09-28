<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get users.
     *
     * Admin / Super Admin:
     *   Can view all users.
     *
     * Company:
     *   Can view only users/workers belonging to own company.
     *
     * Worker:
     *   Can view only own profile.
     */
    public function index(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if (!in_array(
            $authUser->role,
            ['super_admin', 'admin', 'company', 'worker'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view users.',
            ], 403);
        }

        $query = User::with('company');

        /*
         * Company isolation.
         */
        if ($authUser->role === 'company') {
            if (empty($authUser->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No company is linked with this account.',
                ], 403);
            }

            $query->where(
                'company_id',
                $authUser->company_id
            );
        }

        /*
         * Worker can only see own user record.
         */
        if ($authUser->role === 'worker') {
            $query->where('id', $authUser->id);
        }

        /*
         * Filters
         */
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        /*
         * Only Admin / Super Admin can filter
         * arbitrary company IDs.
         */
        if (
            $request->filled('company_id')
            && in_array(
                $authUser->role,
                ['super_admin', 'admin'],
                true
            )
        ) {
            $query->where(
                'company_id',
                $request->company_id
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'mobile',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        return response()->json([
            'success' => true,
            'message' => 'Users fetched successfully.',
            'data' => $query
                ->latest()
                ->paginate(20),
        ]);
    }

    /**
     * Create user.
     *
     * Only Admin / Super Admin.
     */
    public function store(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if (!in_array(
            $authUser->role,
            ['super_admin', 'admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to create users.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:20',
                'unique:users,mobile',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],

            'role' => [
                'required',
                Rule::in([
                    'admin',
                    'company',
                    'worker',
                ]),
            ],

            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        /*
         * Company users and workers
         * must belong to a company.
         */
        if (
            in_array(
                $validated['role'],
                ['company', 'worker'],
                true
            )
            && empty($validated['company_id'])
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Company is required for company users and workers.',
            ], 422);
        }

        /*
         * Admin must not belong to company.
         */
        if ($validated['role'] === 'admin') {
            $validated['company_id'] = null;
        }

        $user = User::create([
            'name' => $validated['name'],
            'mobile' => $validated['mobile'] ?? null,
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'company_id' => $validated['company_id'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        $user->load('company');

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => [
                'user' => $user,
            ],
        ], 201);
    }

    /**
     * Get single user.
     */
    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $authUser = $request->user();

        if (!in_array(
            $authUser->role,
            ['super_admin', 'admin', 'company', 'worker'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view users.',
            ], 403);
        }

        $query = User::with('company');

        /*
         * Company can only access
         * users belonging to own company.
         */
        if ($authUser->role === 'company') {
            if (empty($authUser->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No company is linked with this account.',
                ], 403);
            }

            $query->where(
                'company_id',
                $authUser->company_id
            );
        }

        /*
         * Worker can only access own record.
         */
        if ($authUser->role === 'worker') {
            $query->where(
                'id',
                $authUser->id
            );
        }

        $user = $query->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found or you do not have access to this user.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User fetched successfully.',
            'data' => [
                'user' => $user,
            ],
        ]);
    }

    /**
     * Update user.
     *
     * Only Admin / Super Admin.
     */
    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $authUser = $request->user();

        if (!in_array(
            $authUser->role,
            ['super_admin', 'admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update users.',
            ], 403);
        }

        $user = User::findOrFail($id);

        /*
         * Protect Super Admin account
         * from being modified by normal Admin.
         */
        if (
            $user->role === 'super_admin'
            && $authUser->role !== 'super_admin'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Only super admin can update a super admin account.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'mobile')
                    ->ignore($user->id),
            ],

            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],

            'password' => [
                'sometimes',
                'string',
                'min:8',
            ],

            'role' => [
                'sometimes',
                Rule::in([
                    'admin',
                    'company',
                    'worker',
                ]),
            ],

            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        /*
         * Determine final role/company after update,
         * not only values present in request.
         */
        $finalRole = $validated['role']
            ?? $user->role;

        $finalCompanyId = array_key_exists(
            'company_id',
            $validated
        )
            ? $validated['company_id']
            : $user->company_id;

        /*
         * Company/worker must always
         * have company.
         */
        if (
            in_array(
                $finalRole,
                ['company', 'worker'],
                true
            )
            && empty($finalCompanyId)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Company is required for company users and workers.',
            ], 422);
        }

        /*
         * Admin should not remain attached
         * to any company.
         */
        if ($finalRole === 'admin') {
            $validated['company_id'] = null;
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => [
                'user' => $user
                    ->fresh()
                    ->load('company'),
            ],
        ]);
    }
}