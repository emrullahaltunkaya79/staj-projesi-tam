<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tahsilat (ödeme alma) servisi.
 *
 * Tahsilat kaydı tek bir veritabanı işleminde yürütülür:
 *  1. Cari satır kilidi (lockForUpdate) ile kilitlenir ve şirkete aitliği doğrulanır,
 *  2. Tahsilat tutarının güncel bakiyeyi aşıp aşmadığı kontrol edilir —
 *     aşarsa "Tahsilat tutarı güncel bakiyeyi aşamaz." hatası ile işlem rollback edilir,
 *  3. Ödeme kaydı oluşturulur ve carinin bakiyesi tahsilat tutarı kadar AZALTILIR.
 */
class PaymentService
{
    /**
     * @param  int  $companyId  Tahsilatın kaydedileceği şirket
     * @param  array  $data  contact_id, amount, payment_type, description
     * @return Payment
     *
     * @throws ValidationException Cari bulunamazsa, bakiye yetersizse veya tutar geçersizse
     */
    public function recordPayment(int $companyId, array $data): Payment
    {
        DB::beginTransaction();

        try {
            // Global scope oturum gerektirdiğinden burada elle sorguluyoruz
            $contact = Contact::where('company_id', $companyId)
                ->where('id', $data['contact_id'])
                ->lockForUpdate()
                ->first();

            if (! $contact) {
                throw ValidationException::withMessages([
                    'contact_id' => 'Seçilen cari bulunamadı.',
                ]);
            }

            $amount = (float) $data['amount'];
            $balance = (float) $contact->balance;

            if ($balance <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Bu carinin tahsil edilecek bakiyesi yok.',
                ]);
            }

            // Tahsilat, carinin güncel borç bakiyesinden fazla olamaz
            if ($amount > $balance) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Tahsilat tutarı güncel bakiyeyi aşamaz. %s carisinin güncel bakiyesi: %s ₺.',
                        $contact->name,
                        number_format($balance, 2, ',', '.')
                    ),
                ]);
            }

            $payment = Payment::create([
                'company_id' => $companyId,
                'contact_id' => $contact->id,
                'amount' => $amount,
                'payment_type' => $data['payment_type'],
                'direction' => 'tahsilat',
                'description' => $data['description'] ?? null,
            ]);

            // Tahsilat alındı: carinin bize olan borcu azalır
            $contact->decrement('balance', $amount);

            DB::commit();

            return $payment->fresh('contact');
        } catch (\Throwable $e) {
            // Herhangi bir hata tüm işlemi geri alır
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Tedarikçiye ödeme: alış faturalarıyla oluşan borcu (negatif bakiye) azaltır.
     * Ödeme tutarı güncel borcu aşamaz.
     *
     * @throws ValidationException
     */
    public function paySupplier(int $companyId, array $data): Payment
    {
        DB::beginTransaction();

        try {
            $contact = Contact::where('company_id', $companyId)
                ->where('id', $data['contact_id'])
                ->lockForUpdate()
                ->first();

            if (! $contact) {
                throw ValidationException::withMessages([
                    'contact_id' => 'Seçilen cari bulunamadı.',
                ]);
            }

            if ($contact->type !== 'tedarikci') {
                throw ValidationException::withMessages([
                    'contact_id' => 'Ödeme yalnızca tedarikçi carilere yapılabilir. ("'.$contact->name.'" bir müşteri.)',
                ]);
            }

            $amount = (float) $data['amount'];
            $debt = abs((float) $contact->balance);

            if ($debt <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Bu tedarikçiye ödenecek borç yok.',
                ]);
            }

            if ($amount > $debt) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Ödeme tutarı güncel borcu aşamaz. %s tedarikçisine olan borç: %s ₺.',
                        $contact->name,
                        number_format($debt, 2, ',', '.')
                    ),
                ]);
            }

            $payment = Payment::create([
                'company_id' => $companyId,
                'contact_id' => $contact->id,
                'amount' => $amount,
                'payment_type' => $data['payment_type'],
                'direction' => 'odeme',
                'description' => $data['description'] ?? null,
            ]);

            // Borç ödendi: bakiye sıfıra doğru artar
            $contact->increment('balance', $amount);

            DB::commit();

            return $payment->fresh('contact');
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
