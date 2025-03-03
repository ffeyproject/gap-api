<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\StockGreige;
use Carbon\Carbon;

class GreigeController extends Controller
{
    //

public function rekapStockGreige(Request $request)
{
    // Validasi request
    $request->validate([
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'kondisi_greige' => 'nullable|string',
        'lot_lusi' => 'nullable|string',
        'lot_pakan' => 'nullable|string',
        'motif' => 'nullable|string',
        'asal_greige' => 'nullable|string',
    ]);

    // Ambil parameter dari request
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $status_tsd = $request->input('kondisi_greige');
    $lotLusi = $request->input('lot_lusi');
    $lotPakan = $request->input('lot_pakan');
    $motif = $request->input('motif');
    $asalGreige = $request->input('asal_greige');

    // Query stok greige dengan relasi ke tabel Greige
    $query = StockGreige::with('greige:id,nama_kain')
        ->whereBetween('date', [$startDate, $endDate])
        ->where('status', '2');

    // Tambahkan filter jika ada input dari request
    if (!empty($status_tsd)) {
        $query->where('status_tsd', $status_tsd);
    }

    if (!empty($lotLusi) && $lotLusi !== '-') {
        $query->where('lot_lusi', $lotLusi);
    }

    if (!empty($lotPakan) && $lotPakan !== '-') {
        $query->where('lot_pakan', $lotPakan);
    }

    if (!empty($motif)) {
        $query->whereHas('greige', function ($q) use ($motif) {
            $q->where('nama_kain', 'ILIKE', "%$motif%");
        });
    }

    if (!empty($asalGreige)) {
        $query->where('asal_greige', $asalGreige);
    }

    // Group by `greige_id` dan `grade`, lalu hitung total panjang_m
    // $rekapStock = $query->selectRaw('greige_id, lot_lusi, lot_pakan, status_tsd, asal_greige, grade, note, SUM(panjang_m) as total_panjang')
    // ->groupBy('greige_id', 'lot_lusi', 'lot_pakan', 'status_tsd', 'asal_greige', 'grade', 'note')
    // ->get()
    // ->groupBy(function ($item) {
    //     return $item->greige_id . '-' . $item->lot_lusi . '-' . $item->lot_pakan . '-' . $item->status_tsd . '-' . $item->asal_greige . '-' . $item->grade . '-' . $item->note;
    // })
    // ->map(function ($groupedItems) {
    //       $firstItem = $groupedItems->first();
    //     return [
    //         'greige_id' => $groupedItems->first()->greige_id,
    //         'nama_kain' => optional($groupedItems->first()->greige)->nama_kain,
    //         'grade' => $groupedItems->first()->grade,
    //         'lot_lusi' => $groupedItems->first()->lot_lusi,
    //         'lot_pakan' => $groupedItems->first()->lot_pakan,
    //         'status_tsd' => $groupedItems->first()->status_tsd,
    //         'asal_greige' => $groupedItems->first()->asal_greige,
    //         'note' => $groupedItems->first()->note,
    //         'lebar_kain' => optional($firstItem->greigeGroup)->lebar_kain,
    //         'total_panjang' => $groupedItems->sum('total_panjang'),
    //     ];
    // })->values();


    $rekapStock = $query->selectRaw('greige_id, lot_lusi, lot_pakan, status_tsd, asal_greige, grade, note, SUM(panjang_m) as total_panjang')
    ->groupBy('greige_id', 'lot_lusi', 'lot_pakan', 'status_tsd', 'asal_greige', 'grade', 'note')
    ->get()
    ->sortBy(function ($item) {
        // Urutkan berdasarkan nama_kain
        return optional($item->greige)->nama_kain;
    })
    ->groupBy(function ($item) {
        // Kelompokkan tanpa memasukkan grade agar bisa digabung
        return $item->greige_id . '-' . $item->lot_lusi . '-' . $item->lot_pakan . '-' . $item->status_tsd . '-' . $item->asal_greige . '-' . $item->note;
    })
    ->map(function ($groupedItems) {
        $firstItem = $groupedItems->first();

        return [
            'greige_id' => $firstItem->greige_id,
            'nama_kain' => optional($firstItem->greige)->nama_kain,
            'lot_lusi' => $firstItem->lot_lusi,
            'lot_pakan' => $firstItem->lot_pakan,
            'status_tsd' => $firstItem->status_tsd,
            'asal_greige' => $firstItem->asal_greige,
            'note' => $firstItem->note,
            'lebar_kain' => optional($firstItem->greigeGroup)->lebar_kain,
            'grade' => $groupedItems->mapWithKeys(function ($item) {
                return [$item->grade => $item->total_panjang];
            }),
            'total_panjang' => $groupedItems->sum('total_panjang'),
        ];
    })->values();



    // Return response JSON
    return response()->json([
        'status' => 'success',
        'data' => $rekapStock
    ]);
}



}
