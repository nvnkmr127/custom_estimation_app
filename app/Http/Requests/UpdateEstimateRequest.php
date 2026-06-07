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

    protected function prepareForValidation()
    {
        if ($this->wantsJson()) {
            $sectionsInput = $this->input('sections');
            $itemsInput = $this->input('items');

            $type = $this->input('type') ?? (is_array($sectionsInput) && count($sectionsInput) > 0 ? 'room_based' : 'standard');

            $this->merge([
                'type' => $type,
                'status' => $this->input('status') ?? \App\Models\Estimate::EST_STATUS_DRAFT,
                'estimate_date' => $this->input('estimate_date') ?? now()->toDateString(),
                'currency' => $this->input('currency') ?? 'USD',
                'discount_type' => $this->input('discount_type') ?? 'percentage',
                'discount_value' => $this->input('discount_value') ?? 0,
            ]);

            if ($type === 'room_based' && is_array($sectionsInput)) {
                foreach ($sectionsInput as $sIdx => $section) {
                    if (isset($section['items']) && is_array($section['items'])) {
                        foreach ($section['items'] as $iIdx => $item) {
                            if (isset($item['product_id']) && !empty($item['product_id'])) {
                                $product = \App\Models\Product::find($item['product_id']);
                                if ($product) {
                                    if (!isset($item['name']) || empty($item['name'])) {
                                        $sectionsInput[$sIdx]['items'][$iIdx]['name'] = $product->name;
                                    }
                                    if (!isset($item['unit_price'])) {
                                        $sectionsInput[$sIdx]['items'][$iIdx]['unit_price'] = $product->unit_price;
                                    }
                                }
                            }
                            if (isset($item['discount_percentage'])) {
                                $sectionsInput[$sIdx]['items'][$iIdx]['discount_percent'] = $item['discount_percentage'];
                            }
                        }
                    }
                }
                $this->merge(['sections' => $sectionsInput]);
            }

            if ($type === 'standard' && is_array($itemsInput)) {
                foreach ($itemsInput as $iIdx => $item) {
                    if (isset($item['product_id']) && !empty($item['product_id'])) {
                        $product = \App\Models\Product::find($item['product_id']);
                        if ($product) {
                            if (!isset($item['name']) || empty($item['name'])) {
                                $itemsInput[$iIdx]['name'] = $product->name;
                            }
                            if (!isset($item['unit_price'])) {
                                $itemsInput[$iIdx]['unit_price'] = $product->unit_price;
                            }
                        }
                    }
                    if (isset($item['discount_percentage'])) {
                        $itemsInput[$iIdx]['discount_percent'] = $item['discount_percentage'];
                    }
                }
                $this->merge(['items' => $itemsInput]);
            }
        }
    }

    public function rules()
    {
        // Allowed statuses for Update
        $statusRules = [Estimate::EST_STATUS_DRAFT];

        if ($this->user()->hasRole(['super_admin', 'admin', 'estimator_admin'])) {
            $statusRules = [
                Estimate::EST_STATUS_DRAFT,
                Estimate::EST_STATUS_SENT,
                Estimate::EST_STATUS_ACCEPTED,
                Estimate::EST_STATUS_DECLINED,
                Estimate::EST_STATUS_EXPIRED,
                Estimate::EST_STATUS_PENDING_APPROVAL,
                Estimate::EST_STATUS_APPROVED,
            ];
        } else {
            $statusRules = [Estimate::EST_STATUS_DRAFT, Estimate::EST_STATUS_PENDING_APPROVAL];
        }

        return [
            'title' => 'nullable|string|max:255',
            'client_id' => 'required|integer',
            'estimate_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:estimate_date',
            'status' => ['required', Rule::in($statusRules)],
            'currency' => 'required|string|max:10',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'coupon_discount' => 'nullable|numeric|min:0',
            'transportation_charges' => 'nullable|numeric|min:0',
            'client_note' => 'nullable|string',
            'admin_note' => 'nullable|string',
            'terms' => 'nullable|string',
            'pdf_theme' => 'nullable|string',
            'pdf_template_id' => 'nullable|exists:pdf_templates,id',
            'layout_type' => 'nullable|string|in:modern,classic,simple',
            'coupon_code_id' => 'nullable|exists:coupon_codes,id',
            'type' => 'required|in:standard,room_based',
            'last_update_timestamp' => 'nullable|date',

            // Standard Items
            'items' => 'nullable|array|required_if:type,standard',
            'items.*.id' => 'nullable|integer',
            'items.*.product_id' => 'nullable|integer',
            'items.*.name' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0',
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
            'items.*.selected_options' => 'nullable|array',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',

            // Room Based Sections
            'sections' => 'nullable|array|required_if:type,room_based',
            'sections.*.id' => 'nullable|integer',
            'sections.*.name' => 'required_with:sections|string',
            'sections.*.section_type' => 'nullable|string|in:room,package',
            'sections.*.is_package' => 'boolean',
            'sections.*.items' => 'nullable|array',
            'sections.*.items.*.id' => 'nullable|integer',
            'sections.*.items.*.product_id' => 'nullable|integer',
            'sections.*.items.*.name' => 'required|string',
            'sections.*.items.*.unit_price' => 'required|numeric|min:0',
            'sections.*.items.*.quantity' => 'required|numeric|min:0',
            'sections.*.items.*.size' => 'nullable|string',
            'sections.*.items.*.description' => 'nullable|string',
            'sections.*.items.*.internal_note' => 'nullable|string',
            'sections.*.items.*.is_package' => 'boolean',
            'sections.*.items.*.formula' => 'nullable|string',
            'sections.*.items.*.length' => 'nullable|numeric',
            'sections.*.items.*.width' => 'nullable|numeric',
            'sections.*.items.*.height' => 'nullable|numeric',
            'sections.*.items.*.unit_type_id' => 'nullable|exists:unit_types,id',
            'sections.*.items.*.unit_type' => 'required_with:sections.*.items.*.unit_type_id|nullable|string',
            'sections.*.items.*.tax_1' => 'nullable|numeric|min:0',
            'sections.*.items.*.tax_2' => 'nullable|numeric|min:0',
            'sections.*.items.*.options' => 'nullable|array',
            'sections.*.items.*.options.*.name' => 'nullable|string',
            'sections.*.items.*.options.*.value' => 'nullable|string',
            'sections.*.items.*.options.*.price_adjustment' => 'nullable|numeric',
            'sections.*.items.*.selected_options' => 'nullable|array',
            'sections.*.items.*.discount_percent' => 'nullable|numeric|min:0|max:100',

            'tax_1' => 'nullable|numeric|min:0',
            'tax_2' => 'nullable|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            // Unit/Unit Type Consistency
            if (($data['type'] ?? '') === 'standard' && !empty($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    if (!empty($item['unit_type_id']) && empty($item['unit_type'])) {
                        $validator->errors()->add("items.{$index}.unit_type", "A specific unit (e.g. sqft) is required when a Unit Type is selected.");
                    }
                }
            }
            if (($data['type'] ?? '') === 'room_based' && !empty($data['sections'])) {
                foreach ($data['sections'] as $sIndex => $section) {
                    if (!empty($section['items'])) {
                        foreach ($section['items'] as $iIndex => $item) {
                            if (!empty($item['unit_type_id']) && empty($item['unit_type'])) {
                                $validator->errors()->add("sections.{$sIndex}.items.{$iIndex}.unit_type", "A specific unit (e.g. sqft) is required for '{$item['name']}' in '{$section['name']}'.");
                            }
                        }
                    }
                }
            }
        });
    }
}
