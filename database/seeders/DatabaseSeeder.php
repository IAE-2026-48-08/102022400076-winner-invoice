<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AuctionItem;
use App\Models\Winner;
use App\Models\Invoice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Users
        $admin = User::create([
            'name' => 'Raqieza Walloaz',
            'email' => 'raqieza@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $user1 = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        $user2 = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        // 2. Seed Auction Items
        $item1 = AuctionItem::create([
            'name' => 'Laptop ASUS ROG Zephyrus',
            'description' => 'Laptop gaming high-end spec dewa.',
            'final_price' => 25000000.00,
            'status' => 'completed',
        ]);

        $item2 = AuctionItem::create([
            'name' => 'iPhone 15 Pro Max',
            'description' => 'Smartphone flagship Apple terbaru 256GB.',
            'final_price' => 19000000.00,
            'status' => 'completed',
        ]);

        $item3 = AuctionItem::create([
            'name' => 'Mercedes-Benz C200 2024',
            'description' => 'Mobil sedan mewah warna hitam metalik.',
            'final_price' => 950000000.00,
            'status' => 'completed',
        ]);

        $item4 = AuctionItem::create([
            'name' => 'iPad Pro M4',
            'description' => 'iPad Pro terbaru dengan layar OLED 11 inci.',
            'final_price' => 16000000.00,
            'status' => 'completed',
        ]);

        $item5 = AuctionItem::create([
            'name' => 'PlayStation 5 Slim',
            'description' => 'Konsol game PlayStation 5 versi slim.',
            'final_price' => 8500000.00,
            'status' => 'completed',
        ]);

        // 3. Seed Winners & Invoices (Simulating completed checkouts)
        // Checkout 1: John Doe checkouts Laptop ASUS ROG
        $winner1 = Winner::create([
            'auction_item_id' => $item1->id,
            'user_id' => $user1->id,
            'winning_bid' => 25000000.00,
            'won_at' => now()->subDays(2),
        ]);

        Invoice::create([
            'winner_id' => $winner1->id,
            'invoice_number' => 'INV/' . date('Ymd') . '/0001',
            'amount' => 25000000.00,
            'status' => 'paid',
            'receipt_number' => 'REC-SOAP-99882233',
        ]);

        // Checkout 2: Jane Smith checkouts iPhone 15 Pro Max
        $winner2 = Winner::create([
            'auction_item_id' => $item2->id,
            'user_id' => $user2->id,
            'winning_bid' => 19000000.00,
            'won_at' => now()->subDays(1),
        ]);

        Invoice::create([
            'winner_id' => $winner2->id,
            'invoice_number' => 'INV/' . date('Ymd') . '/0002',
            'amount' => 19000000.00,
            'status' => 'pending',
            'receipt_number' => null, // Pending SOAP audit
        ]);
    }
}
