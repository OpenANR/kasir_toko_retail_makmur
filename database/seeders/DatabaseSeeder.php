<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default Cashier / Admin User
        User::firstOrCreate(
            ['email' => 'kasir@tokomakmur.com'],
            [
                'name' => 'Kasir Toko Makmur',
                'password' => Hash::make('password123'),
            ]
        );

        // Products from PDF and additional typical retail store items
        $products = [
            [
                'code' => 'BRG001',
                'barcode' => '899123456001',
                'name' => 'Indomie Goreng',
                'price' => 3500,
                'cost_price' => 2800,
                'stock' => 120,
                'category' => 'Makanan Instan',
                'unit' => 'pcs',
            ],
            [
                'code' => 'BRG002',
                'barcode' => '899123456002',
                'name' => 'Aqua 600ml',
                'price' => 4000,
                'cost_price' => 3000,
                'stock' => 85,
                'category' => 'Minuman',
                'unit' => 'botol',
            ],
            [
                'code' => 'BRG003',
                'barcode' => '899123456003',
                'name' => 'Teh Botol Sosro',
                'price' => 5000,
                'cost_price' => 3800,
                'stock' => 60,
                'category' => 'Minuman',
                'unit' => 'botol',
            ],
            [
                'code' => 'BRG004',
                'barcode' => '899123456004',
                'name' => 'Beras 5 Kg',
                'price' => 75000,
                'cost_price' => 67000,
                'stock' => 40,
                'category' => 'Sembako',
                'unit' => 'karung',
            ],
            [
                'code' => 'BRG005',
                'barcode' => '899123456005',
                'name' => 'Minyak Goreng 1 Liter',
                'price' => 18000,
                'cost_price' => 15500,
                'stock' => 50,
                'category' => 'Sembako',
                'unit' => 'pouch',
            ],
            [
                'code' => 'BRG006',
                'barcode' => '899123456006',
                'name' => 'Gula Pasir 1 Kg',
                'price' => 17000,
                'cost_price' => 14500,
                'stock' => 55,
                'category' => 'Sembako',
                'unit' => 'kg',
            ],
            [
                'code' => 'BRG007',
                'barcode' => '899123456007',
                'name' => 'Kopi Kapal Api Special Mix',
                'price' => 2000,
                'cost_price' => 1500,
                'stock' => 150,
                'category' => 'Minuman',
                'unit' => 'sachet',
            ],
            [
                'code' => 'BRG008',
                'barcode' => '899123456008',
                'name' => 'Susu Ultra Milk Cokelat 250ml',
                'price' => 6500,
                'cost_price' => 5200,
                'stock' => 70,
                'category' => 'Minuman',
                'unit' => 'kotak',
            ],
            [
                'code' => 'BRG009',
                'barcode' => '899123456009',
                'name' => 'Sabun Mandi Lifebuoy',
                'price' => 4500,
                'cost_price' => 3500,
                'stock' => 65,
                'category' => 'Kebersihan',
                'unit' => 'batang',
            ],
            [
                'code' => 'BRG010',
                'barcode' => '899123456010',
                'name' => 'Roti Tawar Sari Roti',
                'price' => 15000,
                'cost_price' => 12500,
                'stock' => 30,
                'category' => 'Makanan',
                'unit' => 'bungkus',
            ],
            [
                'code' => 'BRG011',
                'barcode' => '899123456011',
                'name' => 'Telur Ayam 1 Kg',
                'price' => 28000,
                'cost_price' => 24000,
                'stock' => 40,
                'category' => 'Sembako',
                'unit' => 'kg',
            ],
        ];

        foreach ($products as $prod) {
            Product::updateOrCreate(['code' => $prod['code']], $prod);
        }

        // Customers
        $customers = [
            [
                'name' => 'Umum',
                'phone' => null,
                'member_number' => null,
                'type' => 'Umum',
                'discount_percent' => 0,
                'address' => 'Pelanggan Reguler',
            ],
            [
                'name' => 'Budi Santoso',
                'phone' => '081234567890',
                'member_number' => 'MBR-001',
                'type' => 'Pelanggan Member',
                'discount_percent' => 5,
                'address' => 'Jl. Mawar No. 12, Jember',
            ],
            [
                'name' => 'Siti Aminah',
                'phone' => '082345678901',
                'member_number' => 'MBR-002',
                'type' => 'Member VIP',
                'discount_percent' => 10,
                'address' => 'Jl. Melati No. 45, Jember',
            ],
            [
                'name' => 'Hendra Pratama',
                'phone' => '085678901234',
                'member_number' => 'MBR-003',
                'type' => 'Pelanggan Member',
                'discount_percent' => 5,
                'address' => 'Jl. Kenanga No. 8, Jember',
            ],
        ];

        foreach ($customers as $cust) {
            Customer::updateOrCreate(
                ['name' => $cust['name'], 'type' => $cust['type']],
                $cust
            );
        }
    }
}
