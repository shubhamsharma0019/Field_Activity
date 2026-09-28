<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /**
     * Get companies.
     *
     * Admin / Super Admin:
     *   Can view all companies.
     *
     * Company:
     *   Can view only own company.
     *
     * Worker:
     *   No access to company listing.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['super_admin', 'admin', 'company'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view companies.',
            ], 403);
        }

        $query = Company::query();

        /*
         * Company isolation.
         */
        if ($user->role === 'company') {
            if (empty($user->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No company is linked with this account.',
                ], 403);
            }

            $query->where(
                'id',
                $user->company_id
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
                        'company_code',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'contact_person',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'mobile',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $companies = $query
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Companies fetched successfully.',
            'data' => $companies,
        ]);
    }

    /**
     * Create company.
     *
     * Only Admin / Super Admin.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['super_admin', 'admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin or super admin can create companies.',
            ], 403);
        }

        $validated = $request->validate([
            'company_code' => [
                'required',
                'string',
                'max:30',
                'unique:companies,company_code',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'contact_person' => [
                'nullable',
                'string',
                'max:150',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        $company = Company::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Company created successfully.',
            'data' => [
                'company' => $company,
            ],
        ], 201);
    }

    /**
     * Get single company.
     */
    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['super_admin', 'admin', 'company'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view companies.',
            ], 403);
        }

        $query = Company::withCount([
            'users',
            'projects',
        ]);

        /*
         * Company can only access itself.
         */
        if ($user->role === 'company') {
            if (empty($user->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No company is linked with this account.',
                ], 403);
            }

            $query->where(
                'id',
                $user->company_id
            );
        }

        $company = $query->find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found or you do not have access to this company.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Company fetched successfully.',
            'data' => [
                'company' => $company,
            ],
        ]);
    }

    /**
     * Update company.
     *
     * Only Admin / Super Admin.
     */
    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['super_admin', 'admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin or super admin can update companies.',
            ], 403);
        }

        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'company_code' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique(
                    'companies',
                    'company_code'
                )->ignore($company->id),
            ],

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],

            'contact_person' => [
                'nullable',
                'string',
                'max:150',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        $company->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Company updated successfully.',
            'data' => [
                'company' => $company->fresh(),
            ],
        ]);
    }

    /**
     * Delete company.
     *
     * Only Admin / Super Admin.
     */
    public function destroy(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        if (!in_array(
            $user->role,
            ['super_admin', 'admin'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin or super admin can delete companies.',
            ], 403);
        }

        $company = Company::findOrFail($id);

        /*
         * Do not delete a company that still
         * has users or projects connected.
         */
        if ($company->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Company cannot be deleted because users are linked to it.',
            ], 409);
        }

        if ($company->projects()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Company cannot be deleted because projects are linked to it.',
            ], 409);
        }

        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'Company deleted successfully.',
        ]);
    }
}