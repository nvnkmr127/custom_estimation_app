<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PdfTemplate;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $templates = PdfTemplate::whereIn('name', ['Modern', 'Premium', 'Minimalist'])->get();

        foreach ($templates as $template) {
            $html = $template->html_content;

            // Define the transportation block based on template style
            $transportationBlock = '';

            if ($template->name === 'Modern') {
                $transportationBlock = '
            {IF_transportation_charges}
            <p style="display: table; width: 100%;">
                <span style="display: table-cell; text-align: left;">Transportation</span>
                <span style="display: table-cell; text-align: right; font-weight: 700;">{currency} {transportation_charges}</span>
            </p>
            {END_IF}';
            } elseif ($template->name === 'Premium') {
                $transportationBlock = '
                {IF_transportation_charges}
                <tr>
                    <td>Transportation</td>
                    <td class="text-right">{currency} {transportation_charges}</td>
                </tr>
                {END_IF}';
            } elseif ($template->name === 'Minimalist') {
                // Minimalist puts total in a separate div
                // We'll insert it before total-minimal
                $transportationBlock = '
                {IF_transportation_charges}
                <div style="text-align: center; font-size: 12px; margin-bottom: 10px;">
                    TRANSPORTATION: {currency} {transportation_charges}
                </div>
                {END_IF}';
            }

            // Insert logic
            if ($template->name === 'Modern') {
                // Insert before Grand Total div
                $search = '<div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid var(--primary-color);">';
                if (strpos($html, $search) !== false) {
                    $html = str_replace($search, $transportationBlock . "\n" . $search, $html);
                }
            } elseif ($template->name === 'Premium') {
                // Insert before Grand Total row
                $search = '<tr class="grand-total-row">';
                if (strpos($html, $search) !== false) {
                    $html = str_replace($search, $transportationBlock . "\n" . $search, $html);
                }
            } elseif ($template->name === 'Minimalist') {
                // Insert before total-minimal
                $search = '<div class="total-minimal">';
                if (strpos($html, $search) !== false) {
                    $html = str_replace($search, $transportationBlock . "\n" . $search, $html);
                }
            }

            $template->update(['html_content' => $html]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting string replacements is complex and risky without versioning.
        // We will assume a re-seed is preferred if rollback is needed, or just leave it.
    }
};
