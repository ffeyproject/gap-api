<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DefectInspectingItem;
use App\MstKodeDefect;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DefectItemController extends Controller
{
    public function index()
    {
        $defectItems = DefectInspectingItem::all();

        return response()->json([
            'success' => true,
            'data' => $defectItems
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $defectItem = DefectInspectingItem::create($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $defectItem
        ]);
    }

    public function show($id)
    {
        $defectItem = DefectInspectingItem::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $defectItem
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $defectItem = DefectInspectingItem::findOrFail($id);
        $defectItem->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $defectItem
        ]);
    }

    public function destroy($id)
    {
        $defectItem = DefectInspectingItem::findOrFail($id);
        $defectItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Defect item deleted successfully'
        ]);
    }


//     public function countByNoUrut(Request $request)
// {
//     $year = $request->query('tahun', now()->year);

//     $rows = DB::table('defect_inspecting_items as dii')
//         ->join('mst_kode_defect as mkd', 'dii.mst_kode_defect_id', '=', 'mkd.id')
//         ->select(
//             DB::raw('EXTRACT(MONTH FROM dii.created_at) as bulan'),
//             'mkd.no_urut',
//             'mkd.nama_defect',
//             DB::raw('COUNT(*) as total')
//         )
//         ->whereRaw('EXTRACT(YEAR FROM dii.created_at) = ?', [$year])
//         ->groupBy(
//             DB::raw('EXTRACT(MONTH FROM dii.created_at)'),
//             'mkd.no_urut',
//             'mkd.nama_defect'
//         )
//         ->orderBy(DB::raw('EXTRACT(MONTH FROM dii.created_at)'))
//         ->orderBy('mkd.no_urut')
//         ->get();

//     $namaBulan = [
//         1 => 'Januari',
//         2 => 'Februari',
//         3 => 'Maret',
//         4 => 'April',
//         5 => 'Mei',
//         6 => 'Juni',
//         7 => 'Juli',
//         8 => 'Agustus',
//         9 => 'September',
//         10 => 'Oktober',
//         11 => 'November',
//         12 => 'Desember',
//     ];

//     $data = [];
//     foreach ($rows as $row) {
//         $bulanIndex = (int) $row->bulan;
//         $bulanNama = $namaBulan[$bulanIndex] ?? "Bulan-$bulanIndex";
//         $no_urut = $row->no_urut;

//         $data[$bulanNama][$no_urut] = [
//             'nama_defect' => $row->nama_defect,
//             'total' => $row->total,
//         ];
//     }

//     return response()->json([
//         'success' => true,
//         'year' => $year,
//         'data' => $data
//     ]);
// }


//     public function countByNoUrut(Request $request)
// {
//     $year = $request->query('tahun', now()->year);

//     $rows = DB::table('defect_inspecting_items as dii')
//         ->join('mst_kode_defect as mkd', 'dii.mst_kode_defect_id', '=', 'mkd.id')
//         ->select(
//             DB::raw('EXTRACT(MONTH FROM dii.created_at) as bulan'),
//             'mkd.no_urut',
//             'mkd.nama_defect',
//             DB::raw('COUNT(*) as total'),
//             DB::raw('SUM(dii.meterage) as total_meterage')
//         )
//         ->whereRaw('EXTRACT(YEAR FROM dii.created_at) = ?', [$year])
//         ->groupBy(
//             DB::raw('EXTRACT(MONTH FROM dii.created_at)'),
//             'mkd.no_urut',
//             'mkd.nama_defect'
//         )
//         ->orderBy(DB::raw('EXTRACT(MONTH FROM dii.created_at)'))
//         ->orderBy('mkd.no_urut')
//         ->get();

//     // Nama bulan dalam Bahasa Indonesia
//     $namaBulan = [
//         1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
//         5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
//         9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
//     ];

//     $data = [];
//     foreach ($rows as $row) {
//         $bulanIndex = (int) $row->bulan;
//         $bulanNama = $namaBulan[$bulanIndex] ?? "Bulan-$bulanIndex";
//         $no_urut = $row->no_urut;

//         $data[$bulanNama][$no_urut] = [
//             'nama_defect' => $row->nama_defect,
//             'total' => $row->total,
//             'total_meterage' => (float) $row->total_meterage,
//         ];
//     }

//     return response()->json([
//         'success' => true,
//         'year' => $year,
//         'data' => $data
//     ]);
// }


// public function countByNoUrut(Request $request)
// {
//     $year = $request->query('tahun', now()->year);

//     $rows = DB::table('defect_inspecting_items as dii')
//         ->join('mst_kode_defect as mkd', 'dii.mst_kode_defect_id', '=', 'mkd.id')
//         ->leftJoin('inspecting_mkl_bj_items as imi', 'dii.inspecting_mklbj_item_id', '=', 'imi.id')
//         ->leftJoin('inspecting_item as ii', 'dii.inspecting_item_id', '=', 'ii.id')
//         ->leftJoin('inspecting_mkl_bj as im', 'imi.inspecting_id', '=', 'im.id') // ✅ JOIN ke inspecting_mkl_bj
//         ->leftJoin('trn_inspecting as ti', 'ii.inspecting_id', '=', 'ti.id')     // ✅ JOIN ke trn_inspecting
//         ->select(
//             DB::raw('EXTRACT(MONTH FROM COALESCE(dii.created_at, NOW())) as bulan'),
//             'mkd.no_urut',
//             'mkd.nama_defect',
//             DB::raw('COUNT(*) as total'),
//             DB::raw('SUM(dii.meterage) as total_meterage')
//         )
//         ->whereRaw('EXTRACT(YEAR FROM COALESCE(dii.created_at, NOW())) = ?', [$year])
//         ->where('ti.status', 4)
//         ->where(function ($query) {
//             $query->where(function ($q) {
//                 $q->whereNotNull('imi.grade')
//                    ->whereIn('imi.grade', [2, 3]);
//             })->orWhere(function ($q) {
//                 $q->whereNotNull('ii.grade')
//                    ->whereIn('ii.grade', [2, 3]);
//             });
//         })
//         ->where(function ($query1) {
//             $query1->where(function ($q) {
//                 $q->whereNotNull('imi.grade')
//                    ->whereIn('imi.grade', [2, 3]);
//             })->orWhere(function ($q) {
//                 $q->whereNotNull('ii.grade')
//                    ->whereIn('ii.grade', [2, 3]);
//             });
//         })
//         ->groupBy(
//             DB::raw('EXTRACT(MONTH FROM COALESCE(dii.created_at, NOW()))'),
//             'mkd.no_urut',
//             'mkd.nama_defect'
//         )
//         ->orderBy(DB::raw('EXTRACT(MONTH FROM COALESCE(dii.created_at, NOW()))'))
//         ->orderBy('mkd.no_urut')
//         ->get();

//     $namaBulan = [
//         1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
//         5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
//         9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
//     ];

//     $data = [];
//     foreach ($rows as $row) {
//         $bulanIndex = (int) $row->bulan;
//         $bulanNama = $namaBulan[$bulanIndex] ?? "Bulan-$bulanIndex";
//         $no_urut = $row->no_urut;

//         $data[$bulanNama][$no_urut] = [
//             'nama_defect' => $row->nama_defect,
//             'total' => $row->total,
//             'total_meterage' => (float) $row->total_meterage,
//         ];
//     }

//     return response()->json([
//         'success' => true,
//         'year' => $year,
//         'data' => $data
//     ]);
// }

public function countByNoUrut(Request $request)
{
    $year = $request->query('tahun', now()->year);

    $items = DefectInspectingItem::with([
            'mstKodeDefect',
            'inspectingItem.inspecting',
            'inspectingMklbjItem.inspectingMklbj',
        ])
        ->whereYear('created_at', $year)
        ->whereHas('mstKodeDefect', function ($q) {
            $q->whereNotNull('no_urut');
        })
        ->where(function ($query) {
            // Filter status 4 (Inspecting) atau 3 (InspectingMklbj)
            $query->whereHas('inspectingItem.inspecting', function ($q) {
                $q->where('status', 4);
            })->orWhereHas('inspectingMklbjItem.inspectingMklbj', function ($q) {
                $q->where('status', 3);
            });
        })
        ->where(function ($query) {
            $query->where(function ($q) {
                $q->whereHas('inspectingItem', function ($sub) {
                    $sub->whereIn('grade', [2, 3]);
                })->orWhereDoesntHave('inspectingItem');
            })->where(function ($q) {
                $q->whereHas('inspectingMklbjItem', function ($sub) {
                    $sub->whereIn('grade', [2, 3]);
                })->orWhereDoesntHave('inspectingMklbjItem');
            });
        })
        ->get();

    // Inisialisasi nama bulan
    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $data = [];

    foreach ($items as $item) {
        $bulan = optional($item->created_at)->format('n'); // bulan numerik (1–12)
        if (!$bulan) continue;

        $bulanNama = $namaBulan[(int) $bulan] ?? "Bulan-$bulan";
        $no_urut = optional($item->mstKodeDefect)->no_urut;
        $nama_defect = optional($item->mstKodeDefect)->nama_defect;

        if ($no_urut === null) continue;

        if (!isset($data[$bulanNama][$no_urut])) {
            $data[$bulanNama][$no_urut] = [
                'nama_defect' => $nama_defect,
                'total' => 0,
                'total_meterage' => 0.0,
            ];
        }

        $data[$bulanNama][$no_urut]['total'] += 1;
        $data[$bulanNama][$no_urut]['total_meterage'] += (float) $item->meterage;
    }

    return response()->json([
        'success' => true,
        'year' => $year,
        'data' => $data
    ]);
}


}
