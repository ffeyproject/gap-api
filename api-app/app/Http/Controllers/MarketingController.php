<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Sc;
use Illuminate\Support\Facades\DB;

class MarketingController extends Controller
{


    private const A           = 1;
    private const B           = 2;
    private const C           = 3;
    private const PK          = 4;
    private const Sample      = 5;
    private const A_PLUS  = 7;
    private const A_ASTERISK  = 8;

        public function getMarketing()
    {
        $marketings = \DB::table('trn_sc as sc')
            ->join('user as u', 'u.id', '=', 'sc.marketing_id')
            ->where('u.status', 10)
            ->select('u.id', 'u.full_name', 'u.username')
            ->distinct()
            ->get()
            ->map(function ($u) {
                return [
                    'id'   => $u->id,
                    'name' => !empty(trim($u->full_name)) ? $u->full_name : $u->username,
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json($marketings);
    }



    public function outstanding(Request $request)
    {
        // Validasi input
        $query = $request->query();

        $validator = Validator::make($query, [
            'customer_id'  => 'nullable|numeric',
            'marketing_id' => 'nullable|numeric',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date',
            'po_no'       => 'nullable|string',
            'no_wo'       => 'nullable|string',
            'wo_greige'   => 'nullable|string',
            'is_sisaan'   => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated  = $validator->validated();
        $customerId  = $validated['customer_id'] ?? null;
        $marketingId = $validated['marketing_id'] ?? null;
        $startDate   = $validated['start_date'];
        $endDate    = $validated['end_date'];
        $poNo       = $validated['po_no'] ?? null;
        $noWo       = $validated['no_wo'] ?? null;
        $woGreige   = $validated['wo_greige'] ?? null;
        $isSisaan   = filter_var($request->query('is_sisaan', false), FILTER_VALIDATE_BOOLEAN) || $request->query('is_sisaan') === '1';

        if (!$customerId && !$marketingId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => ['customer_id' => ['Pilih Customer atau Marketing terlebih dahulu']],
            ], 422);
        }

        // Ambil data pengiriman buyer per MO
        $kirimByMo = \DB::table('trn_kirim_buyer as k')
            ->join('trn_kirim_buyer_item as i', 'i.kirim_buyer_id', '=', 'k.id')
            ->whereNotNull('k.mo_id')
            ->groupBy('k.mo_id')
            ->select('k.mo_id', \DB::raw('SUM(i.qty) as total_kirim'))
            ->pluck('total_kirim', 'k.mo_id');

        // Ambil qty per grade dari Inspecting (punya kartu proses)
        $qtySumByKartu = \DB::table('trn_inspecting as i')
            ->join('inspecting_item as ii', 'ii.inspecting_id', '=', 'i.id')
            ->select(
                'i.kartu_process_dyeing_id',
                'ii.grade',
                \DB::raw('SUM(ii.qty) as qty_sum'),
                \DB::raw('MIN(i.unit) as unit')
            )
            ->where('i.status', 4)
            ->groupBy('i.kartu_process_dyeing_id', 'ii.grade')
            ->get()
            ->groupBy('kartu_process_dyeing_id')
            ->map(function ($rows) {
                $data = [
                    'unit'   => $rows->first()->unit,
                    'grades' => [],
                ];
                foreach ($rows as $row) {
                    $data['grades'][$row->grade] = $row->qty_sum;
                }
                return $data;
            });

        // Ambil qty per grade dari Inspecting MKLBJ (by wo_color_id)
        $qtyMklbj = \DB::table('inspecting_mkl_bj as im')
            ->join('inspecting_mkl_bj_items as imi', 'imi.inspecting_id', '=', 'im.id')
            ->select(
                'im.wo_color_id',
                'imi.grade',
                \DB::raw('SUM(imi.qty) as qty_sum')
            )
            ->where('im.status', 3)
            ->groupBy('im.wo_color_id', 'imi.grade')
            ->get()
            ->groupBy('wo_color_id')
            ->map(function ($rows) {
                $gradeLabels = [
                    1 => 'A',
                    2 => 'B',
                    3 => 'C',
                    4 => 'PK',
                    5 => 'Sample',
                    6 => 'A+',
                    7 => 'A*',
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                    'PK' => 'PK',
                    'Sample' => 'Sample',
                    'A_PLUS' => 'A+',
                    'A_ASTERISK' => 'A*',
                ];

                $data = ['grades' => []];
                foreach ($rows as $row) {
                    $label = $gradeLabels[$row->grade] ?? $row->grade;
                    $data['grades'][$label] = ($data['grades'][$label] ?? 0) + $row->qty_sum;
                }
                return $data;
            });

        // Ambil data SC dengan relasi MO, WO, proses
        $scs = Sc::with([
            'customer',
            'marketing',
            'mo' => function ($query) use ($poNo, $noWo, $woGreige) {
                $query->where('status', 3)
                    ->where('process', 1)
                    ->when($poNo, function ($q) use ($poNo) {
                        $q->whereNotNull('no_po')
                        ->where('no_po', 'like', "%{$poNo}%");
                    })
                    ->with([
                        'scGreige.greigeGroup',
                        'wo' => function ($woQuery) use ($noWo, $woGreige) {
                            $woQuery->when($noWo, function ($wq) use ($noWo) {
                                    $wq->whereNotNull('no')
                                    ->where('no', 'like', "%{$noWo}%");
                                })
                                ->when($woGreige, function ($wq) use ($woGreige) {
                                    $wq->whereHas('greige', function ($gq) use ($woGreige) {
                                        $gq->whereRaw('LOWER(nama_kain) LIKE ?', ['%' . strtolower($woGreige) . '%']);
                                    });
                                })
                                ->with([
                                    'greige',
                                    'woColor.kartuProsesDyeings.kartuProsesDyeingItem',
                                    'woColor.kartuProsesDyeings.kartuProsesDyeingProcesses.processDyeing',
                                    'woColor.moColor',
                                ]);
                        }
                    ]);
            }
        ])
        ->when($customerId, function ($q) use ($customerId) {
            $q->where('cust_id', $customerId);
        })
        ->when($marketingId, function ($q) use ($marketingId) {
            $q->where('marketing_id', $marketingId);
        })
        ->whereBetween('date', [$startDate, $endDate])
        ->get();

        // canonical grade keys
        $gradeKeys = ['A','A+','A*','B','C','PK','Sample'];

        // Format hasil group per SC
        $formatted = $scs->map(function ($sc) use ($qtySumByKartu, $qtyMklbj, $gradeKeys, $kirimByMo, $isSisaan) {
            $moList = $sc->mo ? $sc->mo->map(function ($mo) use ($qtySumByKartu, $qtyMklbj, $gradeKeys, $kirimByMo, $isSisaan) {
                // Perhitungan Qty MO target (Yard / Meter)
                $totalBatchMo = collect($mo->wo)->flatMap(function ($w) {
                    return $w->woColor ?? [];
                })->sum('qty');
                $gg = $mo->scGreige ? $mo->scGreige->greigeGroup : null;
                $perBatch = (float) ($gg->qty_per_batch ?? 1);
                $susut = (float) ($gg->nilai_penyusutan ?? 0);
                $priceParam = (int) ($mo->scGreige->price_param ?? 1);
                $targetQtyMo = $totalBatchMo * $perBatch * (1 - ($susut / 100));
                if ($priceParam === 2) {
                    $targetQtyMo *= 1.093613298;
                }
                $totalKirim = (float) ($kirimByMo->get($mo->id, 0));

                // Jika menu sisaan: MO yang sudah terkirim penuh (tidak ada sisaan) jangan dimunculkan
                if ($isSisaan && $targetQtyMo > 0 && $totalKirim >= $targetQtyMo) {
                    return null;
                }

                $woList = $mo->wo ? $mo->wo->map(function ($wo) use ($qtySumByKartu, $qtyMklbj, $gradeKeys) {
                    return [
                        'wo_no'     => $wo->no,
                        'wo_date'   => $wo->date,
                        'wo_greige' => optional($wo->greige)->nama_kain,
                        'wo_colors' => $wo->woColor->map(function ($woColor) use ($qtySumByKartu, $qtyMklbj, $gradeKeys) {
                            // MKLBJ grades for this wo_color
                            $mklbjGrades = $qtyMklbj->get($woColor->id)['grades'] ?? [];

                            // aggregate KP all grades (numeric keys possible)
                            $kpAllGrades = $woColor->kartuProsesDyeings->flatMap(function ($kp) use ($qtySumByKartu) {
                                $qtyData = $qtySumByKartu->get($kp->id, ['grades' => []]);
                                return $qtyData['grades'];
                            })->toArray();

                            // map numeric grade keys to labels and sum KP totals
                            $mapNumToLabel = [
                                1 => 'A',
                                2 => 'B',
                                3 => 'C',
                                4 => 'PK',
                                5 => 'Sample',
                                6 => 'A+',
                                7 => 'A*',
                            ];
                            $combined = [];
                            foreach ($kpAllGrades as $k => $v) {
                                if (is_numeric($k)) {
                                    $label = $mapNumToLabel[(int)$k] ?? (string)$k;
                                } else {
                                    $label = (string)$k;
                                }
                                $combined[$label] = ($combined[$label] ?? 0) + $v;
                            }
                            // tambah MKLBJ (sekali)
                            foreach ($mklbjGrades as $label => $v) {
                                $combined[$label] = ($combined[$label] ?? 0) + $v;
                            }

                            // normalisasi total_grades agar semua key ada
                            $combinedNormalized = array_fill_keys($gradeKeys, 0);
                            foreach ($gradeKeys as $gk) {
                                if (isset($combined[$gk])) {
                                    $combinedNormalized[$gk] = 0 + $combined[$gk];
                                }
                            }

                            // build kartu_proses array: KPI values ONLY
                            $kartuProsesArr = $woColor->kartuProsesDyeings->map(function ($kp) use ($qtySumByKartu, $gradeKeys) {
                                $qtyData = $qtySumByKartu->get($kp->id, ['grades' => [], 'unit' => null]);
                                $unitName = $qtyData['unit'] == 1 ? 'Yard' : ($qtyData['unit'] == 2 ? 'Meter' : null);

                                $qtyGradesPerKP = [
                                    'A'   => ($qtyData['grades'][1] ?? 0),
                                    'B'   => ($qtyData['grades'][2] ?? 0),
                                    'C'   => ($qtyData['grades'][3] ?? 0),
                                    'PK'  => ($qtyData['grades'][4] ?? 0),
                                    'Sample' => ($qtyData['grades'][5] ?? 0),
                                    'A+'  => ($qtyData['grades'][6] ?? 0),
                                    'A*'  => ($qtyData['grades'][7] ?? 0),
                                ];

                                return [
                                    'no'   => $kp->nomor_kartu,
                                    'date' => $kp->date,
                                    'berat' => $kp->berat,
                                    'lebar' => $kp->lebar,
                                    'approved' => $kp->approved_at,
                                    'qty_sum_terima' => $qtyGradesPerKP,
                                    'unit' => $unitName,
                                    'items' => $kp->kartuProsesDyeingItem->map(function ($item) {
                                        return [
                                            'roll_no' => $item->roll_no,
                                            'meter'   => $item->meter,
                                        ];
                                    }),
                                    'panjang_greige' => $kp->kartuProsesDyeingItem->sum('panjang_m'),
                                    'processes' => $kp->kartuProsesDyeingProcesses
                                        ? $kp->kartuProsesDyeingProcesses
                                            ->whereIn('process_id', [1, 3, 8, 15, 18, 19, 21, 23, 24])
                                            ->map(function ($process) {
                                                $decoded = json_decode($process->value, true);
                                                $tanggal = $decoded['tanggal'] ?? null;
                                                return [
                                                    'process_id'   => $process->process_id,
                                                    'process_name' => optional($process->processDyeing)->nama_proses,
                                                    'value'        => $tanggal,
                                                    'note'         => $process->note,
                                                ];
                                            })
                                            ->values()
                                        : [],
                                ];
                            })->values();

                            // Jika ada MKLBJ (nilai > 0), append 1 MKLBJ row
                            $hasMklbj = false;
                            foreach ($mklbjGrades as $v) {
                                if ((float)$v > 0) { $hasMklbj = true; break; }
                            }

                            if ($hasMklbj) {
                                $mklbjNormalized = array_fill_keys($gradeKeys, 0);
                                foreach ($gradeKeys as $gk) {
                                    if (isset($mklbjGrades[$gk])) {
                                        $mklbjNormalized[$gk] = 0 + $mklbjGrades[$gk];
                                    }
                                }
                                $kartuProsesArr->push((object)[
                                    'no' => 'INS',
                                    'date' => null,
                                    'berat' => null,
                                    'lebar' => null,
                                    'approved' => null,
                                    'qty_sum_terima' => $mklbjNormalized,
                                    'unit' => null,
                                    'items' => collect([]),
                                    'panjang_greige' => 0,
                                    'processes' => [],
                                    'is_mklbj' => true,
                                ]);
                            }

                            if ($kartuProsesArr->isEmpty()) {
                                $kartuProsesArr = collect([ (object)[
                                    'no' => null,
                                    'date' => null,
                                    'berat' => null,
                                    'lebar' => null,
                                    'approved' => null,
                                    'qty_sum_terima' => array_fill_keys($gradeKeys, 0),
                                    'unit' => null,
                                    'items' => collect([]),
                                    'panjang_greige' => 0,
                                    'processes' => [],
                                ] ]);
                            }

                            return [
                                'qty'   => $woColor->qty,
                                'color' => optional($woColor->moColor)->color,
                                'kartu_proses' => $kartuProsesArr->values(),
                                'mklbj'        => $mklbjGrades,
                                'total_grades' => $combinedNormalized,
                            ];
                        })->values(),
                    ];
                }) : [];

                return [
                    'mo_id'             => $mo->id,
                    'mo_no'             => $mo->no,
                    'mo_date'           => $mo->date,
                    'mo_po'             => $mo->no_po,
                    'target_qty_mo'     => round($targetQtyMo, 2),
                    'total_kirim_buyer' => round($totalKirim, 2),
                    'sisa_qty'          => round(max(0, $targetQtyMo - $totalKirim), 2),
                    'wo_list'           => $woList,
                ];
            })->filter()->values() : collect([]);

            if ($isSisaan && $moList->isEmpty()) {
                return null;
            }

            return [
                'sc_no'          => $sc->no,
                'sc_date'        => $sc->date,
                'customer_id'    => $sc->cust_id,
                'customer_name'  => optional($sc->customer)->name ?? '-',
                'marketing_id'   => $sc->marketing_id,
                'marketing_name' => optional($sc->marketing)->full_name ?? (optional($sc->marketing)->username ?? '-'),
                'mo_list'        => $moList,
            ];
        })->filter()->values();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'grouped_outstanding_items' => $formatted,
            ],
        ]);
    }

    public function outstandingPrinting(Request $request)
    {
        // Validasi input
        $query = $request->query();

        $validator = Validator::make($query, [
            'customer_id'  => 'nullable|numeric',
            'marketing_id' => 'nullable|numeric',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date',
            'po_no'       => 'nullable|string',
            'no_wo'       => 'nullable|string',
            'wo_greige'   => 'nullable|string',
            'is_sisaan'   => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated  = $validator->validated();
        $customerId  = $validated['customer_id'] ?? null;
        $marketingId = $validated['marketing_id'] ?? null;
        $startDate   = $validated['start_date'];
        $endDate    = $validated['end_date'];
        $poNo       = $validated['po_no'] ?? null;
        $noWo       = $validated['no_wo'] ?? null;
        $woGreige   = $validated['wo_greige'] ?? null;
        $isSisaan   = filter_var($request->query('is_sisaan', false), FILTER_VALIDATE_BOOLEAN) || $request->query('is_sisaan') === '1';

        if (!$customerId && !$marketingId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => ['customer_id' => ['Pilih Customer atau Marketing terlebih dahulu']],
            ], 422);
        }

        // Ambil data pengiriman buyer per MO
        $kirimByMo = \DB::table('trn_kirim_buyer as k')
            ->join('trn_kirim_buyer_item as i', 'i.kirim_buyer_id', '=', 'k.id')
            ->whereNotNull('k.mo_id')
            ->groupBy('k.mo_id')
            ->select('k.mo_id', \DB::raw('SUM(i.qty) as total_kirim'))
            ->pluck('total_kirim', 'k.mo_id');

        // Ambil qty per grade dari Inspecting (punya kartu proses printing)
        $qtySumByKartu = \DB::table('trn_inspecting as i')
            ->join('inspecting_item as ii', 'ii.inspecting_id', '=', 'i.id')
            ->select(
                'i.kartu_process_printing_id',
                'ii.grade',
                \DB::raw('SUM(ii.qty) as qty_sum'),
                \DB::raw('MIN(i.unit) as unit')
            )
            ->where('i.status', 4)
            ->whereNotNull('i.kartu_process_printing_id')
            ->groupBy('i.kartu_process_printing_id', 'ii.grade')
            ->get()
            ->groupBy('kartu_process_printing_id')
            ->map(function ($rows) {
                $data = [
                    'unit'   => $rows->first()->unit,
                    'grades' => [],
                ];
                foreach ($rows as $row) {
                    $data['grades'][$row->grade] = $row->qty_sum;
                }
                return $data;
            });

        // Ambil qty per grade dari Inspecting MKLBJ (by wo_color_id) berjenis proses wo printing
        $qtyMklbj = \DB::table('inspecting_mkl_bj as im')
            ->join('inspecting_mkl_bj_items as imi', 'imi.inspecting_id', '=', 'im.id')
            ->join('trn_wo as wo', 'wo.id', '=', 'im.wo_id')
            ->join('trn_mo as mo', 'mo.id', '=', 'wo.mo_id')
            ->select(
                'im.wo_color_id',
                'imi.grade',
                \DB::raw('SUM(imi.qty) as qty_sum')
            )
            ->where('im.status', 3)
            ->where('mo.process', 2)
            ->groupBy('im.wo_color_id', 'imi.grade')
            ->get()
            ->groupBy('wo_color_id')
            ->map(function ($rows) {
                $gradeLabels = [
                    1 => 'A',
                    2 => 'B',
                    3 => 'C',
                    4 => 'PK',
                    5 => 'Sample',
                    6 => 'A+',
                    7 => 'A*',
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                    'PK' => 'PK',
                    'Sample' => 'Sample',
                    'A_PLUS' => 'A+',
                    'A_ASTERISK' => 'A*',
                ];

                $data = ['grades' => []];
                foreach ($rows as $row) {
                    $label = $gradeLabels[$row->grade] ?? $row->grade;
                    $data['grades'][$label] = ($data['grades'][$label] ?? 0) + $row->qty_sum;
                }
                return $data;
            });

        // Ambil data SC dengan relasi MO (process 2), WO, proses
        $scs = Sc::whereHas('mo', function ($query) use ($poNo, $noWo, $woGreige) {
                $query->where('status', 3)
                    ->where('process', 2)
                    ->when($poNo, function ($q) use ($poNo) {
                        $q->whereNotNull('no_po')
                        ->where('no_po', 'like', "%{$poNo}%");
                    })
                    ->whereHas('wo', function ($woQuery) use ($noWo, $woGreige) {
                        $woQuery->when($noWo, function ($wq) use ($noWo) {
                                $wq->whereNotNull('no')
                                ->where('no', 'like', "%{$noWo}%");
                            })
                            ->when($woGreige, function ($wq) use ($woGreige) {
                                $wq->whereHas('greige', function ($gq) use ($woGreige) {
                                    $gq->whereRaw('LOWER(nama_kain) LIKE ?', ['%' . strtolower($woGreige) . '%']);
                                });
                            });
                    });
            })
            ->with([
                'customer',
                'marketing',
                'mo' => function ($query) use ($poNo, $noWo, $woGreige) {
                    $query->where('status', 3)
                        ->where('process', 2)
                        ->when($poNo, function ($q) use ($poNo) {
                            $q->whereNotNull('no_po')
                            ->where('no_po', 'like', "%{$poNo}%");
                        })
                        ->with([
                            'scGreige.greigeGroup',
                            'wo' => function ($woQuery) use ($noWo, $woGreige) {
                                $woQuery->when($noWo, function ($wq) use ($noWo) {
                                        $wq->whereNotNull('no')
                                        ->where('no', 'like', "%{$noWo}%");
                                    })
                                    ->when($woGreige, function ($wq) use ($woGreige) {
                                        $wq->whereHas('greige', function ($gq) use ($woGreige) {
                                            $gq->whereRaw('LOWER(nama_kain) LIKE ?', ['%' . strtolower($woGreige) . '%']);
                                        });
                                    })
                                    ->with([
                                        'greige',
                                        'woColor.kartuProsesPrintings.kartuProsesPrintingItem',
                                        'woColor.kartuProsesPrintings.kartuProsesPrintingProcesses.process',
                                        'woColor.moColor',
                                    ]);
                            }
                        ]);
                }
            ])
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('cust_id', $customerId);
            })
            ->when($marketingId, function ($q) use ($marketingId) {
                $q->where('marketing_id', $marketingId);
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // canonical grade keys
        $gradeKeys = ['A','A+','A*','B','C','PK','Sample'];

        // Format hasil group per SC
        $formatted = $scs->map(function ($sc) use ($qtySumByKartu, $qtyMklbj, $gradeKeys, $kirimByMo, $isSisaan) {
            $moList = collect($sc->mo)->map(function ($mo) use ($qtySumByKartu, $qtyMklbj, $gradeKeys, $kirimByMo, $isSisaan) {
                // Perhitungan Qty MO target (Yard / Meter)
                $totalBatchMo = collect($mo->wo)->flatMap(function ($w) {
                    return $w->woColor ?? [];
                })->sum('qty');
                $gg = $mo->scGreige ? $mo->scGreige->greigeGroup : null;
                $perBatch = (float) ($gg->qty_per_batch ?? 1);
                $susut = (float) ($gg->nilai_penyusutan ?? 0);
                $priceParam = (int) ($mo->scGreige->price_param ?? 1);
                $targetQtyMo = $totalBatchMo * $perBatch * (1 - ($susut / 100));
                if ($priceParam === 2) {
                    $targetQtyMo *= 1.093613298;
                }
                $totalKirim = (float) ($kirimByMo->get($mo->id, 0));

                // Jika menu sisaan: MO yang sudah terkirim penuh (tidak ada sisaan) jangan dimunculkan
                if ($isSisaan && $targetQtyMo > 0 && $totalKirim >= $targetQtyMo) {
                    return null;
                }

                $woList = collect($mo->wo)->map(function ($wo) use ($qtySumByKartu, $qtyMklbj, $gradeKeys) {
                    $woColors = collect($wo->woColor)->map(function ($woColor) use ($qtySumByKartu, $qtyMklbj, $gradeKeys) {
                        // MKLBJ grades for this wo_color
                        $mklbjGrades = $qtyMklbj->get($woColor->id)['grades'] ?? [];

                        // aggregate KP all grades (numeric keys possible)
                        $kpAllGrades = collect($woColor->kartuProsesPrintings)->flatMap(function ($kp) use ($qtySumByKartu) {
                            $qtyData = $qtySumByKartu->get($kp->id, ['grades' => []]);
                            return $qtyData['grades'];
                        })->toArray();

                        // map numeric grade keys to labels and sum KP totals
                        $mapNumToLabel = [
                            1 => 'A',
                            2 => 'B',
                            3 => 'C',
                            4 => 'PK',
                            5 => 'Sample',
                            6 => 'A+',
                            7 => 'A*',
                        ];
                        $combined = [];
                        foreach ($kpAllGrades as $k => $v) {
                            if (is_numeric($k)) {
                                $label = $mapNumToLabel[(int)$k] ?? (string)$k;
                            } else {
                                $label = (string)$k;
                            }
                            $combined[$label] = ($combined[$label] ?? 0) + $v;
                        }
                        // tambah MKLBJ (sekali)
                        foreach ($mklbjGrades as $label => $v) {
                            $combined[$label] = ($combined[$label] ?? 0) + $v;
                        }

                        // normalisasi total_grades agar semua key ada
                        $combinedNormalized = array_fill_keys($gradeKeys, 0);
                        foreach ($gradeKeys as $gk) {
                            if (isset($combined[$gk])) {
                                $combinedNormalized[$gk] = 0 + $combined[$gk];
                            }
                        }

                        // build kartu_proses array: KPI values ONLY
                        $kartuProsesArr = collect($woColor->kartuProsesPrintings)->map(function ($kp) use ($qtySumByKartu, $gradeKeys) {
                            $qtyData = $qtySumByKartu->get($kp->id, ['grades' => [], 'unit' => null]);
                            $unitName = $qtyData['unit'] == 1 ? 'Yard' : ($qtyData['unit'] == 2 ? 'Meter' : null);

                            $qtyGradesPerKP = [
                                'A'   => ($qtyData['grades'][1] ?? 0),
                                'B'   => ($qtyData['grades'][2] ?? 0),
                                'C'   => ($qtyData['grades'][3] ?? 0),
                                'PK'  => ($qtyData['grades'][4] ?? 0),
                                'Sample' => ($qtyData['grades'][5] ?? 0),
                                'A+'  => ($qtyData['grades'][6] ?? 0),
                                'A*'  => ($qtyData['grades'][7] ?? 0),
                            ];

                            return [
                                'no'   => $kp->nomor_kartu ?? $kp->no,
                                'date' => $kp->date,
                                'berat' => $kp->berat,
                                'lebar' => $kp->lebar,
                                'approved' => $kp->approved_at,
                                'qty_sum_terima' => $qtyGradesPerKP,
                                'unit' => $unitName,
                                'items' => collect($kp->kartuProsesPrintingItem)->map(function ($item) {
                                    return [
                                        'panjang_m' => $item->panjang_m,
                                    ];
                                }),
                                'panjang_greige' => collect($kp->kartuProsesPrintingItem)->sum('panjang_m'),
                                'processes' => collect($kp->kartuProsesPrintingProcesses)
                                    ->map(function ($process) {
                                        $decoded = json_decode($process->value, true);
                                        $tanggal = $decoded['tanggal'] ?? null;
                                        if (!$tanggal && isset($decoded['pengulangan']) && is_array($decoded['pengulangan'])) {
                                            foreach (array_reverse($decoded['pengulangan']) as $p) {
                                                if (!empty($p['data']['tanggal'])) {
                                                    $tanggal = $p['data']['tanggal'];
                                                    break;
                                                }
                                            }
                                        }
                                        return [
                                            'process_id'   => $process->process_id,
                                            'process_name' => optional($process->process)->nama_proses,
                                            'value'        => $tanggal,
                                            'note'         => $process->note,
                                        ];
                                    })
                                    ->values(),
                            ];
                        })->values();

                        // Jika ada MKLBJ (nilai > 0), append 1 MKLBJ row
                        $hasMklbj = false;
                        foreach ($mklbjGrades as $v) {
                            if ((float)$v > 0) { $hasMklbj = true; break; }
                        }

                        if ($hasMklbj) {
                            $mklbjNormalized = array_fill_keys($gradeKeys, 0);
                            foreach ($gradeKeys as $gk) {
                                if (isset($mklbjGrades[$gk])) {
                                    $mklbjNormalized[$gk] = 0 + $mklbjGrades[$gk];
                                }
                            }
                            $kartuProsesArr->push((object)[
                                'no' => 'INS',
                                'date' => null,
                                'berat' => null,
                                'lebar' => null,
                                'approved' => null,
                                'qty_sum_terima' => $mklbjNormalized,
                                'unit' => null,
                                'items' => collect([]),
                                'panjang_greige' => 0,
                                'processes' => [],
                                'is_mklbj' => true,
                            ]);
                        }

                        if ($kartuProsesArr->isEmpty()) {
                            $kartuProsesArr = collect([ (object)[
                                'no' => null,
                                'date' => null,
                                'berat' => null,
                                'lebar' => null,
                                'approved' => null,
                                'qty_sum_terima' => array_fill_keys($gradeKeys, 0),
                                'unit' => null,
                                'items' => collect([]),
                                'panjang_greige' => 0,
                                'processes' => [],
                            ] ]);
                        }

                        return [
                            'qty'   => $woColor->qty,
                            'color' => optional($woColor->moColor)->color,
                            'kartu_proses' => $kartuProsesArr->values(),
                            'mklbj'        => $mklbjGrades,
                            'total_grades' => $combinedNormalized,
                        ];
                    })->values();

                    return [
                        'wo_no'     => $wo->no,
                        'wo_date'   => $wo->date,
                        'wo_greige' => optional($wo->greige)->nama_kain,
                        'wo_colors' => $woColors,
                    ];
                })->filter(function ($wo) {
                    return count($wo['wo_colors']) > 0;
                })->values();

                return [
                    'mo_id'             => $mo->id,
                    'mo_no'             => $mo->no,
                    'mo_date'           => $mo->date,
                    'mo_po'             => $mo->no_po,
                    'target_qty_mo'     => round($targetQtyMo, 2),
                    'total_kirim_buyer' => round($totalKirim, 2),
                    'sisa_qty'          => round(max(0, $targetQtyMo - $totalKirim), 2),
                    'wo_list'           => $woList,
                ];
            })->filter()->values();

            if ($isSisaan && $moList->isEmpty()) {
                return null;
            }

            return [
                'sc_no'          => $sc->no,
                'sc_date'        => $sc->date,
                'customer_id'    => $sc->cust_id,
                'customer_name'  => optional($sc->customer)->name ?? '-',
                'marketing_id'   => $sc->marketing_id,
                'marketing_name' => optional($sc->marketing)->full_name ?? (optional($sc->marketing)->username ?? '-'),
                'mo_list'        => $moList,
            ];
        })->filter()->values();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'grouped_outstanding_items' => $formatted,
            ],
        ]);
    }

    public function followSc(Request $request)
    {
        // 1️⃣ Validasi input
        $validator = Validator::make($request->all(), [
            'customer_id'  => 'nullable|numeric',
            'marketing_id' => 'nullable|numeric',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 2️⃣ Ambil input
        $validated  = $validator->validated();
        $customerId = $validated['customer_id'];
        $startDate  = $validated['start_date'];
        $endDate    = $validated['end_date'];

        // 3️⃣ Ambil data SC + relasi
        $sc = Sc::select('id', 'no')
                ->where('cust_id', $customerId)
                ->where('status', 5) // SC status 5
                ->whereBetween('date', [$startDate, $endDate])
                ->where(function ($q) {
                    //  ⬇️ tambahan kondisi MO status 3
                    $q->doesntHave('mo', 'and', function ($query) {
                        $query->where('status', 3); // exclude MO status 3
                    })
                    ->orDoesntHave('moColors')
                    ->orWhereDoesntHave('moColors.woColor');
                })
                ->with([
                    'scGreiges:id,sc_id,greige_group_id,qty',
                    'scGreiges.greigeGroup:id,nama_kain'
                ])
                ->get();

        // 4️⃣ Group data berdasarkan no_sc
        $grouped = [];
        foreach ($sc as $item) {
            foreach ($item->scGreiges as $greige) {
                $no_sc = $item->no;

                if (!isset($grouped[$no_sc])) {
                    $grouped[$no_sc] = [
                        'no_sc' => $no_sc,
                        'nama_greige' => [],
                    ];
                }

                $grouped[$no_sc]['nama_greige'][] = [
                    'nama_kain' => $greige->greigeGroup->nama_kain ?? null,
                    'qty' => number_format((float)$greige->qty, 2, ',', ''),
                ];
            }
        }

        // ubah associative array jadi indexed array
        $data = array_values($grouped);

        // 5️⃣ Return JSON
        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }


    public function kirimBuyer(Request $request)
    {
        $query = $request->query();

        // === 1️⃣ Validasi ===
        $validator = Validator::make($query, [
            'customer_id' => 'nullable|numeric',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
            'po_no'       => 'nullable|string',
            'no_wo'       => 'nullable|string',
            'wo_greige'   => 'nullable|string',
            'sc_no'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // === 2️⃣ Ambil parameter ===
        $v = $validator->validated();
        $customerId = $v['customer_id'] ?? null;
        $startDate  = $v['start_date']  ?? null;
        $endDate    = $v['end_date']    ?? null;
        $poNo       = $v['po_no']       ?? null;
        $noWo       = $v['no_wo']       ?? null;
        $woGreige   = $v['wo_greige']   ?? null;
        $scNo       = $v['sc_no']       ?? null;

        // === 3️⃣ Ambil SC beserta relasi ===
        $scs = Sc::with([
            'scGreiges.greigeGroup',
            'mo' => function ($query) use ($poNo, $noWo, $woGreige) {
                $query->where('status', 3)
                    ->whereIn('process', [1, 2])
                    ->when($poNo, function ($q) use ($poNo) {
                        $q->where('no_po', 'like', '%'.$poNo.'%');
                    })
                    ->with([
                        'wo' => function ($woQuery) use ($noWo, $woGreige) {
                            $woQuery
                                ->when($noWo, function ($q) use ($noWo) {
                                    $q->where('no', 'like', '%'.$noWo.'%');
                                })
                                ->when($woGreige, function ($q) use ($woGreige) {
                                    $q->whereHas('greige', function ($g) use ($woGreige) {
                                        $g->whereRaw('LOWER(nama_kain) LIKE ?', ['%'.strtolower($woGreige).'%']);
                                    });
                                })
                                ->with(['greige', 'woColor.moColor']);
                        }
                    ]);
            }
        ])
        ->when($customerId, function ($q) use ($customerId) {
            $q->where('cust_id', $customerId);
        })
        ->when($scNo, function ($q) use ($scNo) {
            $q->where('no', 'like', '%'.$scNo.'%');
        })
        ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate]);
        })
        ->get();

        // === 4️⃣ Gudang Jadi Summary ===
        $gradeLabels = [
            1=>'A',2=>'B',3=>'C',4=>'PK',5=>'Sample',6=>'A+',7=>'A*',
            'A'=>'A','B'=>'B','C'=>'C','PK'=>'PK','Sample'=>'Sample',
            'A_PLUS'=>'A+','A_ASTERISK'=>'A*'
        ];

        $gudang = \DB::table('trn_gudang_jadi')
            ->selectRaw('wo_id, TRIM(UPPER(color)) as color, grade, SUM(qty) as total_qty')
            ->where('status', 1)
            ->groupBy('wo_id', 'color', 'grade')
            ->get();

        $gudangSummary = $gudang->groupBy(function ($r) {
            return $r->wo_id.'|'.$r->color;
        })->map(function ($rows) use ($gradeLabels) {
            $gradeKeys = ['A','A+','A*','B','C','PK','Sample'];
            $tot = array_fill_keys($gradeKeys, 0.0);
            foreach ($rows as $r) {
                $label = $gradeLabels[$r->grade] ?? $r->grade;
                $tot[$label] = ($tot[$label] ?? 0) + (float) $r->total_qty;
            }
            $tot['Total'] = array_sum($tot);
            return $tot;
        });

        // === 5️⃣ Kirim Buyer Summary ===
        $kirimRaw = \DB::table('trn_kirim_buyer_item as i')
            ->join('trn_gudang_jadi as g', 'i.stock_id', '=', 'g.id')
            ->join('trn_kirim_buyer as k', 'i.kirim_buyer_id', '=', 'k.id')
            ->join('trn_kirim_buyer_header as h', 'k.header_id', '=', 'h.id')
            ->selectRaw('g.wo_id, TRIM(UPPER(g.color)) as color, g.unit, h.date as tgl_kirim, SUM(i.qty) as qty_kirim')
            ->whereNotNull('g.color')
            ->groupBy('g.wo_id', 'g.color', 'g.unit', 'h.date')
            ->get();

        $unitLabels = [1 => 'Yard', 2 => 'Meter', 3 => 'Pcs', 4 => 'Kilogram'];

        $kirimSummary = $kirimRaw->groupBy(function ($r) {
            return $r->wo_id.'|'.$r->color;
        })->map(function ($rows) use ($unitLabels) {
            $first = $rows->first();
            $unit  = $unitLabels[$first->unit] ?? '-';
            return [
                'unit' => $unit,
                'tgl_kirim_list' => $rows->map(function ($r) {
                    return [
                        'tgl' => \Carbon\Carbon::parse($r->tgl_kirim)->format('d-m-Y'),
                        'qty' => (float) $r->qty_kirim,
                    ];
                })->sortBy('tgl')->values(),
                'total_kirim' => $rows->sum('qty_kirim'),
            ];
        });

        // === 6️⃣ Gabungkan Data ===
        $formatted = $scs->map(function ($sc) use ($gudangSummary, $kirimSummary) {
            $totalQtySc = 0.0;

            if ($sc->scGreiges && $sc->scGreiges->count()) {
                foreach ($sc->scGreiges as $sg) {
                    if (!$sg->greigeGroup) continue;
                    $param = (int) $sg->price_param;
                    if ($param === 1) {
                        $totalQtySc += $sg->getQtyFinish();
                    } elseif ($param === 2) {
                        $totalQtySc += $sg->getQtyFinishToYard();
                    } else {
                        $totalQtySc += (float) $sg->qty;
                    }
                }
            }

            return [
                'sc_no'        => $sc->no,
                'sc_date'      => $sc->date,
                'total_qty_sc' => round($totalQtySc, 2),
                'mo_list'      => $sc->mo->map(function ($mo) use ($gudangSummary, $kirimSummary) {
                    return [
                        'mo_id'   => $mo->id,
                        'mo_no'   => $mo->no,
                        'mo_po'   => $mo->no_po,
                        'process' => $mo->process,
                        'wo_list' => $mo->wo->map(function ($wo) use ($gudangSummary, $kirimSummary) {
                            return [
                                'wo_no'     => $wo->no,
                                'wo_date'   => $wo->date,
                                'wo_greige' => optional($wo->greige)->nama_kain,
                                'wo_colors' => collect($wo->woColor)->map(function ($wc) use ($wo, $gudangSummary, $kirimSummary) {
                                    $color = strtoupper(trim(optional($wc->moColor)->color));
                                    $key   = $wo->id.'|'.$color;
                                    return [
                                        'color'          => $color,
                                        'qty'            => (float) $wc->qty,
                                        'gudang_jadi'    => $gudangSummary[$key] ?? [],
                                        'tgl_kirim_list' => $kirimSummary[$key]['tgl_kirim_list'] ?? [],
                                        'total_kirim'    => $kirimSummary[$key]['total_kirim'] ?? 0,
                                        'unit'           => $kirimSummary[$key]['unit'] ?? '-',
                                    ];
                                })->values(),
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        // === 7️⃣ Return JSON ===
        return response()->json([
            'status' => 'success',
            'data'   => ['grouped_outstanding_items' => $formatted],
        ]);
    }



}