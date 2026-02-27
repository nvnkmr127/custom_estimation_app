<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\EstimateItem;
use Illuminate\Support\Collection;

class EstimateDiffService
{
    /**
     * Calculate difference between two estimate versions.
     * Returns an array of changes.
     */
    public function calculateDiff(Estimate $old, Estimate $new): array
    {
        $changes = [
            'overview' => [],
            'items' => [
                'added' => [],
                'removed' => [],
                'modified' => [],
            ],
        ];

        // 1. Compare Overview Fields
        $overviewFields = [
            'grand_total' => 'Grand Total',
            'status' => 'Status',
            'client_note' => 'Client Note',
            'admin_note' => 'Internal Note',
            'terms' => 'Terms',
            'expiry_date' => 'Expiry Date',
        ];

        foreach ($overviewFields as $field => $label) {
            $oldVal = $old->$field;
            $newVal = $new->$field;

            // Handle Dates
            if ($oldVal instanceof \Carbon\Carbon)
                $oldVal = $oldVal->format('Y-m-d');
            if ($newVal instanceof \Carbon\Carbon)
                $newVal = $newVal->format('Y-m-d');

            // Numeric check for totals
            if (in_array($field, ['grand_total'])) {
                if (abs((float) $oldVal - (float) $newVal) > 0.01) {
                    $changes['overview'][] = [
                        'label' => $label,
                        'old' => $oldVal,
                        'new' => $newVal,
                        'is_currency' => true,
                    ];
                }
            } elseif ($oldVal != $newVal) {
                $changes['overview'][] = [
                    'label' => $label,
                    'old' => $oldVal,
                    'new' => $newVal,
                    'is_currency' => false,
                ];
            }
        }

        // 2. Compare Items
        $this->compareItems($old, $new, $changes);

        return $changes;
    }

    protected function compareItems(Estimate $old, Estimate $new, array &$changes)
    {
        // Load relationships
        $old->loadMissing('items.section');
        $new->loadMissing('items.section');

        // Identify Items Key
        // Priority 1: original_item_id (Perfect Match)
        // Priority 2: Fallback to [Section|ProductID|Name] for legacy items
        $getKey = function (EstimateItem $item) {
            // An item without an original_item_id is the root item of its lineage.
            // Using (original_item_id ?? id) ensures that V1 (with ID 101) matches V2 (with original_item_id 101).
            $lineageId = $item->original_item_id ?? $item->id;

            if ($lineageId) {
                return 'ID:' . $lineageId;
            }

            // Fallback for unsaved/temporary items if they ever hit this service
            $sectionName = $item->section ? $item->section->name : 'General';
            return "TEMP:{$sectionName}|" . ($item->product_id ?? 'custom') . "|{$item->name}";
        };

        $oldItems = $old->items->keyBy($getKey);
        $newItems = $new->items->keyBy($getKey);

        // Track changes grouped by section
        // Structure: $changes['items']['added']['Section Name'][] = Item

        // 1. ADDED & MODIFIED
        foreach ($newItems as $key => $newItem) {
            $section = $newItem->section ? $newItem->section->name : 'General';

            if (!$oldItems->has($key)) {
                $changes['items']['added'][$section][] = $newItem;
            } else {
                // Check modifications
                $oldItem = $oldItems->get($key);
                $diffs = $this->getItemDiffs($oldItem, $newItem);
                if (!empty($diffs)) {
                    $changes['items']['modified'][$section][] = [
                        'item' => $newItem,
                        'changes' => $diffs,
                    ];
                }
            }
        }

        // 2. REMOVED
        foreach ($oldItems as $key => $oldItem) {
            if (!$newItems->has($key)) {
                $section = $oldItem->section ? $oldItem->section->name : 'General';
                $changes['items']['removed'][$section][] = $oldItem;
            }
        }
    }

    protected function getItemDiffs(EstimateItem $old, EstimateItem $new): array
    {
        $diffs = [];
        $fields = [
            'quantity' => 'Quantity',
            'unit_price' => 'Unit Price',
            'total' => 'Total',
            'internal_note' => 'Internal Note', // Admin only usually, but good to track
            'description' => 'Description'
        ];

        foreach ($fields as $field => $label) {
            $oldVal = $old->$field;
            $newVal = $new->$field;

            // Handle numeric
            if (in_array($field, ['unit_price', 'total', 'quantity'])) {
                if (abs((float) $oldVal - (float) $newVal) > 0.001) {
                    $diffs[] = [
                        'field' => $label,
                        'old' => (float) $oldVal,
                        'new' => (float) $newVal,
                    ];
                }
            }
            // Handle Strings
            elseif (trim((string) $oldVal) !== trim((string) $newVal)) {
                $diffs[] = [
                    'field' => $label,
                    'old' => $oldVal,
                    'new' => $newVal,
                ];
            }
        }

        return $diffs;
    }
}
