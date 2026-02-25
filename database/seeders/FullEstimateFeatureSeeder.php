<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\ApprovalChain;
use App\Models\ApprovalChecklist;
use App\Models\Client;
use App\Models\Estimate;
use App\Models\EstimateAnalytic;
use App\Models\EstimateApproval;
use App\Models\EstimateApprovalChecklistItem;
use App\Models\EstimateComment;
use App\Models\EstimateItem;
use App\Models\EstimateSection;
use App\Models\Follower;
use App\Models\PdfTemplate;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FullEstimateFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Full Feature Estimate Seeding...');

        // 1. Setup Prerequisites (User, Client, Template, Chain, Checklist)
        $user = User::where('role', 'super_admin')->first() ?: User::factory()->create([
            'name' => 'Master Estimator',
            'email' => 'estimator@example.com',
            'role' => 'super_admin'
        ]);

        $client = Client::first() ?: Client::factory()->create([
            'name' => 'High Profile Client',
            'email' => 'client@example.com'
        ]);

        // Ensure we have a PDF template
        $pdfTemplate = PdfTemplate::first() ?: PdfTemplate::create([
            'name' => 'Premium Modern Template',
            'is_active' => true,
            'is_default' => true,
            'primary_color' => '#2563eb',
            'font_family' => 'Inter',
            'html_content' => '<h1>Estimate</h1>', // Basic placeholder
            'css_content' => 'body { font-family: sans-serif; }',
        ]);

        // Ensure we have an approval chain
        $approvalChain = ApprovalChain::first() ?: ApprovalChain::create([
            'name' => 'Standard Corporate Chain',
            'description' => 'Standard approval workflow for large estimates'
        ]);

        if ($approvalChain->steps()->count() === 0) {
            $approvalChain->steps()->create([
                'user_id' => $user->id,
                'order' => 1,
                'role' => 'approver'
            ]);
        }

        // Setup Checklists
        $checklistTasks = ['Verify dimensions', 'Check material availability', 'Confirm delivery timeline'];
        foreach ($checklistTasks as $task) {
            ApprovalChecklist::firstOrCreate(['task' => $task], ['is_required' => true]);
        }
        $checklists = ApprovalChecklist::all();

        // Ensure we have products
        $products = Product::limit(10)->get();
        if ($products->isEmpty()) {
            $this->command->warn('No products found. Creating some dummy products...');
            Product::create([
                'name' => 'Premium Flooring',
                'unit_price' => 150,
                'unit_type' => 'sqft',
                'status' => 'active'
            ]);
            Product::create([
                'name' => 'Luxe Paint',
                'unit_price' => 500,
                'unit_type' => 'ltr',
                'status' => 'active'
            ]);
            $products = Product::all();
        }

        // 2. Create the Master Estimate
        $estimate = Estimate::create([
            'estimate_number' => 'EST-FEATURE-' . date('Ymd') . '-' . Str::random(4),
            'title' => 'The Ultimate Feature Test Project',
            'client_id' => $client->id,
            'estimate_date' => Carbon::now(),
            'expiry_date' => Carbon::now()->addDays(30),
            'currency' => '₹',
            'type' => 'room_based',
            'estimate_status' => Estimate::EST_STATUS_SENT,
            'approval_status' => Estimate::APP_STATUS_APPROVED,
            'client_status' => Estimate::CLT_STATUS_VIEWED,
            'created_by' => $user->id,
            'pdf_template_id' => $pdfTemplate->id,
            'approval_chain_id' => $approvalChain->id,
            'subtotal' => 0,
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'discount_total' => 5000,
            'transportation_charges' => 2500,
            'tax_1' => 18, // GST
            'grand_total' => 0,
            'client_note' => 'This estimate includes all advanced features including sections, item variations, global discounts, and approval history.',
            'admin_note' => 'Internal test for full feature set.',
            'terms' => "+ Standard payment terms: 50% advance, 40% on delivery, 10% post-installation.",
            'version' => 1,
            'is_current_version' => true,
            'last_viewed_at' => Carbon::now()->subHours(2),
            'view_count' => 15,
            'sent_at' => Carbon::now()->subDays(1),
        ]);

        $this->command->info("Estimate created: {$estimate->estimate_number}");

        // 3. Add Sections and Items
        $sectionsData = [
            'Living Area' => ['Flooring', 'Wall', 'Wiring'],
            'Master Bedroom' => ['Paint', 'Ceiling', 'Lights'],
            'Kitchen' => ['Plumbing', 'Cabinets', 'Tiles']
        ];

        $runningSubtotal = 0;
        $orderIndex = 0;

        foreach ($sectionsData as $sectionName => $keywords) {
            $section = EstimateSection::create([
                'estimate_id' => $estimate->id,
                'name' => $sectionName,
                'order_index' => $orderIndex++,
                'subtotal' => 0
            ]);

            $sectionSubtotal = 0;
            foreach ($keywords as $kIndex => $kw) {
                // Find a product that matches keyword or just random
                $product = Product::where('name', 'LIKE', "%$kw%")->first() ?: $products->random();

                $qty = rand(5, 50);
                $unitPrice = $product->unit_price;

                // Demo Formula/Dimensions for some items
                $length = $kIndex == 0 ? 10 : null;
                $width = $kIndex == 0 ? 15 : null;
                $size = ($length && $width) ? ($length * $width) : 1;
                $formula = ($length && $width) ? 'area' : 'fixed';

                $itemTotal = ($qty * $unitPrice * $size);

                EstimateItem::create([
                    'estimate_id' => $estimate->id,
                    'estimate_section_id' => $section->id,
                    'product_id' => $product->id,
                    'name' => $product->name . " ($kw)",
                    'description' => $product->description ?: "High quality $kw implementation.",
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_type' => $product->unit_type ?: 'nos',
                    'total' => $itemTotal,
                    'order_index' => $kIndex,
                    'length' => $length,
                    'width' => $width,
                    'size' => $size,
                    'formula' => $formula,
                    'is_complimentary' => false,
                ]);

                $sectionSubtotal += $itemTotal;
            }

            $section->update(['subtotal' => $sectionSubtotal]);
            $runningSubtotal += $sectionSubtotal;
        }

        // Add complimentary item
        $product = $products->random();
        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'name' => "Bonus: " . $product->name,
            'quantity' => 1,
            'unit_price' => $product->unit_price,
            'original_price' => $product->unit_price,
            'unit_type' => $product->unit_type ?: 'nos',
            'total' => 0,
            'is_complimentary' => true,
            'order_index' => $orderIndex++,
        ]);

        // Update Estimate Totals
        $taxableAmount = $runningSubtotal - $estimate->discount_total;
        if ($taxableAmount < 0)
            $taxableAmount = 0;

        $taxAmount = $taxableAmount * 0.18;
        $grandTotal = $taxableAmount + $taxAmount + $estimate->transportation_charges;

        $estimate->update([
            'subtotal' => $runningSubtotal,
            'total_tax' => $taxAmount,
            'grand_total' => $grandTotal
        ]);

        // 4. Add Metadata (Comments, Approvals, Analytics, Logs)

        // Internal Comments
        EstimateComment::create([
            'estimate_id' => $estimate->id,
            'commentable_type' => Estimate::class,
            'commentable_id' => $estimate->id,
            'user_id' => $user->id,
            'comment' => 'Technical validation complete by engineering team.',
            'type' => 'internal'
        ]);

        // Client Comments with Thread
        $clientComment = EstimateComment::create([
            'estimate_id' => $estimate->id,
            'commentable_type' => Estimate::class,
            'commentable_id' => $estimate->id,
            'client_name' => $client->name,
            'client_email' => $client->email,
            'comment' => 'The living area marble seems expensive. Any alternatives?',
            'type' => 'client'
        ]);

        EstimateComment::create([
            'estimate_id' => $estimate->id,
            'commentable_type' => Estimate::class,
            'commentable_id' => $estimate->id,
            'user_id' => $user->id,
            'comment' => 'We can look at vitrified tiles as an alternative for a 40% cost reduction.',
            'type' => 'client',
            'parent_id' => $clientComment->id
        ]);

        // Formal Approvals
        EstimateApproval::create([
            'estimate_id' => $estimate->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'comments' => 'Budget and scope alignment confirmed.'
        ]);

        // Checklist Status
        foreach ($checklists as $index => $checklist) {
            EstimateApprovalChecklistItem::create([
                'estimate_id' => $estimate->id,
                'approval_checklist_id' => $checklist->id,
                'is_completed' => $index < 2,
                'completed_by' => $index < 2 ? $user->id : null,
                'completed_at' => $index < 2 ? Carbon::now() : null,
            ]);
        }

        // Analytics Data
        $analytics = [
            ['action' => 'view', 'device' => 'Desktop', 'browser' => 'Chrome'],
            ['action' => 'view', 'device' => 'Mobile', 'browser' => 'Safari'],
            ['action' => 'download_pdf', 'device' => 'Desktop', 'browser' => 'Chrome'],
            ['action' => 'comment_added', 'device' => 'Mobile', 'browser' => 'Safari'],
        ];

        foreach ($analytics as $data) {
            EstimateAnalytic::create(array_merge($data, [
                'estimate_id' => $estimate->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0...',
                'platform' => 'macOS',
                'is_unique' => true
            ]));
        }

        // Followers
        Follower::create([
            'user_id' => $user->id,
            'followable_type' => Estimate::class,
            'followable_id' => $estimate->id,
            'permissions' => ['view', 'edit', 'comment']
        ]);

        // Activity Trail
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'created',
            'subject_type' => Estimate::class,
            'subject_id' => $estimate->id,
            'description' => 'Global Master Estimate created for full feature verification.'
        ]);

        $this->command->info("Full Feature Estimate Seeding completed successfully! Estimate ID: {$estimate->id}");
    }
}
