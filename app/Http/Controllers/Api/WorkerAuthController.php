<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class WorkerAuthController extends Controller
{
    /**
     * Login a worker from the mobile application.
     *
     * POST /api/worker/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => [
                'required',
                'string',
                'max:255',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);

        $login = strtolower($validated['login']);

        $worker = User::where('role', 'worker')
            ->where(function ($query) use ($login) {
                $query->where('email', $login)
                    ->orWhere('mobile', $login);
            })
            ->first();

        if (
            !$worker
            || !Hash::check($validated['password'], $worker->password)
        ) {
            throw ValidationException::withMessages([
                'login' => ['Invalid mobile/email or password.'],
            ]);
        }

        if ($worker->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your worker account is inactive.',
            ], 403);
        }

        $worker->tokens()->delete();

        $token = $worker->createToken('worker_auth_token')->plainTextToken;

        $worker->load('company');

        return response()->json([
            'success' => true,
            'message' => 'Worker login successful.',
            'data' => [
                'worker' => [
                    'id' => $worker->id,
                    'name' => $worker->name,
                    'mobile' => $worker->mobile,
                    'email' => $worker->email,
                    'role' => $worker->role,
                    'status' => $worker->status,
                    'company_id' => $worker->company_id,
                    'company' => $worker->company ? [
                        'id' => $worker->company->id,
                        'company_code' => $worker->company->company_code,
                        'name' => $worker->company->name,
                    ] : null,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Register a worker from the mobile application.
     *
     * POST /api/worker/register
     */
    public function register(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Registration Data
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'mobile' => [
                'required',
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
                'confirmed',
            ],

            'company_code' => [
                'required',
                'string',
                'max:100',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Find Company Using Company Code
        |--------------------------------------------------------------------------
        |
        | Mobile app will NOT send company_id.
        |
        | Example:
        | COMP001
        |
        */

        $company = Company::where(
            'company_code',
            $validated['company_code']
        )->first();


        /*
        |--------------------------------------------------------------------------
        | Invalid Company
        |--------------------------------------------------------------------------
        */

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid company code.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Inactive Company
        |--------------------------------------------------------------------------
        */

        if ($company->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This company is currently inactive.',
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Create Worker
        |--------------------------------------------------------------------------
        */

        $worker = DB::transaction(
            function () use ($validated, $company) {

                return User::create([
                    'name' => $validated['name'],

                    'mobile' => $validated['mobile'],

                    'email' => strtolower(
                        $validated['email']
                    ),

                    /*
                     * User model already has:
                     *
                     * 'password' => 'hashed'
                     *
                     * So Laravel automatically hashes it.
                     */
                    'password' => $validated['password'],

                    /*
                     * Never take role from mobile app.
                     */
                    'role' => 'worker',

                    /*
                     * Company comes from verified company_code.
                     */
                    'company_id' => $company->id,

                    'status' => 'active',
                ]);
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Load Company
        |--------------------------------------------------------------------------
        */

        $worker->load('company');


        /*
        |--------------------------------------------------------------------------
        | Registration Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' => 'Worker registered successfully.',

            'data' => [
                'worker' => [
                    'id' => $worker->id,

                    'name' => $worker->name,

                    'mobile' => $worker->mobile,

                    'email' => $worker->email,

                    'role' => $worker->role,

                    'status' => $worker->status,

                    'company_id' => $worker->company_id,

                    'company' => [
                        'id' => $company->id,

                        'company_code' =>
                            $company->company_code,

                        'name' => $company->name,
                    ],
                ],
            ],
        ], 201);
    }
}
