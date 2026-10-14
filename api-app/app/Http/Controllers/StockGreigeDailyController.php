<?php

namespace App\Http\Controllers;

use App\Greige;
use App\GreigeGroup;
use App\StockGreigeDaily;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockGreigeDailyController extends Controller
{
    /**
     * Lists all daily stock records with day-to-day changes and filtering.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'greige_id' => 'nullable|integer',
            'greige_group_id' => 'nullable|integer',
            'asal_greige' => 'nullable|integer',
            'motif' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:500',
            'only_incoming' => 'nullable|boolean',
            'auto_snapshot' => 'nullable|boolean',
        ]);

        $date = $request->input('date');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $greigeId = $request->input('greige_id');
        $greigeGroupId = $request->input('greige_group_id');
        $asalGreige = $request->input('asal_greige');
        $motif = $request->input('motif');
        $perPage = (int)$request->input('per_page', 20);
        $onlyIncoming = filter_var($request->input('only_incoming', false), FILTER_VALIDATE_BOOLEAN);
        $autoSnapshot = filter_var($request->input('auto_snapshot', false), FILTER_VALIDATE_BOOLEAN);

        // Jika requested auto_snapshot aktif, ambil snapshot terbaru sebelum memuat data
        if ($autoSnapshot) {
            $snapshotDate = $date ?: date('Y-m-d');
            $userId = $this->getAuthenticatedUserId($request);
            try {
                StockGreigeDaily::snapshotStock($snapshotDate, $userId);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Auto snapshot failed: ' . $e->getMessage());
            }
        }

        // Ambil 10 tanggal snapshot terakhir yang ada di database (cached untuk kecepatan response)
        $availableDates = \Illuminate\Support\Facades\Cache::remember('stock_greige_daily_available_dates', 180, function () {
            return StockGreigeDaily::select('date')
                ->distinct()
                ->orderBy('date', 'desc')
                ->limit(10)
                ->pluck('date')
                ->map(function ($d) {
                    return is_string($d) ? substr($d, 0, 10) : (is_object($d) ? $d->format('Y-m-d') : (string)$d);
                })
                ->values();
        });

        // Riwayat tanggal inputan roll fisik yang masuk ke gudang fresh (cached 3 menit)
        $warehouseEntryDates = \Illuminate\Support\Facades\Cache::remember('warehouse_entry_dates_recent', 180, function () {
            return DB::table('trn_stock_greige')
                ->select([
                    'date',
                    DB::raw('COUNT(id) as total_roll'),
                    DB::raw('ROUND(SUM(panjang_m)::numeric, 2) as total_panjang'),
                ])
                ->where('jenis_gudang', StockGreigeDaily::JG_FRESH)
                ->where('status', StockGreigeDaily::STATUS_VALID)
                ->whereNotNull('date')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(10)
                ->get();
        });

        $query = StockGreigeDaily::with([
            'greige:id,nama_kain,alias,group_id',
            'greigeGroup:id,nama_kain,lebar_kain',
            'createdBy:id,username,full_name',
            'updatedBy:id,username,full_name',
        ]);

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

        if (!empty($asalGreige)) {
            $query->where('asal_greige', $asalGreige);
        }

        if (!empty($motif)) {
            $matchingGreigeIds = DB::table('mst_greige')
                ->where('nama_kain', 'ILIKE', "%{$motif}%")
                ->orWhere('alias', 'ILIKE', "%{$motif}%")
                ->pluck('id');
            $query->whereIn('greige_id', $matchingGreigeIds);
        }

        $query->orderBy('date', 'desc')
              ->orderBy('greige_id', 'asc')
              ->orderBy('asal_greige', 'asc');

        if ($request->has('all') && filter_var($request->input('all'), FILTER_VALIDATE_BOOLEAN)) {
            $records = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($perPage);
            $records = collect($paginated->items());
        }

        // Hitung selisih dengan tanggal sebelumnya untuk motif & asal_greige yang sama
        $this->computeDifferences($records);

        $totalStockHariIniM = (float)round($records->sum('stock_hari_ini_m'), 2);
        $totalStockHariIniRoll = (int)$records->sum('stock_hari_ini_roll');
        $totalPrevStockM = (float)round($records->sum('prev_total_panjang'), 2);
        $totalPrevStockRoll = (int)$records->sum('prev_total_roll');

        $totalMasukM = (float)round($records->where('diff_panjang', '>', 0)->sum('diff_panjang'), 2);
        $totalMasukRoll = (int)$records->where('diff_roll', '>', 0)->sum('diff_roll');
        $totalKeluarM = (float)round(abs($records->where('diff_panjang', '<', 0)->sum('diff_panjang')), 2);
        $totalKeluarRoll = (int)abs($records->where('diff_roll', '<', 0)->sum('diff_roll'));
        $totalMotifMasuk = (int)$records->where('is_stock_masuk', true)->count();

        // Jika filter hanya stok masuk aktif
        if ($onlyIncoming) {
            $records = $records->filter(function ($item) {
                return $item->is_stock_masuk;
            })->values();
        }

        $maxCreated = (int)StockGreigeDaily::max('created_at');
        $maxUpdated = (int)StockGreigeDaily::max('updated_at');
        $latestSnapshotTimestamp = max($maxCreated, $maxUpdated);
        $latestSnapshotAt = $latestSnapshotTimestamp ? date('d-m-Y H:i:s', $latestSnapshotTimestamp) : '-';

        $summary = [
            'total_records' => $records->count(),
            'grand_total_m' => (float)$records->sum('total_panjang'),
            'grand_total_roll' => (int)$records->sum('total_roll'),
            'total_stock_hari_ini_m' => $totalStockHariIniM,
            'total_stock_hari_ini_roll' => $totalStockHariIniRoll,
            'total_prev_stock_m' => $totalPrevStockM,
            'total_prev_stock_roll' => $totalPrevStockRoll,
            'total_masuk_m' => $totalMasukM,
            'total_masuk_roll' => $totalMasukRoll,
            'total_keluar_m' => $totalKeluarM,
            'total_keluar_roll' => $totalKeluarRoll,
            'total_motif_masuk' => $totalMotifMasuk,
            'total_grade_a' => (float)$records->sum('grade_a'),
            'total_grade_b' => (float)$records->sum('grade_b'),
            'total_grade_c' => (float)$records->sum('grade_c'),
            'total_grade_d' => (float)$records->sum('grade_d'),
            'total_grade_e' => (float)$records->sum('grade_e'),
            'total_grade_ng' => (float)$records->sum('grade_ng'),
            'total_grade_lain' => (float)$records->sum('grade_lain'),
        ];

        return response()->json([
            'success' => true,
            'status' => 'success',
            'latest_snapshot_at' => $latestSnapshotAt,
            'available_dates' => $availableDates,
            'warehouse_entry_dates' => $warehouseEntryDates,
            'asal_greige_options' => StockGreigeDaily::asalGreigeOptions(),
            'summary' => $summary,
            'pagination' => $paginated ? [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ] : null,
            'data' => $records,
        ]);
    }

    /**
     * Matrix / Pivot view comparing stock progression day by day for all motifs and asal_greige.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function matrix(Request $request)
    {
        $request->validate([
            'startDate' => 'nullable|date_format:Y-m-d',
            'endDate' => 'nullable|date_format:Y-m-d',
            'greige_id' => 'nullable|integer',
            'greige_group_id' => 'nullable|integer',
            'asal_greige' => 'nullable|integer',
            'motif' => 'nullable|string',
        ]);

        $startDate = $request->input('startDate', date('Y-m-d', strtotime('-14 days')));
        $endDate = $request->input('endDate', date('Y-m-d'));
        $greigeId = $request->input('greige_id');
        $greigeGroupId = $request->input('greige_group_id');
        $asalGreige = $request->input('asal_greige');
        $motif = $request->input('motif');

        // Ambil daftar tanggal yang ada di database pada rentang tersebut
        $dates = StockGreigeDaily::whereBetween('date', [$startDate, $endDate])
            ->select('date')
            ->distinct()
            ->orderBy('date', 'asc')
            ->pluck('date')
            ->map(function ($d) {
                return is_string($d) ? substr($d, 0, 10) : (is_object($d) ? $d->format('Y-m-d') : (string)$d);
            })
            ->values()
            ->toArray();

        // Ambil data stock greige daily per motif, group, asal_greige, dan tanggal
        $query = DB::table('trn_stock_greige_daily as tsgd')
            ->select([
                DB::raw("CAST(tsgd.date AS VARCHAR) as date_str"),
                'tsgd.date',
                'tsgd.greige_id',
                'tsgd.greige_group_id',
                'tsgd.asal_greige',
                'mg.nama_kain as greige_nama',
                'mgg.nama_kain as group_nama',
                'tsgd.total_panjang',
                'tsgd.total_roll',
            ])
            ->join('mst_greige as mg', 'mg.id', '=', 'tsgd.greige_id')
            ->leftJoin('mst_greige_group as mgg', 'mgg.id', '=', 'tsgd.greige_group_id')
            ->whereBetween('tsgd.date', [$startDate, $endDate]);

        if (!empty($greigeId)) {
            $query->where('tsgd.greige_id', $greigeId);
        }

        if (!empty($greigeGroupId)) {
            $query->where('tsgd.greige_group_id', $greigeGroupId);
        }

        if (!empty($asalGreige)) {
            $query->where('tsgd.asal_greige', $asalGreige);
        }

        if (!empty($motif)) {
            $matchingGreigeIds = DB::table('mst_greige')
                ->where('nama_kain', 'ILIKE', "%{$motif}%")
                ->orWhere('alias', 'ILIKE', "%{$motif}%")
                ->pluck('id');
            $query->whereIn('tsgd.greige_id', $matchingGreigeIds);
        }

        $rawRows = $query->orderBy('mg.nama_kain', 'asc')
            ->orderBy('tsgd.asal_greige', 'asc')
            ->orderBy('tsgd.date', 'asc')
            ->get();

        $matrixData = [];
        $motifs = [];
        $asalOptions = StockGreigeDaily::asalGreigeOptions();

        foreach ($rawRows as $row) {
            $gId = (int)$row->greige_id;
            $asal = (int)$row->asal_greige;
            $compositeKey = "{$gId}_{$asal}";
            $dateKey = substr((string)$row->date_str, 0, 10);

            if (!isset($motifs[$compositeKey])) {
                $motifs[$compositeKey] = [
                    'composite_key' => $compositeKey,
                    'greige_id' => $gId,
                    'nama' => $row->greige_nama,
                    'group' => $row->group_nama,
                    'asal_greige' => $asal,
                    'asal_greige_name' => $asalOptions[$asal] ?? '-',
                ];
            }

            $matrixData[$compositeKey][$dateKey] = [
                'qty' => (float)$row->total_panjang,
                'roll' => (int)$row->total_roll,
                'diff_qty' => 0,
                'diff_roll' => 0,
            ];
        }

        // Hitung selisih antar tanggal untuk setiap kombinasi motif + asal_greige
        foreach ($matrixData as $compositeKey => &$datesData) {
            $prevQty = null;
            $prevRoll = null;
            foreach ($dates as $d) {
                if (isset($datesData[$d])) {
                    if ($prevQty !== null) {
                        $datesData[$d]['diff_qty'] = round($datesData[$d]['qty'] - $prevQty, 2);
                        $datesData[$d]['diff_roll'] = $datesData[$d]['roll'] - $prevRoll;
                    } else {
                        $datesData[$d]['diff_qty'] = 0;
                        $datesData[$d]['diff_roll'] = 0;
                    }
                    $prevQty = $datesData[$d]['qty'];
                    $prevRoll = $datesData[$d]['roll'];
                }
            }
        }
        unset($datesData);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'greigeId' => $greigeId,
            'asalGreige' => $asalGreige,
            'dates' => $dates,
            'motifs' => array_values($motifs),
            'matrixData' => $matrixData,
        ]);
    }

    /**
     * Menyimpan snapshot stock harian per motif & asal greige dari gudang fresh valid
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSnapshot(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $date = $request->input('date', date('Y-m-d'));
        $userId = $this->getAuthenticatedUserId($request);

        try {
            $result = StockGreigeDaily::snapshotStock($date, $userId);

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
     * Detail stock harian berdasarkan ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $model = StockGreigeDaily::with([
            'greige:id,nama_kain,alias,group_id',
            'greigeGroup:id,nama_kain,lebar_kain',
            'createdBy:id,username,full_name',
            'updatedBy:id,username,full_name',
        ])->find($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Data stock harian tidak ditemukan.',
            ], 404);
        }

        $date = is_string($model->date) ? substr($model->date, 0, 10) : ($model->date ? $model->date->format('Y-m-d') : null);
        $incomingRolls = [];
        $hourlySummary = [];

        if ($date) {
            $incomingRolls = DB::table('trn_stock_greige')
                ->where('greige_id', $model->greige_id)
                ->where('asal_greige', $model->asal_greige)
                ->where('date', $date)
                ->where('jenis_gudang', StockGreigeDaily::JG_FRESH)
                ->where('status', StockGreigeDaily::STATUS_VALID)
                ->select([
                    'id',
                    'panjang_m',
                    'grade',
                    'lot_lusi',
                    'lot_pakan',
                    'no_document',
                    'pengirim',
                    'created_at',
                    'created_by'
                ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($r) {
                    $ts = is_numeric($r->created_at) ? (int)$r->created_at : strtotime($r->created_at);
                    return [
                        'id' => $r->id,
                        'panjang_m' => (float)$r->panjang_m,
                        'grade' => $r->grade,
                        'lot_lusi' => $r->lot_lusi ?: '-',
                        'lot_pakan' => $r->lot_pakan ?: '-',
                        'no_document' => $r->no_document ?: '-',
                        'pengirim' => $r->pengirim ?: '-',
                        'time' => $ts ? date('H:i:s', $ts) : '-',
                        'time_short' => $ts ? date('H:i', $ts) : '-',
                        'datetime' => $ts ? date('d-m-Y H:i:s', $ts) : '-',
                    ];
                });

            $hourlySummary = $incomingRolls->groupBy('time_short')->map(function ($items, $time) {
                return [
                    'jam' => $time,
                    'total_roll' => count($items),
                    'total_m' => (float)$items->sum('panjang_m'),
                ];
            })->values();
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $model,
            'incoming_rolls' => $incomingRolls,
            'hourly_summary' => $hourlySummary,
        ]);
    }

    /**
     * Menghapus record stock harian
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $model = StockGreigeDaily::find($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Data stock harian tidak ditemukan.',
            ], 404);
        }

        $date = is_string($model->date) ? substr($model->date, 0, 10) : $model->date->format('Y-m-d');
        $motif = $model->greigeNamaKain;
        $asalName = $model->asal_greige_name;

        $model->delete();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => "Data stock harian tanggal {$date} untuk motif {$motif} (Asal: {$asalName}) berhasil dihapus.",
        ]);
    }

    /**
     * Export daily stock report to CSV / Excel stream
     *
     * @param Request $request
     * @return StreamedResponse
     */
    public function exportExcel(Request $request)
    {
        $date = $request->input('date');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $greigeId = $request->input('greige_id');
        $greigeGroupId = $request->input('greige_group_id');
        $asalGreige = $request->input('asal_greige');
        $motif = $request->input('motif');

        $query = StockGreigeDaily::with(['greige', 'greigeGroup']);

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

        if (!empty($asalGreige)) {
            $query->where('asal_greige', $asalGreige);
        }

        if (!empty($motif)) {
            $matchingGreigeIds = DB::table('mst_greige')
                ->where('nama_kain', 'ILIKE', "%{$motif}%")
                ->orWhere('alias', 'ILIKE', "%{$motif}%")
                ->pluck('id');
            $query->whereIn('greige_id', $matchingGreigeIds);
        }

        $models = $query->orderBy('date', 'desc')
            ->orderBy('greige_id', 'asc')
            ->orderBy('asal_greige', 'asc')
            ->get();

        $this->computeDifferences($models);

        $filename = 'rekap_stock_greige_harian_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($models) {
            $output = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fputs($output, "\xEF\xBB\xBF");

            // Header kolom
            fputcsv($output, [
                'ID',
                'Tanggal',
                'Motif / Nama Kain',
                'Group Greige',
                'Asal Greige',
                'Stock Kemarin (m)',
                'Roll Kemarin',
                'Stock Hari Ini (m)',
                'Roll Hari Ini',
                'Jumlah Stock (m)',
                'Jumlah Roll',
                'Perubahan (+/-) (m)',
                'Perubahan (+/-) Roll',
                'Grade A (m)',
                'Grade B (m)',
                'Grade C (m)',
                'Grade D (m)',
                'Grade E (m)',
                'Grade NG (m)',
                'Grade Lain (m)',
                'Waktu Simpan',
            ], ';');

            foreach ($models as $m) {
                $dateFormatted = is_string($m->date) ? substr($m->date, 0, 10) : ($m->date ? $m->date->format('Y-m-d') : '');
                $createdAtFormatted = $m->created_at ? (is_numeric($m->created_at) ? date('Y-m-d H:i:s', $m->created_at) : (string)$m->created_at) : '';

                fputcsv($output, [
                    $m->id,
                    $dateFormatted,
                    $m->greige ? $m->greige->nama_kain : '-',
                    $m->greigeGroup ? $m->greigeGroup->nama_kain : '-',
                    $m->asal_greige_name,
                    $m->prev_total_panjang,
                    $m->prev_total_roll,
                    $m->stock_hari_ini_m ?? 0,
                    $m->stock_hari_ini_roll ?? 0,
                    $m->total_panjang,
                    $m->total_roll,
                    $m->diff_panjang,
                    $m->diff_roll,
                    $m->grade_a,
                    $m->grade_b,
                    $m->grade_c,
                    $m->grade_d,
                    $m->grade_e,
                    $m->grade_ng,
                    $m->grade_lain,
                    $createdAtFormatted,
                ], ';');
            }

            fclose($output);
        }, 200, $headers);
    }

    /**
     * Menghitung nilai perbandingan stok dengan snapshot sebelumnya untuk masing-masing item
     * (berdasarkan motif & asal_greige yang sama)
     *
     * @param \Illuminate\Support\Collection $items
     */
    protected function computeDifferences($items)
    {
        if ($items->isEmpty()) {
            return;
        }

        // Ambil semua tanggal unik dari items
        $dates = $items->map(function ($item) {
            return is_string($item->date) ? substr($item->date, 0, 10) : ($item->date ? $item->date->format('Y-m-d') : null);
        })->filter()->unique()->values()->toArray();

        // Prefetch data roll fisik kain masuk di trn_stock_greige (fresh & valid)
        $todayIncomingMap = [];
        if (!empty($dates)) {
            $incomingRows = DB::table('trn_stock_greige')
                ->select([
                    'date',
                    'greige_id',
                    'asal_greige',
                    DB::raw('COUNT(id) as count_roll'),
                    DB::raw('COALESCE(SUM(panjang_m), 0) as sum_panjang'),
                    DB::raw('MIN(created_at) as min_created_at'),
                    DB::raw('MAX(created_at) as max_created_at')
                ])
                ->where('jenis_gudang', StockGreigeDaily::JG_FRESH)
                ->where('status', StockGreigeDaily::STATUS_VALID)
                ->whereIn('date', $dates)
                ->groupBy('date', 'greige_id', 'asal_greige')
                ->get();

            foreach ($incomingRows as $row) {
                $d = is_string($row->date) ? substr($row->date, 0, 10) : (string)$row->date;
                $key = "{$d}_{$row->greige_id}_{$row->asal_greige}";
                $minTime = $row->min_created_at ? date('H:i', (int)$row->min_created_at) : null;
                $maxTime = $row->max_created_at ? date('H:i', (int)$row->max_created_at) : null;
                $todayIncomingMap[$key] = [
                    'm' => (float)$row->sum_panjang,
                    'roll' => (int)$row->count_roll,
                    'min_time' => $minTime,
                    'max_time' => $maxTime,
                    'jam_display' => ($minTime && $maxTime) ? ($minTime === $maxTime ? $minTime : "{$minTime} - {$maxTime}") : null,
                ];
            }
        }

        foreach ($items as $item) {
            $currentDate = is_string($item->date) ? substr($item->date, 0, 10) : ($item->date ? $item->date->format('Y-m-d') : null);
            $key = "{$currentDate}_{$item->greige_id}_{$item->asal_greige}";

            $todayStockM = isset($todayIncomingMap[$key]) ? (float)$todayIncomingMap[$key]['m'] : 0.0;
            $todayStockRoll = isset($todayIncomingMap[$key]) ? (int)$todayIncomingMap[$key]['roll'] : 0;

            $item->stock_hari_ini_m = $todayStockM;
            $item->stock_hari_ini_roll = $todayStockRoll;
            $item->stock_masuk_jam = isset($todayIncomingMap[$key]['jam_display']) ? $todayIncomingMap[$key]['jam_display'] : null;

            // Cari snapshot sebelum tanggal ini
            $prev = StockGreigeDaily::where('greige_id', $item->greige_id)
                ->where('asal_greige', $item->asal_greige)
                ->where('date', '<', $currentDate)
                ->orderBy('date', 'desc')
                ->first();

            if ($prev) {
                // Jika ada snapshot kemarin tetapi nilainya sama persis dengan total_panjang hari ini
                // padahal ada barang masuk hari ini (karena snapshot kemarin terbuat belakangan):
                // Maka total stok kemarin sampai tanggal H-1 adalah total_panjang dikurangi stok masuk hari ini!
                if ($todayStockM > 0 && abs((float)$prev->total_panjang - (float)$item->total_panjang) < 0.01) {
                    $item->prev_total_panjang = max(0, round((float)$item->total_panjang - $todayStockM, 2));
                    $item->prev_total_roll = max(0, (int)$item->total_roll - $todayStockRoll);
                } else {
                    $item->prev_total_panjang = (float)$prev->total_panjang;
                    $item->prev_total_roll = (int)$prev->total_roll;
                }
                $item->prev_date = is_string($prev->date) ? substr($prev->date, 0, 10) : ($prev->date ? $prev->date->format('Y-m-d') : null);
            } else {
                // Jika belum ada record snapshot di hari sebelumnya:
                // Stok kemarin adalah total saat ini dikurangi barang masuk hari ini
                $item->prev_total_panjang = max(0, round((float)$item->total_panjang - $todayStockM, 2));
                $item->prev_total_roll = max(0, (int)$item->total_roll - $todayStockRoll);
                $item->prev_date = $currentDate ? date('Y-m-d', strtotime($currentDate . ' -1 day')) : null;
            }

            $item->diff_panjang = round((float)$item->total_panjang - (float)$item->prev_total_panjang, 2);
            $item->diff_roll = (int)$item->total_roll - (int)$item->prev_total_roll;
            $item->is_stock_masuk = ($todayStockM > 0 || $item->diff_panjang > 0);
        }
    }

    /**
     * Ringkasan penerimaan stok greige masuk per tanggal (baik dari tracking harian maupun dari roll fisik gudang).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function incomingSummary(Request $request)
    {
        // 1. Data tanggal penerimaan roll fisik di gudang fresh (trn_stock_greige)
        $physicalIncoming = DB::table('trn_stock_greige as tsg')
            ->select([
                'tsg.date',
                DB::raw('COUNT(tsg.id) as total_roll'),
                DB::raw('ROUND(SUM(tsg.panjang_m)::numeric, 2) as total_panjang'),
                DB::raw('COUNT(DISTINCT tsg.greige_id) as total_motif'),
                DB::raw('MAX(tsg.created_at) as latest_created_at'),
            ])
            ->where('tsg.jenis_gudang', StockGreigeDaily::JG_FRESH)
            ->where('tsg.status', StockGreigeDaily::STATUS_VALID)
            ->whereNotNull('tsg.date')
            ->groupBy('tsg.date')
            ->orderBy('tsg.date', 'desc')
            ->limit(30)
            ->get()
            ->map(function ($item) {
                $item->total_roll = (int)$item->total_roll;
                $item->total_panjang = (float)$item->total_panjang;
                $item->total_motif = (int)$item->total_motif;
                $item->latest_input_time = $item->latest_created_at ? (is_numeric($item->latest_created_at) ? date('d-m-Y H:i', (int)$item->latest_created_at) : (string)$item->latest_created_at) : '-';
                return $item;
            });

        // 2. Data tanggal snapshot harian (trn_stock_greige_daily)
        $snapshotDates = StockGreigeDaily::select('date')
            ->distinct()
            ->orderBy('date', 'desc')
            ->limit(30)
            ->pluck('date')
            ->map(function ($d) {
                return is_string($d) ? substr($d, 0, 10) : (is_object($d) ? $d->format('Y-m-d') : (string)$d);
            })
            ->values();

        $maxCreated = (int)StockGreigeDaily::max('created_at');
        $maxUpdated = (int)StockGreigeDaily::max('updated_at');
        $latestSnapshotTimestamp = max($maxCreated, $maxUpdated);
        $latestSnapshotAt = $latestSnapshotTimestamp ? date('d-m-Y H:i:s', $latestSnapshotTimestamp) : '-';

        return response()->json([
            'success' => true,
            'status' => 'success',
            'latest_snapshot_at' => $latestSnapshotAt,
            'snapshot_dates' => $snapshotDates,
            'physical_incoming_by_date' => $physicalIncoming,
        ]);
    }

    /**
     * Mendapatkan user ID dari header Authorization (JWT sub claim atau verification_token)
     * tanpa menggunakan guard token default yang memerlukan kolom api_token.
     *
     * @param Request|null $request
     * @return int|null
     */
    protected function getAuthenticatedUserId(?Request $request = null)
    {
        $request = $request ?: request();
        $authHeader = $request->header('Authorization', '');
        $token = trim(str_replace('Bearer ', '', $authHeader));

        if (empty($token)) {
            return null;
        }

        // 1. Ekstrak 'sub' dari JWT token jika berformat header.payload.signature
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

        // 2. Fallback: Cari di tabel user berdasarkan verification_token
        try {
            $user = User::where('verification_token', $token)->first();
            if ($user) {
                return (int)$user->id;
            }
        } catch (\Throwable $e) {
            // Abaikan error database
        }

        return null;
    }
}
