<?php

namespace App\Http\Controllers;

use App\Imports\ItemImport;
use App\Imports\TransactionImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    public function index()
    {
        return view('import.index');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
            'type' => 'required|in:items,transactions',
        ], [
            'file.required' => 'File wajib dipilih.',
            'file.mimes'    => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max'      => 'Ukuran file maksimal 5MB.',
            'type.required' => 'Tipe import wajib dipilih.',
        ]);

        $type = $request->type;
        $file = $request->file('file');

        try {
            if ($type === 'items') {
                $import = new ItemImport(auth()->user());
                Excel::import($import, $file);

                $successCount = $import->getRowCount();
                $skipCount = $import->getSkipCount();
                $failures = $import->failures();

                $message = "Import Barang selesai: {$successCount} baris berhasil diimport.";
                if ($skipCount > 0) {
                    $message .= " {$skipCount} baris dilewati (duplikat nama/no normalisasi).";
                }

                return redirect()->route('import.index')->with('success', $message)
                    ->with('import_failures', $this->failuresForSession($failures))
                    ->with('import_type', 'Barang');

            } else {
                $import = new TransactionImport(auth()->user());
                Excel::import($import, $file);

                $successCount = $import->getRowCount();
                $unmatchedItems = $import->getUnmatchedItems();
                $failures = $import->failures();

                $message = "Import Transaksi selesai: {$successCount} baris berhasil diimport.";
                if (count($unmatchedItems) > 0) {
                    $message .= " Barang tidak ditemukan: " . implode(', ', $unmatchedItems) . ".";
                }

                return redirect()->route('import.index')->with('success', $message)
                    ->with('import_failures', $this->failuresForSession($failures))
                    ->with('import_type', 'Transaksi');
            }
        } catch (\Throwable $e) {
            Log::error('Import error', ['message' => $e->getMessage(), 'type' => $type]);
            return redirect()->route('import.index')->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    public function downloadTemplate($type)
    {
        abort_unless(in_array($type, ['items', 'transactions'], true), 404);

        $isTeknik = auth()->user()?->bidang === 'teknik';
        $filename = match (true) {
            $type === 'items' && $isTeknik => 'template_barang_teknik.xlsx',
            $type === 'transactions' && $isTeknik => 'template_transaksi_teknik.xlsx',
            $type === 'items' => 'template_barang.xlsx',
            default => 'template_transaksi.xlsx',
        };
        $path = public_path("templates/{$filename}");

        if (!$isTeknik && file_exists($path)) {
            return response()->download($path);
        }

        return $this->generateTemplate($type, $isTeknik);
    }

    private function generateTemplate(string $type, bool $isTeknik = false)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($type === 'items') {
            $sheet->setTitle('Template Barang');
            if ($isTeknik) {
                $headers = ['no_normalisasi', 'nama_barang', 'kategori', 'komponen', 'lokasi', 'volume', 'satuan', 'min_stok'];
                $sample = ['SU-01-LAN-001', 'Kabel LAN Cat6 305m', 'Spare Part', 'Jaringan', 'Gudang Teknik A1', 25, 'Box', 2];
            } else {
                $headers = ['nama_barang', 'kategori', 'satuan', 'min_stok'];
                $sample = ['Kertas HVS A4', 'ATK', 'Rim', 10];
            }
        } else {
            $sheet->setTitle('Template Transaksi');
            if ($isTeknik) {
                $headers = ['tanggal', 'jenis', 'no_normalisasi', 'nama_barang', 'ship_unloader', 'lokasi', 'jumlah', 'satuan', 'keterangan'];
                $sample = ['17/04/2026', 'in', 'SU-01-LAN-001', 'Kabel LAN Cat6 305m', '1,2', 'Gudang Teknik A1', 5, 'Box', 'Goods receipt teknik'];
            } else {
                $headers = ['tanggal', 'nama_barang', 'jenis', 'jumlah', 'harga', 'keterangan'];
                $sample = ['17/04/2026', 'Kertas HVS A4', 'in', 50, 55000, 'Pembelian bulanan'];
            }
        }

        // Set headers with styling
        foreach ($headers as $i => $header) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $header);
            $sheet->getStyle("{$col}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '10B981'],
                ],
            ]);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set sample data
        foreach ($sample as $i => $value) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}2", $value);
        }

        // Generate and download
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = match (true) {
            $type === 'items' && $isTeknik => 'template_barang_teknik.xlsx',
            $type === 'transactions' && $isTeknik => 'template_transaksi_teknik.xlsx',
            $type === 'items' => 'template_barang.xlsx',
            default => 'template_transaksi.xlsx',
        };

        $tempPath = storage_path("app/{$filename}");
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    private function failuresForSession($failures): ?array
    {
        if (!$failures || $failures->count() === 0) {
            return null;
        }

        return $failures
            ->map(fn($failure) => [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
            ])
            ->values()
            ->all();
    }
}
