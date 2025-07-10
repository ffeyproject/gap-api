<?php

namespace App\Http\Controllers;

use App\DefectInspectingItem;
use App\Inspecting;
use App\InspectingItem;
use App\InspectingMklbj;
use App\InspectingMklbjItem;
use Illuminate\Http\Request;
use App\User;
use App\KartuProsesDyeing;
use App\KartuProsesPrinting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    //
    public function index()
    {
        try {
            $token = request()->header('Authorization');
            $token = str_replace('Bearer ', '', $token);

            $users = User::where('verification_token', $token)->first();
            $kartuProsesDyeing = KartuProsesDyeing::with('wo','woColor','mo','moColor','sc','scGreige')->limit(100)->get();
            $kartuProsesPrinting = KartuProsesPrinting::with('wo','woColor','mo','moColor','sc','scGreige')->limit(100)->get();

            $myInspecting = Inspecting::with([
                'sc',
                'scGreige',
                'mo',
                'wo',
                'kartuProcessDyeing',
                'kartuProcessPrinting',
                'createdBy',
                'updatedBy',
                'approvedBy',
                'deliveredBy',
                'k3l',
                'inspectingItem.defect_item'
            ])->where('created_by', $users->id)->orderBy('created_at', 'desc')->limit(5)->get();

            $countKartuDyeing = KartuProsesDyeing::whereHas('mo', function($q){
                $q->where('process', 1)
                ->whereYear('date', date('Y'));
            })->count();
            $countKartuPrinting = KartuProsesDyeing::whereHas('mo', function($q){
                $q->where('process', 2);
            })->count();
            $countMyInspecting = Inspecting::where('created_by', $users->id)
                ->whereYear('tanggal_inspeksi', date('Y'))
                ->whereMonth('tanggal_inspeksi', date('m'))
                ->count() + InspectingMklbj::where('created_by', $users->id)
                ->whereYear('tgl_inspeksi', date('Y'))
                ->whereMonth('tgl_inspeksi', date('m'))
                ->count();

            $inspectingsPerYear = [];
            for ($month = 1; $month <= 12; $month++) {
                $countInspecting = Inspecting::where('created_by', $users->id)
                    ->whereYear('tanggal_inspeksi', date('Y'))
                    ->whereMonth('tanggal_inspeksi', $month)
                    ->count();

                $countInspectingMklbj = InspectingMklbj::where('created_by', $users->id)
                    ->whereYear('tgl_inspeksi', date('Y'))
                    ->whereMonth('tgl_inspeksi', $month)
                    ->count();

                $inspectingsPerYear[$month] = $countInspecting + $countInspectingMklbj;
            }

            $recentKartuProsesDyeing = Inspecting::with([
                'sc',
                'scGreige',
                'mo',
                'wo',
                'kartuProcessDyeing',
                'kartuProcessPrinting',
                'createdBy',
                'updatedBy',
                'approvedBy',
                'deliveredBy',
                'k3l',
                'inspectingItem.defect_item'
            ])->where('created_by', $users->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'users' => $users,
                'kartu_proses_dyeing' => $kartuProsesDyeing,
                'kartu_proses_printing' => $kartuProsesPrinting,
                'count_kartu_dyeing' => $countKartuDyeing,
                'count_kartu_printing' => $countKartuPrinting,
                'count_my_inspecting' => $countMyInspecting,
                'recent_kartu_proses_dyeing' => $recentKartuProsesDyeing,
                'inspectings_per_year' => $inspectingsPerYear,
                'my_inspecting' => $myInspecting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function grafik()
    {
        try {
            $token = request()->header('Authorization');
            $token = str_replace('Bearer ', '', $token);

            $users = User::where('verification_token', $token)->first();
            $kartuProsesDyeing = KartuProsesDyeing::with('wo','woColor','mo','moColor','sc','scGreige')->limit(100)->get();
            $kartuProsesPrinting = KartuProsesPrinting::with('wo','woColor','mo','moColor','sc','scGreige')->limit(100)->get();

            $myInspecting = Inspecting::with([
                'sc',
                'scGreige',
                'mo',
                'wo',
                'kartuProcessDyeing',
                'kartuProcessPrinting',
                'createdBy',
                'updatedBy',
                'approvedBy',
                'deliveredBy',
                'k3l',
                'inspectingItem.defect_item'
            ])->where('created_by', $users->id)->orderBy('created_at', 'desc')->limit(5)->get();

            $countKartuDyeing = KartuProsesDyeing::whereHas('mo', function($q){
                $q->where('process', 1)
                ->whereYear('date', date('Y'));
            })->count();
            $countKartuPrinting = KartuProsesDyeing::whereHas('mo', function($q){
                $q->where('process', 2);
            })->count();
            $countMyInspecting = Inspecting::where('created_by', $users->id)
                ->whereYear('tanggal_inspeksi', date('Y'))
                ->whereMonth('tanggal_inspeksi', date('m'))
                ->count() + InspectingMklbj::where('created_by', $users->id)
                ->whereYear('tgl_inspeksi', date('Y'))
                ->whereMonth('tgl_inspeksi', date('m'))
                ->count();

            $inspectingsPerYear = [];
            for ($month = 1; $month <= 12; $month++) {
                $countInspecting = Inspecting::where('created_by', $users->id)
                    ->whereYear('tanggal_inspeksi', date('Y'))
                    ->whereMonth('tanggal_inspeksi', $month)
                    ->count();

                $countInspectingMklbj = InspectingMklbj::where('created_by', $users->id)
                    ->whereYear('tgl_inspeksi', date('Y'))
                    ->whereMonth('tgl_inspeksi', $month)
                    ->count();

                $inspectingsPerYear[$month] = $countInspecting + $countInspectingMklbj;
            }

            $recentKartuProsesDyeing = Inspecting::with([
                'sc',
                'scGreige',
                'mo',
                'wo',
                'kartuProcessDyeing',
                'kartuProcessPrinting',
                'createdBy',
                'updatedBy',
                'approvedBy',
                'deliveredBy',
                'k3l',
                'inspectingItem.defect_item'
            ])->where('created_by', $users->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'users' => $users,
                'kartu_proses_dyeing' => $kartuProsesDyeing,
                'kartu_proses_printing' => $kartuProsesPrinting,
                'count_kartu_dyeing' => $countKartuDyeing,
                'count_kartu_printing' => $countKartuPrinting,
                'count_my_inspecting' => $countMyInspecting,
                'recent_kartu_proses_dyeing' => $recentKartuProsesDyeing,
                'inspectings_per_year' => $inspectingsPerYear,
                'my_inspecting' => $myInspecting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function kartuDyeing(Request $request)
    {
        try {
            $nomorKartu = $request->input('no');
            $kartuProsesDyeing = KartuProsesDyeing::with([
                'wo',
                'woColor.moColor',
                'mo',
                'sc',
                'scGreige',
                'kartuProsesDyeingItem'
            ])
                ->whereHas('mo', function($q) {
                    $q->where('process', 1);
                })
                ->when($nomorKartu, function ($query) use ($nomorKartu) {
                    $query->where('no', 'like', '%' . $nomorKartu . '%');
                })
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            if (count($kartuProsesDyeing) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maaf nomer kartu yang anda cari tidak ditemukan'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $kartuProsesDyeing
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }



public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'id_kartu' => 'required|integer',
            'no_lot' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'jenis_inspek' => 'required',
            'no_memo' => 'nullable',
            'inspection_table' => 'nullable',
            'inspect_result' => 'required|array',
        ]);

        $token = request()->header('Authorization');
        $token = str_replace('Bearer ', '', $token);
        $users = User::where('verification_token', $token)->first();

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        $kartuProsesDyeing = KartuProsesDyeing::with([
            'wo',
            'mo',
            'sc',
            'scGreige',
        ])->find($validatedData['id_kartu']);

        if (!$kartuProsesDyeing) {
            return response()->json([
                'success' => false,
                'message' => 'ID Kartu tidak ditemukan atau tidak valid',
            ], 404);
        }

        $dataToStore = [
            'sc_id' => $kartuProsesDyeing->sc->id ?? null,
            'sc_greige_id' => $kartuProsesDyeing->scGreige->id ?? null,
            'mo_id' => $kartuProsesDyeing->mo->id ?? null,
            'wo_id' => $kartuProsesDyeing->wo->id ?? null,
            'kartu_process_dyeing_id' => $kartuProsesDyeing->id,
            'jenis_process' => 1,
            'no_urut' => null,
            'no' => null,
            'date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'tanggal_inspeksi' => \Carbon\Carbon::now()->format('Y-m-d'),
            'no_lot' => $validatedData['no_lot'],
            'kombinasi' => $kartuProsesDyeing->woColor->moColor->color ?? '-',
            'note' => '',
            'status' => 1,
            'unit' => $validatedData['unit'],
            'jenis_inspek' => $validatedData['jenis_inspek'],
            'no_memo' => $validatedData['no_memo'] ?? '-',
            'created_by' => $users->id,
            'updated_by' => $users->id,
            'k3l_code' => $kartuProsesDyeing->k3l_code ?? '-',
            'created_at' => \Carbon\Carbon::now()->timestamp,
            'updated_at' => \Carbon\Carbon::now()->timestamp,
            'inspection_table' => $validatedData['inspection_table'],
        ];

        $inspecting = Inspecting::create($dataToStore);

        foreach ($validatedData['inspect_result'] as $key => $items) {

            foreach ($items as $index => $item) {

                // $no_urut = InspectingItem::where('inspecting_id', $inspecting->id)->count() + 1;

                $inspectingItem = [
                    'inspecting_id' => $inspecting->id,
                    'grade' => $item['grade'] ?? '-',
                    'join_piece' => isset($item['join_piece']) ? $item['join_piece'] : null,
                    'qty' => $item['qty'] ?? 0,
                    'note' => null,
                    'qty_sum' => null,
                    'is_head' => 0,
                    'qr_code' => 'INS-' . $inspecting->id . '-' . (InspectingItem::latest('id')->first()->id + 1),
                    'qty_count' =>  0,
                    'qr_code_desc' => null,
                    'qr_print_at' => null,
                    'lot_no' => $item['lot_no'] ?? '',
                    'defect' => null,
                    'stock_id' => $item['stock_id'] ?? '',
                    'qty_bit' => $item['qty_bit'] ?? null,
                    'gsm_item' => $item['gsm_item'] ?? null,
                    'no_urut' => $item['no_urut'] ?? null,
                ];

                $inspectingItemModel = InspectingItem::create($inspectingItem);

                if (isset($item['defect']) && is_array($item['defect'])) {
                    foreach ($item['defect'] as $defect) {
                        $defectInspectingItem = [
                            'inspecting_item_id' => $inspectingItemModel->id,
                            'mst_kode_defect_id' => $defect['kode_defect'] ?? null,
                            'meterage' => $defect['meter_defect'] ?? null,
                            'point' => $defect['point'] ?? null,
                        ];

                        DefectInspectingItem::create($defectInspectingItem);
                    }
                }

                $getItemBasedOnInspectingId = InspectingItem::where('inspecting_id', $inspecting->id)->get();

                foreach ($getItemBasedOnInspectingId as $gIBOII) {
                    $qty_sum = InspectingItem::where('join_piece', $gIBOII->join_piece)
                        ->where('inspecting_id', $inspecting->id)
                        ->sum('qty');
                    $qty_count = InspectingItem::where('join_piece', $gIBOII->join_piece)
                        ->where('inspecting_id', $inspecting->id)
                        ->count();
                    $is_head = InspectingItem::where('join_piece', $gIBOII->join_piece)
                        ->where('inspecting_id', $inspecting->id)
                        ->where('join_piece', '!=', '')
                        ->orderBy('no_urut', 'asc')
                        ->first();

                    $gIBOII->qty_sum = ($is_head && ($is_head->id != $gIBOII->id)) ? null : ($gIBOII->join_piece == null || $gIBOII->join_piece == "" ? $gIBOII->qty : $qty_sum);
                    $gIBOII->is_head = ($is_head && ($is_head->id != $gIBOII->id)) ? 0 : 1;
                    $gIBOII->qty_count = ($is_head && ($is_head->id != $gIBOII->id)) ? 0 : ($gIBOII->join_piece == null || $gIBOII->join_piece == "" ? 1 : $qty_count);
                    $gIBOII->qr_code = 'INS-' . $gIBOII->inspecting_id . '-' . $gIBOII->id;

                    $gIBOII->save();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Data Inspecting berhasil disimpan',
            'data' => $inspecting->load('inspectingItem'),
        ]);
    } catch (\Exception $e) {
        Log::error('Exception: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan saat menyimpan data',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    public function storeMklbj(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'wo_id' => 'required|integer',
                'color' => 'required|integer',
                'no_lot' => 'required|string|max:255',
                'unit' => 'required|string|max:255',
                'jenis_inspek' => 'required',
                'no_memo' => 'nullable',
                'inspection_table' => 'nullable',
                'jenis_makloon' => 'required|string|max:255',
                'inspect_result' => 'required|array',
            ]);

            $token = $request->header('Authorization');
            $token = str_replace('Bearer ', '', $token);
            $users = User::where('verification_token', $token)->first();

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi data gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();

            // Data untuk disimpan ke database
            $dataToStore = [
                'wo_id' => $validatedData['wo_id'],
                'wo_color_id' => $validatedData['color'],
                'no_lot' => $validatedData['no_lot'],
                'satuan' => $validatedData['unit'],
                'jenis' => $validatedData['jenis_makloon'],
                'jenis_inspek' => $validatedData['jenis_inspek'],
                'no_memo' => $validatedData['no_memo'] ?? '-',
                'tgl_inspeksi' => \Carbon\Carbon::now()->format('Y-m-d'),
                'tgl_kirim' => \Carbon\Carbon::now()->format('Y-m-d'),
                'status' => 1,
                'no_urut' => null,
                'no' => null,
                'created_at' => \Carbon\Carbon::now()->timestamp,
                'created_by' => $users->id,
                'updated_at' => \Carbon\Carbon::now()->timestamp,
                'updated_by' => $users->id,
                'inspection_table' => $validatedData['inspection_table'],
            ];

            // Simpan data ke database
            $inspectingMklbj = InspectingMklbj::create($dataToStore);

            // Proses `inspect_result`
            foreach ($validatedData['inspect_result'] as $item) {

            //      $lastNoUrut = InspectingMklbjItem::where('inspecting_id', $inspectingMklbj->id)
            //     ->max('no_urut');
            // $nextNoUrut = $lastNoUrut ? $lastNoUrut + 1 : 1;


                $inspectingMklbjItem = [
                    'inspecting_id' => $inspectingMklbj->id,
                    'grade' => $item['grade'] ?? '-',
                    'join_piece' => $item['join_piece'] ?? null,
                    'qty' => $item['qty'] ?? 0,
                    'lot_no' => $item['lot_no'] ?? '',
                    'qr_code' => 'INS-' . $inspectingMklbj->id . '-' . (InspectingMklbjItem::latest('id')->first()->id + 1),
                    'gsm_item' => $item['gsm_item'] ?? null,
                    'no_urut' => $item['no_urut'] ?? null,
                    'qty_bit' => $item['qty_bit'] ?? null,
                ];

                $inspectingItemModel = InspectingMklbjItem::create($inspectingMklbjItem);

                // Proses `defect`
                if (isset($item['defect']) && is_array($item['defect'])) {
                    foreach ($item['defect'] as $defect) {
                        $defectInspectingItem = [
                            'inspecting_mklbj_item_id' => $inspectingItemModel->id,
                            'mst_kode_defect_id' => $defect['kode_defect'] ?? null,
                            'meterage' => $defect['meter_defect'] ?? null,
                            'point' => $defect['point'] ?? null,
                        ];

                        DefectInspectingItem::create($defectInspectingItem);
                    }
                }
            }

            // Tambahan: Proses berdasarkan skrip baru
            $items = InspectingMklbjItem::where('inspecting_id', $inspectingMklbj->id)->get();

            foreach ($items as $item) {
                $qtySum = InspectingMklbjItem::where('join_piece', $item->join_piece)
                    ->where('inspecting_id', $inspectingMklbj->id)
                    ->sum('qty');

                $qtyCount = InspectingMklbjItem::where('join_piece', $item->join_piece)
                    ->where('inspecting_id', $inspectingMklbj->id)
                    ->count();

                $isHead = InspectingMklbjItem::where('join_piece', $item->join_piece)
                    ->where('inspecting_id', $inspectingMklbj->id)
                    ->where('join_piece', '!=', '')
                    ->orderBy('no_urut', 'asc')
                    ->orderBy('id')
                    ->first();

                $item->qty_sum = ($isHead && $isHead->id != $item->id) ? null : ($item->join_piece == null || $item->join_piece == "" ? $item->qty : $qtySum);
                $item->qr_code = 'MKL-' . $item->inspecting_id . '-' . $item->id;
                $item->is_head = ($isHead && $isHead->id != $item->id) ? 0 : 1;
                $item->qty_count = ($isHead && $isHead->id != $item->id) ? 0 : ($item->join_piece == null || $item->join_piece == "" ? 1 : $qtyCount);
                $item->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Data Inspecting berhasil disimpan',
                'data' => $inspectingMklbj->load('inspectingMklbjItem'),
            ]);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function storeItemMklbj(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'inspecting_id' => 'required|integer',
                'qty' => 'required|integer',
                'grade' => 'required|integer',
                'join_piece' => 'nullable|string|max:255',
                'lot_no' => 'nullable|string|max:255',
                'gsm_item' => 'nullable',
                'qty_bit' => 'nullable|integer',
                'defect' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi data gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();

            // Ensure the inspecting_id exists in the Inspecting table
            $inspecting = InspectingMklbj::find($validatedData['inspecting_id']);
            if (!$inspecting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inspecting ID tidak ditemukan',
                ], 404);
            }

            $lastItem = InspectingMklbjItem::where('inspecting_id', $inspecting->id)
            ->orderByDesc('no_urut')
            ->first();

            $noUrut = $lastItem ? $lastItem->no_urut + 1 : 1;

            // Create the InspectingItem
            $inspectingItem = [
                'inspecting_id' => $inspecting->id,
                'grade' => $validatedData['grade'],
                'join_piece' => $validatedData['join_piece'] ?? null,
                'qty' => $validatedData['qty'],
                'note' => null,
                'qty_sum' => null,
                'is_head' => 0,
                'qr_code' => 'INS-' . $inspecting->id . '-' . (InspectingMklbjItem::latest('id')->first()->id + 1),
                'qty_count' => 0,
                'qr_code_desc' => null,
                'qr_print_at' => null,
                'lot_no' => $validatedData['lot_no'] ?? '',
                'defect' => null,
                'qty_bit' => $validatedData['qty_bit'] ?? null,
                'gsm_item' => $validatedData['gsm_item'] ?? null,
                'no_urut' => $noUrut
            ];

            $inspectingItemModel = InspectingMklbjItem::create($inspectingItem);

            // Store the defects if available
            if (isset($validatedData['defect']) && is_array($validatedData['defect'])) {
                foreach ($validatedData['defect'] as $defect) {
                    $defectInspectingItem = [
                        'inspecting_mklbj_item_id' => $inspectingItemModel->id,
                        'mst_kode_defect_id' => $defect['mst_kode_defect_id'] ?? null,
                        'meterage' => $defect['meterage'] ?? null,
                        'point' => $defect['point'] ?? null,
                    ];
                    DefectInspectingItem::create($defectInspectingItem);
                }
            }

            // Update the inspecting item data for qty_sum, qty_count, etc.
            $items = InspectingMklbjItem::where('inspecting_id', $inspecting->id)->get();

            foreach ($items as $item) {
                $qtySum = InspectingMklbjItem::where('join_piece', $item->join_piece)
                    ->where('inspecting_id', $inspecting->id)
                    ->sum('qty');

                $qtyCount = InspectingMklbjItem::where('join_piece', $item->join_piece)
                    ->where('inspecting_id', $inspecting->id)
                    ->count();

                $isHead = InspectingMklbjItem::where('join_piece', $item->join_piece)
                    ->where('inspecting_id', $inspecting->id)
                    ->where('join_piece', '!=', '')
                    ->orderByDesc('is_head')
                    ->orderBy('id')
                    ->first();

                $item->qty_sum = ($isHead && $isHead->id != $item->id) ? null : ($item->join_piece == null || $item->join_piece == "" ? $item->qty : $qtySum);
                $item->qr_code = 'MKL-' . $item->inspecting_id . '-' . $item->id;
                $item->is_head = ($isHead && $isHead->id != $item->id) ? 0 : 1;
                $item->qty_count = ($isHead && $isHead->id != $item->id) ? 0 : ($item->join_piece == null || $item->join_piece == "" ? 1 : $qtyCount);
                $item->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Data Inspecting Item berhasil disimpan',
                'data' => $inspectingItemModel,
            ]);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'inspecting_id' => 'required|integer',
                'qty' => 'required|integer',
                'grade' => 'required|integer',
                'join_piece' => 'nullable|string|max:255',
                'lot_no' => 'nullable|string|max:255',
                'defect' => 'nullable|array',
                'stock_id' => 'nullable|integer',
                'qty_bit' => 'nullable|integer',
                'gsm_item' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi data gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();

            // Ensure the inspecting_id exists in the Inspecting table
            $inspecting = Inspecting::find($validatedData['inspecting_id']);
            if (!$inspecting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inspecting ID tidak ditemukan',
                ], 404);
            }

             // Hitung no_urut berdasarkan inspecting_id
            //            $lastItem = InspectingItem::where('inspecting_id', $inspecting->id)
            //     ->orderBy('no_urut', 'desc')
            //     ->first();

            // $nextNoUrut = $lastItem ? $lastItem->no_urut + 1 : 1;

             $lastItem = InspectingItem::where('inspecting_id', $inspecting->id)
            ->orderByDesc('no_urut')
            ->first();

            $noUrut = $lastItem ? $lastItem->no_urut + 1 : 1;

            // Create the InspectingItem
            $inspectingItem = [
                'inspecting_id' => $inspecting->id,
                'grade' => $validatedData['grade'],
                'join_piece' => $validatedData['join_piece'] ?? null,
                'qty' => $validatedData['qty'],
                'note' => null,
                'qty_sum' => null,
                'is_head' => 0,
                'qr_code' => 'INS-' . $inspecting->id . '-' . (InspectingItem::latest('id')->first()->id + 1),
                'qty_count' => 0,
                'qr_code_desc' => null,
                'qr_print_at' => null,
                'lot_no' => $validatedData['lot_no'] ?? '',
                'defect' => null,
                'stock_id' => $validatedData['stock_id'] ?? '',
                'qty_bit' => $validatedData['qty_bit'] ?? null,
                'gsm_item' => $validatedData['gsm_item'] ?? null,
                'no_urut' => $noUrut,
            ];

            $inspectingItemModel = InspectingItem::create($inspectingItem);

            // Store the defects if available
            if (isset($validatedData['defect']) && is_array($validatedData['defect'])) {
                foreach ($validatedData['defect'] as $defect) {
                    $defectInspectingItem = [
                        'inspecting_item_id' => $inspectingItemModel->id,
                        'mst_kode_defect_id' => $defect['mst_kode_defect_id'] ?? null,
                        'meterage' => $defect['meterage'] ?? null,
                        'point' => $defect['point'] ?? null,
                    ];
                    DefectInspectingItem::create($defectInspectingItem);
                }
            }

            // Update the inspecting item data for qty_sum, qty_count, etc.
            $getItemBasedOnInspectingId = InspectingItem::where('inspecting_id', $inspecting->id)->get();
            foreach ($getItemBasedOnInspectingId as $gIBOII) {
                $qty_sum = InspectingItem::where('join_piece', $gIBOII->join_piece)
                    ->where('inspecting_id', $inspecting->id)
                    ->sum('qty');
                $qty_count = InspectingItem::where('join_piece', $gIBOII->join_piece)
                    ->where('inspecting_id', $inspecting->id)
                    ->count();
                $is_head = InspectingItem::where('join_piece', $gIBOII->join_piece)
                    ->where('inspecting_id', $inspecting->id)
                    ->where('join_piece', '!=', '')
                    ->orderBy('is_head', 'desc')
                    ->first();

                $gIBOII->qty_sum = ($is_head && ($is_head->id != $gIBOII->id)) ? null : ($gIBOII->join_piece == null || $gIBOII->join_piece == "" ? $gIBOII->qty : $qty_sum);
                $gIBOII->is_head = ($is_head && ($is_head->id != $gIBOII->id)) ? 0 : 1;
                $gIBOII->qty_count = ($is_head && ($is_head->id != $gIBOII->id)) ? 0 : ($gIBOII->join_piece == null || $gIBOII->join_piece == "" ? 1 : $qty_count);
                $gIBOII->qr_code = 'INS-' . $gIBOII->inspecting_id . '-' . $gIBOII->id;
                $gIBOII->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Data Inspecting Item berhasil disimpan',
                'data' => $inspectingItemModel,
            ]);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storePrinting(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_kartu' => 'required|integer',
                'no_lot' => 'required|string|max:255',
                'unit' => 'required|string|max:255',
                'jenis_inspek' => 'required',
                'no_memo' => 'nullable',
                'inspection_table' => 'nullable',
                'inspect_result' => 'required|array',
            ]);

            $token = request()->header('Authorization');
            $token = str_replace('Bearer ', '', $token);
            $users = User::where('verification_token', $token)->first();

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi data gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();

            $kartuProsesPrinting = KartuProsesPrinting::with([
                'wo',
                'mo',
                'sc',
                'scGreige',
            ])->find($validatedData['id_kartu']);

            if (!$kartuProsesPrinting) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID Kartu tidak ditemukan atau tidak valid',
                ], 404);
            }

            $dataToStore = [
                'sc_id' => $kartuProsesPrinting->sc->id ?? null,
                'sc_greige_id' => $kartuProsesPrinting->scGreige->id ?? null,
                'mo_id' => $kartuProsesPrinting->mo->id ?? null,
                'wo_id' => $kartuProsesPrinting->wo->id ?? null,
                'kartu_process_printing_id' => $kartuProsesPrinting->id,
                'jenis_process' => 2,
                'no_urut' => null,
                'no' => null,
                'date' => \Carbon\Carbon::now()->format('Y-m-d'),
                'tanggal_inspeksi' => \Carbon\Carbon::now()->format('Y-m-d'),
                'no_lot' => $validatedData['no_lot'],
                'kombinasi' => $kartuProsesPrinting->woColor->moColor->color ?? '-',
                'note' => '',
                'status' => 1,
                'unit' => $validatedData['unit'],
                'jenis_inspek' => $validatedData['jenis_inspek'],
                'no_memo' => $validatedData['no_memo'] ?? '-',
                'created_by' => $users->id,
                'updated_by' => $users->id,
                'k3l_code' => $kartuProsesPrinting->k3l_code ?? '-',
                'created_at' => \Carbon\Carbon::now()->timestamp,
                'updated_at' => \Carbon\Carbon::now()->timestamp,
                'inspection_table' => $validatedData['inspection_table'],
            ];

            $inspecting = Inspecting::create($dataToStore);

            foreach ($validatedData['inspect_result'] as $key => $items) {
                //  $lastItem = InspectingItem::where('inspecting_id', $inspecting->id)->orderBy('no_urut', 'desc')
                // ->first();;

            // $lastUrut = $lastItem ? $lastItem->no_urut + 1 : 1;

                foreach ($items as $index => $item) {
                    $inspectingItem = [
                        'inspecting_id' => $inspecting->id,
                        'grade' => $item['grade'] ?? '-',
                        'join_piece' => isset($item['join_piece']) ? $item['join_piece'] : null,
                        'qty' => $item['qty'] ?? 0,
                        'note' => null,
                        'qty_sum' => null,
                        'is_head' => 0,
                        'qr_code' => 'INS-' . $inspecting->id . '-' . (InspectingItem::latest('id')->first()->id + 1),
                        'qty_count' =>  0,
                        'qr_code_desc' => null,
                        'qr_print_at' => null,
                        'lot_no' => $item['lot_no'] ?? '',
                        'defect' => null,
                        'stock_id' => $item['stock_id'] ?? '',
                        'qty_bit' => $item['qty_bit'] ?? null,
                        'gsm_item' => $item['gsm_item'] ?? null,
                        'no_urut' => $item['no_urut'] ?? null,
                    ];

                    $inspectingItemModel = InspectingItem::create($inspectingItem);

                    if (isset($item['defect']) && is_array($item['defect'])) {
                        foreach ($item['defect'] as $defect) {
                            $defectInspectingItem = [
                                'inspecting_item_id' => $inspectingItemModel->id,
                                'mst_kode_defect_id' => $defect['kode_defect'] ?? null,
                                'meterage' => $defect['meter_defect'] ?? null,
                                'point' => $defect['point'] ?? null,
                            ];

                            DefectInspectingItem::create($defectInspectingItem);
                        }
                    }

                    $getItemBasedOnInspectingId = InspectingItem::where('inspecting_id', $inspecting->id)->get();

                    foreach ($getItemBasedOnInspectingId as $gIBOII) {
                        $qty_sum = InspectingItem::where('join_piece', $gIBOII->join_piece)
                            ->where('inspecting_id', $inspecting->id)
                            ->sum('qty');
                        $qty_count = InspectingItem::where('join_piece', $gIBOII->join_piece)
                            ->where('inspecting_id', $inspecting->id)
                            ->count();
                        $is_head = InspectingItem::where('join_piece', $gIBOII->join_piece)
                            ->where('inspecting_id', $inspecting->id)
                            ->where('join_piece', '!=', '')
                            ->orderBy('is_head', 'desc')
                            ->first();

                        $gIBOII->qty_sum = ($is_head && ($is_head->id != $gIBOII->id)) ? null : ($gIBOII->join_piece == null || $gIBOII->join_piece == "" ? $gIBOII->qty : $qty_sum);
                        $gIBOII->is_head = ($is_head && ($is_head->id != $gIBOII->id)) ? 0 : 1;
                        $gIBOII->qty_count = ($is_head && ($is_head->id != $gIBOII->id)) ? 0 : ($gIBOII->join_piece == null || $gIBOII->join_piece == "" ? 1 : $qty_count);
                        $gIBOII->qr_code = 'INS-' . $gIBOII->inspecting_id . '-' . $gIBOII->id;

                        $gIBOII->save();
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Data Inspecting berhasil disimpan',
                'data' => $inspecting->load('inspectingItem'),
            ]);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function kartuProsesMklbj(Request $request)
    {
        try {
            $woId = $request->input('wo_id');
            $woColorId = $request->input('wo_color_id');
            $kartuProsesMklbj = InspectingMklbj::with([
                'wo',
                'woColor.moColor'
            ])
                ->when($woId, function ($query) use ($woId) {
                    $query->where('wo_id', $woId);
                })
                ->when($woColorId, function ($query) use ($woColorId) {
                    $query->where('wo_color_id', $woColorId);
                })
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            if (count($kartuProsesMklbj) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maaf nomer kartu yang anda cari tidak ditemukan'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $kartuProsesMklbj
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function getWo(Request $request)
    {
        try {
            $wo = Wo::with('woColor.moColor')->get();
            return response()->json([
                'success' => true,
                'wo' => $wo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function kartuPrinting(Request $request)
    {
        try {
            $nomerKartu = $request->input('no');
            $kartuProsesPrinting = KartuProsesPrinting::with([
                'createdBy',
                'updatedBy',
                'mo',
                'sc',
                'scGreige',
                'wo',
                'woColor.moColor',
                'kartuProsesPrintingItem'
            ])
                ->whereHas('mo', function($q){
                    $q->where('process', 2);
                })
                ->when($nomerKartu, function ($query) use ($nomerKartu) {
                    $query->where('no', 'like', '%' . $nomerKartu . '%');
                })
                ->get();
             return response()->json([
                'success' => true,
                'data' => $kartuProsesPrinting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }


}