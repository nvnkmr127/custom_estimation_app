@extends('guide.layout')

@section('content')
    <div class="mb-12">
        <h1 class="text-3xl font-extrabold text-slate-900">Product Catalog Management</h1>
        <p class="mt-4 text-lg text-slate-600">The central database for all materials, labor, and services used in your
            estimates.</p>
    </div>

    <!-- Use Case (Light Theme) -->
    <div class="bg-teal-50 border border-teal-200 rounded-2xl p-8 shadow-sm mb-16 relative overflow-hidden">
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-4">
                <span class="bg-teal-100 text-teal-800 text-xs font-bold px-2 py-1 rounded uppercase tracking-wide">Use
                    Case</span>
                <span class="text-teal-900 text-sm font-medium">Inventory Management</span>
            </div>
            <h3 class="text-2xl font-bold mb-3 text-teal-900 mt-0">Scenario: Seasonal Price Update</h3>
            <p class="text-teal-800 text-sm mb-6 max-w-xl leading-relaxed">
                Lumber prices have increased by 15%. You need to update all wood-related products to reflect the new cost
                basis and protect margins.
            </p>
            <div class="flex flex-wrap gap-2 text-xs font-mono text-teal-700">
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">1. Filter by "Lumber"
                    Category</span>
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">2. Bulk Edit Prices</span>
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">3. Save Changes</span>
            </div>
        </div>
    </div>

    <!-- Products -->
    <div class="mb-20" id="products">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Products & Offerings</h2>
        <p class="text-slate-600 mb-8">You can add products manually or import them in bulk.</p>

        <div class="bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm mb-12">
            <div class="p-6 bg-slate-50 border-b border-slate-200 font-bold text-slate-800">
                Key Form Fields (Create Product)
            </div>
            <table class="min-w-full divide-y divide-slate-200">
                <tbody class="divide-y divide-slate-200 bg-white">
                    <tr>
                        <td class="px-8 py-5 text-sm font-bold text-slate-900 w-1/4 bg-slate-50/50">SKU</td>
                        <td class="px-8 py-5 text-sm text-slate-600">Unique Stock Keeping Unit identifier. Must be unique
                            across the system.</td>
                    </tr>
                    <tr>
                        <td class="px-8 py-5 text-sm font-bold text-slate-900 bg-slate-50/50">Name</td>
                        <td class="px-8 py-5 text-sm text-slate-600">The display name shown to clients on the proposal.</td>
                    </tr>
                    <tr>
                        <td class="px-8 py-5 text-sm font-bold text-slate-900 bg-slate-50/50">Base Price</td>
                        <td class="px-8 py-5 text-sm text-slate-600">The default selling price (e.g., ₹500). Can be
                            overridden in individual estimates.</td>
                    </tr>
                    <tr>
                        <td class="px-8 py-5 text-sm font-bold text-slate-900 bg-slate-50/50">Unit</td>
                        <td class="px-8 py-5 text-sm text-slate-600">Measurement unit (e.g., Sq Ft, Hour, Each, Box).</td>
                    </tr>
                    <tr>
                        <td class="px-8 py-5 text-sm font-bold text-slate-900 bg-slate-50/50">Taxable</td>
                        <td class="px-8 py-5 text-sm text-slate-600">Checkbox. If checked, sales tax rules (GST) will apply
                            to this item.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="p-6 bg-blue-50 text-blue-900 rounded-lg text-sm mb-8 border border-blue-100">
            <strong>Pro Tip:</strong> Use the "Category" dropdown to group items. Categories help filter reports and
            organize the product picker.
        </div>
    </div>

    <!-- Packages & Categories -->
    <div class="grid md:grid-cols-2 gap-10 mb-20">
        <div>
            <h3 class="text-xl font-bold text-slate-900 mb-6">Item Packages</h3>
            <p class="text-slate-600 text-sm mb-4 leading-relaxed">Packages allow you to bundle multiple products into a
                single line item.</p>
            <div class="bg-white p-5 rounded border border-slate-200 shadow-sm">
                <strong class="block text-slate-800 mb-2">Example: Vanity Kit</strong>
                <ul class="list-disc list-inside text-sm text-slate-600 space-y-2">
                    <li>Cabinet (₹15,000)</li>
                    <li>Sink (₹5,000)</li>
                    <li>aucet (₹3,000)</li>
                </ul>
                <div class="mt-3 pt-3 border-t border-slate-100 text-right font-bold text-slate-900">Total: ₹23,000</div>
            </div>
        </div>
        <div>
            <h3 class="text-xl font-bold text-slate-900 mb-6">Categories</h3>
            <p class="text-slate-600 text-sm mb-4 leading-relaxed">Organize your inventory hierarchy for better reporting.
            </p>
            <ul class="space-y-3">
                <li class="flex items-center gap-3 p-3 bg-white rounded border border-slate-200">
                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                    <span class="text-sm font-medium text-slate-700">Flooring</span>
                </li>
                <li class="flex items-center gap-3 p-3 bg-white rounded border border-slate-200">
                    <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                    <span class="text-sm font-medium text-slate-700">Plumbing</span>
                </li>
                <li class="flex items-center gap-3 p-3 bg-white rounded border border-slate-200">
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                    <span class="text-sm font-medium text-slate-700">Labor</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Coupons -->
    <div class="mb-16 border-t border-slate-200 pt-12" id="coupons">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Coupons & Discounts</h2>
        <div class="flex gap-4">
            <div class="flex-1">
                <p class="text-slate-600 text-sm mb-6 max-w-3xl leading-relaxed">Coupons are applied to the estimate
                    subtotal. They can be fixed amount (₹1000 off) or percentage (10% off).</p>
                <div class="bg-yellow-50 p-6 rounded-lg border border-yellow-200 text-sm">
                    <strong class="text-yellow-900 block mb-3">Validation Rules:</strong>
                    <ul class="list-disc list-inside space-y-2 text-yellow-800">
                        <li><strong>Expiration Date:</strong> Codes expire at midnight on this date.</li>
                        <li><strong>Usage Limit:</strong> Maximum times a code can be used globally (e.g., "First 50
                            customers").</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection