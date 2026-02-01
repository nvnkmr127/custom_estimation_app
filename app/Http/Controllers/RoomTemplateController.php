<?php

namespace App\Http\Controllers;

use App\Models\RoomTemplate;
use App\Models\UnitType;
use Illuminate\Http\Request;

class RoomTemplateController extends Controller
{
    public function index()
    {
        return view('templates.index');
    }

    public function show(RoomTemplate $template)
    {
        return view('templates.show', compact('template'));
    }

    public function create()
    {
        $products = \App\Models\Product::with(['images', 'options.values'])->orderBy('name', 'asc')->get();
        $unitTypes = UnitType::all();

        return view('templates.edit', compact('products', 'unitTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'items' => 'required|array',
            'items.*.item_name' => 'required|string', // Generic item name
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.unit_type' => 'nullable|string',
            'items.*.unit_type_id' => 'nullable|exists:unit_types,id',
            'items.*.description' => 'nullable|string',
            'items.*.length' => 'nullable|numeric|min:0',
            'items.*.width' => 'nullable|numeric|min:0',
            'items.*.height' => 'nullable|numeric|min:0',
            'items.*.calculation_method' => 'nullable|string',
            'items.*.formula_string' => 'nullable|string',
            'items.*.options' => 'nullable|array',
            'allowed_unit_types' => 'nullable|array',
            'allowed_unit_types.*' => 'exists:unit_types,id',
        ]);

        // Convert empty string unit_type_id to null
        if (isset($validated['items'])) {
            foreach ($validated['items'] as &$item) {
                if (isset($item['unit_type_id']) && $item['unit_type_id'] === '') {
                    $item['unit_type_id'] = null;
                }
            }
        }

        RoomTemplate::create($validated);

        return redirect()->route('templates.index')->with('success', 'Room Template created successfully.');
    }

    public function edit(RoomTemplate $template)
    {
        $products = \App\Models\Product::with(['images', 'options.values'])->orderBy('name', 'asc')->get();
        $unitTypes = UnitType::all();

        return view('templates.edit', compact('template', 'products', 'unitTypes'));
    }

    public function update(Request $request, RoomTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'items' => 'required|array',
            'items.*.item_name' => 'required|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.unit_type' => 'nullable|string',
            'items.*.unit_type_id' => 'nullable|exists:unit_types,id',
            'items.*.description' => 'nullable|string',
            'items.*.length' => 'nullable|numeric|min:0',
            'items.*.width' => 'nullable|numeric|min:0',
            'items.*.height' => 'nullable|numeric|min:0',
            'items.*.calculation_method' => 'nullable|string',
            'items.*.formula_string' => 'nullable|string',
            'items.*.options' => 'nullable|array',
            'allowed_unit_types' => 'nullable|array',
            'allowed_unit_types.*' => 'exists:unit_types,id',
        ]);

        // Convert empty string unit_type_id to null
        if (isset($validated['items'])) {
            foreach ($validated['items'] as &$item) {
                if (isset($item['unit_type_id']) && $item['unit_type_id'] === '') {
                    $item['unit_type_id'] = null;
                }
            }
        }

        $template->update($validated);

        return redirect()->route('templates.index')->with('success', 'Room Template updated successfully.');
    }

    public function destroy(RoomTemplate $template)
    {
        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Room Template deleted successfully.');
    }
}
