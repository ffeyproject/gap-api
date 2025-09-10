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

    // public function outstanding(Request $request)
    // {
    //     // Validasi input
    //     $query = $request->query();

    //     $validator = Validator::make($query, [
    //         'customer_id' => 'required|numeric',
    //         'start_date'  => 'required|date',
    //         'end_date'    => 'required|date',
    //         'po_no'       => 'nullable|string',
    //         'no_wo'       => 'nullable|string',
    //         'wo_greige'   => 'nullable|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'Validation failed',
    //             'errors'  => $validator->errors(),
    //         ], 422);
    //     }

    //     $validated  = $validator->validated();
    //     $customerId = $validated['customer_id'];
    //     $startDate  = $validated['start_date'];
    //     $endDate    = $validated['end_date'];
    //     $poNo       = $validated['po_no'] ?? null;
    //     $noWo       = $validated['no_wo'] ?? null;
    //     $woGreige = $validated['wo_greige'] ?? null;

    //     $qtySumByKartu = \DB::table('trn_inspecting as i')
    //     ->join('inspecting_item as ii', 'ii.inspecting_id', '=', 'i.id')
    //     ->select(
    //         'i.kartu_process_dyeing_id',
    //         'ii.grade',
    //         \DB::raw('SUM(ii.qty) as qty_sum'),
    //         \DB::raw('MIN(i.unit) as unit')
    //     )
    //     ->where('i.status', 4)
    //     ->groupBy('i.kartu_process_dyeing_id', 'ii.grade')
    //     ->get()
    //     ->groupBy('kartu_process_dyeing_id')
    //     ->map(function ($rows) {
    //         $data = [
    //             'unit'   => $rows->first()->unit,
    //             'grades' => [],
    //         ];

    //         foreach ($rows as $row) {
    //             $data['grades'][$row->grade] = $row->qty_sum;
    //         }

    //         return $data;
    //     });

    //     // Ambil data SC dengan relasi MO, WO, proses
    //     $scs = Sc::with([
    //         'mo' => function ($query) use ($poNo, $noWo, $woGreige) {
    //             $query->where('status', 3)
    //                 ->where('process', 1)
    //                 ->when($poNo, function ($q) use ($poNo) {
    //                     $q->whereNotNull('no_po')
    //                     ->where('no_po', 'like', "%{$poNo}%");
    //                 })
    //                 // ->with([
    //                 //     'wo.woColor.kartuProsesDyeings.kartuProsesDyeingItem',
    //                 //     'wo.woColor.kartuProsesDyeings.kartuProsesDyeingProcesses.processDyeing',
    //                 // ]);
    //                 ->with([
    //                 'wo' => function ($woQuery) use ($noWo, $woGreige ) {
    //                     $woQuery->when($noWo, function ($wq) use ($noWo) {
    //                         $wq->whereNotNull('no')
    //                         ->where('no', 'like', "%{$noWo}%");
    //                     })
    //                     ->when($woGreige, function ($wq) use ($woGreige) {
    //                             $wq->whereHas('greige', function ($gq) use ($woGreige) {
    //                             $gq->whereRaw('LOWER(nama_kain) LIKE ?', ['%' . strtolower($woGreige) . '%']);
    //                             });
    //                         })
    //                     ->with([
    //                         'greige',
    //                         'woColor.kartuProsesDyeings.kartuProsesDyeingItem',
    //                         'woColor.kartuProsesDyeings.kartuProsesDyeingProcesses.processDyeing',
    //                     ]);
    //                 }
    //             ]);
    //         }
    //     ])
    //     ->where('cust_id', $customerId)
    //     ->whereBetween('date', [$startDate, $endDate])
    //     ->get();

    //     // Format hasil group per SC
    //     $formatted = $scs->map(function ($sc) use ($qtySumByKartu) {
    //         return [
    //             'sc_no'   => $sc->no,
    //             'sc_date' => $sc->date,
    //             'mo_list' => $sc->mo ? $sc->mo->map(function ($mo) use ($qtySumByKartu) {
    //                 return [
    //                     'mo_id'   => $mo->id,
    //                     'mo_no'   => $mo->no,
    //                     'mo_date' => $mo->date,
    //                     'mo_po'   => $mo->no_po,
    //                     'wo_list' => $mo->wo ? $mo->wo->map(function ($wo) use ($qtySumByKartu) {
    //                         return [
    //                             'wo_no'     => $wo->no,
    //                             'wo_date'   => $wo->date,
    //                             'wo_greige' => optional($wo->greige)->nama_kain,
    //                             'wo_colors' => $wo->woColor->map(function ($woColor) use ($qtySumByKartu) {
    //                                 return [
    //                                     'qty'   => $woColor->qty,
    //                                     'color' => optional($woColor->moColor)->color,
    //                                     'kartu_proses' => $woColor->kartuProsesDyeings->map(function ($kp) use ($qtySumByKartu) {
    //                                         $qtyData = $qtySumByKartu->get($kp->id, ['grades' => [], 'unit' => null]);

    //                                         $unitName = $qtyData['unit'] == 1 ? 'Yard' : ($qtyData['unit'] == 2 ? 'Meter' : null);

    //                                         $gradeLabels = [
    //                                             'A'           => 'A',
    //                                             'B'           => 'B',
    //                                             'C'           => 'C',
    //                                             'PK'          => 'PK',
    //                                             'Sample'      => 'Sample',
    //                                             'A_PLUS'      => 'A+',
    //                                             'A_ASTERISK'  => 'A*',
    //                                         ];

    //                                         $qtyGrades = [
    //                                             'A'           => ($qtyData['grades'][self::A] ?? 0),
    //                                             'B'           => ($qtyData['grades'][self::B] ?? 0) ,
    //                                             'C'           => ($qtyData['grades'][self::C] ?? 0) ,
    //                                             'PK'          => ($qtyData['grades'][self::PK] ?? 0) ,
    //                                             'Sample'      => ($qtyData['grades'][self::Sample] ?? 0) ,
    //                                             'A_PLUS'      => ($qtyData['grades'][self::A_PLUS] ?? 0) ,
    //                                             'A_ASTERISK'  => ($qtyData['grades'][self::A_ASTERISK] ?? 0)
    //                                         ];

    //                                         return [
    //                                             'no'   => $kp->nomor_kartu,
    //                                             'date' => $kp->date,
    //                                             'berat' => $kp->berat,
    //                                             'lebar' => $kp->lebar,
    //                                             'approved' => $kp->approved_at,
    //                                             // 'qty_sum_terima' => collect($qtyGrades)->map(function($v, $k) use ($gradeLabels) {
    //                                             //     return $gradeLabels[$k] . ': ' . $v;
    //                                             // })->implode(', '),
    //                                             'qty_sum_terima' => collect($qtyGrades)->mapWithKeys(function($v, $k) use ($gradeLabels) {
    //                                                 return [$gradeLabels[$k] => $v];
    //                                             }),
    //                                             'unit' => $unitName,

    //                                             'items' => $kp->kartuProsesDyeingItem->map(function ($item) {
    //                                                 return [
    //                                                     'roll_no' => $item->roll_no,
    //                                                     'meter'   => $item->meter,
    //                                                 ];
    //                                             }),

    //                                             'panjang_greige' => $kp->kartuProsesDyeingItem->sum('panjang_m'),

    //                                             'processes' => $kp->kartuProsesDyeingProcesses
    //                                                 ? $kp->kartuProsesDyeingProcesses
    //                                                     ->whereIn('process_id', [1, 3, 8, 15, 18, 19, 21, 23, 24])
    //                                                     ->map(function ($process) {
    //                                                         $decoded = json_decode($process->value, true);
    //                                                         $tanggal = $decoded['tanggal'] ?? null;

    //                                                         return [
    //                                                             'process_id'   => $process->process_id,
    //                                                             'process_name' => optional($process->processDyeing)->nama_proses,
    //                                                             'value'        => $tanggal,
    //                                                             'note'         => $process->note,
    //                                                         ];
    //                                                     })
    //                                                     ->values()
    //                                                 : [],
    //                                         ];
    //                                     }),
    //                                 ];
    //                             }),
    //                         ];
    //                     }) : [],
    //                 ];
    //             }) : [],
    //         ];
    //     });

    //     // ✅ Hitung total_by_grade dari hasil formatted
    //     $totals = [
    //         'A'   => 0,
    //         'B'   => 0,
    //         'C'   => 0,
    //         'PK'  => 0,
    //         'Sample' => 0,
    //         'A+'  => 0,   // ✅ pakai label final
    //         'A*'  => 0,   // ✅ pakai label final
    //     ];

    //     foreach ($formatted as $sc) {
    //         foreach ($sc['mo_list'] as $mo) {
    //             foreach ($mo['wo_list'] as $wo) {
    //                 foreach ($wo['wo_colors'] as $woColor) {
    //                     foreach ($woColor['kartu_proses'] as $kp) {
    //                         foreach ($kp['qty_sum_terima'] as $grade => $val) {
    //                             $totals[$grade] += $val;
    //                         }
    //                     }
    //                 }
    //             }
    //         }
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'data'   => [
    //             'grouped_outstanding_items' => $formatted,
    //             'total_by_grade' => $totals ?? [
    //             'A' => 0, 'B' => 0, 'C' => 0,
    //             'PK' => 0, 'Sample' => 0,
    //             'A+' => 0, 'A*' => 0,
    //         ],
    //         ],
    //     ]);
    // }



    public function outstanding(Request $request)
    {
        // Validasi input
        $query = $request->query();

        $validator = Validator::make($query, [
            'customer_id' => 'required|numeric',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date',
            'po_no'       => 'nullable|string',
            'no_wo'       => 'nullable|string',
            'wo_greige'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated  = $validator->validated();
        $customerId = $validated['customer_id'];
        $startDate  = $validated['start_date'];
        $endDate    = $validated['end_date'];
        $poNo       = $validated['po_no'] ?? null;
        $noWo       = $validated['no_wo'] ?? null;
        $woGreige   = $validated['wo_greige'] ?? null;

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
            'mo' => function ($query) use ($poNo, $noWo, $woGreige) {
                $query->where('status', 3)
                    ->where('process', 1)
                    ->when($poNo, function ($q) use ($poNo) {
                        $q->whereNotNull('no_po')
                        ->where('no_po', 'like', "%{$poNo}%");
                    })
                    ->with([
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
        ->where('cust_id', $customerId)
        ->whereBetween('date', [$startDate, $endDate])
        ->get();

        // canonical grade keys
        $gradeKeys = ['A','A+','A*','B','C','PK','Sample'];

        // Format hasil group per SC
        $formatted = $scs->map(function ($sc) use ($qtySumByKartu, $qtyMklbj, $gradeKeys) {
            return [
                'sc_no'   => $sc->no,
                'sc_date' => $sc->date,
                'mo_list' => $sc->mo ? $sc->mo->map(function ($mo) use ($qtySumByKartu, $qtyMklbj, $gradeKeys) {
                    return [
                        'mo_id'   => $mo->id,
                        'mo_no'   => $mo->no,
                        'mo_date' => $mo->date,
                        'mo_po'   => $mo->no_po,
                        'wo_list' => $mo->wo ? $mo->wo->map(function ($wo) use ($qtySumByKartu, $qtyMklbj, $gradeKeys) {
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

                                    // build kartu_proses array: KPI values ONLY (tidak menambahkan MKLBJ ke tiap KP)
                                    $kartuProsesArr = $woColor->kartuProsesDyeings->map(function ($kp) use ($qtySumByKartu) {
                                        $qtyData = $qtySumByKartu->get($kp->id, ['grades' => [], 'unit' => null]);
                                        $unitName = $qtyData['unit'] == 1 ? 'Yard' : ($qtyData['unit'] == 2 ? 'Meter' : null);

                                        // only kartu proses values here (no MKLBJ)
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

                                    // Jika ada MKLBJ (nilai > 0), append 1 MKLBJ row (hanya sekali) sehingga frontend yang menjumlahkan baris kartu_proses mendapatkan MKLBJ satu kali
                                    $hasMklbj = false;
                                    foreach ($mklbjGrades as $v) {
                                        if ((float)$v > 0) { $hasMklbj = true; break; }
                                    }

                                    if ($hasMklbj) {
                                        // normalisasi MKLBJ agar punya semua gradeKeys (0 bila kosong)
                                        $mklbjNormalized = array_fill_keys($gradeKeys, 0);
                                        foreach ($gradeKeys as $gk) {
                                            if (isset($mklbjGrades[$gk])) {
                                                $mklbjNormalized[$gk] = 0 + $mklbjGrades[$gk];
                                            }
                                        }
                                        // append MKLBJ row
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

                                    // jika tetap kosong (tidak ada KP & tidak ada MKLBJ) buat dummy kosong
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
                                        'kartu_proses' => $kartuProsesArr->values(), // includes appended MKLBJ row if present
                                        'mklbj'        => $mklbjGrades,
                                        'total_grades' => $combinedNormalized,
                                    ];
                                })->values(),
                            ];
                        }) : [],
                    ];
                }) : [],
            ];
        });

        // // Hitung total_by_grade dari hasil formatted (canonical gradeKeys)
        // $totals = array_fill_keys($gradeKeys, 0);

        // foreach ($formatted as $sc) {
        //     foreach ($sc['mo_list'] as $mo) {
        //         foreach ($mo['wo_list'] as $wo) {
        //             foreach ($wo['wo_colors'] as $woColor) {
        //                 $tg = $woColor['total_grades'] ?? [];
        //                 foreach ($gradeKeys as $gk) {
        //                     $totals[$gk] += 0 + ($tg[$gk] ?? 0);
        //                 }
        //             }
        //         }
        //     }
        // }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'grouped_outstanding_items' => $formatted,
                // 'total_by_grade' => $totals,
            ],
        ]);
    }



    public function outstandingPrinting(Request $request)
    {
        // Validasi input
        $query = $request->query();

        $validator = Validator::make($query, [
            'customer_id' => 'required|numeric',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date',
            'po_no'       => 'nullable|string',
            'no_wo'       => 'nullable|string',
            'wo_greige'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated  = $validator->validated();
        $customerId = $validated['customer_id'];
        $startDate  = $validated['start_date'];
        $endDate    = $validated['end_date'];
        $poNo       = $validated['po_no'] ?? null;
        $noWo       = $validated['no_wo'] ?? null;
        $woGreige   = $validated['wo_greige'] ?? null;

        // Ambil qty per grade dari Inspecting (punya kartu proses)
        $qtySumByKartu = \DB::table('trn_inspecting as i')
            ->join('inspecting_item as ii', 'ii.inspecting_id', '=', 'i.id')
            ->select(
                'i.kartu_process_printing_id',
                'ii.grade',
                \DB::raw('SUM(ii.qty) as qty_sum'),
                \DB::raw('MIN(i.unit) as unit')
            )
            ->where('i.status', 4)
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
            'mo' => function ($query) use ($poNo, $noWo, $woGreige) {
                $query->where('status', 3)
                    ->where('process', 2)
                    ->when($poNo, function ($q) use ($poNo) {
                        $q->whereNotNull('no_po')
                        ->where('no_po', 'like', "%{$poNo}%");
                    })
                    ->with([
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
                                    'woColor.kartuProsesPrintings.kartuProsesPrintingProcesses.processPrinting',
                                    'woColor.moColor',
                                ]);
                        }
                    ]);
            }
        ])
        ->where('cust_id', $customerId)
        ->whereBetween('date', [$startDate, $endDate])
        ->get();

        // canonical grade keys
        $gradeKeys = ['A','A+','A*','B','C','PK','Sample'];

        // Format hasil group per SC
        $formatted = $scs->map(function ($sc) use ($qtySumByKartu, $qtyMklbj, $gradeKeys) {
            return [
                'sc_no'   => $sc->no,
                'sc_date' => $sc->date,
                'mo_list' => collect($sc->mo)->map(function ($mo) use ($qtySumByKartu, $qtyMklbj, $gradeKeys) {
                    return [
                        'mo_id'   => $mo->id,
                        'mo_no'   => $mo->no,
                        'mo_date' => $mo->date,
                        'mo_po'   => $mo->no_po,
                        'wo_list' => collect($mo->wo)->map(function ($wo) use ($qtySumByKartu, $qtyMklbj, $gradeKeys) {
                            return [
                                'wo_no'     => $wo->no,
                                'wo_date'   => $wo->date,
                                'wo_greige' => optional($wo->greige)->nama_kain,
                                'wo_colors' => collect($wo->woColor)->map(function ($woColor) use ($qtySumByKartu, $qtyMklbj, $gradeKeys) {
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

                                    // build kartu_proses array: KPI values ONLY (tidak menambahkan MKLBJ ke tiap KP)
                                    $kartuProsesArr = collect($woColor->kartuProsesPrintings)->map(function ($kp) use ($qtySumByKartu, $gradeKeys) {
                                        $qtyData = $qtySumByKartu->get($kp->id, ['grades' => [], 'unit' => null]);
                                        $unitName = $qtyData['unit'] == 1 ? 'Yard' : ($qtyData['unit'] == 2 ? 'Meter' : null);

                                        // only kartu proses values here (no MKLBJ)
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
                                            'items' => collect($kp->kartuProsesDyeingItem)->map(function ($item) {
                                                return [
                                                    'roll_no' => $item->roll_no,
                                                    'meter'   => $item->meter,
                                                ];
                                            }),
                                            'panjang_greige' => collect($kp->kartuProsesDyeingItem)->sum('panjang_m'),
                                            'processes' => collect($kp->kartuProsesPrintingProcesses)
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
                                            'no' => 'INS2',
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

                                    // jika tetap kosong → dummy
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
                        })->values(),
                    ];
                })->values(),
            ];
        });


        // // Hitung total_by_grade dari hasil formatted (canonical gradeKeys)
        // $totals = array_fill_keys($gradeKeys, 0);

        // foreach ($formatted as $sc) {
        //     foreach ($sc['mo_list'] as $mo) {
        //         foreach ($mo['wo_list'] as $wo) {
        //             foreach ($wo['wo_colors'] as $woColor) {
        //                 $tg = $woColor['total_grades'] ?? [];
        //                 foreach ($gradeKeys as $gk) {
        //                     $totals[$gk] += 0 + ($tg[$gk] ?? 0);
        //                 }
        //             }
        //         }
        //     }
        // }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'grouped_outstanding_items' => $formatted,
                // 'total_by_grade' => $totals,
            ],
        ]);
    }





}
