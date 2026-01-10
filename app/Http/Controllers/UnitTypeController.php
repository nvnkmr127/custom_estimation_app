<?php

namespace App\Http\Controllers;

use App\Models\UnitType;
use Illuminate\Http\Request;

class UnitTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $unitTypes = UnitType::all();
        return view('unit_types.index', compact('unitTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'units' => 'required|array|min:1',
            'units.*' => 'required|string|max:50',
        ]);

        UnitType::create([
            'name' => $validated['name'],
            'units' => $validated['units'],
        ]);

        return redirect()->route('unit-types.index')->with('success', 'Unit Type created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UnitType $unitType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'units' => 'required|array|min:1',
            'units.*' => 'required|string|max:50',
        ]);

        $unitType->update([
            'name' => $validated['name'],
            'units' => $validated['units'],
        ]);

        return redirect()->route('unit-types.index')->with('success', 'Unit Type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UnitType $unitType)
    {
        $unitType->delete();
        return redirect()->route('unit-types.index')->with('success', 'Unit Type deleted successfully.');
    }
}
