<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_cashier_page_loads_successfully()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('TOKO RETAIL MAKMUR');
        $response->assertSee('Ringkasan Pembayaran');
        $response->assertSee('Keranjang Belanja');
    }

    public function test_product_search_api_returns_results()
    {
        $response = $this->getJson('/kasir/cari-produk?q=Indomie');
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Indomie Goreng']);
    }

    public function test_product_search_by_barcode()
    {
        $response = $this->getJson('/kasir/cari-produk?q=899123456001');
        $response->assertStatus(200);
        $response->assertJsonFragment(['barcode' => '899123456001']);
    }

    public function test_complete_payment_transaction_flow()
    {
        $product = Product::where('code', 'BRG001')->first();
        $initialStock = $product->stock;

        $payload = [
            'items' => [
                [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'price' => $product->price,
                    'qty' => 2,
                ]
            ],
            'payment' => 10000,
            'payment_method' => 'Tunai',
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tax' => 0,
            'other_fee' => 0,
            'customer_name' => 'Umum',
        ];

        $response = $this->postJson('/kasir/transaksi', $payload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $responseData = $response->json();
        $this->assertDatabaseHas('transactions', [
            'id' => $responseData['transaction_id'],
            'grand_total' => 7000,
            'paid_amount' => 10000,
            'change_amount' => 3000,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('transaction_details', [
            'transaction_id' => $responseData['transaction_id'],
            'product_code' => 'BRG001',
            'qty' => 2,
            'subtotal' => 7000,
        ]);

        // Stock decreased
        $product->refresh();
        $this->assertEquals($initialStock - 2, $product->stock);
    }

    public function test_hold_and_resume_transaction()
    {
        $product = Product::first();

        // 1. Hold
        $holdPayload = [
            'items' => [
                [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'price' => $product->price,
                    'qty' => 3,
                ]
            ],
            'customer_name' => 'Budi Santoso',
            'payment_method' => 'Tunai',
        ];

        $holdResponse = $this->postJson('/kasir/tahan', $holdPayload);
        $holdResponse->assertStatus(200);
        $holdResponse->assertJson(['success' => true]);

        $heldId = $holdResponse->json('held_id');
        $this->assertDatabaseHas('transactions', [
            'id' => $heldId,
            'status' => 'held',
        ]);

        // 2. List held
        $listResponse = $this->getJson('/kasir/transaksi-ditahan');
        $listResponse->assertStatus(200);
        $listResponse->assertJsonFragment(['id' => $heldId]);

        // 3. Resume
        $resumeResponse = $this->postJson("/kasir/transaksi-ditahan/{$heldId}/lanjutkan");
        $resumeResponse->assertStatus(200);
        $resumeResponse->assertJson(['success' => true]);
        $this->assertCount(1, $resumeResponse->json('cart'));

        // 4. Delete held
        $deleteResponse = $this->deleteJson("/kasir/transaksi-ditahan/{$heldId}");
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('transactions', ['id' => $heldId]);
    }

    public function test_print_receipt_page()
    {
        $trx = Transaction::create([
            'transaction_number' => 'TRX-TEST-001',
            'customer_name' => 'Test Pelanggan',
            'subtotal' => 10000,
            'grand_total' => 10000,
            'paid_amount' => 10000,
            'change_amount' => 0,
            'payment_method' => 'Tunai',
            'status' => 'completed',
        ]);

        TransactionDetail::create([
            'transaction_id' => $trx->id,
            'product_code' => 'BRG001',
            'product_name' => 'Indomie Goreng',
            'price' => 5000,
            'qty' => 2,
            'subtotal' => 10000,
        ]);

        $response = $this->get("/kasir/transaksi/{$trx->id}/print");
        $response->assertStatus(200);
        $response->assertSee('TOKO RETAIL MAKMUR');
        $response->assertSee('TRX-TEST-001');
        $response->assertSee('Indomie Goreng');
    }

    public function test_product_crud()
    {
        // Create
        $response = $this->post('/produk', [
            'code' => 'BRG999',
            'barcode' => '899999999999',
            'name' => 'Snack Keripik Singkong',
            'price' => 8500,
            'stock' => 50,
            'unit' => 'bungkus',
        ]);
        $response->assertRedirect('/produk');
        $this->assertDatabaseHas('products', ['code' => 'BRG999']);

        $product = Product::where('code', 'BRG999')->first();

        // Update
        $updateResponse = $this->put("/produk/{$product->id}", [
            'code' => 'BRG999',
            'barcode' => '899999999999',
            'name' => 'Snack Keripik Singkong Pedas',
            'price' => 9000,
            'stock' => 60,
        ]);
        $updateResponse->assertRedirect('/produk');
        $this->assertDatabaseHas('products', ['name' => 'Snack Keripik Singkong Pedas', 'price' => 9000]);

        // Delete
        $deleteResponse = $this->delete("/produk/{$product->id}");
        $deleteResponse->assertRedirect('/produk');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_closing_cashier_page()
    {
        $response = $this->get('/tutup-kasir');
        $response->assertStatus(200);
        $response->assertSee('Laporan Tutup Kasir / Rekap Shift');
        $response->assertSee('Rekonsiliasi Kas Tunai Fisik');
    }
}
