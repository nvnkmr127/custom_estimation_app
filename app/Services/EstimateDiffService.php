<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\EstimateItem;

class EstimateDiffService
{
    /**
     * Calculate difference between two estimate versions.
     * Returns a detailed structured array of changes and summary impact.
     */
    public function calculateDiff(Estimate $old, Estimate $new): array
    {
        $oldGrandTotal = (float) $old->grand_total;
        $newGrandTotal = (float) $new->grand_total;
        $netChange = $newGrandTotal - $oldGrandTotal;
        $percentChange = $oldGrandTotal > 0 ? ($netChange / $oldGrandTotal) * 100 : 0;

        $changes = [
            'summary' => [
                'old_version' => $old->version,
                'new_version' => $new->version,
                'old_grand_total' => $oldGrandTotal,
                'new_grand_total' => $newGrandTotal,
                'net_change' => $netChange,
                'percent_change' => $percentChange,
                'added_count' => 0,
                'modified_count' => 0,
                'removed_count' => 0,
            ],
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
            'subtotal' => 'Subtotal',
            'total_tax' => 'Tax Total',
            'discount' => 'Discount',
            'transportation_charges' => 'Transportation Charges',
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

            $isCurrency = in_array($field, ['grand_total', 'subtotal', 'total_tax', 'discount', 'transportation_charges']);

            if ($isCurrency) {
                if (abs((float) $oldVal - (float) $newVal) > 0.01) {
                    $changes['overview'][] = [
                        'label' => $label,
                        'old' => (float) $oldVal,
                        'new' => (float) $newVal,
                        'is_currency' => true,
                    ];
                }
            } elseif (trim((string) $oldVal) !== trim((string) $newVal)) {
                $changes['overview'][] = [
                    'label' => $label,
                    'old' => $oldVal ?? '—',
                    'new' => $newVal ?? '—',
                    'is_currency' => false,
                ];
            }
        }

        // 2. Compare Items
        $this->compareItems($old, $new, $changes);

        return $changes;
    }

    protected function compareItems(Estimate $old, Estimate $new, array &$changes): void
    {
        // Load relationships
        $old->loadMissing('items.section');
        $new->loadMissing('items.section');

        // Identify Items Key using original_item_id lineage tracking or fallback
        $getKey = function (EstimateItem $item) {
            $lineageId = $item->original_item_id ?? $item->id;

            if ($lineageId) {
                return 'ID:' . $lineageId;
            }

            $sectionName = $item->section ? $item->section->name : 'General';
            return "TEMP:{$sectionName}|" . ($item->product_id ?? 'custom') . "|{$item->name}";
        };

        $oldItems = $old->items->keyBy($getKey);
        $newItems = $new->items->keyBy($getKey);

        $addedCount = 0;
        $modifiedCount = 0;
        $removedCount = 0;

        // 1. ADDED & MODIFIED
        foreach ($newItems as $key => $newItem) {
            $section = $newItem->section ? $newItem->section->name : 'General';

            if (!$oldItems->has($key)) {
                $changes['items']['added'][$section][] = $newItem;
                $addedCount++;
            } else {
                $oldItem = $oldItems->get($key);
                $diffs = $this->getItemDiffs($oldItem, $newItem);
                if (!empty($diffs)) {
                    $changes['items']['modified'][$section][] = [
                        'item' => $newItem,
                        'changes' => $diffs,
                    ];
                    $modifiedCount++;
                }
            }
        }

        // 2. REMOVED
        foreach ($oldItems as $key => $oldItem) {
            if (!$newItems->has($key)) {
                $section = $oldItem->section ? $oldItem->section->name : 'General';
                $changes['items']['removed'][$section][] = $oldItem;
                $removedCount++;
            }
        }

        $changes['summary']['added_count'] = $addedCount;
        $changes['summary']['modified_count'] = $modifiedCount;
        $changes['summary']['removed_count'] = $removedCount;
    }

    protected function getItemDiffs(EstimateItem $old, EstimateItem $new): array
    {
        $diffs = [];
        $fields = [
            'name' => 'Item Name',
            'quantity' => 'Quantity',
            'unit_price' => 'Unit Price',
            'total' => 'Total',
            'size' => 'Size',
            'unit_type' => 'Unit Type',
            'internal_note' => 'Internal Note',
            'description' => 'Description'
        ];

        // Track section movement
        $oldSectionName = $old->section ? $old->section->name : 'General';
        $newSectionName = $new->section ? $new->section->name : 'General';
        if ($oldSectionName !== $newSectionName) {
            $diffs[] = [
                'field' => 'Room / Section',
                'old' => $oldSectionName,
                'new' => $newSectionName,
            ];
        }

        foreach ($fields as $field => $label) {
            $oldVal = $old->$field;
            $newVal = $new->$field;

            if (in_array($field, ['unit_price', 'total', 'quantity'])) {
                if (abs((float) $oldVal - (float) $newVal) > 0.001) {
                    $diffs[] = [
                        'field' => $label,
                        'old' => (float) $oldVal,
                        'new' => (float) $newVal,
                    ];
                }
            } elseif (trim((string) $oldVal) !== trim((string) $newVal)) {
                $diffs[] = [
                    'field' => $label,
                    'old' => $oldVal ?? '—',
                    'new' => $newVal ?? '—',
                ];
            }
        }

        return $diffs;
    }
}
