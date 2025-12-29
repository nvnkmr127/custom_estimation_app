<?php

namespace App\Http\Controllers;

use App\Models\ItemPackage;
use Illuminate\Http\Request;

class ItemPackageController extends Controller
{
    public function index()
    {
        $packages = ItemPackage::latest()->paginate(10);
        return view('packages.index', compact('packages'));
    }

    public function show(ItemPackage $package)
    {
        return view('packages.show', compact('package'));
    }

    public function create()
    {
        return view('packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'items' => 'required|array',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.unit_type' => 'nullable|string',
        ]);

        // Calculate total price automatically
        $total = 0;
        foreach ($validated['items'] as $item) {
            $total += ($item['quantity'] * ($item['unit_price'] ?? 0));
        }
        $validated['total_price'] = $total;

        ItemPackage::create($validated);

        return redirect()->route('packages.index')->with('success', 'Item Package created successfully.');
    }

    public function edit(ItemPackage $package)
    {
        return view('packages.edit', compact('package'));
    }

    public function update(Request $request, ItemPackage $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'items' => 'required|array',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.unit_type' => 'nullable|string',
        ]);

        $total = 0;
        foreach ($validated['items'] as $item) {
            $total += ($item['quantity'] * ($item['unit_price'] ?? 0));
        }
        $validated['total_price'] = $total;

        $package->update($validated);

        return redirect()->route('packages.index')->with('success', 'Item Package updated successfully.');
    }

    public function destroy(ItemPackage $package)
    {
        $package->delete();
        return redirect()->route('packages.index')->with('success', 'Item Package deleted successfully.');
    }
}
