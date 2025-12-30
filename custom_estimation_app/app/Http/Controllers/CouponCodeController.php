<?php

namespace App\Http\Controllers;

use App\Models\CouponCode;
use Illuminate\Http\Request;

class CouponCodeController extends Controller
{
    /**
     * Display a listing of coupon codes.
     */
    public function index()
    {
        $coupons = CouponCode::withCount('estimates')
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('coupons.index', compact('coupons'));
    }

    /**
     * Show the form for creating a new coupon code.
     */
    public function create()
    {
        return view('coupons.create');
    }

    /**
     * Store a newly created coupon code.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupon_codes,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'boolean',
        ]);

        // Convert code to uppercase
        $validated['code'] = strtoupper($validated['code']);
        $validated['is_active'] = $request->has('is_active');

        CouponCode::create($validated);

        return redirect()->route('coupons.index')
            ->with('success', 'Coupon code created successfully.');
    }

    /**
     * Display the specified coupon code.
     */
    public function show(CouponCode $coupon)
    {
        $coupon->load([
            'estimates' => function ($query) {
                $query->latest()->limit(10);
            },
        ]);

        return view('coupons.show', compact('coupon'));
    }

    /**
     * Show the form for editing the specified coupon code.
     */
    public function edit(CouponCode $coupon)
    {
        return view('coupons.edit', compact('coupon'));
    }

    /**
     * Update the specified coupon code.
     */
    public function update(Request $request, CouponCode $coupon)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupon_codes,code,'.$coupon->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['is_active'] = $request->has('is_active');

        $coupon->update($validated);

        return redirect()->route('coupons.index')
            ->with('success', 'Coupon code updated successfully.');
    }

    /**
     * Remove the specified coupon code.
     */
    public function destroy(CouponCode $coupon)
    {
        // Check if coupon has been used
        if ($coupon->estimates()->count() > 0) {
            return redirect()->route('coupons.index')
                ->with('error', 'Cannot delete coupon code that has been used in estimates.');
        }

        $coupon->delete();

        return redirect()->route('coupons.index')
            ->with('success', 'Coupon code deleted successfully.');
    }

    /**
     * Validate a coupon code (AJAX endpoint).
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'total' => 'required|numeric|min:0',
        ]);

        $coupon = CouponCode::where('code', strtoupper($request->code))->first();

        if (! $coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid coupon code.',
            ]);
        }

        if (! $coupon->isValid($request->total)) {
            return response()->json([
                'valid' => false,
                'message' => $coupon->getValidationMessage($request->total),
            ]);
        }

        $discount = $coupon->calculateDiscount($request->total);

        return response()->json([
            'valid' => true,
            'coupon_id' => $coupon->id,
            'discount' => $discount,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'message' => 'Coupon applied successfully!',
        ]);
    }
}
