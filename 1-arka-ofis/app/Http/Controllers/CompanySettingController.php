<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanySettingController extends Controller
{
    public function edit(Request $request)
    {
        abort_unless($request->user()->can('manage', \App\Models\User::class), 403);

        return view('settings.edit', ['company' => $request->user()->company]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->can('manage', \App\Models\User::class), 403);

        /** @var Company $company */
        $company = $request->user()->company;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Şirket adı zorunludur.',
            'logo.image' => 'Logo bir resim dosyası olmalıdır (JPG, PNG veya WebP, en fazla 4 MB).',
            'logo.max' => 'Logo en fazla 4 MB olabilir.',
        ]);

        // Değişen logo dosyalarını güncelleme başarılı OLDUKTAN sonra sil (yetim dosya kalmasın)
        $oldLogoPath = $company->logo_path;
        $newLogoPath = $company->logo_path;

        // Logo kaldırma
        if ($request->boolean('remove_logo')) {
            $newLogoPath = null;
        }

        // Yeni logo yükleme (eskisinin yerini alır; 256px'e küçültülür)
        if ($request->hasFile('logo')) {
            $newLogoPath = $request->file('logo')->store('logos');
            $this->downscaleLogo($newLogoPath);
        }

        $company->fill($validated);
        $company->logo_path = $newLogoPath;
        $company->save();

        // Model kaydedildi; eski dosyayı şimdi temizle
        if ($oldLogoPath && $oldLogoPath !== $newLogoPath) {
            Storage::delete($oldLogoPath);
        }

        return redirect()->route('ayarlar.edit')
            ->with('success', 'Şirket ayarları güncellendi.');
    }

    /**
     * Logoyu en fazla 256px'e küçültür: data URI ile her sayfaya gömüldüğü
     * için büyük dosyalar paneli şişirir.
     */
    private function downscaleLogo(string $path): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            return;
        }

        $raw = Storage::get($path);
        $src = @imagecreatefromstring($raw);
        if ($src === false) {
            return;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, 256 / max($w, $h));
        if ($scale === 1 && strlen($raw) < 100 * 1024) {
            imagedestroy($src);
            return;
        }

        $dst = imagecreatetruecolor((int) round($w * $scale), (int) round($h * $scale));
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, imagesx($dst), imagesy($dst), $w, $h);
        ob_start();
        imagepng($dst, null, 6);
        Storage::put($path, ob_get_clean());
        imagedestroy($src);
        imagedestroy($dst);
    }

    /**
     * Ödeme + entegrasyon ayarları (CSPR adres/kur, webhook URL/secret).
     */
    public function updateIntegration(Request $request)
    {
        abort_unless($request->user()->can('manage', \App\Models\User::class), 403);

        $validated = $request->validate([
            'casper_address' => ['nullable', 'string', 'max:120'],
            'casper_network' => ['required', 'in:testnet,mainnet'],
            'casper_rate' => ['required', 'numeric', 'min:0.0001'],
            'ecommerce_webhook_url' => ['nullable', 'url', 'max:500'],
            'ecommerce_webhook_secret' => ['nullable', 'string', 'max:200'],
        ], [
            'casper_network.required' => 'Ağ seçimi zorunludur.',
            'casper_rate.required' => 'CSPR/TL kuru zorunludur.',
            'ecommerce_webhook_url.url' => 'Webhook URL geçerli bir adres olmalıdır.',
        ]);

        \App\Models\Setting::set('casper_receiving_address', $validated['casper_address']);
        \App\Models\Setting::set('casper_network', $validated['casper_network']);
        \App\Models\Setting::set('casper_rate', $validated['casper_rate']);
        \App\Models\Setting::set('ecommerce_webhook_url', $validated['ecommerce_webhook_url'] ?? '');

        if ($request->filled('ecommerce_webhook_secret')) {
            \App\Models\Setting::set('ecommerce_webhook_secret', $validated['ecommerce_webhook_secret']);
        }

        return redirect()->route('ayarlar.edit')
            ->with('success', 'Entegrasyon ayarları güncellendi.');
    }

    /**
     * Yönetici için elle yedek oluşturur (spatie/laravel-backup).
     */
    public function backup(Request $request)
    {
        abort_unless($request->user()->can('manage', \App\Models\User::class), 403);

        try {
            \Illuminate\Support\Facades\Artisan::call('backup:run', ['--only-db' => true]);
            $output = \Illuminate\Support\Facades\Artisan::output();
            $lastLine = trim((string) last(array_filter(explode("\n", $output))));

            return redirect()->route('ayarlar.edit')
                ->with('success', 'Yedek oluşturuldu. '.$lastLine);
        } catch (\Throwable $e) {
            return redirect()->route('ayarlar.edit')
                ->with('error', 'Yedek oluşturulamadı: '.$e->getMessage());
        }
    }
}
