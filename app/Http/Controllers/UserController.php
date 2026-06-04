<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $roles = $this->getAvailableRoles();

        return view('users.index', compact('users', 'roles'));
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
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(array_keys(PermissionService::getRoles()))],
            'max_discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        // Only Super Admin can assign a Super Admin role
        if ($validated['role'] === 'super_admin' && !auth()->user()->hasRole('super_admin')) {
            return back()->with('error', 'Only Super Admins can assign the Super Admin role.');
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['max_discount_percentage'] = $validated['max_discount_percentage'] ?? 0;

        $user = User::create($validated);

        // Audit Log
        \App\Models\ActivityLog::log('user_created', $user, "User {$user->name} ({$user->role}) was created by " . auth()->user()->name);

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
        if ($user->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')) {
            return back()->with('error', 'You do not have permission to modify a Super Admin.');
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', Rule::in(array_keys(PermissionService::getRoles()))],
            'max_discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        // SSO users cannot change their email or password locally
        if ($user->source !== 'sso') {
            $rules['email'] = ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)];
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        $validated = $request->validate($rules);

        // Only Super Admin can change someone to Super Admin or change a Super Admin's role
        if (($validated['role'] === 'super_admin' || $user->hasRole('super_admin')) && !auth()->user()->hasRole('super_admin')) {
            return back()->with('error', 'Only Super Admins can manage Super Admin roles.');
        }

        if ($user->source !== 'sso' && !empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['max_discount_percentage'] = $validated['max_discount_percentage'] ?? 0;

        $user->update($validated);

        // Audit Log
        \App\Models\ActivityLog::log('user_updated', $user, "User {$user->name} was updated by " . auth()->user()->name);

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

        // Audit Log
        \App\Models\ActivityLog::log('user_deleted', $user, "User {$user->name} was deleted by " . auth()->user()->name);

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Display a listing of soft-deleted users.
     */
    public function trash()
    {
        $users = User::onlyTrashed()->latest()->paginate(15);

        return view('users.trash', compact('users'));
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(Request $request, $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);

        // Check if email is already taken by an active user
        $emailClean = preg_replace('/\.deleted\.\d+$/', '', $user->email);
        $duplicate = User::where('email', $emailClean)->first();

        if ($duplicate) {
            return back()->with('error', "Cannot restore user. The email {$emailClean} is already in use by another active user.");
        }

        // Restore email and restore user
        $user->email = $emailClean;
        $user->restore();

        // Audit Log
        \App\Models\ActivityLog::log('user_restored', $user, "User {$user->name} was restored by " . auth()->user()->name);

        return redirect()->route('users.index')->with('success', 'User restored successfully.');
    }

    /**
     * Get available roles with descriptions.
     */
    private function getAvailableRoles()
    {
        $roles = PermissionService::getRoles();

        return collect($roles)->mapWithKeys(function ($details, $key) {
            return [$key => $details['name'] . ' - ' . $details['description']];
        })->toArray();
    }
}
