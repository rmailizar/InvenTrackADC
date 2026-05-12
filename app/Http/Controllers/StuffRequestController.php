<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StuffRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StuffRequestController extends Controller
{
    /**
     * Public landing page: stock recap + request form (no auth required).
     */
    public function publicIndex(Request $request)
    {
        $requestedBidang = $request->input('bidang', old('bidang'));
        $activeBidang = in_array($requestedBidang, ['teknik', 'umum'], true)
            ? $requestedBidang
            : 'umum';

        $query = Item::query()->where('bidang', $activeBidang);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('no_normalisasi', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $items = $query->orderBy('name')->get();
        $categories = Item::where('bidang', $activeBidang)->select('category')->distinct()->pluck('category');
        $allItems = Item::where('bidang', $activeBidang)->orderBy('name')->get(); // for the select dropdown

        return view('public.stuff-request', compact('items', 'categories', 'allItems', 'activeBidang'));
    }

    /**
     * Store a stock request (no auth required).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'requester_name' => 'required|string|max:255',
            'nip' => 'required|string|max:50',
            'jabatan' => 'required|string|max:100',
            'bidang' => 'required|in:teknik,umum',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ], [
            'requester_name.required' => 'Nama wajib diisi.',
            'nip.required' => 'NIP wajib diisi.',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'bidang.required' => 'Bidang wajib diisi.',
            'lines.required' => 'Tambahkan minimal satu barang.',
            'lines.min' => 'Tambahkan minimal satu barang.',
            'lines.*.item_id.required' => 'Pilih barang pada setiap baris.',
            'lines.*.item_id.exists' => 'Barang tidak ditemukan.',
            'lines.*.quantity.required' => 'Jumlah wajib diisi.',
            'lines.*.quantity.min' => 'Jumlah minimal 1.',
        ]);

        $linesInput = $validated['lines'];
        unset($validated['lines']);

        $mergedByItem = [];
        foreach ($linesInput as $line) {
            $id = (int) $line['item_id'];
            $mergedByItem[$id] = ($mergedByItem[$id] ?? 0) + (int) $line['quantity'];
        }

        $itemsById = Item::whereIn('id', array_keys($mergedByItem))
            ->where('bidang', $validated['bidang'])
            ->get()
            ->keyBy('id');

        if ($itemsById->count() !== count($mergedByItem)) {
            throw ValidationException::withMessages([
                'lines' => 'Barang yang dipilih tidak sesuai dengan bidang permintaan.',
            ]);
        }

        $stockErrors = [];

        foreach ($mergedByItem as $itemId => $quantity) {
            $item = $itemsById->get($itemId);
            if ($item && $quantity > $item->current_stock) {
                $stockErrors[] = "Jumlah {$item->name} melebihi stok tersedia ({$item->current_stock} {$item->unit}).";
            }
        }

        if ($stockErrors !== []) {
            throw ValidationException::withMessages([
                'lines' => $stockErrors,
            ]);
        }

        DB::transaction(function () use ($validated, $mergedByItem) {
            $validated['status'] = 'pending';
            $stuffRequest = StuffRequest::create($validated);
            foreach ($mergedByItem as $itemId => $quantity) {
                $stuffRequest->lines()->create([
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                ]);
            }
        });

        return redirect()->route('public.stuff-request', ['bidang' => $validated['bidang']])
            ->with('success', 'Permintaan barang berhasil dikirim! Permintaan Anda akan ditinjau oleh Admin.');
    }

    /**
     * Admin/staff: list stock requests with date filter.
     */
    public function adminIndex(Request $request)
    {
        $query = StuffRequest::with(['lines.item', 'processor', 'completer'])
            ->visibleFor(auth()->user())
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('requester_name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhereHas('lines.item', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $requests = $query->paginate(15)->withQueryString();

        $pendingCount = StuffRequest::visibleFor(auth()->user())->pending()->count();

        return view('stuff-requests.index', compact('requests', 'pendingCount'));
    }

    /**
     * Approve a stock request.
     */
    public function approve(StuffRequest $stuffRequest)
    {
        $this->authorizeRequestDepartment($stuffRequest);
        abort_if($stuffRequest->bidang === 'teknik', 403, 'Permintaan barang Teknik langsung diselesaikan tanpa tahap approve/reject.');

        if ($stuffRequest->status !== 'pending') {
            return back()->with('error', 'Request ini sudah diproses.');
        }

        $stuffRequest->update([
            'status' => 'approved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', "Stuff Request dari {$stuffRequest->requester_name} telah disetujui.");
    }

    /**
     * Reject a stock request.
     */
    public function reject(StuffRequest $stuffRequest)
    {
        $this->authorizeRequestDepartment($stuffRequest);
        abort_if($stuffRequest->bidang === 'teknik', 403, 'Stuff Request Teknik langsung diselesaikan tanpa tahap approve/reject.');

        if ($stuffRequest->status !== 'pending') {
            return back()->with('error', 'Request ini sudah diproses.');
        }

        $stuffRequest->update([
            'status' => 'rejected',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', "Stuff Request dari {$stuffRequest->requester_name} telah ditolak.");
    }

    /**
     * Mark an approved stock request as completed.
     */
    public function complete(StuffRequest $stuffRequest)
    {
        $this->authorizeRequestDepartment($stuffRequest);

        $allowedStatus = $stuffRequest->bidang === 'teknik' ? 'pending' : 'approved';

        if ($stuffRequest->status !== $allowedStatus) {
            return back()->with('error', $stuffRequest->bidang === 'teknik'
                ? 'Hanya request pending Bidang Teknik yang bisa langsung diselesaikan.'
                : 'Hanya request yang sudah disetujui yang bisa diselesaikan.');
        }

        $stuffRequest->load('lines.item');

        try {
            DB::transaction(function () use ($stuffRequest) {
                foreach ($stuffRequest->lines as $line) {
                    if ($line->item && $line->item->current_stock < $line->quantity) {
                        throw new \RuntimeException("Stok {$line->item->name} tidak mencukupi.");
                    }
                }

                foreach ($stuffRequest->lines as $line) {
                    Transaction::create([
                        'item_id' => $line->item_id,
                        'user_id' => auth()->id(),
                        'bidang' => $stuffRequest->bidang,
                        'no_normalisasi' => $line->item->no_normalisasi,
                        'lokasi' => $line->item->lokasi,
                        'volume' => (int) $line->quantity,
                        'ship_unloader' => $line->item->ship_unloader,
                        'date' => now()->toDateString(),
                        'type' => 'out',
                        'quantity' => (int) $line->quantity,
                        'price' => 0,
                        'description' => "Otomatis dibuat dari Permintaan Barang ID: {$stuffRequest->id}",
                        'status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                }

                $updatePayload = [
                    'status' => 'completed',
                    'completed_by' => auth()->id(),
                    'completed_at' => now(),
                ];

                if ($stuffRequest->bidang === 'teknik') {
                    $updatePayload['processed_by'] = auth()->id();
                    $updatePayload['processed_at'] = now();
                }

                $stuffRequest->update($updatePayload);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Permintaan barang dari {$stuffRequest->requester_name} telah diselesaikan dan transaksi OUT approved otomatis dibuat.");
    }

    /**
     * Cancel an approved stock request, or a pending Teknik request by admin.
     */
    public function cancel(StuffRequest $stuffRequest)
    {
        $this->authorizeRequestDepartment($stuffRequest);

        $user = auth()->user();
        $canCancelPendingTeknik = $stuffRequest->bidang === 'teknik'
            && $stuffRequest->status === 'pending'
            && $user->isAdmin()
            && $user->isTeknik();

        if ($stuffRequest->status !== 'approved' && !$canCancelPendingTeknik) {
            return back()->with('error', 'Hanya request yang sudah disetujui, atau request pending Bidang Teknik oleh admin Teknik, yang bisa dibatalkan.');
        }

        $stuffRequest->update([
            'status' => 'cancelled',
            'completed_by' => auth()->id(),
            'completed_at' => now(),
        ]);

        return back()->with('success', "Permintaan barang dari {$stuffRequest->requester_name} telah dibatalkan.");
    }

    private function authorizeRequestDepartment(StuffRequest $stuffRequest): void
    {
        abort_unless(auth()->user()->canAccessBidang($stuffRequest->bidang), 403, 'Anda tidak memiliki akses ke permintaan bidang ini.');
    }
}
