<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Product;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

/**
 * Excel'den içe aktarma: cariler, ürünler ve stok sayım sonuçları.
 *
 * Satır satır işlenir; geçersiz satırlar atlanıp özet raporlanır.
 * Ürün stokları model olayları üzerinden otomatik olarak
 * "açılış" ve "düzenleme" stok hareketlerini üretir.
 */
class ImportController extends Controller
{
    // ---------- CARİLER ----------

    public function contactsForm()
    {
        return view('imports.contacts');
    }

    public function importContacts(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:4096'],
        ], ['file.required' => 'İçe aktarılacak dosyayı seçin.', 'file.extensions' => 'Yalnızca .xlsx, .xls veya .csv dosyaları yüklenebilir.']);

        $rows = $this->readRows($request);
        $stats = ['eklenen' => 0, 'guncellenen' => 0, 'atlanan' => 0, 'hatalar' => []];

        foreach ($rows as $i => $row) {
            $type = mb_strtolower(trim((string) ($row[0] ?? '')));
            $name = trim((string) ($row[1] ?? ''));
            $satirNo = $i + 2; // başlık satırı + 1

            if ($name === '') {
                $stats['atlanan']++;
                continue;
            }

            $mappedType = str_contains($type, 'tedarik') ? 'tedarikci' : 'musteri';

            $contact = Contact::where('name', $name)->first();
            $payload = [
                'type' => $mappedType,
                'phone' => $this->val($row, 2),
                'email' => $this->val($row, 3),
                'tax_number' => $this->val($row, 4),
                'address' => $this->val($row, 5),
            ];

            if ($contact) {
                $contact->update(array_filter($payload, fn ($v) => $v !== null && $v !== ''));
                $stats['guncellenen']++;
            } else {
                Contact::create(array_merge(['name' => $name], $payload));
                $stats['eklenen']++;
            }

            if (count($stats['hatalar']) < 5 && filter_var($payload['email'] ?? '', FILTER_VALIDATE_EMAIL) === false && ($row[3] ?? '') !== '') {
                $stats['hatalar'][] = "Satır {$satirNo}: geçersiz e-posta atlandı.";
            }
        }

        $message = sprintf(
            'Cari içe aktarma tamamlandı: %d eklendi, %d güncellendi, %d atlandı.',
            $stats['eklenen'], $stats['guncellenen'], $stats['atlanan']
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    // ---------- ÜRÜNLER ----------

    public function productsForm()
    {
        return view('imports.products');
    }

    public function importProducts(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:4096'],
        ], ['file.required' => 'İçe aktarılacak dosyayı seçin.', 'file.extensions' => 'Yalnızca .xlsx, .xls veya .csv dosyaları yüklenebilir.']);

        $rows = $this->readRows($request);
        $stats = ['eklenen' => 0, 'guncellenen' => 0, 'atlanan' => 0];

        foreach ($rows as $i => $row) {
            $code = trim((string) ($row[0] ?? ''));
            $name = trim((string) ($row[1] ?? ''));

            if ($name === '') {
                $stats['atlanan']++;
                continue;
            }

            $payload = [
                'code' => $code ?: null,
                'name' => $name,
                'unit' => trim((string) ($row[2] ?? '')) ?: 'adet',
                'purchase_price' => (float) str_replace(',', '.', (string) ($row[3] ?? 0)) ?: 0,
                'sale_price' => (float) str_replace(',', '.', (string) ($row[4] ?? 0)) ?: 0,
                'stock_quantity' => (int) ($row[5] ?? 0),
            ];

            // Kod varsa koda göre, yoksa isme göre eşleştir
            $product = $code !== ''
                ? Product::where('code', $code)->first()
                : Product::where('name', $name)->first();

            if ($product) {
                $product->update($payload);
                $stats['guncellenen']++;
            } else {
                Product::create($payload); // açılış hareketi model olayıyla otomatik
                $stats['eklenen']++;
            }
        }

        $message = sprintf(
            'Ürün içe aktarma tamamlandı: %d eklendi, %d güncellendi, %d atlandı.',
            $stats['eklenen'], $stats['guncellenen'], $stats['atlanan']
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    // ---------- STOK SAYIM ----------

    public function stockCountForm()
    {
        return view('imports.stock_count');
    }

    public function importStockCount(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:4096'],
        ], ['file.required' => 'İçe aktarılacak dosyayı seçin.', 'file.extensions' => 'Yalnızca .xlsx, .xls veya .csv dosyaları yüklenebilir.']);

        $rows = $this->readRows($request);
        $stats = ['sayilan' => 0, 'atlanan' => 0];

        foreach ($rows as $i => $row) {
            $code = trim((string) ($row[0] ?? ''));
            $name = trim((string) ($row[1] ?? ''));
            $counted = trim((string) ($row[4] ?? ''));

            if ($counted === '' || ! is_numeric($counted)) {
                $stats['atlanan']++;
                continue;
            }

            $product = $code !== ''
                ? Product::where('code', $code)->first()
                : Product::where('name', $name)->first();

            if (! $product) {
                $stats['atlanan']++;
                continue;
            }

            // Güncelleme, model olayı üzerinden "düzenleme" stok hareketi üretir
            $product->update(['stock_quantity' => (int) $counted]);
            $stats['sayilan']++;
        }

        $message = sprintf(
            'Stok sayımı işlendi: %d ürün güncellendi, %d satır atlandı. Farklar "Düzeltme" hareketi olarak kaydedildi.',
            $stats['sayilan'], $stats['atlanan']
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    // ---------- Şablonlar ----------

    public function contactsTemplate()
    {
        return Excel::download(new \App\Exports\ContactsImportTemplate, 'cari-aktarim-sablonu.xlsx');
    }

    public function productsTemplate()
    {
        return Excel::download(new \App\Exports\ProductsImportTemplate, 'urun-aktarim-sablonu.xlsx');
    }

    // ---------- Yardımcılar ----------

    private function readRows(Request $request): array
    {
        // UploadedFile nesnesini doğrudan geçiyoruz: Linux'ta tmp dosyası uzantısız
        // olduğundan yol üzerinden tip algılaması başarısız olur, nesne orijinal
        // dosya adından (.xlsx/.csv) tipi doğru çözer
        $file = $request->file('file');
        $sheets = Excel::toArray(new class {}, $file);
        $rows = collect(reset($sheets) ?: [])->values();

        // İlk satır başlık: atla
        return $rows->skip(1)
            ->filter(fn ($row) => collect($row)->filter(fn ($cell) => trim((string) $cell) !== '')->isNotEmpty())
            ->values()
            ->all();
    }

    private function val(array $row, int $index): ?string
    {
        $value = trim((string) ($row[$index] ?? ''));

        return $value !== '' ? $value : null;
    }
}
