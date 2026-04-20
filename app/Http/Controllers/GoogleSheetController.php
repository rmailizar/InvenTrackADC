<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GoogleSheetController extends Controller
{
    protected function ensureGoogleServicesAutoloaded(): void
    {
        if (class_exists(\Google\Service\Sheets::class)) {
            return;
        }

        $base = base_path('vendor/google/apiclient-services/src');
        if (!is_dir($base)) {
            throw new \Exception('google/apiclient-services is not installed.');
        }

        // Fallback autoloader: some environments ship vendor/ without generated classmap.
        // This maps Google\Service\* classes to vendor/google/apiclient-services/src/*
        spl_autoload_register(function (string $class) use ($base) {
            $prefix = 'Google\\Service\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix)); // e.g. Sheets\Resource\SpreadsheetsValues
            $path = $base . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

            if (is_file($path)) {
                require_once $path;
                return;
            }

            // Some classes live at the root of src (e.g. Sheets.php)
            $alt = $base . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
            if (is_file($alt)) {
                require_once $alt;
            }
        }, prepend: true);

        if (!class_exists(\Google\Service\Sheets::class)) {
            throw new \Exception('Failed to autoload Google\\Service\\Sheets. Please run Composer install/dump-autoload.');
        }
    }

    protected function getClient()
    {
        $this->ensureGoogleServicesAutoloaded();

        $client = new \Google\Client();
        $client->setApplicationName('InvenTrack');
        $client->setScopes([\Google\Service\Sheets::SPREADSHEETS]);

        $json = config('services.google_sheets.credentials_json');

        if (!$json) {
            throw new \Exception('Google credentials JSON not found in config.');
        }

        $decoded = json_decode($json, true);

        if (!$decoded) {
            throw new \Exception('Invalid Google credentials JSON format.');
        }

        $client->setAuthConfig($decoded);

        return $client;
    }

    protected function getSheetsService()
    {
        $this->ensureGoogleServicesAutoloaded();
        return new Sheets($this->getClient());
    }

    /**
     * Sync approved transactions to Google Sheets
     */
    public function syncTransactions($transactions = null)
    {
        $service = $this->getSheetsService();
        $spreadsheetId = config('services.google_sheets.spreadsheet_id');
        $sheetName = config('services.google_sheets.sheet_name');

        if (!$spreadsheetId) {
            throw new \Exception('Spreadsheet ID not configured.');
        }

        if ($transactions === null) {
            $transactions = Transaction::approved()
                ->with(['item', 'user', 'approver'])
                ->orderBy('date', 'asc')
                ->get();
        }

        if ($transactions instanceof Collection) {
            $transactions->load(['item', 'user', 'approver']);
        }

        $rows = [];
        foreach ($transactions as $tx) {
            $rows[] = [
                $tx->id,
                $tx->date->format('d/m/Y'),
                $tx->item->name ?? '-',
                $tx->item->category ?? '-',
                strtoupper($tx->type),
                $tx->quantity,
                $tx->item->unit ?? '-',
                $tx->user->name ?? '-',
                $tx->description ?? '-',
                strtoupper($tx->status),
                $tx->approver->name ?? '-',
                $tx->approved_at ? $tx->approved_at->format('d/m/Y H:i') : '-',
            ];
        }

        if (empty($rows)) {
            return;
        }

        $body = new ValueRange([
            'values' => $rows,
        ]);

        $params = [
            'valueInputOption' => 'USER_ENTERED',
        ];

        $service->spreadsheets_values->append(
            $spreadsheetId,
            'Sheet1!A:L',
            $body,
            $params
        );
    }

    /**
     * Keep Google Sheets in sync with MySQL (source of truth).
     * Clears sheet then writes header + all approved transactions.
     */
    public function syncAllApprovedToSheet(): int
    {
        $service = $this->getSheetsService();
        $spreadsheetId = config('services.google_sheets.spreadsheet_id');
        $sheetName = config('services.google_sheets.sheet_name');

        if (!$spreadsheetId) {
            throw new \Exception('Spreadsheet ID not configured.');
        }

        // Clear existing data
        $service->spreadsheets_values->clear(
            $spreadsheetId,
            'Sheet1!A:L',
            new \Google\Service\Sheets\ClearValuesRequest()
        );

        // Add headers (include internal Transaction ID for easier tracking)
        $headers = new ValueRange([
            'values' => [
                [
                    'ID',
                    'Tanggal',
                    'Nama Barang',
                    'Kategori',
                    'Jenis',
                    'Jumlah',
                    'Satuan',
                    'User',
                    'Keterangan',
                    'Status',
                    'Disetujui Oleh',
                    'Tanggal Approval',
                ]
            ],
        ]);

        $service->spreadsheets_values->update(
            $spreadsheetId,
            'Sheet1!A1:L1',
            $headers,
            ['valueInputOption' => 'USER_ENTERED']
        );

        $transactions = Transaction::approved()
            ->with(['item', 'user', 'approver'])
            ->orderBy('date', 'asc')
            ->get();

        $this->syncTransactions($transactions);

        return $transactions->count();
    }

    /**
     * Full sync: Clear sheet and re-sync all approved transactions
     */
    public function fullSync()
    {
        try {
            $count = $this->syncAllApprovedToSheet();
            return back()->with('success', 'Berhasil sinkronisasi ' . $count . ' transaksi ke Google Sheets.');
        } catch (\Exception $e) {
            Log::error('Google Sheets fullSync failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }
}
