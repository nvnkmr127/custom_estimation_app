<?php

namespace App\Http\Controllers;

use App\Models\RoomTemplate;
use Illuminate\Http\Request;

class RoomTemplateController extends Controller
{
    public function index()
    {
        $templates = RoomTemplate::latest()->paginate(10);

        return view('templates.index', compact('templates'));
    }

    public function show(RoomTemplate $template)
    {
        return view('templates.show', compact('template'));
    }

    public function create()
    {
        $products = \App\Models\Product::orderBy('name', 'asc')->get();

        return view('templates.create', compact('products'));
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
        ]);

        RoomTemplate::create($validated);

        return redirect()->route('templates.index')->with('success', 'Room Template created successfully.');
    }

    public function edit(RoomTemplate $template)
    {
        $products = \App\Models\Product::orderBy('name', 'asc')->get();

        return view('templates.edit', compact('template', 'products'));
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
        ]);

        $template->update($validated);

        return redirect()->route('templates.index')->with('success', 'Room Template updated successfully.');
    }

    public function destroy(RoomTemplate $template)
    {
        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Room Template deleted successfully.');
    }
}
