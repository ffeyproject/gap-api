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


    public function countByNoUrut(Request $request)
{
    $year = $request->query('tahun', now()->year);

    // $rows = DB::table('defect_inspecting_items as dii')
    //     ->join('mst_kode_defect as mkd', 'dii.mst_kode_defect_id', '=', 'mkd.id')
    //     ->select(
    //         DB::raw('EXTRACT(MONTH FROM dii.created_at) as bulan'),
    //         'mkd.no_urut',
    //         'mkd.nama_defect',
    //         DB::raw('COUNT(*) as total'),
    //         DB::raw('SUM(dii.meterage) as total_meterage')
    //     )
    //     ->whereRaw('EXTRACT(YEAR FROM dii.created_at) = ?', [$year])
    //     ->groupBy(
    //         DB::raw('EXTRACT(MONTH FROM dii.created_at)'),
    //         'mkd.no_urut',
    //         'mkd.nama_defect'
    //     )
    //     ->orderBy(DB::raw('EXTRACT(MONTH FROM dii.created_at)'))
    //     ->orderBy('mkd.no_urut')
    //     ->get();


    $rows = DB::table('defect_inspecting_items as dii')
    ->join('mst_kode_defect as mkd', 'dii.mst_kode_defect_id', '=', 'mkd.id')
    ->join('inspecting_mkl_bj_items as imi', 'dii.inspecting_mklbj_item_id', '=', 'imi.id')
    ->join('inspecting_item as ii', 'dii.inspecting_item_id', '=', 'ii.id')
    ->select(
        DB::raw('EXTRACT(MONTH FROM dii.created_at) as bulan'),
        'mkd.no_urut',
        'mkd.nama_defect',
        DB::raw('COUNT(*) as total'),
        DB::raw('SUM(dii.meterage) as total_meterage')
    )
    ->whereRaw('EXTRACT(YEAR FROM dii.created_at) = ?', [$year])
    ->where(function ($query) {
        $query->whereIn('imi.grade', [2, 3])
              ->orWhereIn('ii.grade', [2, 3]);
    }) // ✅ Filter jika grade B atau C di salah satu tabel
    ->groupBy(
        DB::raw('EXTRACT(MONTH FROM dii.created_at)'),
        'mkd.no_urut',
        'mkd.nama_defect'
    )
    ->orderBy(DB::raw('EXTRACT(MONTH FROM dii.created_at)'))
    ->orderBy('mkd.no_urut')
    ->get();

    // Nama bulan dalam Bahasa Indonesia
    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $data = [];
    foreach ($rows as $row) {
        $bulanIndex = (int) $row->bulan;
        $bulanNama = $namaBulan[$bulanIndex] ?? "Bulan-$bulanIndex";
        $no_urut = $row->no_urut;

        $data[$bulanNama][$no_urut] = [
            'nama_defect' => $row->nama_defect,
            'total' => $row->total,
            'total_meterage' => (float) $row->total_meterage,
        ];
    }

    return response()->json([
        'success' => true,
        'year' => $year,
        'data' => $data
    ]);
}



}