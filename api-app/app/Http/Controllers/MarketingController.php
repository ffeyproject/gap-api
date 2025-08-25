<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Sc;

class MarketingController extends Controller
{

// public function outstanding(Request $request)
// {
//     // Ambil query string dan validasi
//     $query = $request->query();

//     $validator = Validator::make($query, [
//         'customer_id' => 'required|numeric',
//         'start_date'  => 'required|date',
//         'end_date'    => 'required|date',
//     ]);

//     if ($validator->fails()) {
//         return response()->json([
//             'status'  => 'error',
//             'message' => 'Validation failed',
//             'errors'  => $validator->errors(),
//         ], 422);
//     }

//     $validated   = $validator->validated();
//     $customerId  = $validated['customer_id'];
//     $startDate   = $validated['start_date'];
//     $endDate     = $validated['end_date'];


//      // Query agregasi sum qty per kartu_process_dyeing_id untuk inspecting status=4 (diterima gudang)
//     $qtySumByKartu = \DB::table('trn_inspecting as i')
//         ->join('trn_inspecting_item as ii', 'ii.inspecting_id', '=', 'i.id')
//         ->select('i.kartu_process_dyeing_id', \DB::raw('SUM(ii.qty) as qty_sum'))
//         ->where('i.status', 4)
//         ->groupBy('i.kartu_process_dyeing_id')
//         ->get()
//         ->keyBy('kartu_process_dyeing_id')
//        ->map(function ($row) {
//             return $row->qty_sum;
//         });

//     // Ambil data SC dengan relasi MO, WO, dan proses
//     $scs = Sc::with([
//         'mo' => function ($query) {
//             $query->where('status', 3)
//                 ->where('process', 1)
//                 ->with([
//                     'wo.woColor.kartuProsesDyeings.kartuProsesDyeingItem',
//                     'wo.woColor.kartuProsesDyeings.kartuProsesDyeingProcesses.processDyeing',
//                     'wo.woColor.kartuProsesDyeings.inspectings',
//                 ]);
//         }
//     ])
//     ->where('cust_id', $customerId)
//     ->whereBetween('date', [$startDate, $endDate])
//     ->get();

//     // Format hasil group per SC
//     $formatted = $scs->map(function ($sc) {
//         return [
//             'sc_no'   => $sc->no,
//             'sc_date' => $sc->date,
//             'mo_list' => $sc->mo ? $sc->mo->map(function ($mo) {
//                 return [
//                     'mo_no'   => $mo->no,
//                     'mo_date' => $mo->date,
//                     'mo_po'   => $mo->no_po,
//                     'wo_list' => $mo->wo ? $mo->wo->map(function ($wo) {
//                         return [
//                             'wo_no'     => $wo->no,
//                             'wo_date'   => $wo->date,
//                             'wo_greige' => optional($wo->greige)->nama_kain,
//                             'wo_colors' => $wo->woColor->map(function ($woColor) {
//                                 return [
//                                     'qty'   => $woColor->qty,
//                                     'color' => optional($woColor->moColor)->color,
//                                     'kartu_proses' => $woColor->kartuProsesDyeings->map(function ($kp) {
//                                         return [
//                                             'no'             => $kp->nomor_kartu,
//                                             'date'           => $kp->date,
//                                             'berat'          => $kp->berat,
//                                             'lebar'          => $kp->lebar,
//                                             'approved'       => $kp->approved_at,
//                                             'items'          => $kp->kartuProsesDyeingItem->map(function ($item) {
//                                                 return [
//                                                     'roll_no' => $item->roll_no,
//                                                     'meter'   => $item->meter,
//                                                 ];
//                                             }),
//                                            'panjang_greige' => $kp->kartuProsesDyeingItem->sum('panjang_m'),
//                                            'processes' => $kp->kartuProsesDyeingProcesses
//                                             ? $kp->kartuProsesDyeingProcesses
//                                                 ->whereIn('process_id', [1, 3, 8, 15, 18, 19, 21, 23, 24])
//                                                 ->map(function ($process) {
//                                                     $decoded = json_decode($process->value, true);
//                                                     $tanggal = $decoded['tanggal'] ?? null;

//                                                     return [
//                                                         'process_id'   => $process->process_id,
//                                                         'process_name' => optional($process->processDyeing)->nama_proses,
//                                                         'value'        => $tanggal,
//                                                         'note'         => $process->note,
//                                                     ];
//                                                 })
//                                                 ->values()
//                                             : [],
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

//     return response()->json([
//         'status' => 'success',
//         'data'   => [
//             'grouped_outstanding_items' => $formatted,
//         ],
//     ]);
// }

     private const A           = 1;
    private const B           = 2;
    private const C           = 3;
    private const PK          = 4;
    private const Sample      = 5;
    private const A_PLUS  = 7;
    private const A_ASTERISK  = 8;

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
    $woGreige = $validated['wo_greige'] ?? null;

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

    // Ambil data SC dengan relasi MO, WO, proses
    $scs = Sc::with([
        'mo' => function ($query) use ($poNo, $noWo, $woGreige) {
            $query->where('status', 3)
                ->where('process', 1)
                ->when($poNo, function ($q) use ($poNo) {
                    $q->whereNotNull('no_po')
                      ->where('no_po', 'like', "%{$poNo}%");
                })
                // ->with([
                //     'wo.woColor.kartuProsesDyeings.kartuProsesDyeingItem',
                //     'wo.woColor.kartuProsesDyeings.kartuProsesDyeingProcesses.processDyeing',
                // ]);
                ->with([
                'wo' => function ($woQuery) use ($noWo, $woGreige ) {
                    $woQuery->when($noWo, function ($wq) use ($noWo) {
                        $wq->whereNotNull('no')
                           ->where('no', 'like', "%{$noWo}%");
                    })
                    ->when($woGreige, function ($wq) use ($woGreige) {
                            $wq->whereHas('greige', function ($gq) use ($woGreige) {
                                $gq->where('nama_kain', 'like', "%{$woGreige}%");
                            });
                        })
                    ->with([
                        'greige',
                        'woColor.kartuProsesDyeings.kartuProsesDyeingItem',
                        'woColor.kartuProsesDyeings.kartuProsesDyeingProcesses.processDyeing',
                    ]);
                }
            ]);
        }
    ])
    ->where('cust_id', $customerId)
    ->whereBetween('date', [$startDate, $endDate])
    ->get();

    // Format hasil group per SC
    $formatted = $scs->map(function ($sc) use ($qtySumByKartu) {
        return [
            'sc_no'   => $sc->no,
            'sc_date' => $sc->date,
            'mo_list' => $sc->mo ? $sc->mo->map(function ($mo) use ($qtySumByKartu) {
                return [
                    'mo_id'   => $mo->id,
                    'mo_no'   => $mo->no,
                    'mo_date' => $mo->date,
                    'mo_po'   => $mo->no_po,
                    'wo_list' => $mo->wo ? $mo->wo->map(function ($wo) use ($qtySumByKartu) {
                        return [
                            'wo_no'     => $wo->no,
                            'wo_date'   => $wo->date,
                            'wo_greige' => optional($wo->greige)->nama_kain,
                            'wo_colors' => $wo->woColor->map(function ($woColor) use ($qtySumByKartu) {
                                return [
                                    'qty'   => $woColor->qty,
                                    'color' => optional($woColor->moColor)->color,
                                    'kartu_proses' => $woColor->kartuProsesDyeings->map(function ($kp) use ($qtySumByKartu) {
                                        $qtyData = $qtySumByKartu->get($kp->id, ['grades' => [], 'unit' => null]);

                                        $unitName = $qtyData['unit'] == 1 ? 'Yard' : ($qtyData['unit'] == 2 ? 'Meter' : null);

                                        $gradeLabels = [
                                            'A'           => 'A',
                                            'B'           => 'B',
                                            'C'           => 'C',
                                            'PK'          => 'PK',
                                            'Sample'      => 'Sample',
                                            'A_PLUS'      => 'A+',
                                            'A_ASTERISK'  => 'A*',
                                        ];

                                        $qtyGrades = [
                                            'A'           => ($qtyData['grades'][self::A] ?? 0),
                                            'B'           => ($qtyData['grades'][self::B] ?? 0) ,
                                            'C'           => ($qtyData['grades'][self::C] ?? 0) ,
                                            'PK'          => ($qtyData['grades'][self::PK] ?? 0) ,
                                            'Sample'      => ($qtyData['grades'][self::Sample] ?? 0) ,
                                            'A_PLUS'      => ($qtyData['grades'][self::A_PLUS] ?? 0) ,
                                            'A_ASTERISK'  => ($qtyData['grades'][self::A_ASTERISK] ?? 0)
                                        ];

                                        return [
                                            'no'   => $kp->nomor_kartu,
                                            'date' => $kp->date,
                                            'berat' => $kp->berat,
                                            'lebar' => $kp->lebar,
                                            'approved' => $kp->approved_at,
                                            // 'qty_sum_terima' => collect($qtyGrades)->map(function($v, $k) use ($gradeLabels) {
                                            //     return $gradeLabels[$k] . ': ' . $v;
                                            // })->implode(', '),
                                            'qty_sum_terima' => collect($qtyGrades)->mapWithKeys(function($v, $k) use ($gradeLabels) {
                                                return [$gradeLabels[$k] => $v];
                                            }),
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
                                    }),
                                ];
                            }),
                        ];
                    }) : [],
                ];
            }) : [],
        ];
    });

    return response()->json([
        'status' => 'success',
        'data'   => [
            'grouped_outstanding_items' => $formatted,
        ],
    ]);
}




}