<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequestChecklist;
use Illuminate\Http\Request;

class ChangeRequestChecklistController extends Controller
{
    public function index()
    {
        $checklists = ChangeRequestChecklist::orderBy('display_order')->get();
        return view('change_request_checklists.index', compact('checklists'));
    }

    public function create()
    {
        return view('change_request_checklists.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task' => 'required|string|max:255',
            'display_order' => 'integer',
        ]);

        ChangeRequestChecklist::create($validated);

        return redirect()->route('change-request-checklists.index')
            ->with('success', 'Change request checklist item created successfully.');
    }

    public function edit(ChangeRequestChecklist $changeRequestChecklist)
    {
        $checklist = $changeRequestChecklist;
        return view('change_request_checklists.edit', compact('checklist'));
    }

    public function update(Request $request, ChangeRequestChecklist $changeRequestChecklist)
    {
        $validated = $request->validate([
            'task' => 'required|string|max:255',
            'display_order' => 'integer',
        ]);

        $changeRequestChecklist->update($validated);

        return redirect()->route('change-request-checklists.index')
            ->with('success', 'Change request checklist item updated successfully.');
    }

    public function destroy(ChangeRequestChecklist $changeRequestChecklist)
    {
        $changeRequestChecklist->delete();

        return redirect()->route('change-request-checklists.index')
            ->with('success', 'Change request checklist item deleted successfully.');
    }
}
