<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
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

        // Fetch points ONLY from pointsys for each user
        $pointsService = new PointsService();
        $usersWithPoints = $users->map(function ($user) use ($pointsService) {
            if ($user->point_customer_id) {
                $pointsData = $pointsService->getCustomerPoints($user->point_customer_id);
                if ($pointsData) {
                    $user->points = $pointsData['points'] ?? 0;
                    $user->points_tier = $pointsData['tier'] ?? 'bronze';
                } else {
                    // No points from pointsys - set to 0
                    $user->points = 0;
                    $user->points_tier = 'bronze';
                }
            } else {
                // No customer ID - no points
                $user->points = 0;
                $user->points_tier = 'bronze';
            }
            return $user;
        });

        return Inertia::render('Users', [
            'users' => $usersWithPoints,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('UserCreate');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:admin,user',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone_number' => $validated['phone_number'],
            'address' => $validated['address'],
            'country' => $validated['country'],
        ]);

        // Register user in points system and get customer ID
        $pointsService = new PointsService();
        $customerId = $pointsService->createCustomer([
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
        ]);

        // Update user with customer ID if registration was successful
        if ($customerId) {
            $user->update(['point_customer_id' => $customerId]);
        }

        // Send user data to external API (existing functionality)
        $this->registerUserInExternalSystem($user);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Register user in external point system
     */
    private function registerUserInExternalSystem(User $user)
    {
        try {
            $apiKey = config('services.external_api.key');
            $apiUrl = config('services.external_api.url');

            // Debug: Check if API key exists
            if (empty($apiKey)) {
                throw new \Exception('External API key is not configured');
            }

            // Debug: Log the data being sent
            $postData = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number ?: '01000000000',
                'address' => $user->address ?? '',
                'country' => $user->country ?? '',
                'role' => $user->role,
                'external_id' => $user->id, // Reference to our local user ID
            ];

            // Create a debug file to track API calls
            file_put_contents(storage_path('logs/api_debug.log'),
                date('Y-m-d H:i:s') . " - Sending to external API:\n" .
                "URL: " . $apiUrl . "/customers/register\n" .
                "Data: " . json_encode($postData) . "\n\n",
                FILE_APPEND | LOCK_EX
            );

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($apiUrl . '/customers/register', $postData);

            // Log the response to debug file
            file_put_contents(storage_path('logs/api_debug.log'),
                date('Y-m-d H:i:s') . " - Response from external API:\n" .
                "Status: " . $response->status() . "\n" .
                "Body: " . $response->body() . "\n\n",
                FILE_APPEND | LOCK_EX
            );
        } catch (\Exception $e) {
            // Log exception to debug file
            file_put_contents(storage_path('logs/api_debug.log'),
                date('Y-m-d H:i:s') . " - Exception occurred:\n" .
                "Error: " . $e->getMessage() . "\n\n",
                FILE_APPEND | LOCK_EX
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deleting the currently authenticated user
            if (Auth::id() === $user->id) {
                return back()->withErrors(['error' => 'You cannot delete your own account.']);
            }

            $user->delete();

            return back()->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete user.']);
        }
    }
}
