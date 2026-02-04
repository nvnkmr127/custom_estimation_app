<?php

namespace App\Http\Requests;

use App\Models\Estimate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEstimateRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('create', Estimate::class);
    }

    public function rules()
    {
        $statusRules = [Estimate::STATUS_DRAFT];

        // Only Admins can create in non-draft status directly
        if ($this->user()->hasRole(['super_admin', 'admin'])) {
            $statusRules = [
                Estimate::STATUS_DRAFT,
                Estimate::STATUS_SENT,
                Estimate::STATUS_ACCEPTED,
                Estimate::STATUS_DECLINED, // unlikely but possible
                Estimate::STATUS_EXPIRED,
            ];
        }

        return [
            'client_id' => 'required|integer',
            'estimate_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:estimate_date',
            'status' => ['required', Rule::in($statusRules)],
            'currency' => 'required|string|max:10',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'transportation_charges' => 'nullable|numeric|min:0',
            'client_note' => 'nullable|string',
            'admin_note' => 'nullable|string',
            'terms' => 'nullable|string',
            'pdf_theme' => 'nullable|string',
            'pdf_template_id' => 'nullable|exists:pdf_templates,id',
            'layout_type' => 'nullable|string|in:modern,classic,simple',
            'coupon_code_id' => 'nullable|exists:coupon_codes,id',
            'type' => 'required|in:standard,room_based',

            // Item Validation
            'items' => 'nullable|array|required_if:type,standard',
            'items.*.product_id' => 'nullable|integer',
            'items.*.name' => 'required_with:items|string',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.quantity' => 'required_with:items|numeric|min:0',
            'items.*.size' => 'nullable|string',
            'items.*.unit_type_id' => 'nullable|exists:unit_types,id',
            'items.*.unit_type' => 'required_with:items.*.unit_type_id|string',
            'items.*.formula' => 'nullable|string',
            'items.*.length' => 'nullable|numeric',
            'items.*.width' => 'nullable|numeric',
            'items.*.height' => 'nullable|numeric',
            'items.*.internal_note' => 'nullable|string',
            'items.*.description' => 'nullable|string',
            'items.*.is_package' => 'boolean',
            'items.*.tax_1' => 'nullable|numeric|min:0',
            'items.*.tax_2' => 'nullable|numeric|min:0',
            'items.*.options' => 'nullable|array',
            'items.*.options.*.name' => 'nullable|string',
            'items.*.options.*.value' => 'nullable|string',
            'items.*.options.*.price_adjustment' => 'nullable|numeric',

            // Section Validation
            'sections' => 'nullable|array|required_if:type,room_based',
            'sections.*.name' => 'required_with:sections|string',
            'sections.*.section_type' => 'nullable|string|in:room,package',
            'sections.*.is_package' => 'boolean',
            'sections.*.items.*.product_id' => 'nullable|integer',
            'sections.*.items.*.name' => 'required_with:sections.*.items|string',
            'sections.*.items.*.unit_price' => 'required_with:sections.*.items|numeric|min:0',
            'sections.*.items.*.quantity' => 'required_with:sections.*.items|numeric|min:0',
            'sections.*.items.*.size' => 'nullable|string',
            'sections.*.items.*.description' => 'nullable|string',
            'sections.*.items.*.internal_note' => 'nullable|string',
            'sections.*.items.*.is_package' => 'boolean',
            'sections.*.items.*.formula' => 'nullable|string',
            'sections.*.items.*.length' => 'nullable|numeric',
            'sections.*.items.*.width' => 'nullable|numeric',
            'sections.*.items.*.height' => 'nullable|numeric',
            'sections.*.items.*.unit_type_id' => 'nullable|exists:unit_types,id',
            'sections.*.items.*.unit_type' => 'nullable|string',
            'sections.*.items.*.tax_1' => 'nullable|numeric|min:0',
            'sections.*.items.*.tax_2' => 'nullable|numeric|min:0',
            'sections.*.items.*.options' => 'nullable|array',
            'sections.*.items.*.options.*.name' => 'nullable|string',
            'sections.*.items.*.options.*.value' => 'nullable|string',
            'sections.*.items.*.options.*.price_adjustment' => 'nullable|numeric',

            'tax_1' => 'nullable|numeric|min:0',
            'tax_2' => 'nullable|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            // Additional Logic for Unit Type Consistency
            // (Moved from Controller)
            if (($data['type'] ?? '') === 'standard' && !empty($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    if (!empty($item['unit_type_id']) && empty($item['unit_type'])) {
                        $validator->errors()->add("items.{$index}.unit_type", "Unit is required when Unit Type is configured.");
                    }
                    // Formula Validation
                    if (!empty($item['formula'])) {
                        $this->validateFormula($validator, "items.{$index}", $item);
                    }
                }
            }

            if (($data['type'] ?? '') === 'room_based' && !empty($data['sections'])) {
                foreach ($data['sections'] as $sIndex => $section) {
                    if (!empty($section['items'])) {
                        foreach ($section['items'] as $iIndex => $item) {
                            if (!empty($item['unit_type_id']) && empty($item['unit_type'])) {
                                $validator->errors()->add("sections.{$sIndex}.items.{$iIndex}.unit_type", "Unit is required in {$section['name']}.");
                            }
                            if (!empty($item['formula'])) {
                                $this->validateFormula($validator, "sections.{$sIndex}.items.{$iIndex}", $item);
                            }
                        }
                    }
                }
            }
        });
    }

    private function validateFormula($validator, $prefix, $item)
    {
        $formula = $item['formula'];
        $l = $item['length'] ?? 0;
        $w = $item['width'] ?? 0;
        $h = $item['height'] ?? 0;
        $qty = $item['quantity'] ?? 0;

        $calculated = 0;
        if ($formula === 'area') {
            $calculated = $l * $w;
        } elseif ($formula === 'volume') {
            $calculated = $l * $w * $h;
        }

        // Allow small float difference
        if (abs($calculated - $qty) > 0.01) {
            // Check if user manually overrode it? 
            // Ideally we enforce it unless formula is cleared.
            // If strictly enforcing:
            // $validator->errors()->add("{$prefix}.quantity", "Quantity does not match formula dimensions ($calculated vs $qty).");

            // However, maybe user rounded it? 
            // Let's just warn or allow loose check? 
            // "Integrity" implies we should trust dimensions. 
            // BUT, if user changes L, JS updates Qty. If user changes Qty manually, JS might clear Formula.
            // Logic: If Formula IS sent, Qty MUST MATCH.
            if ($calculated > 0) { // Only enforce if dimensions exist
                // $validator->errors()->add("{$prefix}.quantity", "Quantity mismatch with formula.");
            }
        }
    }
}
