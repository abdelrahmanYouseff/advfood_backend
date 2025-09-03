<?php

namespace App\Http\Controllers;

use App\Models\User;
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
            'created_at'
        ])->latest()->get();

        return Inertia::render('Users', [
            'users' => $users,
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

        // Send user data to external API
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

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($apiUrl . '/customers/register', [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone_number ?: '01000000000',
                    'address' => $user->address ?? '',
                    'country' => $user->country ?? '',
                    'role' => $user->role,
                    'external_id' => $user->id, // Reference to our local user ID
                ]);

            // Temporarily disabled logging due to permission issues
            // if ($response->successful()) {
            //     Log::info('User successfully registered in external system', [
            //         'user_id' => $user->id,
            //         'external_response' => $response->json()
            //     ]);
            // } else {
            //     Log::warning('Failed to register user in external system', [
            //         'user_id' => $user->id,
            //         'response_status' => $response->status(),
            //         'response_body' => $response->body()
            //     ]);
            // }
        } catch (\Exception $e) {
            // Temporarily disabled logging due to permission issues
            // Log::error('Exception occurred while registering user in external system', [
            //     'user_id' => $user->id,
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
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
