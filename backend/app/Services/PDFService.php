<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PDFService
{
    /**
     * Generate transaction report PDF
     */
    public function generateTransactionReport(int $merchantId, array $transactions, array $summary): string
    {
        $pdf = Pdf::loadView('pdf.transaction-report', [
            'transactions' => $transactions,
            'summary' => $summary,
            'generated_at' => now(),
        ]);

        $filename = "transactions_{$merchantId}_" . now()->format('Y-m-d_His') . '.pdf';
        $path = "reports/{$filename}";

        Storage::disk('local')->put($path, $pdf->output());

        return storage_path("app/{$path}");
    }

    /**
     * Generate monthly report PDF
     */
    public function generateMonthlyReport(int $merchantId, array $stats, string $month): string
    {
        $pdf = Pdf::loadView('pdf.monthly-report', [
            'stats' => $stats,
            'month' => $month,
            'generated_at' => now(),
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = "monthly_report_{$merchantId}_{$month}.pdf";
        $path = "reports/{$filename}";

        Storage::disk('local')->put($path, $pdf->output());

        return storage_path("app/{$path}");
    }

    /**
     * Generate QR code voucher PDF
     */
    public function generateQRVoucher(array $qrCodeData, array $advantageData): string
    {
        $pdf = Pdf::loadView('pdf.qr-voucher', [
            'qr_code' => $qrCodeData,
            'advantage' => $advantageData,
        ]);

        $pdf->setPaper([0, 0, 226.77, 340.16], 'portrait'); // 80mm x 120mm

        $filename = "voucher_{$qrCodeData['code']}.pdf";
        $path = "vouchers/{$filename}";

        Storage::disk('local')->put($path, $pdf->output());

        return storage_path("app/{$path}");
    }

    /**
     * Generate analytics report PDF
     */
    public function generateAnalyticsReport(int $merchantId, array $analytics, string $period): string
    {
        $pdf = Pdf::loadView('pdf.analytics-report', [
            'analytics' => $analytics,
            'period' => $period,
            'generated_at' => now(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = "analytics_{$merchantId}_{$period}.pdf";
        $path = "reports/{$filename}";

        Storage::disk('local')->put($path, $pdf->output());

        return storage_path("app/{$path}");
    }

    /**
     * Generate invoice PDF
     */
    public function generateInvoice(array $invoiceData): string
    {
        $pdf = Pdf::loadView('pdf.invoice', $invoiceData);

        $filename = "invoice_{$invoiceData['invoice_number']}.pdf";
        $path = "invoices/{$filename}";

        Storage::disk('local')->put($path, $pdf->output());

        return storage_path("app/{$path}");
    }
}
