<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = User::latest()->paginate(15);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = $this->getAvailableRoles();

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['super_admin', 'estimator_admin', 'sales_manager', 'sales'])],
            'max_discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['max_discount_percentage'] = $validated['max_discount_percentage'] ?? 0;

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $roles = $this->getAvailableRoles();

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        // Protect Super Admin from non-Super Admin
        if ($user->hasRole('super_admin') && ! auth()->user()->hasRole('super_admin')) {
            return back()->with('error', 'You do not have permission to modify a Super Admin.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['super_admin', 'estimator_admin', 'sales_manager', 'sales'])],
            'max_discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        // Only Super Admin can change someone to Super Admin or change a Super Admin's role
        if (($validated['role'] === 'super_admin' || $user->hasRole('super_admin')) && ! auth()->user()->hasRole('super_admin')) {
            return back()->with('error', 'Only Super Admins can manage Super Admin roles.');
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['max_discount_percentage'] = $validated['max_discount_percentage'] ?? 0;

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Protect Super Admin
        if ($user->hasRole('super_admin')) {
            return back()->with('error', 'Super Admin accounts cannot be deleted.');
        }

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Get available roles with descriptions.
     */
    private function getAvailableRoles()
    {
        return [
            'super_admin' => 'Super Admin - Full system access',
            'estimator_admin' => 'Estimator Admin - Manage estimates and products',
            'sales_manager' => 'Sales Manager - Approve estimates',
            'sales' => 'Sales - Create and manage estimates',
        ];
    }
}
