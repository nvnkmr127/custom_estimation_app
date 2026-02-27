<?php

namespace App\Services\Pdf;

class VariableRegistry
{
    /**
     * Get the complete definition of available variables.
     * This acts as the Single Source of Truth for documentation and validation.
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            // --- Estimate Details ---
            'estimate_number' => [
                'category' => 'Estimate Details',
                'description' => 'The unique estimate number.',
                'example' => 'EST-001'
            ],
            'estimate_title' => [
                'category' => 'Estimate Details',
                'description' => 'Title or project name of the estimate.',
                'example' => 'Kitchen Renovation'
            ],
            'estimate_date' => [
                'category' => 'Estimate Details',
                'description' => 'Date the estimate was created.',
                'example' => 'Feb 14, 2024'
            ],
            'expiry_date' => [
                'category' => 'Estimate Details',
                'description' => 'Expiration date of the estimate.',
                'example' => 'Mar 14, 2024'
            ],
            'status' => [
                'category' => 'Estimate Details',
                'description' => 'Current status (Draft, Sent, Accepted, etc).',
                'example' => 'Sent'
            ],
            'currency' => [
                'category' => 'Estimate Details',
                'description' => 'Currency symbol/code.',
                'example' => '$'
            ],

            // --- Financials ---
            'subtotal' => [
                'category' => 'Financials',
                'description' => 'Estimate subtotal (formatted).',
                'example' => '1,000.00'
            ],
            'discount_total' => [
                'category' => 'Financials',
                'description' => 'Total discount amount (formatted).',
                'example' => '50.00'
            ],
            'tax_total' => [
                'category' => 'Financials',
                'description' => 'Total tax amount (formatted).',
                'example' => '95.00'
            ],
            'grand_total' => [
                'category' => 'Financials',
                'description' => 'Final total amount (formatted).',
                'example' => '1,045.00'
            ],
            'transportation_charges' => [
                'category' => 'Financials',
                'description' => 'Transportation or shipping charges (formatted).',
                'example' => '25.00'
            ],
            'dynamic_totals' => [
                'category' => 'Financials',
                'description' => 'Complete, formatted totals table (Recommended over manual construction).',
                'example' => '(HTML Table)'
            ],

            // --- Client Info ---
            'client_name' => [
                'category' => 'Client Info',
                'description' => 'Name of the client.',
                'example' => 'John Doe'
            ],
            'client_email' => [
                'category' => 'Client Info',
                'description' => 'Client email address.',
                'example' => 'john@example.com'
            ],
            'client_phone' => [
                'category' => 'Client Info',
                'description' => 'Client phone number.',
                'example' => '+1 555 123 4567'
            ],
            'client_address' => [
                'category' => 'Client Info',
                'description' => 'Client physical address.',
                'example' => '123 Main St, Springfield'
            ],
            'client_id' => [
                'category' => 'Client Info',
                'description' => 'System ID for the client.',
                'example' => '42'
            ],

            // --- Company Info ---
            'company_name' => [
                'category' => 'Company Info',
                'description' => 'Your company legal name.',
                'example' => 'Acme Corp'
            ],
            'company_email' => [
                'category' => 'Company Info',
                'description' => 'Your company email.',
                'example' => 'support@acme.com'
            ],
            'company_phone' => [
                'category' => 'Company Info',
                'description' => 'Your company phone.',
                'example' => '+1 800 555 0199'
            ],
            'company_address' => [
                'category' => 'Company Info',
                'description' => 'Your company street address.',
                'example' => '99 Innovation Dr'
            ],
            'company_city' => [
                'category' => 'Company Info',
                'description' => 'Your company city.',
                'example' => 'Tech City'
            ],
            'company_logo' => [
                'category' => 'Company Info',
                'description' => 'Company logo as an <img> tag.',
                'example' => '<img src="..." />'
            ],
            'estimator_name' => [
                'category' => 'Company Info',
                'description' => 'Name of the person who created the estimate.',
                'example' => 'Alice Agent'
            ],
            'estimator_email' => [
                'category' => 'Company Info',
                'description' => 'Email of the estimator.',
                'example' => 'alice@acme.com'
            ],

            // --- Notes & Terms ---
            'client_note' => [
                'category' => 'Notes & Terms',
                'description' => 'Notes visible to client (HTML safe).',
                'example' => 'Please review carefully.'
            ],
            'terms' => [
                'category' => 'Notes & Terms',
                'description' => 'Terms and conditions text (HTML safe).',
                'example' => 'Standard terms apply.'
            ],
            'admin_note' => [
                'category' => 'Notes & Terms',
                'description' => 'Internal admin notes.',
                'example' => 'Internal use only.'
            ],
            'approval_stamp' => [
                'category' => 'Media & Seals',
                'description' => 'Circular "APPROVED" ink stamp (Only visible if accepted).',
                'example' => '(Circular SVG Stamp)'
            ],
            'signature_image' => [
                'category' => 'Media & Seals',
                'description' => 'Client digital signature preview with timestamp.',
                'example' => '(Signature Image + Metadata)'
            ],

            // --- Logic Flags (Booleans) ---
            'has_discount' => [
                'category' => 'Logic Flags',
                'description' => '1 if discount exists, 0 otherwise. Use with {IF_has_discount}.',
                'example' => '1'
            ],
            'has_tax' => [
                'category' => 'Logic Flags',
                'description' => '1 if tax exists, 0 otherwise.',
                'example' => '1'
            ],
            'has_transportation' => [
                'category' => 'Logic Flags',
                'description' => '1 if transportation charges exist, 0 otherwise.',
                'example' => '0'
            ],
            'has_client_note' => [
                'category' => 'Logic Flags',
                'description' => '1 if client note is present.',
                'example' => '1'
            ],
            'has_terms' => [
                'category' => 'Logic Flags',
                'description' => '1 if terms are present.',
                'example' => '1'
            ],
            'has_items' => [
                'category' => 'Logic Flags',
                'description' => '1 if the estimate has items.',
                'example' => '1'
            ],
            'room_based' => [
                'category' => 'Logic Flags',
                'description' => '1 if estimate is Room/Section based.',
                'example' => '1'
            ],

            // --- Loops (Contextual) ---
            'LOOP_ITEMS' => [
                'category' => 'Loops',
                'description' => 'Start of items loop block.',
                'example' => '{LOOP_ITEMS}...{END_LOOP}'
            ],
            'END_LOOP' => [
                'category' => 'Loops',
                'description' => 'End of items loop block.',
                'example' => ''
            ],
            'LOOP_SECTIONS' => [
                'category' => 'Loops',
                'description' => 'Start of section loop (for room-based estimates).',
                'example' => '{LOOP_SECTIONS}...{END_LOOP_SECTIONS}'
            ],
            'END_LOOP_SECTIONS' => [
                'category' => 'Loops',
                'description' => 'End of section loop.',
                'example' => ''
            ],

            // --- Item Context Variables (Only valid inside LOOP_ITEMS) ---
            'item_name' => [
                'category' => 'Item Context',
                'description' => 'Name of the line item.',
                'example' => 'Labor'
            ],
            'item_description' => [
                'category' => 'Item Context',
                'description' => 'Description of the item.',
                'example' => 'Installation services'
            ],
            'item_options' => [
                'category' => 'Item Context',
                'description' => 'Formatted variants/options string.',
                'example' => 'Color: Red | Size: L'
            ],
            'item_image' => [
                'category' => 'Item Context',
                'description' => 'Image of the item (if available).',
                'example' => '<img ... />'
            ],
            'item_size' => [
                'category' => 'Item Context',
                'description' => 'Size of the item.',
                'example' => 'Large'
            ],
            'item_unit_configuration' => [
                'category' => 'Item Context',
                'description' => 'Formula and dimensions.',
                'example' => 'Area (L: 10 x W: 5)'
            ],
            'item_length' => [
                'category' => 'Item Context',
                'description' => 'Length of the item.',
                'example' => '10'
            ],
            'item_width' => [
                'category' => 'Item Context',
                'description' => 'Width of the item.',
                'example' => '5'
            ],
            'item_height' => [
                'category' => 'Item Context',
                'description' => 'Height of the item.',
                'example' => '2'
            ],
            'item_quantity' => [
                'category' => 'Item Context',
                'description' => 'Quantity.',
                'example' => '10'
            ],
            'item_unit' => [
                'category' => 'Item Context',
                'description' => 'Unit type short code.',
                'example' => 'hrs'
            ],
            'item_unit_full' => [
                'category' => 'Item Context',
                'description' => 'Full unit name.',
                'example' => 'Hours'
            ],
            'item_price' => [
                'category' => 'Item Context',
                'description' => 'Unit price.',
                'example' => '50.00'
            ],
            'item_subtotal' => [
                'category' => 'Item Context',
                'description' => 'Price * Quantity (before tax).',
                'example' => '500.00'
            ],
            'item_tax_1_percent' => [
                'category' => 'Item Context',
                'description' => 'Tax 1 Rate.',
                'example' => '10'
            ],
            'item_tax_2_percent' => [
                'category' => 'Item Context',
                'description' => 'Tax 2 Rate.',
                'example' => '5'
            ],
            'item_tax_percent' => [
                'category' => 'Item Context',
                'description' => 'Total tax rate for item.',
                'example' => '15'
            ],
            'item_total' => [
                'category' => 'Item Context',
                'description' => 'Total line cost including tax.',
                'example' => '575.00'
            ],
            'item_comments' => [
                'category' => 'Item Context',
                'description' => 'Formatted HTML block of item comments.',
                'example' => '<div class="comments">...</div>'
            ],

            // --- Section Context Variables (Only valid inside LOOP_SECTIONS) ---
            'section_name' => [
                'category' => 'Section Context',
                'description' => 'Name of the section/room.',
                'example' => 'Living Room'
            ],
            'section_subtotal' => [
                'category' => 'Section Context',
                'description' => 'Subtotal for the section.',
                'example' => '4,500.00'
            ],

            // --- Charts ---
            'CHART_ROOMS' => [
                'category' => 'Logic Blocks',
                'description' => 'Image URL for room distribution chart (Room-based only).',
                'example' => 'https://quickchart.io/...'
            ],
            'CHART_SECTIONS_PIE' => [
                'category' => 'Logic Blocks',
                'description' => 'Pie chart of section subtotals.',
                'example' => 'https://quickchart.io/...'
            ],
            'CHART_SECTIONS_BAR' => [
                'category' => 'Logic Blocks',
                'description' => 'Bar chart of section subtotals.',
                'example' => 'https://quickchart.io/...'
            ],
            'CHART_ITEMS_PIE' => [
                'category' => 'Logic Blocks',
                'description' => 'Pie chart of top items by cost.',
                'example' => 'https://quickchart.io/...'
            ],
            'CHART_ITEMS_BAR' => [
                'category' => 'Logic Blocks',
                'description' => 'Bar chart of top items by cost.',
                'example' => 'https://quickchart.io/...'
            ],
        ];
    }
}
