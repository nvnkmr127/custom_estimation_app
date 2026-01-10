<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands = Brand::all();
        return view('brands.index', compact('brands'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('brands.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'logo_url' => 'nullable|url',
            'primary_color' => 'required|string',
            'secondary_color' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        if ($request->has('is_default') && $request->is_default) {
            Brand::where('is_default', true)->update(['is_default' => false]);
        }

        Brand::create($validated);

        return redirect()->route('brands.index')->with('success', 'Brand created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Brand $brand)
    {
        return view('brands.edit', compact('brand'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'logo_url' => 'nullable|url',
            'primary_color' => 'required|string',
            'secondary_color' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        if ($request->has('is_default') && $request->is_default) {
            Brand::where('id', '!=', $brand->id)->update(['is_default' => false]);
        }

        $brand->update($validated);

        return redirect()->route('brands.index')->with('success', 'Brand updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        // Don't delete the only default brand? 
        $brand->delete();
        return redirect()->route('brands.index')->with('success', 'Brand deleted successfully.');
    }
}
