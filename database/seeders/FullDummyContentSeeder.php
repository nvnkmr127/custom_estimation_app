<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\EstimateSection;
use App\Models\ItemPackage;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RoomTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
        ];

        $categoryIds = [];
        foreach ($categories as $main => $subs) {
            $cat = ProductCategory::create(['name' => $main, 'description' => "$main materials and supplies"]);
            $categoryIds[$main] = $cat->id;
        }

        $this->command->info('Categories created.');

        // 2. Create Products
        $products = [
            ['name' => 'Italian Marble Slab', 'cat' => 'Flooring', 'price' => 450, 'unit' => 'sqft'],
            ['name' => 'Vitrified Tiles 2x2', 'cat' => 'Flooring', 'price' => 65, 'unit' => 'sqft'],
            ['name' => 'Teak Wood Plank', 'cat' => 'Woodwork', 'price' => 2500, 'unit' => 'cft'],
            ['name' => 'Greenply 19mm Plywood', 'cat' => 'Woodwork', 'price' => 110, 'unit' => 'sqft'],
            ['name' => 'Asian Paints Royale Luxury', 'cat' => 'Paint', 'price' => 550, 'unit' => 'ltr'],
            ['name' => 'Birla White Putty', 'cat' => 'Paint', 'price' => 45, 'unit' => 'kg'],
            ['name' => 'Havells Modular Switch', 'cat' => 'Electrical', 'price' => 180, 'unit' => 'nos'],
            ['name' => 'Finolex 2.5mm Wire', 'cat' => 'Electrical', 'price' => 2400, 'unit' => 'bundle'],
            ['name' => 'Jaguar Wall Mixer', 'cat' => 'Plumbing', 'price' => 4500, 'unit' => 'nos'],
            ['name' => 'PVC Pipe 4 inch', 'cat' => 'Plumbing', 'price' => 350, 'unit' => 'length'],
            ['name' => 'Saint Gobain Gypsum Board', 'cat' => 'False Ceiling', 'price' => 45, 'unit' => 'sqft'],
        ];

        $productModels = [];
        foreach ($products as $p) {
            $product = Product::create([
                'name' => $p['name'],
                'category_id' => $categoryIds[$p['cat']],
                'sku' => strtoupper(substr($p['cat'], 0, 3)) . '-' . rand(1000, 9999),
                'description' => 'Premium quality ' . strtolower($p['name']) . ' for modern homes.',
                'unit_price' => $p['price'],
                'unit_type' => $p['unit'],
                'status' => 'active',
            ]);
            $productModels[$p['name']] = $product;

            // Add Images
            $text = urlencode($p['name']);
            \App\Models\ProductImage::create([
                'product_id' => $product->id,
                'image_path' => "https://placehold.co/600x400/e2e8f0/475569?text={$text}", // External URL handled by views now
                'display_order' => 0,
            ]);
        }

        $this->command->info('Products created.');

        // 3. Create Clients
        $clients = Client::factory()->count(10)->create();
        $this->command->info('Clients created.');

        // 4. Create Room Templates (Crucial for Room-Based Estimation)
        $templates = [
            [
                'name' => 'Living Room (Premium)',
                'description' => 'Complete living room interior setup',
                'items' => [
                    ['name' => 'False Ceiling', 'quantity' => 250, 'unit_price' => 120, 'unit_type' => 'sqft'],
                    ['name' => 'Wall Painting (Royale)', 'quantity' => 600, 'unit_price' => 45, 'unit_type' => 'sqft'],
                    ['name' => 'Flooring (Italian Marble)', 'quantity' => 250, 'unit_price' => 650, 'unit_type' => 'sqft'],
                    ['name' => 'Electrical Points', 'quantity' => 12, 'unit_price' => 850, 'unit_type' => 'point'],
                ]
            ],
            [
                'name' => 'Master Bedroom',
                'description' => 'Bedroom with wardrobe and loft',
                'items' => [
                    ['name' => 'Wardrobe (8x7)', 'quantity' => 56, 'unit_price' => 1800, 'unit_type' => 'sqft'],
                    ['name' => 'Loft Storage', 'quantity' => 24, 'unit_price' => 1200, 'unit_type' => 'sqft'],
                    ['name' => 'Wall Painting', 'quantity' => 450, 'unit_price' => 38, 'unit_type' => 'sqft'],
                    ['name' => 'Study Table Unit', 'quantity' => 1, 'unit_price' => 15000, 'unit_type' => 'nos'],
                ]
            ],
            [
                'name' => 'Modular Kitchen',
                'description' => 'L-Shaped kitchen with accessories',
                'items' => [
                    ['name' => 'Base Cabinets', 'quantity' => 45, 'unit_price' => 2200, 'unit_type' => 'sqft'],
                    ['name' => 'Wall Cabinets', 'quantity' => 30, 'unit_price' => 1800, 'unit_type' => 'sqft'],
                    ['name' => 'Granite Countertop', 'quantity' => 40, 'unit_price' => 450, 'unit_type' => 'sqft'],
                    ['name' => 'SS Sink & Tap', 'quantity' => 1, 'unit_price' => 8500, 'unit_type' => 'set'],
                    ['name' => 'Kitchen Dado Tiles', 'quantity' => 60, 'unit_price' => 120, 'unit_type' => 'sqft'],
                ]
            ],
            [
                'name' => 'Bathroom Renovation',
                'description' => 'Complete bathroom overhaul',
                'items' => [
                    ['name' => 'Floor Tiles (Anti-skid)', 'quantity' => 45, 'unit_price' => 85, 'unit_type' => 'sqft'],
                    ['name' => 'Wall Tiles', 'quantity' => 250, 'unit_price' => 75, 'unit_type' => 'sqft'],
                    ['name' => 'Plumbing Works', 'quantity' => 1, 'unit_price' => 12000, 'unit_type' => 'lumpsum'],
                    ['name' => 'Sanitary Fittings By Jaguar', 'quantity' => 1, 'unit_price' => 35000, 'unit_type' => 'set'],
                ]
            ]
        ];

        foreach ($templates as $t) {
            RoomTemplate::create([
                'name' => $t['name'],
                'description' => $t['description'],
                'items' => $t['items']
            ]);
        }
        $this->command->info('Room Templates created.');

        // 5. Create Packages
        ItemPackage::create([
            'name' => 'Quick Electrical Fix',
            'description' => 'Basic electrical overhaul for a room',
            'total_price' => 15000,
            'items' => [
                ['item_name' => 'Modular Switches', 'quantity' => 10, 'unit_type' => 'nos', 'unit_price' => 250],
                ['item_name' => 'LED Panel Lights', 'quantity' => 6, 'unit_type' => 'nos', 'unit_price' => 650],
                ['item_name' => 'Ceiling Fan', 'quantity' => 1, 'unit_type' => 'nos', 'unit_price' => 3500],
                ['item_name' => 'Labour Charges', 'quantity' => 1, 'unit_type' => 'day', 'unit_price' => 1500],
            ]
        ]);

        $this->command->info('Packages created.');

        // 6. Create Estimates (Standard & Room-Based)
        $user = User::first() ?? User::factory()->create();

        // Standard Estimate
        // Standard Estimate
        $est1 = Estimate::create([
            'estimate_number' => 'EST-' . date('Y') . '-' . uniqid(),
            'title' => 'Apartment Interior Renovation',
            'client_id' => $clients[0]->id,
            'estimate_date' => Carbon::now(),
            'expiry_date' => Carbon::now()->addDays(30),
            'currency' => '₹',
            'status' => 'draft',
            'type' => 'standard',
            'subtotal' => 0,
            'created_by' => $user->id,
            'pdf_theme' => 'modern',
        ]);

        $subtotal1 = 0;
        foreach ($products as $key => $p) {
            if ($key > 3)
                break; // Add first 4 products
            $qty = rand(10, 50);
            $total = $qty * $p['price'];
            EstimateItem::create([
                'estimate_id' => $est1->id,
                'name' => $p['name'],
                'description' => 'Best quality item',
                'quantity' => $qty,
                'unit_price' => $p['price'],
                'unit_type' => $p['unit'],
                'total' => $total, // Corrected column name
                'tax_1' => 18,
                'tax_2' => 0,
            ]);
            $subtotal1 += $total;
        }
        $est1->update(['subtotal' => $subtotal1, 'grand_total' => $subtotal1 * 1.18]);

        // Room-Based Estimate
        $est2 = Estimate::create([
            'estimate_number' => 'EST-' . date('Y') . '-' . uniqid(),
            'title' => 'Villa Full Furnishing',
            'client_id' => $clients[1]->id,
            'estimate_date' => Carbon::now(),
            'expiry_date' => Carbon::now()->addDays(15),
            'currency' => '₹',
            'status' => 'sent',
            'type' => 'room_based',
            'subtotal' => 0,
            'created_by' => $user->id,
            'pdf_theme' => 'classic',
        ]);

        $est2Total = 0;
        $rooms = ['Living Room', 'Guest Bedroom'];
        foreach ($rooms as $index => $roomName) {
            $section = EstimateSection::create([
                'estimate_id' => $est2->id,
                'name' => $roomName,
                'order_index' => $index,
                'subtotal' => 0
            ]);

            $sectionTotal = 0;
            // Add some items to section
            for ($i = 0; $i < 3; $i++) {
                $p = $products[rand(0, count($products) - 1)];
                $qty = rand(20, 100);
                $total = $qty * $p['price'];

                EstimateItem::create([
                    'estimate_id' => $est2->id,
                    'estimate_section_id' => $section->id, // Linked to sections
                    'name' => $p['name'],
                    'quantity' => $qty,
                    'unit_price' => $p['price'],
                    'unit_type' => $p['unit'],
                    'total' => $total // Corrected column name
                ]);
                $sectionTotal += $total;
            }
            $section->update(['subtotal' => $sectionTotal]);
            $est2Total += $sectionTotal;
        }
        $est2->update(['subtotal' => $est2Total, 'grand_total' => $est2Total]);

        $this->command->info('Estimates created. Seeding completed successfully!');
    }
}
