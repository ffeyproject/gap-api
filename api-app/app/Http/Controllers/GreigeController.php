<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\StockGreige;
use App\StockGreigeOpname;
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
    $query = StockGreige::with('greige:id,nama_kain,available')
        ->whereBetween('date', [$startDate, $endDate])
        ->where('status', '2')
        ->where('jenis_gudang', 1);

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


    $rekapStock = $query->selectRaw('
        greige_id, 
        lot_lusi, 
        lot_pakan, 
        status_tsd, 
        asal_greige, 
        grade, 
        note, 
        SUM(panjang_m) as total_panjang,
        SUM(CASE WHEN (is_hasil_setting = true OR lokasi_setting IS NOT NULL) AND lokasi_setting = 1 THEN panjang_m ELSE 0 END) as total_setting_gap,
        SUM(CASE WHEN (is_hasil_setting = true OR lokasi_setting IS NOT NULL) AND lokasi_setting = 2 THEN panjang_m ELSE 0 END) as total_setting_luar,
        SUM(CASE WHEN is_hasil_setting = true AND (lokasi_setting IS NULL OR lokasi_setting NOT IN (1, 2)) THEN panjang_m ELSE 0 END) as total_setting_lain,
        SUM(CASE WHEN is_hasil_setting = true THEN panjang_m ELSE 0 END) as total_hasil_setting,
        SUM(CASE WHEN lokasi_setting IS NOT NULL THEN panjang_m ELSE 0 END) as total_lokasi_setting,
        COUNT(CASE WHEN (is_hasil_setting = true OR lokasi_setting IS NOT NULL) AND lokasi_setting = 1 THEN 1 END) as roll_setting_gap,
        COUNT(CASE WHEN (is_hasil_setting = true OR lokasi_setting IS NOT NULL) AND lokasi_setting = 2 THEN 1 END) as roll_setting_luar,
        COUNT(CASE WHEN is_hasil_setting = true AND (lokasi_setting IS NULL OR lokasi_setting NOT IN (1, 2)) THEN 1 END) as roll_setting_lain,
        COUNT(CASE WHEN is_hasil_setting = true THEN 1 END) as roll_hasil_setting,
        COUNT(CASE WHEN lokasi_setting IS NOT NULL THEN 1 END) as roll_lokasi_setting
    ')
    ->groupBy('greige_id', 'lot_lusi', 'lot_pakan', 'status_tsd', 'asal_greige', 'grade', 'note')
    ->get()
    ->sortBy(function ($item) {
        // Urutkan berdasarkan nama_kain
        return optional($item->greige)->nama_kain;
    })
    ->groupBy(function ($item) {
        // Kelompokkan tanpa memasukkan grade agar bisa digabung
        return $item->greige_id . '-' . $item->lot_lusi . '-' . $item->lot_pakan . '-' . $item->status_tsd . '-' . $item->asal_greige . '-' . $item->note . '-' . $item->available;
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
            'available' => optional($firstItem->greige)->available,
            'lebar_kain' => optional($firstItem->greigeGroup)->lebar_kain,
            'grade' => $groupedItems->mapWithKeys(function ($item) {
                return [$item->grade => $item->total_panjang];
            }),
            'total_panjang' => (float)$groupedItems->sum('total_panjang'),
            'total_setting_gap' => (float)$groupedItems->sum('total_setting_gap'),
            'total_setting_luar' => (float)$groupedItems->sum('total_setting_luar'),
            'total_setting_lain' => (float)$groupedItems->sum('total_setting_lain'),
            'total_hasil_setting' => (float)$groupedItems->sum('total_hasil_setting'),
            'total_lokasi_setting' => (float)$groupedItems->sum('total_lokasi_setting'),
            'roll_setting_gap' => (int)$groupedItems->sum('roll_setting_gap'),
            'roll_setting_luar' => (int)$groupedItems->sum('roll_setting_luar'),
            'roll_setting_lain' => (int)$groupedItems->sum('roll_setting_lain'),
            'roll_hasil_setting' => (int)$groupedItems->sum('roll_hasil_setting'),
            'roll_lokasi_setting' => (int)$groupedItems->sum('roll_lokasi_setting'),
        ];
    })->values();



    // Return response JSON
    return response()->json([
        'status' => 'success',
        'data' => $rekapStock
    ]);
}

public function rekapStockGreigeOpname(Request $request)
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

    // Query stok greige opname dengan relasi ke tabel Greige
    $query = StockGreigeOpname::with('greige:id,nama_kain,available')
        ->whereBetween('date', [$startDate, $endDate])
        ->where('status', '2')
        ->where('jenis_gudang', 1);

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

    $rekapStock = $query->selectRaw('greige_id, lot_lusi, lot_pakan, status_tsd, asal_greige, grade, note, SUM(panjang_m) as total_panjang')
        ->groupBy('greige_id', 'lot_lusi', 'lot_pakan', 'status_tsd', 'asal_greige', 'grade', 'note')
        ->get()
        ->sortBy(function ($item) {
            // Urutkan berdasarkan nama_kain
            return optional($item->greige)->nama_kain;
        })
        ->groupBy(function ($item) {
            // Kelompokkan tanpa memasukkan grade agar bisa digabung
            $available = optional($item->greige)->available;
            return $item->greige_id . '-' . $item->lot_lusi . '-' . $item->lot_pakan . '-' . $item->status_tsd . '-' . $item->asal_greige . '-' . $item->note . '-' . $available;
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
                'available' => optional($firstItem->greige)->available,
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

/**
 * Menyimpan snapshot stock harian per motif dari Packing List Greige (trn_stock_greige fresh valid)
 *
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function saveDailyStock(Request $request)
{
    $request->validate([
        'date' => 'nullable|date_format:Y-m-d',
    ]);

    $date = $request->input('date', date('Y-m-d'));
    $userId = $this->getAuthenticatedUserId($request);

    try {
        $result = \App\StockGreigeDaily::snapshotStock($date, $userId);

        $msg = sprintf(
            'Berhasil menyimpan stock harian tanggal %s. Total %d motif (%s m, %d roll). Baru: %d, Terupdate: %d.',
            $result['date'],
            $result['total_motifs'],
            number_format($result['grand_total_m'], 2, ',', '.'),
            $result['grand_total_roll'],
            $result['new_saved'],
            $result['updated']
        );

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => $msg,
            'data' => $result,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'status' => 'error',
            'message' => 'Gagal menyimpan snapshot stock harian: ' . $e->getMessage(),
        ], 500);
    }
}

/**
 * Mengambil data rekap stock harian (trn_stock_greige_daily)
 *
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function getDailyStock(Request $request)
{
    $request->validate([
        'date' => 'nullable|date_format:Y-m-d',
        'start_date' => 'nullable|date_format:Y-m-d',
        'end_date' => 'nullable|date_format:Y-m-d',
        'greige_id' => 'nullable|integer',
        'greige_group_id' => 'nullable|integer',
        'motif' => 'nullable|string',
    ]);

    $date = $request->input('date');
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $greigeId = $request->input('greige_id');
    $greigeGroupId = $request->input('greige_group_id');
    $motif = $request->input('motif');

    $query = \App\StockGreigeDaily::with(['greige:id,nama_kain,alias,group_id', 'greigeGroup:id,nama_kain,lebar_kain', 'createdBy:id,username,full_name']);

    if (!empty($date)) {
        $query->where('date', $date);
    } elseif (!empty($startDate) && !empty($endDate)) {
        $query->whereBetween('date', [$startDate, $endDate]);
    }

    if (!empty($greigeId)) {
        $query->where('greige_id', $greigeId);
    }

    if (!empty($greigeGroupId)) {
        $query->where('greige_group_id', $greigeGroupId);
    }

    if (!empty($motif)) {
        $query->whereHas('greige', function ($q) use ($motif) {
            $q->where('nama_kain', 'ILIKE', "%{$motif}%")
              ->orWhere('alias', 'ILIKE', "%{$motif}%");
        });
    }

    $data = $query->orderBy('date', 'desc')->orderBy('greige_id', 'asc')->get();

    $summary = [
        'total_records' => $data->count(),
        'grand_total_m' => (float)$data->sum('total_panjang'),
        'grand_total_roll' => (int)$data->sum('total_roll'),
        'total_grade_a' => (float)$data->sum('grade_a'),
        'total_grade_b' => (float)$data->sum('grade_b'),
        'total_grade_c' => (float)$data->sum('grade_c'),
        'total_grade_d' => (float)$data->sum('grade_d'),
        'total_grade_e' => (float)$data->sum('grade_e'),
        'total_grade_ng' => (float)$data->sum('grade_ng'),
        'total_grade_lain' => (float)$data->sum('grade_lain'),
    ];

    return response()->json([
        'success' => true,
        'status' => 'success',
        'summary' => $summary,
        'data' => $data,
    ]);
}

    protected function getAuthenticatedUserId(?Request $request = null)
    {
        $request = $request ?: request();
        $authHeader = $request->header('Authorization', '');
        $token = trim(str_replace('Bearer ', '', $authHeader));

        if (empty($token)) {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payloadJson = base64_decode(strtr($parts[1], '-_', '+/'));
            if ($payloadJson) {
                $payload = json_decode($payloadJson, true);
                if (isset($payload['sub']) && is_numeric($payload['sub'])) {
                    return (int)$payload['sub'];
                }
            }
        }

        try {
            $user = \App\User::where('verification_token', $token)->first();
            if ($user) {
                return (int)$user->id;
            }
        } catch (\Throwable $e) {
        }

        return null;
    }
}
