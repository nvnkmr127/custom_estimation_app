<?php

namespace App\Http\Controllers;

use App\Models\ApprovalChecklist;
use Illuminate\Http\Request;

class ApprovalChecklistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $checklists = ApprovalChecklist::orderBy('created_at', 'desc')->get();
        return view('approval_checklists.index', compact('checklists'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('approval_checklists.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'task' => 'required|string|max:255',
            'is_required' => 'boolean',
        ]);

        ApprovalChecklist::create([
            'task' => $validated['task'],
            'is_required' => $request->has('is_required'),
        ]);

        return redirect()->route('approval-checklists.index')
            ->with('success', 'Checklist item created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ApprovalChecklist $approvalChecklist)
    {
        return view('approval_checklists.edit', compact('approvalChecklist'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ApprovalChecklist $approvalChecklist)
    {
        $validated = $request->validate([
            'task' => 'required|string|max:255',
            'is_required' => 'boolean',
        ]);

        $approvalChecklist->update([
            'task' => $validated['task'],
            'is_required' => $request->has('is_required'),
        ]);

        return redirect()->route('approval-checklists.index')
            ->with('success', 'Checklist item updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ApprovalChecklist $approvalChecklist)
    {
        $approvalChecklist->delete();

        return redirect()->route('approval-checklists.index')
            ->with('success', 'Checklist item deleted successfully.');
    }
}
