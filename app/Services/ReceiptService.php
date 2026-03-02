<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReceiptService
{
    /**
     * Generate a PDF receipt for a donation.
     *
     * @param string $type ('fitrah', 'mall', 'sedekah')
     * @param object $donation
     * @return string Path to the generated PDF
     */
    public function generateReceipt($type, $donation)
    {
        $data = $this->prepareData($type, $donation);
        $fileName = 'Receipt_' . $type . '_' . $donation->id . '_' . Str::random(8) . '.pdf';
        $path = 'receipts/' . $fileName;

        $pdf = Pdf::loadView('reports.receipt', $data);
        
        // Ensure directory exists
        if (!Storage::disk('public')->exists('receipts')) {
            Storage::disk('public')->makeDirectory('receipts');
        }

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Get the public URL for a receipt.
     */
    public function getReceiptUrl($path)
    {
        return asset('storage/' . $path);
    }

    /**
     * Prepare data for the blade template.
     */
    protected function prepareData($type, $donation)
    {
        $common = [
            'receipt_no' => 'REC/' . strtoupper($type) . '/' . date('Ymd') . '/' . str_pad($donation->id, 4, '0', STR_PAD_LEFT),
            'date' => $donation->tanggal ?? $donation->created_at->toDateString(),
            'type_label' => $this->getTypeLabel($type),
        ];

        switch ($type) {
            case 'fitrah':
                return array_merge($common, [
                    'donor_name' => $donation->nama,
                    'amount_money' => $donation->jumlah_uang,
                    'amount_rice' => $donation->jumlah_beras_kg,
                    'jiwa' => $donation->jumlah_jiwa,
                    'category' => 'Zakat Fitrah',
                    'note' => 'Pembayaran Zakat Fitrah tahun ' . ($donation->tahun ?? date('Y')),
                ]);
            case 'mall':
                return array_merge($common, [
                    'donor_name' => $donation->nama_muzaki ?? 'Hamba Allah',
                    'amount_money' => $donation->jumlah,
                    'amount_rice' => 0,
                    'jiwa' => 1,
                    'category' => $donation->kategori ?? 'Zakat Mall',
                    'note' => $donation->keterangan ?? 'Pembayaran Zakat Mall',
                ]);
            case 'sedekah':
                return array_merge($common, [
                    'donor_name' => $donation->nama_donatur ?? 'Hamba Allah',
                    'amount_money' => $donation->jumlah,
                    'amount_rice' => 0,
                    'jiwa' => 1,
                    'category' => 'Infak & Sedekah',
                    'note' => $donation->tujuan ?? 'Infaq/Sedekah via Baitulmal',
                ]);
        }

        return $common;
    }

    protected function getTypeLabel($type)
    {
        return [
            'fitrah' => 'Zakat Fitrah',
            'mall' => 'Zakat Maal',
            'sedekah' => 'Infak / Sedekah'
        ][$type] ?? 'Donasi';
    }
}
