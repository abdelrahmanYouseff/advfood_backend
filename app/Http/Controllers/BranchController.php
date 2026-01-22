<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    /**
     * Display a listing of branches.
     */
    public function index(): Response
    {
        $branches = Branch::orderBy('name')->get();

        \Log::info('📍 Branches page accessed', [
            'branches_count' => $branches->count(),
            'branches' => $branches->toArray(),
        ]);

        return Inertia::render('Branches', [
            'branches' => $branches,
        ]);
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create(): Response
    {
        return Inertia::render('BranchCreate');
    }

    /**
     * Store a newly created branch in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:branches,email',
            'password' => 'required|string|min:8',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['password'] = bcrypt($validated['password']);

        Branch::create($validated);

        return redirect()->route('branches.index')
            ->with('success', 'تم إضافة الفرع بنجاح');
    }

    /**
     * Display the specified branch.
     */
    public function show(Branch $branch): Response
    {
        return Inertia::render('BranchShow', [
            'branch' => $branch,
        ]);
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit(Branch $branch): Response
    {
        return Inertia::render('BranchEdit', [
            'branch' => $branch,
        ]);
    }

    /**
     * Update the specified branch in storage.
     */
    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:branches,email,' . $branch->id,
            'password' => 'nullable|string|min:8',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $branch->update($validated);

        return redirect()->route('branches.index')
            ->with('success', 'تم تحديث الفرع بنجاح');
    }

    /**
     * Remove the specified branch from storage.
     */
    public function destroy(Branch $branch)
    {
        $branch->delete();

        return redirect()->route('branches.index')
            ->with('success', 'تم حذف الفرع بنجاح');
    }
}
