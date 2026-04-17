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
                $import = new ItemImport();
                Excel::import($import, $file);

                $successCount = $import->getRowCount();
                $skipCount = $import->getSkipCount();
                $failures = $import->failures();

                $message = "✅ Import Barang selesai: {$successCount} baris berhasil diimport.";
                if ($skipCount > 0) {
                    $message .= " {$skipCount} baris dilewati (duplikat).";
                }

                return redirect()->route('import.index')->with('success', $message)
                    ->with('import_failures', $failures->count() > 0 ? $failures : null)
                    ->with('import_type', 'Barang');

            } else {
                $import = new TransactionImport();
                Excel::import($import, $file);

                $successCount = $import->getRowCount();
                $unmatchedItems = $import->getUnmatchedItems();
                $failures = $import->failures();

                $message = "✅ Import Transaksi selesai: {$successCount} baris berhasil diimport (status: pending).";
                if (count($unmatchedItems) > 0) {
                    $message .= " Barang tidak ditemukan: " . implode(', ', $unmatchedItems) . ".";
                }

                return redirect()->route('import.index')->with('success', $message)
                    ->with('import_failures', $failures->count() > 0 ? $failures : null)
                    ->with('import_type', 'Transaksi');
            }
        } catch (\Throwable $e) {
            Log::error('Import error', ['message' => $e->getMessage(), 'type' => $type]);
            return redirect()->route('import.index')->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    public function downloadTemplate($type)
    {
        $filename = $type === 'items' ? 'template_barang.xlsx' : 'template_transaksi.xlsx';
        $path = public_path("templates/{$filename}");

        if (!file_exists($path)) {
            // Generate template on the fly
            return $this->generateTemplate($type);
        }

        return response()->download($path);
    }

    private function generateTemplate(string $type)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($type === 'items') {
            $sheet->setTitle('Template Barang');
            $headers = ['nama_barang', 'kategori', 'satuan', 'min_stok'];
            $sample = ['Kertas HVS A4', 'ATK', 'Rim', 10];
        } else {
            $sheet->setTitle('Template Transaksi');
            $headers = ['tanggal', 'nama_barang', 'jenis', 'jumlah', 'harga', 'keterangan'];
            $sample = ['17/04/2026', 'Kertas HVS A4', 'masuk', 50, 55000, 'Pembelian bulanan'];
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
        $filename = $type === 'items' ? 'template_barang.xlsx' : 'template_transaksi.xlsx';

        $tempPath = storage_path("app/{$filename}");
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }
}
