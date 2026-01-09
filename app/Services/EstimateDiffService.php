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
        // Helper to generate a unique key for matching
        $getKey = function (EstimateItem $item) {
            // We use a simplified key: [Section Name] + Product ID + Name
            // This is a heuristic. If there are duplicates, it might overlap, but it's the best without stable IDs across versions.
            $sectionName = $item->section ? $item->section->name : 'General';
            return "{$sectionName}|" . ($item->product_id ?? 'custom') . "|{$item->name}";
        };

        // Load relationships if not loaded
        $old->loadMissing('items.section', 'sections');
        $new->loadMissing('items.section', 'sections');

        $oldItems = $old->items->keyBy($getKey);
        $newItems = $new->items->keyBy($getKey);

        // Check for Added & Modified
        foreach ($newItems as $key => $newItem) {
            if (!$oldItems->has($key)) {
                $changes['items']['added'][] = $newItem;
            } else {
                // Check if contents modified
                $oldItem = $oldItems->get($key);
                $diffs = $this->getItemDiffs($oldItem, $newItem);
                if (!empty($diffs)) {
                    $changes['items']['modified'][] = [
                        'item' => $newItem,
                        'changes' => $diffs,
                    ];
                }
            }
        }

        // Check for Removed
        foreach ($oldItems as $key => $oldItem) {
            if (!$newItems->has($key)) {
                $changes['items']['removed'][] = $oldItem;
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
            'internal_note' => 'Internal Note',
            'description' => 'Description'
        ];

        foreach ($fields as $field => $label) {
            $oldVal = $old->$field;
            $newVal = $new->$field;

            // Handle formatting strictly
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
                    'old' => $oldVal,
                    'new' => $newVal,
                ];
            }
        }

        return $diffs;
    }
}
