<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #ef4444; margin-bottom: 20px;">⚠️ Peringatan Stok Rendah</h2>
        <p>Halo Admin,</p>
        <p>Stok barang berikut sudah menipis dan perlu segera diisi ulang:</p>
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Nama Barang</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $item->name }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Kategori</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $item->category }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Stok Saat Ini</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; color: #ef4444;">{{ $item->current_stock }} {{ $item->unit }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Minimum Stok</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $item->min_stock }} {{ $item->unit }}</td></tr>
        </table>
        <p>Silakan segera lakukan pengisian ulang stok.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #999; font-size: 12px;">Email ini dikirim otomatis oleh sistem InvenTrack.</p>
    </div>
</body>
</html>
