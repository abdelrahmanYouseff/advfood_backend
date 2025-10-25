<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Delete a user from the database
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Validate the user ID
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف المستخدم غير صحيح',
                    'error' => 'Invalid user ID'
                ], 400);
            }

            // Find the user
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود',
                    'error' => 'User not found'
                ], 404);
            }

            // Check if user is trying to delete themselves
            if (Auth::id() === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك حذف حسابك الخاص',
                    'error' => 'Cannot delete your own account'
                ], 403);
            }

            // Check if user is admin (optional authorization check)
            $currentUser = Auth::user();
            if ($currentUser && !$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بحذف المستخدمين',
                    'error' => 'Unauthorized to delete users'
                ], 403);
            }

            // Store user data for logging before deletion
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'point_customer_id' => $user->point_customer_id
            ];

            // Delete the user
            $user->delete();

            // Log the deletion
            Log::info('User deleted successfully', [
                'deleted_user' => $userData,
                'deleted_by' => Auth::id(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المستخدم بنجاح',
                'data' => [
                    'deleted_user_id' => $userData['id'],
                    'deleted_user_name' => $userData['name'],
                    'deleted_at' => now()->format('Y-m-d H:i:s')
                ]
            ], 200);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Error deleting user', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user details by ID
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            // Validate the user ID
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف المستخدم غير صحيح',
                    'error' => 'Invalid user ID'
                ], 400);
            }

            // Find the user
            $user = User::select([
                'id',
                'name',
                'email',
                'phone_number',
                'address',
                'country',
                'role',
                'point_customer_id',
                'created_at'
            ])->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود',
                    'error' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب بيانات المستخدم بنجاح',
                'data' => $user
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving user', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users (for admin)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Check if user is admin
            $currentUser = Auth::user();
            if (!$currentUser || !$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بعرض المستخدمين',
                    'error' => 'Unauthorized to view users'
                ], 403);
            }

            $users = User::select([
                'id',
                'name',
                'email',
                'phone_number',
                'address',
                'country',
                'role',
                'point_customer_id',
                'created_at'
            ])->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'تم جلب قائمة المستخدمين بنجاح',
                'data' => $users,
                'count' => $users->count()
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving users list', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة المستخدمين',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

