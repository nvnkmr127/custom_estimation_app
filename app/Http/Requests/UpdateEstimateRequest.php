<?php

namespace App\Http\Requests;

use App\Models\Estimate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstimateRequest extends FormRequest
{
    public function authorize()
    {
        $estimate = $this->route('estimate');
        return $this->user()->can('update', $estimate);
    }

    public function rules()
    {
        // Allowed statuses for Update:
        // Generally, we shouldn't change status to 'sent'/'accepted' via update form unless Admin.
        // If current is Draft, can stay Draft.
        // Use 'markAs' for transitions.

        $statusRules = [Estimate::STATUS_DRAFT];

        if ($this->user()->hasRole(['super_admin', 'admin'])) {
            // Admin can do anything
            $statusRules = [
                Estimate::STATUS_DRAFT,
                Estimate::STATUS_SENT,
                Estimate::STATUS_ACCEPTED,
                Estimate::STATUS_DECLINED,
                Estimate::STATUS_EXPIRED,
                Estimate::STATUS_WAITING_APPROVAL,
                Estimate::STATUS_APPROVED,
            ];
        } else {
            // Regular User
            // If currently Sent/Approved/Accepted, editing will Trigger Branching (Service logic).
            // Ideally, we shouldn't even validate status here because Service overrides it to 'draft' on branch.
            // But if NOT branching (editing Draft), we can keep Draft or 'Waiting Approval'.
            $statusRules = [Estimate::STATUS_DRAFT, Estimate::STATUS_WAITING_APPROVAL];
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
            'last_update_timestamp' => 'nullable|date', // For Optimistic Lock

            // Item Validation (Same as Store)
            'items' => 'nullable|array|required_if:type,standard',
            'items.*.id' => 'nullable|integer',
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

            'sections' => 'nullable|array|required_if:type,room_based',
            'sections.*.id' => 'nullable|integer',
            'sections.*.name' => 'required_with:sections|string',
            'sections.*.section_type' => 'nullable|string|in:room,package',
            'sections.*.is_package' => 'boolean',
            'sections.*.items' => 'nullable|array',
            'sections.*.items.*.id' => 'nullable|integer',
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

            // Unit Consistency
            if (($data['type'] ?? '') === 'standard' && !empty($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    if (!empty($item['unit_type_id']) && empty($item['unit_type'])) {
                        $validator->errors()->add("items.{$index}.unit_type", "Unit is required.");
                    }
                }
            }
            if (($data['type'] ?? '') === 'room_based' && !empty($data['sections'])) {
                foreach ($data['sections'] as $sIndex => $section) {
                    if (!empty($section['items'])) {
                        foreach ($section['items'] as $iIndex => $item) {
                            if (!empty($item['unit_type_id']) && empty($item['unit_type'])) {
                                $validator->errors()->add("sections.{$sIndex}.items.{$iIndex}.unit_type", "Unit is required.");
                            }
                        }
                    }
                }
            }
        });
    }
}
