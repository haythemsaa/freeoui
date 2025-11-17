<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ExportService
{
    /**
     * Export data to CSV
     */
    public function toCSV(Collection $data, array $headers, string $filename = 'export.csv'): string
    {
        try {
            $path = storage_path('app/exports/' . $filename);

            // Ensure directory exists
            $dir = dirname($path);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            $file = fopen($path, 'w');

            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write headers
            fputcsv($file, $headers, ';');

            // Write data rows
            foreach ($data as $row) {
                $rowData = [];
                foreach ($headers as $key => $header) {
                    $rowData[] = $this->formatValue($row[$key] ?? '');
                }
                fputcsv($file, $rowData, ';');
            }

            fclose($file);

            Log::info('CSV export created', ['filename' => $filename, 'rows' => $data->count()]);

            return $path;
        } catch (\Exception $e) {
            Log::error('CSV export failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Export transactions to CSV
     */
    public function exportTransactions(int $merchantId, ?Carbon $from = null, ?Carbon $to = null): string
    {
        $query = \App\Models\QRCode::query()
            ->join('advantages', 'qr_codes.advantage_id', '=', 'advantages.id')
            ->join('users', 'qr_codes.user_id', '=', 'users.id')
            ->where('advantages.merchant_id', $merchantId)
            ->where('qr_codes.status', 'used')
            ->select([
                'qr_codes.id',
                'qr_codes.code',
                'advantages.title as advantage_title',
                'users.full_name as customer_name',
                'users.phone_number as customer_phone',
                'qr_codes.original_amount',
                'qr_codes.discounted_amount',
                'qr_codes.validated_at',
            ]);

        if ($from) {
            $query->where('qr_codes.validated_at', '>=', $from);
        }

        if ($to) {
            $query->where('qr_codes.validated_at', '<=', $to);
        }

        $transactions = $query->orderBy('qr_codes.validated_at', 'desc')->get();

        $data = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'code' => $transaction->code,
                'advantage' => $transaction->advantage_title,
                'customer_name' => $transaction->customer_name,
                'customer_phone' => $transaction->customer_phone,
                'original_amount' => number_format($transaction->original_amount, 3, ',', ' '),
                'discounted_amount' => number_format($transaction->discounted_amount, 3, ',', ' '),
                'savings' => number_format($transaction->original_amount - $transaction->discounted_amount, 3, ',', ' '),
                'validated_at' => Carbon::parse($transaction->validated_at)->format('d/m/Y H:i'),
            ];
        });

        $headers = [
            'id' => 'ID',
            'code' => 'Code QR',
            'advantage' => 'Avantage',
            'customer_name' => 'Client',
            'customer_phone' => 'Téléphone',
            'original_amount' => 'Montant original (TND)',
            'discounted_amount' => 'Montant après réduction (TND)',
            'savings' => 'Économies (TND)',
            'validated_at' => 'Date de validation',
        ];

        $filename = 'transactions_' . $merchantId . '_' . now()->format('Y-m-d_His') . '.csv';

        return $this->toCSV($data, $headers, $filename);
    }

    /**
     * Export analytics to CSV
     */
    public function exportAnalytics(int $merchantId, ?Carbon $from = null, ?Carbon $to = null): string
    {
        $from = $from ?? now()->subDays(30);
        $to = $to ?? now();

        $analytics = \App\Models\QRCode::query()
            ->join('advantages', 'qr_codes.advantage_id', '=', 'advantages.id')
            ->where('advantages.merchant_id', $merchantId)
            ->where('qr_codes.status', 'used')
            ->whereBetween('qr_codes.validated_at', [$from, $to])
            ->selectRaw('
                DATE(qr_codes.validated_at) as date,
                advantages.title as advantage_title,
                COUNT(*) as total_scans,
                SUM(qr_codes.original_amount - qr_codes.discounted_amount) as total_savings
            ')
            ->groupBy('date', 'advantage_title')
            ->orderBy('date', 'desc')
            ->get();

        $data = $analytics->map(function ($row) {
            return [
                'date' => Carbon::parse($row->date)->format('d/m/Y'),
                'advantage' => $row->advantage_title,
                'scans' => $row->total_scans,
                'savings' => number_format($row->total_savings, 3, ',', ' '),
            ];
        });

        $headers = [
            'date' => 'Date',
            'advantage' => 'Avantage',
            'scans' => 'Nombre de scans',
            'savings' => 'Économies totales (TND)',
        ];

        $filename = 'analytics_' . $merchantId . '_' . now()->format('Y-m-d_His') . '.csv';

        return $this->toCSV($data, $headers, $filename);
    }

    /**
     * Format value for CSV export
     */
    protected function formatValue($value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('d/m/Y H:i');
        }

        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        if (is_null($value)) {
            return '';
        }

        return (string) $value;
    }
}
