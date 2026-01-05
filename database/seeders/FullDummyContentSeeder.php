<?php

namespace Database\Seeders;

use App\Models\ApprovalChain;
use App\Models\Client;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\EstimateSection;
use App\Models\ItemPackage;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\RoomTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FullDummyContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Full Dummy Content Seeding...');

        // 1. Create Categories
        $categories = [
            'Flooring' => ['Tiles', 'Marble', 'Wooden Flooring', 'Granite'],
            'Electrical' => ['Wiring', 'Switches', 'Lights', 'Fans'],
            'Plumbing' => ['Pipes', 'Faucets', 'Sanitary Ware', 'Tanks'],
            'Paint' => ['Interior Paint', 'Exterior Paint', 'Primer', 'Putty'],
            'Woodwork' => ['Plywood', 'Laminates', 'Veneer', 'Hardware'],
            'False Ceiling' => ['Gypsum Board', 'POP', 'Channel', 'LED Strips'],
            'Decor' => ['Wall Art', 'Rugs', 'Curtains', 'Vases'],
            'Smart Home' => ['Smart Bulbs', 'Smart Plugs', 'Cameras', 'Sensors'],
        ];

        $categoryIds = [];
        foreach ($categories as $main => $subs) {
            $cat = ProductCategory::firstOrCreate(
                ['name' => $main],
                ['description' => "$main materials and supplies"]
            );
            $categoryIds[$main] = $cat->id;
        }

        $this->command->info('Categories created.');

        // 2. Create Products with Options
        $products = [
            [
                'name' => 'Italian Marble Slab',
                'cat' => 'Flooring',
                'price' => 450,
                'unit' => 'sqft',
                'options' => ['Finish' => ['Polished' => 0, 'Honed' => 20, 'Antiqued' => 50]]
            ],
            [
                'name' => 'Vitrified Tiles 2x2',
                'cat' => 'Flooring',
                'price' => 65,
                'unit' => 'sqft',
                'options' => ['Color' => ['White' => 0, 'Beige' => 0, 'Grey' => 10], 'Finish' => ['Glossy' => 0, 'Matt' => 0]]
            ],
            ['name' => 'Teak Wood Plank', 'cat' => 'Woodwork', 'price' => 2500, 'unit' => 'cft'],
            ['name' => 'Greenply 19mm Plywood', 'cat' => 'Woodwork', 'price' => 110, 'unit' => 'sqft'],
            [
                'name' => 'Asian Paints Royale Luxury',
                'cat' => 'Paint',
                'price' => 550,
                'unit' => 'ltr',
                'options' => ['Finish' => ['Matte' => 0, 'Satin' => 50, 'Gloss' => 100]]
            ],
            ['name' => 'Birla White Putty', 'cat' => 'Paint', 'price' => 45, 'unit' => 'kg'],
            ['name' => 'Havells Modular Switch', 'cat' => 'Electrical', 'price' => 180, 'unit' => 'nos'],
            ['name' => 'Finolex 2.5mm Wire', 'cat' => 'Electrical', 'price' => 2400, 'unit' => 'bundle'],
            ['name' => 'Jaguar Wall Mixer', 'cat' => 'Plumbing', 'price' => 4500, 'unit' => 'nos'],
            ['name' => 'PVC Pipe 4 inch', 'cat' => 'Plumbing', 'price' => 350, 'unit' => 'length'],
            ['name' => 'Saint Gobain Gypsum Board', 'cat' => 'False Ceiling', 'price' => 45, 'unit' => 'sqft'],
            // New Products
            ['name' => 'Philips Hue Smart Bulb', 'cat' => 'Smart Home', 'price' => 2500, 'unit' => 'nos'],
            ['name' => 'Google Nest Mini', 'cat' => 'Smart Home', 'price' => 4500, 'unit' => 'nos'],
            [
                'name' => 'Abstract Wall Art Canvas',
                'cat' => 'Decor',
                'price' => 1500,
                'unit' => 'nos',
                'options' => ['Size' => ['Small' => 0, 'Medium' => 500, 'Large' => 1200]]
            ],
            [
                'name' => 'Persian Style Rug',
                'cat' => 'Decor',
                'price' => 8500,
                'unit' => 'nos',
                'options' => ['Size' => ['4x6' => 0, '6x9' => 3500], 'Color' => ['Red' => 0, 'Blue' => 0]]
            ],
            [
                'name' => 'Blackout Curtains',
                'cat' => 'Decor',
                'price' => 1200,
                'unit' => 'pair',
                'options' => ['Length' => ['7ft' => 0, '9ft' => 300]]
            ],
        ];

        $productModels = [];
        foreach ($products as $p) {
            $product = Product::updateOrCreate(
                ['name' => $p['name']],
                [
                    'category_id' => $categoryIds[$p['cat']],
                    'sku' => strtoupper(substr($p['cat'], 0, 3)) . '-' . rand(1000, 9999),
                    'description' => 'Premium quality ' . strtolower($p['name']) . ' for modern homes.',
                    'unit_price' => $p['price'],
                    'unit_type' => $p['unit'],
                    'status' => 'active',
                ]
            );
            $productModels[] = $product;

            // Add Options if defined
            if (isset($p['options'])) {
                foreach ($p['options'] as $optName => $values) {
                    $option = ProductOption::firstOrCreate(
                        ['product_id' => $product->id, 'name' => $optName]
                    );
                    foreach ($values as $valName => $priceAdj) {
                        ProductOptionValue::firstOrCreate(
                            ['product_option_id' => $option->id, 'value' => $valName],
                            ['price_adjustment' => $priceAdj]
                        );
                    }
                }
            }

            // Add Images
            $text = urlencode($p['name']);
            \App\Models\ProductImage::firstOrCreate(
                ['product_id' => $product->id],
                [
                    'image_path' => "https://placehold.co/600x400/e2e8f0/475569?text={$text}",
                    'display_order' => 0,
                ]
            );
        }

        $this->command->info('Products created.');

        // 3. Create Clients
        if (Client::count() < 10) {
            Client::factory()->count(10)->create();
        }
        $clients = Client::all();
        $this->command->info('Clients created.');

        // 4. Create Room Templates
        $templates = [
            [
                'name' => 'Living Room (Premium)',
                'description' => 'Complete living room interior setup',
                'items' => [
                    ['name' => 'False Ceiling', 'quantity' => 250, 'unit_price' => 120, 'unit_type' => 'sqft'],
                    ['name' => 'Wall Painting (Royale)', 'quantity' => 600, 'unit_price' => 45, 'unit_type' => 'sqft'],
                    ['name' => 'Flooring (Italian Marble)', 'quantity' => 250, 'unit_price' => 650, 'unit_type' => 'sqft'],
                    ['name' => 'Electrical Points', 'quantity' => 12, 'unit_price' => 850, 'unit_type' => 'point'],
                    ['name' => 'Persian Style Rug', 'quantity' => 1, 'unit_price' => 8500, 'unit_type' => 'nos'],
                    ['name' => 'Abstract Wall Art Canvas', 'quantity' => 2, 'unit_price' => 1500, 'unit_type' => 'nos'],
                ]
            ],
            [
                'name' => 'Home Office Setup',
                'description' => 'Modern productive workspace',
                'items' => [
                    ['name' => 'Teak Wood Plank', 'quantity' => 15, 'unit_price' => 2500, 'unit_type' => 'cft'],
                    ['name' => 'Philips Hue Smart Bulb', 'quantity' => 2, 'unit_price' => 2500, 'unit_type' => 'nos'],
                    ['name' => 'Google Nest Mini', 'quantity' => 1, 'unit_price' => 4500, 'unit_type' => 'nos'],
                    ['name' => 'Havells Modular Switch', 'quantity' => 6, 'unit_price' => 180, 'unit_type' => 'nos'],
                    ['name' => 'Blackout Curtains', 'quantity' => 2, 'unit_price' => 1200, 'unit_type' => 'pair'],
                ]
            ],
            [
                'name' => 'Kids Bedroom',
                'description' => 'Fun and safe room for children',
                'items' => [
                    ['name' => 'Wall Painting (Royale)', 'quantity' => 350, 'unit_price' => 45, 'unit_type' => 'sqft'],
                    ['name' => 'False Ceiling', 'quantity' => 120, 'unit_price' => 120, 'unit_type' => 'sqft'],
                    ['name' => 'Vitrified Tiles 2x2', 'quantity' => 120, 'unit_price' => 65, 'unit_type' => 'sqft'],
                    ['name' => 'Smart Bulbs', 'quantity' => 2, 'unit_price' => 800, 'unit_type' => 'nos'], // Generic ref
                ]
            ],
            // ... (keep usage of simple array for brevity in seeding, assuming RoomTemplate creates items via json or relation)
        ];

        // Ensure templates logic doesn't crash if re-run
        foreach ($templates as $t) {
            RoomTemplate::updateOrCreate(
                ['name' => $t['name']],
                ['description' => $t['description'], 'items' => $t['items']]
            );
        }
        $this->command->info('Room Templates created.');

        // 5. Create Packages
        ItemPackage::updateOrCreate(
            ['name' => 'Quick Electrical Fix'],
            [
                'description' => 'Basic electrical overhaul for a room',
                'total_price' => 15000,
                'items' => [
                    ['item_name' => 'Modular Switches', 'quantity' => 10, 'unit_type' => 'nos', 'unit_price' => 250],
                    ['item_name' => 'LED Panel Lights', 'quantity' => 6, 'unit_type' => 'nos', 'unit_price' => 650],
                    ['item_name' => 'Ceiling Fan', 'quantity' => 1, 'unit_type' => 'nos', 'unit_price' => 3500],
                    ['item_name' => 'Labour Charges', 'quantity' => 1, 'unit_type' => 'day', 'unit_price' => 1500],
                ]
            ]
        );

        $this->command->info('Packages created.');

        // 6. Create Estimates with Approval Chains
        $user = User::where('role', 'estimator')->first() ?? User::factory()->create(['role' => 'estimator']);
        $approvalChain = ApprovalChain::first();

        // Standard Estimate - Draft
        $est1 = Estimate::updateOrCreate(
            ['title' => 'Apartment Interior Renovation'],
            [
                'estimate_number' => 'EST-' . date('Y') . '-001',
                'client_id' => $clients[0]->id,
                'estimate_date' => Carbon::now(),
                'expiry_date' => Carbon::now()->addDays(30),
                'currency' => '₹',
                'status' => 'draft',
                'type' => 'standard',
                'subtotal' => 0,
                'created_by' => $user->id,
                'pdf_theme' => 'modern',
                'approval_chain_id' => $approvalChain ? $approvalChain->id : null,
                'approval_status' => 'draft',
            ]
        );
        $this->seedEstimateItems($est1, $productModels);

        // Room-Based Estimate - Sent
        $est2 = Estimate::updateOrCreate(
            ['title' => 'Villa Full Furnishing'],
            [
                'estimate_number' => 'EST-' . date('Y') . '-002',
                'client_id' => $clients[1]->id,
                'estimate_date' => Carbon::now(),
                'expiry_date' => Carbon::now()->addDays(15),
                'currency' => '₹',
                'status' => 'sent',
                'type' => 'room_based',
                'subtotal' => 0,
                'created_by' => $user->id,
                'pdf_theme' => 'classic',
                'approval_chain_id' => $approvalChain ? $approvalChain->id : null,
                'approval_status' => 'approved', // Pretend it was approved
            ]
        );
        $this->seedRoomBasedItems($est2, $productModels);

        $this->command->info('Estimates created. Seeding completed successfully!');
    }

    private function seedEstimateItems($estimate, $products)
    {
        // Don't duplicate items if they exist
        if ($estimate->items()->count() > 0)
            return;

        $subtotal = 0;
        foreach ($products as $key => $p) {
            if ($key > 3)
                break;
            $qty = rand(10, 50);
            $total = $qty * $p->unit_price;
            EstimateItem::create([
                'estimate_id' => $estimate->id,
                'name' => $p->name,
                'description' => $p->description,
                'quantity' => $qty,
                'unit_price' => $p->unit_price,
                'unit_type' => $p->unit_type,
                'total' => $total,
                'tax_1' => 18,
                'tax_2' => 0,
            ]);
            $subtotal += $total;
        }
        $estimate->update(['subtotal' => $subtotal, 'grand_total' => $subtotal * 1.18]);
    }

    private function seedRoomBasedItems($estimate, $products)
    {
        if ($estimate->sections()->count() > 0)
            return;

        $estTotal = 0;
        $rooms = ['Living Room', 'Guest Bedroom'];
        foreach ($rooms as $index => $roomName) {
            $section = EstimateSection::create([
                'estimate_id' => $estimate->id,
                'name' => $roomName,
                'order_index' => $index,
                'subtotal' => 0
            ]);

            $sectionTotal = 0;
            for ($i = 0; $i < 3; $i++) {
                $p = $products[rand(0, count($products) - 1)];
                $qty = rand(20, 100);
                $total = $qty * $p->unit_price;

                EstimateItem::create([
                    'estimate_id' => $estimate->id,
                    'estimate_section_id' => $section->id,
                    'name' => $p->name,
                    'quantity' => $qty,
                    'unit_price' => $p->unit_price,
                    'unit_type' => $p->unit_type,
                    'total' => $total
                ]);
                $sectionTotal += $total;
            }
            $section->update(['subtotal' => $sectionTotal]);
            $estTotal += $sectionTotal;
        }
        $estimate->update(['subtotal' => $estTotal, 'grand_total' => $estTotal]);
    }
}

