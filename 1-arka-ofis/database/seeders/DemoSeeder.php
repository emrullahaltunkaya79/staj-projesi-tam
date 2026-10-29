<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Product;
use App\Models\Category;
use App\Models\Expense;
use App\Models\RecurringInvoice;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Zengin demo verisi: 4 aylık gerçekçi geçmiş — sunumda grafikler,
 * raporlar, yaşlandırma ve dashboard dolu görünsün.
 */
class DemoSeeder extends Seeder
{
    private const MONTHS_BACK = 4;

    public function run(): void
    {
        DB::transaction(function () {
            $company = \App\Models\Company::create([
                'name' => 'Demo Ticaret A.Ş.',
                'tax_number' => '1234567890',
                'phone' => '0212 000 00 00',
                'address' => 'Örnek Mah. Staj Cad. No:1 Kağıthane / İstanbul',
            ]);

            \App\Models\User::create([
                'company_id' => $company->id,
                'role' => 'admin',
                'name' => 'Stajyer Admin',
                'email' => 'admin@test.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            \App\Models\User::create([
                'company_id' => $company->id,
                'role' => 'kullanici',
                'name' => 'Ayşe Personel',
                'email' => 'personel@test.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            $this->seedCategories($company->id);
            $this->seedWarehouses($company->id);
            $products = $this->seedProducts($company->id);
            $contacts = $this->seedContacts($company->id);

            $invoiceSvc = app(InvoiceService::class);
            $paymentSvc = app(PaymentService::class);
            $companyId = $company->id;

            // ---- 4 ay boyunca her ay satış + alış + gider + tahsilat ----
            for ($m = self::MONTHS_BACK - 1; $m >= 0; $m--) {
                $month = now()->subMonths($m);
                $monthStart = $month->copy()->startOfMonth();

                // Her ay 2-4 satış faturası
                $salesCount = rand(2, 4);
                for ($s = 0; $s < $salesCount; $s++) {
                    $contact = $contacts['customers'][array_rand($contacts['customers'])];
                    $items = [];
                    $itemCount = rand(1, 3);
                    for ($i = 0; $i < $itemCount; $i++) {
                        $product = $products[array_rand($products)];
                        $items[] = [
                            'product_id' => $product->id,
                            'quantity' => rand(1, 8),
                            'unit_price' => (float) $product->sale_price,
                        ];
                    }
                    $issueDate = $monthStart->copy()->addDays(rand(1, 26))->toDateString();
                    $dueDate = (rand(0, 1) ? $monthStart->copy()->addDays(rand(27, 40))->toDateString() : null);

                    try {
                        $invoiceSvc->createSaleInvoice($companyId, [
                            'contact_id' => $contact->id,
                            'issue_date' => $issueDate,
                            'due_date' => $dueDate,
                            'tax_rate' => [0, 10, 20][rand(0, 2)],
                            'items' => $items,
                        ]);
                    } catch (\Throwable) {
                        // stok yetmedi — sorun değil
                    }
                }

                // Her ay 1-2 alış faturası
                $purchaseCount = rand(1, 2);
                for ($p = 0; $p < $purchaseCount; $p++) {
                    $items = [];
                    $product = $products[array_rand($products)];
                    $items[] = [
                        'product_id' => $product->id,
                        'quantity' => rand(10, 30),
                        'unit_price' => (float) $product->purchase_price,
                    ];
                    try {
                        $invoiceSvc->createPurchaseInvoice($companyId, [
                            'contact_id' => $contacts['suppliers'][0]->id,
                            'issue_date' => $monthStart->copy()->addDays(rand(3, 20))->toDateString(),
                            'tax_rate' => 20,
                            'items' => $items,
                        ]);
                    } catch (\Throwable) {
                    }
                }

                // Her ay 2-3 gider
                $giderler = ['Kira' => 5000, 'Elektrik' => rand(600, 1200), 'Internet' => 320, 'Su' => rand(200, 400), 'Lojistik' => rand(400, 800)];
                foreach (array_rand($giderler, rand(2, 3)) as $kategori) {
                    Expense::create([
                        'company_id' => $companyId,
                        'category' => $kategori,
                        'description' => $month->translatedFormat('F Y').' — '.$kategori,
                        'amount' => $giderler[$kategori],
                        'expense_date' => $monthStart->copy()->addDays(rand(1, 27))->toDateString(),
                        'payment_type' => rand(0, 1) ? 'havale' : 'nakit',
                    ]);
                }

                // Tahminî tahsilat (son ay hariç — mevcut faturalardan bazılarını öde)
                if ($m > 0) {
                    $unpaid = \App\Models\Invoice::where('company_id', $companyId)
                        ->where('type', 'satis')
                        ->where('issue_date', '<', $monthStart->copy()->endOfMonth())
                        ->whereHas('contact', fn ($q) => $q->where('balance', '>', 0))
                        ->inRandomOrder()
                        ->limit(rand(1, 2))
                        ->get();

                    foreach ($unpaid as $invoice) {
                        try {
                            $paymentSvc->recordPayment($companyId, [
                                'contact_id' => $invoice->contact_id,
                                'amount' => (float) $invoice->total,
                                'payment_type' => rand(0, 1) ? 'havale' : 'nakit',
                                'description' => $invoice->invoice_no.' tahsilatı',
                            ]);
                        } catch (\Throwable) {
                        }
                    }
                }
            }

            // ---- Örnek teklif (onay bekliyor) ----
            $yildiz = collect($contacts['customers'])->firstWhere('name', 'Yıldız Gıda');
            $klavye = collect($products)->firstWhere('name', 'Klavye');
            if ($yildiz && $klavye) {
                app(\App\Services\QuoteService::class)->createQuote($companyId, [
                    'contact_id' => $yildiz->id,
                    'issue_date' => now()->toDateString(),
                    'valid_until' => now()->addDays(15)->toDateString(),
                    'tax_rate' => 20,
                    'notes' => 'Teslim: ödeme sonrası 3 iş günü.',
                    'items' => [['product_id' => $klavye->id, 'quantity' => 2, 'unit_price' => 450]],
                ]);
            }

            // ---- Tekrarlayan fatura kuralı ----
            $ahmet = collect($contacts['customers'])->firstWhere('name', 'Ahmet Market');
            $mouse = collect($products)->firstWhere('name', 'Mouse');
            if ($ahmet && $mouse) {
                RecurringInvoice::create([
                    'company_id' => $companyId,
                    'contact_id' => $ahmet->id,
                    'title' => 'Aylık Bakım Sözleşmesi',
                    'day_of_month' => 5,
                    'tax_rate' => 20,
                    'items' => [['product_id' => $mouse->id, 'quantity' => 2, 'unit_price' => 249.90]],
                ]);
            }

            // ---- Geciken vadeli fatura (dashboard uyarısı için) ----
            $anadolu = collect($contacts['customers'])->firstWhere('name', 'Anadolu Ofis A.Ş.');
            $monitor = collect($products)->firstWhere('name', 'Monitör');
            if ($anadolu && $monitor) {
                try {
                    $invoiceSvc->createSaleInvoice($companyId, [
                        'contact_id' => $anadolu->id,
                        'issue_date' => now()->subDays(45)->toDateString(),
                        'due_date' => now()->subDays(15)->toDateString(),
                        'tax_rate' => 20,
                        'items' => [['product_id' => $monitor->id, 'quantity' => 1, 'unit_price' => 3200]],
                    ]);
                } catch (\Throwable) {
                }
            }

            // ---- Özel fiyat ----
            if ($ahmet && $klavye) {
                \App\Models\ContactPrice::create([
                    'company_id' => $companyId,
                    'contact_id' => $ahmet->id,
                    'product_id' => $klavye->id,
                    'price' => 399.00,
                ]);
            }

            // ---- Kasa → Banka virman ----
            \App\Models\CashTransfer::create([
                'company_id' => $companyId,
                'direction' => 'nakit_havale',
                'amount' => 2000,
                'description' => 'Bankaya para yatırma',
                'transfer_date' => now()->subDays(10)->toDateString(),
            ]);
        });

        $this->command?->info('Zengin demo verisi hazır! Giriş: admin@test.com / password');
    }

    private function seedCategories(int $companyId): void
    {
        foreach (['Bilgisayar', 'Kırtasiye', 'Elektronik', 'Ofis'] as $name) {
            Category::create(['company_id' => $companyId, 'name' => $name]);
        }
    }

    private function seedWarehouses(int $companyId): void
    {
        \App\Models\Warehouse::create(['company_id' => $companyId, 'name' => 'Merkez Depo', 'code' => 'MRK', 'address' => 'Merkez Ofis']);
        \App\Models\Warehouse::create(['company_id' => $companyId, 'name' => 'Şube Depo', 'code' => 'SB', 'address' => 'Kadıköy Şubesi']);
    }

    private function seedProducts(int $companyId): array
    {
        $cats = Category::where('company_id', $companyId)->pluck('id', 'name');

        $data = [
            ['code' => 'URN-001', 'name' => 'Klavye', 'cat' => 'Bilgisayar', 'purchase' => 250, 'sale' => 450, 'stock' => 25],
            ['code' => 'URN-002', 'name' => 'Mouse', 'cat' => 'Bilgisayar', 'purchase' => 120, 'sale' => 249.90, 'stock' => 45],
            ['code' => 'URN-003', 'name' => 'Monitör 24"', 'cat' => 'Elektronik', 'purchase' => 1800, 'sale' => 3200, 'stock' => 12],
            ['code' => 'URN-004', 'name' => 'USB Kablosu 2m', 'cat' => 'Bilgisayar', 'purchase' => 15, 'sale' => 45, 'stock' => 80],
            ['code' => 'URN-005', 'name' => 'Webcam HD', 'cat' => 'Elektronik', 'purchase' => 300, 'sale' => 550, 'stock' => 18],
            ['code' => 'URN-006', 'name' => 'Defter A4 100Y', 'cat' => 'Kırtasiye', 'purchase' => 32, 'sale' => 65, 'stock' => 120],
            ['code' => 'URN-007', 'name' => 'Tükenmez Kalem', 'cat' => 'Kırtasiye', 'purchase' => 5, 'sale' => 15, 'stock' => 300],
            ['code' => 'URN-008', 'name' => 'Dosya Kapağı', 'cat' => 'Kırtasiye', 'purchase' => 3, 'sale' => 8.50, 'stock' => 200],
            ['code' => 'URN-009', 'name' => 'Kulaklık Bluetooth', 'cat' => 'Elektronik', 'purchase' => 350, 'sale' => 699, 'stock' => 15],
            ['code' => 'URN-010', 'name' => 'Ofis Sandalyesi', 'cat' => 'Ofis', 'purchase' => 800, 'sale' => 1500, 'stock' => 8],
        ];

        $products = [];
        foreach ($data as $d) {
            $products[] = Product::create([
                'company_id' => $companyId,
                'category_id' => $cats[$d['cat']] ?? null,
                'code' => $d['code'],
                'name' => $d['name'],
                'unit' => 'adet',
                'purchase_price' => $d['purchase'],
                'sale_price' => $d['sale'],
                'stock_quantity' => $d['stock'],
            ]);
        }

        return $products;
    }

    private function seedContacts(int $companyId): array
    {
        $customers = [];
        foreach ([
            ['name' => 'Ahmet Market', 'phone' => '0532 111 22 33', 'email' => 'info@ahmetmarket.com'],
            ['name' => 'Yıldız Gıda', 'phone' => '0533 444 55 66', 'email' => 'siparis@yildizgida.com'],
            ['name' => 'Anadolu Ofis A.Ş.', 'phone' => '0216 555 66 77', 'email' => 'satinalma@anadoluofis.com', 'tax_number' => '4567891230'],
            ['name' => 'Teknoloji Market', 'phone' => '0212 888 99 00', 'email' => 'satis@teknolojimarket.com'],
            ['name' => 'Kardeşler Kırtasiye', 'phone' => '0543 222 33 44', 'email' => 'kardesler@kirtasiye.com'],
        ] as $c) {
            $customers[] = Contact::create(array_merge($c, ['company_id' => $companyId, 'type' => 'musteri']));
        }

        $suppliers = [];
        foreach ([
            ['name' => 'Ucuz Toptan Bilgisayar', 'phone' => '0216 777 88 99', 'email' => 'satis@ucuztoptan.com'],
            ['name' => 'Kırtasiye Toptan A.Ş.', 'phone' => '0212 333 44 55', 'email' => 'toptan@kirtasiyetoptan.com'],
        ] as $s) {
            $suppliers[] = Contact::create(array_merge($s, ['company_id' => $companyId, 'type' => 'tedarikci']));
        }

        return ['customers' => $customers, 'suppliers' => $suppliers];
    }
}
