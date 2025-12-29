<?php

namespace App\Http\Controllers;

use App\Models\ApprovalChain;
use App\Models\ApprovalChainStep;
use App\Models\User;
use Illuminate\Http\Request;

class ApprovalChainController extends Controller
{
    /**
     * Display a listing of approval chains.
     */
    public function index()
    {
        $chains = ApprovalChain::withCount('steps')
            ->with('steps.user')
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return view('approval_chains.index', compact('chains'));
    }

    /**
     * Show the form for creating a new approval chain.
     */
    public function create()
    {
        $users = User::whereIn('role', ['super_admin', 'estimator_admin', 'sales_manager'])
            ->orderBy('name')
            ->get();

        return view('approval_chains.create', compact('users'));
    }

    /**
     * Store a newly created approval chain.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'steps' => 'required|array|min:1',
            'steps.*.user_id' => 'required|exists:users,id',
            'steps.*.order' => 'required|integer|min:1',
        ]);

        $chain = ApprovalChain::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        foreach ($validated['steps'] as $stepData) {
            $user = User::find($stepData['user_id']);
            ApprovalChainStep::create([
                'approval_chain_id' => $chain->id,
                'user_id' => $stepData['user_id'],
                'role' => $user->role,
                'order' => $stepData['order'],
            ]);
        }

        return redirect()->route('approval-chains.index')
            ->with('success', 'Approval chain created successfully.');
    }

    /**
     * Display the specified approval chain.
     */
    public function show(ApprovalChain $approvalChain)
    {
        $approvalChain->load('steps.user');
        return view('approval_chains.show', compact('approvalChain'));
    }

    /**
     * Show the form for editing the specified approval chain.
     */
    public function edit(ApprovalChain $approvalChain)
    {
        $approvalChain->load('steps.user');
        $users = User::whereIn('role', ['super_admin', 'estimator_admin', 'sales_manager'])
            ->orderBy('name')
            ->get();

        return view('approval_chains.edit', compact('approvalChain', 'users'));
    }

    /**
     * Update the specified approval chain.
     */
    public function update(Request $request, ApprovalChain $approvalChain)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'steps' => 'required|array|min:1',
            'steps.*.user_id' => 'required|exists:users,id',
            'steps.*.order' => 'required|integer|min:1',
        ]);

        $approvalChain->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Delete existing steps and recreate
        $approvalChain->steps()->delete();

        foreach ($validated['steps'] as $stepData) {
            $user = User::find($stepData['user_id']);
            ApprovalChainStep::create([
                'approval_chain_id' => $approvalChain->id,
                'user_id' => $stepData['user_id'],
                'role' => $user->role,
                'order' => $stepData['order'],
            ]);
        }

        return redirect()->route('approval-chains.index')
            ->with('success', 'Approval chain updated successfully.');
    }

    /**
     * Remove the specified approval chain.
     */
    public function destroy(ApprovalChain $approvalChain)
    {
        // Check if chain is being used by any estimates
        if ($approvalChain->estimates()->count() > 0) {
            return redirect()->route('approval-chains.index')
                ->with('error', 'Cannot delete approval chain that is being used by estimates.');
        }

        $approvalChain->steps()->delete();
        $approvalChain->delete();

        return redirect()->route('approval-chains.index')
            ->with('success', 'Approval chain deleted successfully.');
    }

    /**
     * Set the default approval chain.
     */
    public function setDefault(ApprovalChain $approvalChain)
    {
        // Store in settings (you can implement a settings table or use config)
        // For now, we'll use a simple approach with the database
        \DB::table('settings')->updateOrInsert(
            ['key' => 'default_approval_chain_id'],
            ['value' => $approvalChain->id]
        );

        return redirect()->route('approval-chains.index')
            ->with('success', 'Default approval chain set successfully.');
    }
}
