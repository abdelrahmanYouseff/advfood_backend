<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'country' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create the user with role 'user'
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'country' => $request->country,
                'role' => 'user', // Always set as regular user
            ]);

            // Register user in points system and get customer ID
            \Log::info('Starting points system registration for user: ' . $user->email);
            $pointsService = new PointsService();
            $customerId = $pointsService->createCustomer([
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
            ]);

            \Log::info('Points system registration result: ' . ($customerId ?: 'null'));

            // Update user with customer ID if registration was successful
            if ($customerId) {
                $user->update(['point_customer_id' => $customerId]);
                \Log::info('Updated user with customer ID: ' . $customerId);
            } else {
                \Log::warning('Failed to create customer in points system for user: ' . $user->email);
            }

            // Send user data to external API (existing functionality)
            $userController = new UserController();
            $reflection = new \ReflectionClass($userController);
            $method = $reflection->getMethod('registerUserInExternalSystem');
            $method->setAccessible(true);
            $method->invoke($userController, $user);

            // Generate token for the user
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'address' => $user->address,
                        'country' => $user->country,
                        'role' => $user->role,
                        'point_customer_id' => $user->point_customer_id,
                        'created_at' => $user->created_at,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Attempt to authenticate
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'address' => $user->address,
                        'country' => $user->country,
                        'role' => $user->role,
                        'created_at' => $user->created_at,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 200);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'error' => 'Email or password is incorrect'
            ], 401);
        }
    }

    /**
     * Get user points balance
     */
    public function getPoints(Request $request)
    {
        try {
            // Get the authenticated user
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Check if user has point_customer_id
            if (!$user->point_customer_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not registered in points system',
                    'points_balance' => 0
                ], 404);
            }

            // Try to get points from points system
            $pointsService = new PointsService();
            $pointsData = $pointsService->getCustomerPoints($user->point_customer_id);

            $pointsBalance = 0;
            $source = 'default';

            if ($pointsData !== null) {
                // Extract points balance from response
                if (isset($pointsData['data']['points_balance'])) {
                    $pointsBalance = $pointsData['data']['points_balance'];
                    $source = 'points_system';
                } elseif (isset($pointsData['points_balance'])) {
                    $pointsBalance = $pointsData['points_balance'];
                    $source = 'points_system';
                }
            } else {
                // If points system is not available, return default points
                $pointsBalance = 0;
                $source = 'default';
                \Log::warning('Points system not available, returning default points', [
                    'user_id' => $user->id,
                    'customer_id' => $user->point_customer_id
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Points retrieved successfully',
                'data' => [
                    'customer_id' => $user->point_customer_id,
                    'points_balance' => $pointsBalance,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'source' => $source
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error retrieving points', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving points',
                'error' => $e->getMessage(),
                'points_balance' => 0
            ], 500);
        }
    }
}
