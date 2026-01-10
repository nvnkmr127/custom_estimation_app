<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NurtureRule;
use App\Models\Setting;
use Illuminate\Http\Request;

class NurtureSettingsController extends Controller
{
    public function index()
    {
        $rules = NurtureRule::all();
        $hotLeadThreshold = Setting::getCached('nurture_hot_lead_threshold', 3); // views per hour
        $rescueDiscountLimit = Setting::getCached('nurture_rescue_discount_limit', 10); // max percentage
        $isNurtureActive = Setting::getCached('nurture_system_active', true);

        // Analytics
        $totalNurtured = \App\Models\Estimate::whereNotNull('last_nurtured_at')->count();
        $nurturedWins = \App\Models\Estimate::whereNotNull('last_nurtured_at')->where('status', 'accepted')->count();
        $generatedRevenue = \App\Models\Estimate::whereNotNull('last_nurtured_at')->where('status', 'accepted')->sum('grand_total');
        $conversionRate = $totalNurtured > 0 ? round(($nurturedWins / $totalNurtured) * 100, 1) : 0;

        $analytics = compact('totalNurtured', 'nurturedWins', 'generatedRevenue', 'conversionRate');

        return view('settings.nurture', compact('rules', 'hotLeadThreshold', 'rescueDiscountLimit', 'isNurtureActive', 'analytics'));
    }

    public function storeRule(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_type' => 'required|in:time_based,engagement_based,expiry_based',
            'trigger_days' => 'required|integer',
            'action_type' => 'required|in:email,alert_staff,auto_discount',
            'condition_logic' => 'nullable|array',
            'action_data' => 'nullable|array',
        ]);

        NurtureRule::create($validated);

        return redirect()->back()->with('success', 'Rule created successfully.');
    }

    public function updateRule(Request $request, NurtureRule $rule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_type' => 'required|in:time_based,engagement_based,expiry_based',
            'trigger_days' => 'required|integer',
            'action_type' => 'required|in:email,alert_staff,auto_discount',
            'condition_logic' => 'nullable|array',
            'action_data' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $rule->update($validated);

        return redirect()->back()->with('success', 'Rule updated successfully.');
    }

    public function destroyRule(NurtureRule $rule)
    {
        $rule->delete();
        return redirect()->back()->with('success', 'Rule deleted successfully.');
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'nurture_hot_lead_threshold' => 'required|integer|min:1',
            'nurture_rescue_discount_limit' => 'required|integer|min:0|max:100',
            'nurture_system_active' => 'boolean',
        ]);

        Setting::updateOrCreate(['key' => 'nurture_hot_lead_threshold'], ['value' => $request->nurture_hot_lead_threshold]);
        Setting::updateOrCreate(['key' => 'nurture_rescue_discount_limit'], ['value' => $request->nurture_rescue_discount_limit]);
        Setting::updateOrCreate(['key' => 'nurture_system_active'], ['value' => $request->has('nurture_system_active')]);

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
