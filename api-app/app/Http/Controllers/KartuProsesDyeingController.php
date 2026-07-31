<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\KartuProsesDyeing;
use App\KartuProsesPrinting;
use App\Inspecting;
use App\InspectingMklbj;
use App\User;
use App\Wo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;


class KartuProsesDyeingController extends Controller
{
    //

    public function index()
    {
        $kartuProsesDyeing = KartuProsesDyeing::all();

        return response()->json([
            'success' => true,
            'data' => $kartuProsesDyeing
        ]);
    }



    public function getWo()
    {
        // Ensure you have a valid Authorization header with Bearer token
        $token = request()->header('Authorization');
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization token is missing'
            ], 400);
        }

        // Clean up the token to extract the actual value
        $token = str_replace('Bearer ', '', $token);

        // Retrieve the user associated with the token (if needed)
        $user = User::where('verification_token', $token)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        $woData = Wo::with([
            'sc',
            'scGreige',
            'mo',
            'greige',
            'user',
            'marketing',
            'batalBy',
            'closedBy',
            'createdBy',
            'handling',
            'papperTube',
            'woColor' => function ($query) {
                $query->select('id', 'wo_id', 'mo_color_id')->with([
                    'wo',
                    'moColor' => function ($query) {
                        $query->select('id', 'mo_id', 'color');
                    }
                ]);
            }
        ])
            ->whereHas('mo', function ($query) {
                $query->where('process', 1);
            })
            ->where('jenis_order', 1)
            ->where('status', 5)
            ->limit(300)
            ->orderBy('id', 'desc')
            ->get();

        // Mengembalikan data dalam format JSON
        return response()->json($woData);
    }



    public function searchGetWo(Request $request)
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization token is missing'
            ], 400);
        }

        $token = str_replace('Bearer ', '', $token);
        $user = User::where('verification_token', $token)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token'
                ], 401);
            }

            $noWo = $request->query('no');
            if (!$noWo) {
                return response()->json([]);
            }

            $woData = Wo::with([
                'scGreige',
                'woColor' => function ($query) {
                    $query->select('id', 'wo_id', 'mo_color_id')->with([
                        'moColor' => function ($query) {
                            $query->select('id', 'mo_id', 'color');
                        }
                    ]);
                }
            ])
            ->whereHas('mo', function ($query) {
                $query->whereIn('process', [1, 2]);
            })
            ->whereIn('jenis_order', [1, 2, 3])
            ->where('status', 5)
            ->where('no', 'like', '%' . $noWo . '%')
            ->limit(100)
            ->orderBy('id', 'desc')
            ->get();

            $result = $woData->map(function ($wo) {
                return [
                    'id' => $wo->id,
                    'no' => $wo->no,
                    'mo_id' => $wo->mo_id,
                    'sc_id' => $wo->sc_id,
                    'sc_greige_id' => $wo->sc_greige_id,
                    'sc_greige' => $wo->scGreige ? [
                        'id' => $wo->scGreige->id,
                        'process' => $wo->scGreige->process,
                        'lebar_kain' => $wo->scGreige->lebar_kain,
                        'merek' => $wo->scGreige->merek,
                        'grade' => $wo->scGreige->grade,
                        'piece_length' => $wo->scGreige->piece_length,
                        'unit_price' => $wo->scGreige->unit_price,
                        'qty' => $wo->scGreige->qty,
                        'note' => $wo->scGreige->note,
                    ] : null,
                    'wo_color_id' => $wo->woColor->pluck('id'),
                    'mo_colors' => $wo->woColor->map(function ($woColor) {
                        return [
                            'wo_color_id' => $woColor->id,
                            'mo_color_id' => $woColor->mo_color_id,
                            'color' => $woColor->moColor->color ?? null,
                        ];
                    }),
                ];
        });

        return response()->json($result);
    }

    public function getKartuProsesDyeingById($id)
    {
       $kartuProsesDyeing = KartuProsesDyeing::with('kartuProsesDyeingItem')->find($id);

        if ($kartuProsesDyeing) {
            return response()->json([
                'success' => true,
                'data' => $kartuProsesDyeing
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Data not found!'
            ]);
        }
    }



    public function getKartuProsesPrintingById($id)
    {
        $kartuProsesPrinting = KartuProsesPrinting::with('kartuProsesPrintingItem')->find($id);
        if ($kartuProsesPrinting) {
            return response()->json([
                'success' => true,
                'data' => $kartuProsesPrinting
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Data not found!'
            ]);
        }
    }


    public function store(Request $request)
    {
        $kartuProsesDyeing = new KartuProsesDyeing();
        $kartuProsesDyeing->no_kartu = $request->no_kartu;
        $kartuProsesDyeing->id_wo = $request->id_wo;
        $kartuProsesDyeing->id_mo = $request->id_mo;
        $kartuProsesDyeing->id_sc = $request->id_sc;
        $kartuProsesDyeing->id_sc_greige = $request->id_sc_greige;
        $kartuProsesDyeing->created_by = Auth::user()->id;
        $kartuProsesDyeing->updated_by = Auth::user()->id;

        if ($kartuProsesDyeing->save()) {
            return response()->json([
                'success' => true,
                'data' => $kartuProsesDyeing
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data kartu proses dyeing'
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $kartuProsesDyeing = KartuProsesDyeing::find($id);
        if ($kartuProsesDyeing) {
            $kartuProsesDyeing->no_kartu = $request->no_kartu;
            $kartuProsesDyeing->id_wo = $request->id_wo;
            $kartuProsesDyeing->id_mo = $request->id_mo;
            $kartuProsesDyeing->id_sc = $request->id_sc;
            $kartuProsesDyeing->id_sc_greige = $request->id_sc_greige;
            $kartuProsesDyeing->updated_by = Auth::user()->id;

            if ($kartuProsesDyeing->save()) {
                return response()->json([
                    'success' => true,
                    'data' => $kartuProsesDyeing
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengupdate data kartu proses dyeing'
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Data kartu proses dyeing tidak ditemukan'
            ]);
        }
    }


    public function destroy($id)
    {
        $kartuProsesDyeing = KartuProsesDyeing::find($id);
        if ($kartuProsesDyeing) {
            if ($kartuProsesDyeing->delete()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data kartu proses dyeing berhasil dihapus'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus data kartu proses dyeing'
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Data kartu proses dyeing tidak ditemukan'
            ]);
        }
    }

    public function rekapDyeing(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $noKartu = $request->input('no_kartu');
        $noDo = $request->input('no_do');
        $motif = $request->input('motif');
        $limit = $request->input('limit') ?? $request->input('per_page');
        $page = $request->input('page');

        // Search Bulan & Tahun WO (Mendukung param: month_wo, bulan_wo, month, bulan, year_wo, tahun_wo, year, tahun)
        $woMonth = $request->input('month_wo') ?? $request->input('bulan_wo') ?? $request->input('month') ?? $request->input('bulan');
        $woYear = $request->input('year_wo') ?? $request->input('tahun_wo') ?? $request->input('year') ?? $request->input('tahun');

        $query = Inspecting::with([
            'kartuProcessDyeing.kartuProsesDyeingItem',
            'kartuProcessDyeing.kartuProsesDyeingProcesses.processDyeing',
            'kartuProcessDyeing.wo.greige.GreigeGroup',
            'kartuProcessDyeing.wo.scGreige',
            'kartuProcessDyeing.mo',
            'kartuProcessDyeing.woColor.moColor',
            'kartuProcessDyeing.moColor',
            'createdBy',
            'inspectingItem.stock.greige',
            'inspectingItem.stock.greigeGroup',
            'inspectingItem.defect_item',
        ])
        ->whereIn('status', [4, 5])
        ->whereNotNull('kartu_process_dyeing_id')
        ->whereNull('kartu_process_printing_id');

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        if ($woYear || $woMonth) {
            $query->whereHas('kartuProcessDyeing.wo', function ($woQ) use ($woYear, $woMonth) {
                if ($woYear) {
                    $woQ->whereYear('date', $woYear);
                }
                if ($woMonth) {
                    $woQ->whereMonth('date', $woMonth);
                }
            });
        }

        if ($noKartu) {
            $query->whereHas('kartuProcessDyeing', function ($q) use ($noKartu) {
                $q->where('nomor_kartu', 'like', '%' . $noKartu . '%');
            });
        }

        if ($noDo) {
            $query->where(function ($q) use ($noDo) {
                $q->whereHas('kartuProcessDyeing.wo', function ($woQ) use ($noDo) {
                    $woQ->where('no', 'like', '%' . $noDo . '%');
                })->orWhereHas('inspectingItem.stock', function ($sq) use ($noDo) {
                    $sq->where('no_document', 'like', '%' . $noDo . '%')
                      ->orWhere('pengirim', 'like', '%' . $noDo . '%');
                });
            });
        }

        if ($motif) {
            $query->where(function ($q) use ($motif) {
                $q->whereHas('inspectingItem.stock.greige', function ($sq) use ($motif) {
                    $sq->where('nama_kain', 'like', '%' . $motif . '%');
                })->orWhereHas('kartuProcessDyeing.wo.greige', function ($sq) use ($motif) {
                    $sq->where('nama_kain', 'like', '%' . $motif . '%');
                });
            });
        }

        if ($limit) {
            $limitVal = (int) $limit;
            if ($page) {
                $offset = ((int)$page - 1) * $limitVal;
                $query->offset($offset)->limit($limitVal);
            } else {
                $query->limit($limitVal);
            }
        }

        $inspectings = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->get();

        $reportData = [];

        $summaryTotals = [
            'total_qty_finish' => 0,
            'total_grey' => 0,
            'total_grade_a' => 0,          // 1
            'total_grade_b' => 0,          // 2
            'total_grade_c' => 0,          // 3
            'total_grade_pk' => 0,         // 4
            'total_grade_sample' => 0,     // 5
            'total_grade_a_plus' => 0,     // 7
            'total_grade_a_asterisk' => 0, // 8
            'grand_total_yard' => 0,
            'average_pass_rate' => 0,
        ];

        foreach ($inspectings as $ins) {
            $kp = $ins->kartuProcessDyeing;
            $wo = optional($kp)->wo;
            $mo = optional($kp)->mo;
            $firstItem = $ins->inspectingItem->first();
            $stock = optional($firstItem)->stock;

            // DO Info (Diambil dari no_wo & tgl_wo)
            $doDoc = optional($wo)->no ?? optional($stock)->no_document ?? optional($stock)->pengirim ?? optional($kp)->no_urut ?? '-';
            $tglMasukDo = optional($wo)->date ?? optional($stock)->date ?? optional($stock)->created_at;
            $tglMasukDoFormatted = $tglMasukDo ? Carbon::parse($tglMasukDo)->format('d-m-Y') : '-';

            // Unit DO & Unit Finish
            $unitDoVal = optional($stock)->unit ?? optional(optional($wo)->scGreige)->unit ?? 1;
            $unitDoLabel = ($unitDoVal == 2 || strtolower((string)$unitDoVal) === 'meter') ? 'M' : 'Y';

            $unitFinishVal = $ins->unit ?? 1;
            $unitFinishLabel = ($unitFinishVal == 2 || strtolower((string)$unitFinishVal) === 'meter') ? 'M' : 'Y';
            $unitLabel = $unitFinishLabel; // Untuk konversi grading & defect (berdasarkan unit inspect)

            // Motif Greige & Artikel
            $motifGreige = optional($stock)->greige->nama_kain
                ?? optional(optional($wo)->greige)->nama_kain
                ?? optional(optional($stock)->greigeGroup)->nama_kain
                ?? '-';

            $artikel = optional($mo)->article ?? optional($wo)->article ?? '-';

            // Color code & name
            $moColor = optional(optional($kp)->woColor)->moColor ?? optional($kp)->moColor;
            $kodeWarna = optional($moColor)->code ?? optional($moColor)->color_code ?? '-';
            $warna = optional($moColor)->color ?? optional($moColor)->nama_warna ?? '-';

            // Order Qty & Finish Qty (qty order dari batch woColor)
            $woColor = optional($kp)->woColor;
            $qtyBatchColor = optional($woColor)->qty ?? optional($wo)->qty_batch ?? optional($mo)->qty_batch;
            $qtyOrderStr = ($qtyBatchColor !== null && $qtyBatchColor !== '') ? ($qtyBatchColor . ' BATCH') : '-';
            
            // Hitung Qty Finish (Yard / Meter) dari batch WO Color & GreigeGroup (qty_per_batch, nilai_penyusutan)
            $greigeGroup = optional($stock)->greigeGroup ?? optional(optional($wo)->greige)->GreigeGroup;
            $qtyBatchVal = (float) ($qtyBatchColor ?? 0);
            $qtyPerBatch = (float) (optional($greigeGroup)->qty_per_batch ?? 0);
            $penyusutan = (float) (optional($greigeGroup)->nilai_penyusutan ?? 0);

            if ($qtyBatchVal > 0 && $qtyPerBatch > 0) {
                $totalGreigeMeter = $qtyBatchVal * $qtyPerBatch;
                $finishMeterCalc = $totalGreigeMeter * (1 - ($penyusutan / 100));
                
                if ($unitFinishLabel === 'Y') {
                    $finishQty = $finishMeterCalc * 1.09361; // Finish (Yard)
                } else {
                    $finishQty = $finishMeterCalc; // Finish (Meter)
                }
            } else {
                $scGreige = optional($wo)->scGreige;
                $rawFinish = (float) (optional($scGreige)->qty ?? optional($wo)->qty ?? optional($mo)->qty ?? 0);
                if ($unitFinishLabel === 'Y') {
                    $finishQty = $rawFinish;
                } else {
                    $finishQty = $rawFinish / 1.09361;
                }
            }

            // Dates & Kartu info
            $tglKartu = optional($kp)->date ? Carbon::parse($kp->date)->format('d-m-Y') : '-';
            $noKartuStr = optional($kp)->nomor_kartu ?? '-';
            
            $kpNoLot = optional($kp)->no_lot;
            $kpNote = optional($kp)->note;
            $insNoLot = $ins->no_lot;
            $batch = '-';
            if (!empty($kpNoLot) && $kpNoLot !== '-') {
                $batch = $kpNoLot;
            } elseif (!empty($kpNote) && $kpNote !== '-') {
                $batch = $kpNote;
            } elseif (!empty($insNoLot) && $insNoLot !== '-') {
                $batch = $insNoLot;
            }

            // Total Grey
            $greyQty = (float) (optional($kp)->panjang_m ?? optional($kp)->kartuProsesDyeingItem->sum('panjang_m') ?? 0);

            // Asal Greige / Kain Dalam vs Luar (WJL/TSD/KL)
            $asalGreige = optional($stock)->asal_greige ?? optional($kp)->asal_greige;
            $greigeGroup = optional($stock)->greigeGroup ?? optional(optional($wo)->greige)->GreigeGroup;
            $greige = optional($stock)->greige ?? optional($wo)->greige;

            $jenisKain = optional($greigeGroup)->jenis_kain;
            $namaKainGroup = optional($greigeGroup)->nama_kain;
            $namaKainGreige = optional($greige)->nama_kain;

            $textSearch = strtolower((string)$jenisKain . ' ' . (string)$asalGreige . ' ' . (string)$namaKainGroup . ' ' . (string)$namaKainGreige);

            if ($asalGreige == 2 || $jenisKain == 2 || strpos($textSearch, 'beli') !== false) {
                $wjlTsdKl = 'K LUAR';
            } elseif ($jenisKain == 3 || strpos($textSearch, 'rapier') !== false || strpos($textSearch, 'tsd') !== false) {
                $wjlTsdKl = 'TSD';
            } elseif ($jenisKain == 1 || strpos($textSearch, 'water') !== false || strpos($textSearch, 'jet') !== false || strpos($textSearch, 'wjl') !== false) {
                $wjlTsdKl = 'WJL';
            } elseif ($asalGreige == 3) {
                $wjlTsdKl = 'TSD';
            } elseif ($asalGreige == 1) {
                $wjlTsdKl = 'WJL';
            } else {
                $wjlTsdKl = 'K LUAR';
            }

            // Inspecting info
            $counterTenter = '-';
            if ($kp && $kp->relationLoaded('kartuProsesDyeingProcesses') && $kp->kartuProsesDyeingProcesses) {
                $rfProcess = $kp->kartuProsesDyeingProcesses->first(function ($proc) {
                    return optional($proc->processDyeing)->nama_proses === 'Resin Finish' || $proc->process_id == 11;
                });

                if ($rfProcess && !empty($rfProcess->value)) {
                    $valData = is_array($rfProcess->value) ? $rfProcess->value : json_decode($rfProcess->value, true);
                    if (isset($valData['panjang_jadi']) && $valData['panjang_jadi'] !== '' && $valData['panjang_jadi'] !== '-') {
                        $counterTenter = $valData['panjang_jadi'];
                    }
                }
            }
            if ($counterTenter === '-' && !empty($ins->counter_tenter)) {
                $counterTenter = $ins->counter_tenter;
            }
            $tglInspect = $ins->date ? Carbon::parse($ins->date)->format('d-m-Y') : '-';
            $noMesin = $ins->inspection_table ? ('MC ' . $ins->inspection_table) : '-';
            $operator = optional($ins->createdBy)->name ?? optional($ins->createdBy)->full_name ?? '-';

            // Grading & Defect Breakdown per Grade Key (1=A, 2=B, 3=C, 4=PK, 5=Sample, 7=A+, 8=A*)
            $gradeKeys = [
                1 => 'grade_a',
                2 => 'grade_b',
                3 => 'grade_c',
                4 => 'grade_pk',
                5 => 'grade_sample',
                7 => 'grade_a_plus',
                8 => 'grade_a_asterisk',
            ];

            $gradeData = [
                'grade_a'          => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_b'          => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_c'          => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_pk'         => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_sample'     => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_a_plus'     => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_a_asterisk' => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_other'      => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
            ];

            foreach ($ins->inspectingItem as $item) {
                $itemQty = (float) $item->qty;
                if ($unitLabel === 'M') {
                    $itemQty = $itemQty * 1.09361; // Konversi ke Yard jika unitnya Meter
                }

                $g = (int) $item->grade;
                $targetKey = $gradeKeys[$g] ?? 'grade_other';
                $gradeData[$targetKey]['panjang'] += $itemQty;

                // Kumpulkan kode defect dari string item->defect
                if (!empty($item->defect)) {
                    $rawCodes = array_filter(explode(',', (string)$item->defect));
                    foreach ($rawCodes as $c) {
                        $cTrim = trim($c);
                        if ($cTrim !== '' && $cTrim !== '-') {
                            $gradeData[$targetKey]['codes'][] = $cTrim;
                        }
                    }
                }

                // Kumpulkan total meterage defect dari relation defect_item
                if ($item->relationLoaded('defect_item') && count($item->defect_item) > 0) {
                    foreach ($item->defect_item as $dItem) {
                        $dMeterage = (float) ($dItem->meterage ?? 0);
                        if ($unitLabel === 'M') {
                            $dMeterage = $dMeterage * 1.09361;
                        }
                        $gradeData[$targetKey]['total_defect'] += $dMeterage;
                    }
                }
            }

            // Format objek grading per kategori grade
            $gradingFormatted = [];
            foreach ($gradeData as $gKey => $gInfo) {
                $uniqueCodes = array_values(array_unique($gInfo['codes']));
                natsort($uniqueCodes);
                $kodeStr = !empty($uniqueCodes) ? implode(',', array_values($uniqueCodes)) : '-';

                $gradingFormatted[$gKey] = [
                    'panjang' => round($gInfo['panjang'], 2),
                    'total_defect' => round($gInfo['total_defect'], 2),
                    'kode_defect' => $kodeStr,
                ];
            }

            $totalYardInspected = 0;
            foreach ($gradeData as $gInfo) {
                $totalYardInspected += $gInfo['panjang'];
            }

            $passYard = $gradeData['grade_a']['panjang'] + $gradeData['grade_a_plus']['panjang'];
            $persenPass = $totalYardInspected > 0 ? round(($passYard / $totalYardInspected) * 100, 2) : 0;

            $gradingFormatted['total_yard'] = round($totalYardInspected, 2);
            $gradingFormatted['persen_pass'] = $persenPass;

            // Accumulate Summary Totals
            $summaryTotals['total_qty_finish'] += $finishQty;
            $summaryTotals['total_grey'] += $greyQty;
            $summaryTotals['total_grade_a'] += $gradeData['grade_a']['panjang'];
            $summaryTotals['total_grade_b'] += $gradeData['grade_b']['panjang'];
            $summaryTotals['total_grade_c'] += $gradeData['grade_c']['panjang'];
            $summaryTotals['total_grade_pk'] += $gradeData['grade_pk']['panjang'];
            $summaryTotals['total_grade_sample'] += $gradeData['grade_sample']['panjang'];
            $summaryTotals['total_grade_a_plus'] += $gradeData['grade_a_plus']['panjang'];
            $summaryTotals['total_grade_a_asterisk'] += $gradeData['grade_a_asterisk']['panjang'];
            $summaryTotals['grand_total_yard'] += $totalYardInspected;

            $tglWoStr = optional($wo)->date ? Carbon::parse($wo->date)->format('d-m-Y') : '-';
            $noWoStr = optional($wo)->no ?? '-';

            $reportData[] = [
                'id' => $ins->id,
                'no_wo' => $noWoStr,
                'tgl_wo' => $tglWoStr,
                'tgl_masuk_do' => $tglMasukDoFormatted,
                'no_do' => $doDoc,
                'unit_do' => $unitDoLabel,
                'motif_greige' => $motifGreige,
                'artikel' => $artikel,
                'kode_warna' => $kodeWarna,
                'warna' => $warna,
                'qty_order' => $qtyOrderStr,
                'qty_finish' => round($finishQty, 2),
                'unit_finish' => $unitFinishLabel,
                'tgl_kartu' => $tglKartu,
                'no_kartu' => $noKartuStr,
                'batch' => $batch,
                'grey' => round($greyQty, 2),
                'wjl_tsd_kl' => $wjlTsdKl,
                'counter_tenter' => $counterTenter,
                'tgl_inspect' => $tglInspect,
                'no_mesin' => $noMesin,
                'operator' => $operator,
                'grading' => $gradingFormatted,
            ];
        }

        // Urutkan data berdasarkan no_wo terkecil (ascending) secara natural
        usort($reportData, function ($a, $b) {
            if ($a['no_wo'] === '-') return 1;
            if ($b['no_wo'] === '-') return -1;
            $cmp = strnatcmp($a['no_wo'], $b['no_wo']);
            if ($cmp === 0) {
                return $a['id'] <=> $b['id'];
            }
            return $cmp;
        });

        $summaryTotals['total_qty_finish'] = round($summaryTotals['total_qty_finish'], 2);
        $summaryTotals['total_grey'] = round($summaryTotals['total_grey'], 2);
        $summaryTotals['total_grade_a'] = round($summaryTotals['total_grade_a'], 2);
        $summaryTotals['total_grade_b'] = round($summaryTotals['total_grade_b'], 2);
        $summaryTotals['total_grade_c'] = round($summaryTotals['total_grade_c'], 2);
        $summaryTotals['total_grade_pk'] = round($summaryTotals['total_grade_pk'], 2);
        $summaryTotals['total_grade_sample'] = round($summaryTotals['total_grade_sample'], 2);
        $summaryTotals['total_grade_a_plus'] = round($summaryTotals['total_grade_a_plus'], 2);
        $summaryTotals['total_grade_a_asterisk'] = round($summaryTotals['total_grade_a_asterisk'], 2);
        $summaryTotals['grand_total_yard'] = round($summaryTotals['grand_total_yard'], 2);
        $summaryTotals['average_pass_rate'] = $summaryTotals['grand_total_yard'] > 0
            ? round((($summaryTotals['total_grade_a'] + $summaryTotals['total_grade_a_plus']) / $summaryTotals['grand_total_yard']) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'message' => 'Laporan Rekap Dyeing berhasil diambil',
            'total_records' => count($reportData),
            'summary_totals' => $summaryTotals,
            'data' => $reportData,
        ]);
    }

    public function rekapPrinting(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $noKartu = $request->input('no_kartu');
        $noDo = $request->input('no_do');
        $motif = $request->input('motif');
        $limit = $request->input('limit') ?? $request->input('per_page');
        $page = $request->input('page');

        // Search Bulan & Tahun WO (Mendukung param: month_wo, bulan_wo, month, bulan, year_wo, tahun_wo, year, tahun)
        $woMonth = $request->input('month_wo') ?? $request->input('bulan_wo') ?? $request->input('month') ?? $request->input('bulan');
        $woYear = $request->input('year_wo') ?? $request->input('tahun_wo') ?? $request->input('year') ?? $request->input('tahun');

        // 1. Query Inspecting (Kartu Proses Printing)
        $query1 = Inspecting::with([
            'kartuProcessPrinting.kartuProsesPrintingItem',
            'kartuProcessPrinting.kartuProsesPrintingProcesses.process',
            'kartuProcessPrinting.wo.greige.GreigeGroup',
            'kartuProcessPrinting.wo.scGreige',
            'kartuProcessPrinting.mo',
            'kartuProcessPrinting.woColor.moColor',
            'kartuProcessPrinting.moColor',
            'createdBy',
            'inspectingItem.stock.greige',
            'inspectingItem.stock.greigeGroup',
            'inspectingItem.defect_item',
        ])
        ->whereIn('status', [4, 5])
        ->whereNotNull('kartu_process_printing_id')
        ->whereNull('kartu_process_dyeing_id');

        if ($startDate && $endDate) {
            $query1->whereBetween('date', [$startDate, $endDate]);
        }

        if ($woYear || $woMonth) {
            $query1->whereHas('kartuProcessPrinting.wo', function ($woQ) use ($woYear, $woMonth) {
                if ($woYear) {
                    $woQ->whereYear('date', $woYear);
                }
                if ($woMonth) {
                    $woQ->whereMonth('date', $woMonth);
                }
            });
        }

        if ($noKartu) {
            $query1->whereHas('kartuProcessPrinting', function ($q) use ($noKartu) {
                $q->where('nomor_kartu', 'like', '%' . $noKartu . '%')
                  ->orWhere('no', 'like', '%' . $noKartu . '%');
            });
        }

        if ($noDo) {
            $query1->where(function ($q) use ($noDo) {
                $q->whereHas('kartuProcessPrinting.wo', function ($woQ) use ($noDo) {
                    $woQ->where('no', 'like', '%' . $noDo . '%');
                })->orWhereHas('inspectingItem.stock', function ($sq) use ($noDo) {
                    $sq->where('no_document', 'like', '%' . $noDo . '%')
                       ->orWhere('pengirim', 'like', '%' . $noDo . '%');
                });
            });
        }

        if ($motif) {
            $query1->where(function ($q) use ($motif) {
                $q->whereHas('inspectingItem.stock.greige', function ($sq) use ($motif) {
                    $sq->where('nama_kain', 'like', '%' . $motif . '%');
                })->orWhereHas('kartuProcessPrinting.wo.greige', function ($sq) use ($motif) {
                    $sq->where('nama_kain', 'like', '%' . $motif . '%');
                });
            });
        }

        // 2. Query InspectingMklbj (Makloon Printing, process = 2)
        $query2 = InspectingMklbj::with([
            'wo.greige.GreigeGroup',
            'wo.scGreige',
            'wo.mo',
            'woColor.moColor',
            'createdBy',
            'inspectingMklbjItem.defect_item',
        ])
        ->whereHas('wo.mo', function ($moQ) {
            $moQ->where('process', 2);
        })
        ->whereIn('status', [2, 3, 4, 5]);

        if ($startDate && $endDate) {
            $query2->whereBetween('tgl_inspeksi', [$startDate, $endDate]);
        }

        if ($woYear || $woMonth) {
            $query2->whereHas('wo', function ($woQ) use ($woYear, $woMonth) {
                if ($woYear) {
                    $woQ->whereYear('date', $woYear);
                }
                if ($woMonth) {
                    $woQ->whereMonth('date', $woMonth);
                }
            });
        }

        if ($noKartu) {
            $query2->where(function ($q) use ($noKartu) {
                $q->where('no', 'like', '%' . $noKartu . '%')
                  ->orWhere('no_urut', 'like', '%' . $noKartu . '%');
            });
        }

        if ($noDo) {
            $query2->whereHas('wo', function ($woQ) use ($noDo) {
                $woQ->where('no', 'like', '%' . $noDo . '%');
            });
        }

        if ($motif) {
            $query2->whereHas('wo.greige', function ($sq) use ($motif) {
                $sq->where('nama_kain', 'like', '%' . $motif . '%');
            });
        }

        if ($limit) {
            $limitVal = (int) $limit;
            if ($page) {
                $offset = ((int)$page - 1) * $limitVal;
                $query1->offset($offset)->limit($limitVal);
                $query2->offset($offset)->limit($limitVal);
            } else {
                $query1->limit($limitVal);
                $query2->limit($limitVal);
            }
        }

        $inspectings = $query1->orderBy('date', 'desc')->orderBy('id', 'desc')->get();
        $mklbjInspectings = $query2->orderBy('tgl_inspeksi', 'desc')->orderBy('id', 'desc')->get();

        $reportData = [];

        $summaryTotals = [
            'total_qty_finish' => 0,
            'total_grey' => 0,
            'total_grade_a' => 0,          // 1
            'total_grade_b' => 0,          // 2
            'total_grade_c' => 0,          // 3
            'total_grade_pk' => 0,         // 4
            'total_grade_sample' => 0,     // 5
            'total_grade_a_plus' => 0,     // 7
            'total_grade_a_asterisk' => 0, // 8
            'grand_total_yard' => 0,
            'average_pass_rate' => 0,
        ];

        // Format data Kartu Proses Printing (Inspecting)
        foreach ($inspectings as $ins) {
            $kp = $ins->kartuProcessPrinting;
            $wo = optional($kp)->wo;
            $mo = optional($kp)->mo;
            $firstItem = $ins->inspectingItem->first();
            $stock = optional($firstItem)->stock;

            // DO Info
            $doDoc = optional($wo)->no ?? optional($stock)->no_document ?? optional($stock)->pengirim ?? optional($kp)->no_urut ?? '-';
            $tglMasukDo = optional($wo)->date ?? optional($stock)->date ?? optional($stock)->created_at;
            $tglMasukDoFormatted = $tglMasukDo ? Carbon::parse($tglMasukDo)->format('d-m-Y') : '-';

            // Unit DO & Unit Finish
            $unitDoVal = optional($stock)->unit ?? optional(optional($wo)->scGreige)->unit ?? 1;
            $unitDoLabel = ($unitDoVal == 2 || strtolower((string)$unitDoVal) === 'meter') ? 'M' : 'Y';

            $unitFinishVal = $ins->unit ?? 1;
            $unitFinishLabel = ($unitFinishVal == 2 || strtolower((string)$unitFinishVal) === 'meter') ? 'M' : 'Y';
            $unitLabel = $unitFinishLabel;

            // Motif Greige & Artikel
            $motifGreige = optional($stock)->greige->nama_kain
                ?? optional(optional($wo)->greige)->nama_kain
                ?? optional(optional($stock)->greigeGroup)->nama_kain
                ?? '-';

            $artikel = optional($mo)->article ?? optional($mo)->design ?? optional($wo)->article ?? '-';

            // Color code & name
            $moColor = optional(optional($kp)->woColor)->moColor ?? optional($kp)->moColor;
            $kodeWarna = optional($moColor)->code ?? optional($moColor)->color_code ?? '-';
            $warna = optional($moColor)->color ?? optional($moColor)->nama_warna ?? optional($kp)->kombinasi ?? '-';

            // Order Qty & Finish Qty
            $woColor = optional($kp)->woColor;
            $qtyBatchColor = optional($woColor)->qty ?? optional($wo)->qty_batch ?? optional($mo)->qty_batch;
            $qtyOrderStr = ($qtyBatchColor !== null && $qtyBatchColor !== '') ? ($qtyBatchColor . ' BATCH') : '-';

            $greigeGroup = optional($stock)->greigeGroup ?? optional(optional($wo)->greige)->GreigeGroup;
            $qtyBatchVal = (float) ($qtyBatchColor ?? 0);
            $qtyPerBatch = (float) (optional($greigeGroup)->qty_per_batch ?? 0);
            $penyusutan = (float) (optional($greigeGroup)->nilai_penyusutan ?? 0);

            if ($qtyBatchVal > 0 && $qtyPerBatch > 0) {
                $totalGreigeMeter = $qtyBatchVal * $qtyPerBatch;
                $finishMeterCalc = $totalGreigeMeter * (1 - ($penyusutan / 100));

                if ($unitFinishLabel === 'Y') {
                    $finishQty = $finishMeterCalc * 1.09361;
                } else {
                    $finishQty = $finishMeterCalc;
                }
            } else {
                $scGreige = optional($wo)->scGreige;
                $rawFinish = (float) (optional($scGreige)->qty ?? optional($wo)->qty ?? optional($mo)->qty ?? 0);
                if ($unitFinishLabel === 'Y') {
                    $finishQty = $rawFinish;
                } else {
                    $finishQty = $rawFinish / 1.09361;
                }
            }

            // Dates & Kartu info
            $tglKartu = optional($kp)->date ? Carbon::parse($kp->date)->format('d-m-Y') : '-';
            $noKartuStr = optional($kp)->nomor_kartu ?? '-';

            $kpNoLot = optional($kp)->no_lot;
            $kpNote = optional($kp)->note;
            $insNoLot = $ins->no_lot;
            $batch = '-';
            if (!empty($kpNoLot) && $kpNoLot !== '-') {
                $batch = $kpNoLot;
            } elseif (!empty($kpNote) && $kpNote !== '-') {
                $batch = $kpNote;
            } elseif (!empty($insNoLot) && $insNoLot !== '-') {
                $batch = $insNoLot;
            }

            // Total Grey
            $greyQty = (float) (optional($kp)->panjang_m ?? optional($kp)->kartuProsesPrintingItem->sum('panjang_m') ?? 0);

            // Asal Greige / Kain Dalam vs Luar (WJL/TSD/KL)
            $asalGreige = optional($stock)->asal_greige ?? optional($kp)->asal_greige;
            $greigeGroup = optional($stock)->greigeGroup ?? optional(optional($wo)->greige)->GreigeGroup;
            $greige = optional($stock)->greige ?? optional($wo)->greige;

            $jenisKain = optional($greigeGroup)->jenis_kain;
            $namaKainGroup = optional($greigeGroup)->nama_kain;
            $namaKainGreige = optional($greige)->nama_kain;

            $textSearch = strtolower((string)$jenisKain . ' ' . (string)$asalGreige . ' ' . (string)$namaKainGroup . ' ' . (string)$namaKainGreige);

            if ($asalGreige == 2 || $jenisKain == 2 || strpos($textSearch, 'beli') !== false) {
                $wjlTsdKl = 'K LUAR';
            } elseif ($jenisKain == 3 || strpos($textSearch, 'rapier') !== false || strpos($textSearch, 'tsd') !== false) {
                $wjlTsdKl = 'TSD';
            } elseif ($jenisKain == 1 || strpos($textSearch, 'water') !== false || strpos($textSearch, 'jet') !== false || strpos($textSearch, 'wjl') !== false) {
                $wjlTsdKl = 'WJL';
            } elseif ($asalGreige == 3) {
                $wjlTsdKl = 'TSD';
            } elseif ($asalGreige == 1) {
                $wjlTsdKl = 'WJL';
            } else {
                $wjlTsdKl = 'K LUAR';
            }

            // Inspecting info - Counter Tenter dari Resin finish
            $counterTenter = '-';
            if ($kp && $kp->relationLoaded('kartuProsesPrintingProcesses') && $kp->kartuProsesPrintingProcesses) {
                $rfProcess = $kp->kartuProsesPrintingProcesses->first(function ($proc) {
                    $pName = strtolower((string) optional($proc->process)->nama_proses);
                    return $pName === 'resin finish' || $proc->process_id == 14;
                });

                if ($rfProcess && !empty($rfProcess->value)) {
                    $valData = is_array($rfProcess->value) ? $rfProcess->value : json_decode($rfProcess->value, true);
                    if (isset($valData['panjang_jadi']) && $valData['panjang_jadi'] !== '' && $valData['panjang_jadi'] !== '-') {
                        $counterTenter = $valData['panjang_jadi'];
                    }
                }
            }
            if ($counterTenter === '-' && !empty($ins->counter_tenter)) {
                $counterTenter = $ins->counter_tenter;
            }

            $tglInspect = $ins->date ? Carbon::parse($ins->date)->format('d-m-Y') : '-';
            $noMesin = $ins->inspection_table ? ('MC ' . $ins->inspection_table) : '-';
            $operator = optional($ins->createdBy)->name ?? optional($ins->createdBy)->full_name ?? '-';

            // Grading Breakdown
            $gradeKeys = [
                1 => 'grade_a',
                2 => 'grade_b',
                3 => 'grade_c',
                4 => 'grade_pk',
                5 => 'grade_sample',
                7 => 'grade_a_plus',
                8 => 'grade_a_asterisk',
            ];

            $gradeData = [
                'grade_a'          => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_b'          => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_c'          => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_pk'         => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_sample'     => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_a_plus'     => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_a_asterisk' => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_other'      => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
            ];

            foreach ($ins->inspectingItem as $item) {
                $itemQty = (float) $item->qty;
                if ($unitLabel === 'M') {
                    $itemQty = $itemQty * 1.09361;
                }

                $g = (int) $item->grade;
                $targetKey = $gradeKeys[$g] ?? 'grade_other';
                $gradeData[$targetKey]['panjang'] += $itemQty;

                if (!empty($item->defect)) {
                    $rawCodes = array_filter(explode(',', (string)$item->defect));
                    foreach ($rawCodes as $c) {
                        $cTrim = trim($c);
                        if ($cTrim !== '' && $cTrim !== '-') {
                            $gradeData[$targetKey]['codes'][] = $cTrim;
                        }
                    }
                }

                if ($item->relationLoaded('defect_item') && count($item->defect_item) > 0) {
                    foreach ($item->defect_item as $dItem) {
                        $dMeterage = (float) ($dItem->meterage ?? 0);
                        if ($unitLabel === 'M') {
                            $dMeterage = $dMeterage * 1.09361;
                        }
                        $gradeData[$targetKey]['total_defect'] += $dMeterage;
                    }
                }
            }

            $gradingFormatted = [];
            foreach ($gradeData as $gKey => $gInfo) {
                $uniqueCodes = array_values(array_unique($gInfo['codes']));
                natsort($uniqueCodes);
                $kodeStr = !empty($uniqueCodes) ? implode(',', array_values($uniqueCodes)) : '-';

                $gradingFormatted[$gKey] = [
                    'panjang' => round($gInfo['panjang'], 2),
                    'total_defect' => round($gInfo['total_defect'], 2),
                    'kode_defect' => $kodeStr,
                ];
            }

            $totalYardInspected = 0;
            foreach ($gradeData as $gInfo) {
                $totalYardInspected += $gInfo['panjang'];
            }

            $passYard = $gradeData['grade_a']['panjang'] + $gradeData['grade_a_plus']['panjang'];
            $persenPass = $totalYardInspected > 0 ? round(($passYard / $totalYardInspected) * 100, 2) : 0;

            $gradingFormatted['total_yard'] = round($totalYardInspected, 2);
            $gradingFormatted['persen_pass'] = $persenPass;

            $summaryTotals['total_qty_finish'] += $finishQty;
            $summaryTotals['total_grey'] += $greyQty;
            $summaryTotals['total_grade_a'] += $gradeData['grade_a']['panjang'];
            $summaryTotals['total_grade_b'] += $gradeData['grade_b']['panjang'];
            $summaryTotals['total_grade_c'] += $gradeData['grade_c']['panjang'];
            $summaryTotals['total_grade_pk'] += $gradeData['grade_pk']['panjang'];
            $summaryTotals['total_grade_sample'] += $gradeData['grade_sample']['panjang'];
            $summaryTotals['total_grade_a_plus'] += $gradeData['grade_a_plus']['panjang'];
            $summaryTotals['total_grade_a_asterisk'] += $gradeData['grade_a_asterisk']['panjang'];
            $summaryTotals['grand_total_yard'] += $totalYardInspected;

            $tglWoStr = optional($wo)->date ? Carbon::parse($wo->date)->format('d-m-Y') : '-';
            $noWoStr = optional($wo)->no ?? '-';

            $reportData[] = [
                'id' => $ins->id,
                'no_wo' => $noWoStr,
                'tgl_wo' => $tglWoStr,
                'tgl_masuk_do' => $tglMasukDoFormatted,
                'no_do' => $doDoc,
                'unit_do' => $unitDoLabel,
                'motif_greige' => $motifGreige,
                'artikel' => $artikel,
                'kode_warna' => $kodeWarna,
                'warna' => $warna,
                'qty_order' => $qtyOrderStr,
                'qty_finish' => round($finishQty, 2),
                'unit_finish' => $unitFinishLabel,
                'tgl_kartu' => $tglKartu,
                'no_kartu' => $noKartuStr,
                'batch' => $batch,
                'grey' => round($greyQty, 2),
                'wjl_tsd_kl' => $wjlTsdKl,
                'counter_tenter' => $counterTenter,
                'tgl_inspect' => $tglInspect,
                'no_mesin' => $noMesin,
                'operator' => $operator,
                'grading' => $gradingFormatted,
            ];
        }

        // Format data Makloon Printing (InspectingMklbj)
        foreach ($mklbjInspectings as $mkl) {
            $wo = optional($mkl)->wo;
            $mo = optional($wo)->mo;

            $doDoc = optional($wo)->no ?? $mkl->no ?? '-';
            $tglMasukDo = optional($wo)->date ?? $mkl->tgl_inspeksi ?? $mkl->created_at;
            $tglMasukDoFormatted = $tglMasukDo ? Carbon::parse($tglMasukDo)->format('d-m-Y') : '-';

            $unitDoVal = optional(optional($wo)->scGreige)->unit ?? 1;
            $unitDoLabel = ($unitDoVal == 2 || strtolower((string)$unitDoVal) === 'meter') ? 'M' : 'Y';

            $unitFinishVal = $mkl->satuan ?? 1;
            $unitFinishLabel = ($unitFinishVal == 2 || strtolower((string)$unitFinishVal) === 'meter' || strtolower((string)$unitFinishVal) === 'm') ? 'M' : 'Y';
            $unitLabel = $unitFinishLabel;

            $motifGreige = optional(optional($wo)->greige)->nama_kain ?? '-';
            $artikel = optional($mo)->article ?? optional($mo)->design ?? optional($wo)->article ?? '-';

            $moColor = optional($mkl->woColor)->moColor;
            $kodeWarna = optional($moColor)->code ?? optional($moColor)->color_code ?? '-';
            $warna = optional($moColor)->color ?? optional($moColor)->nama_warna ?? '-';

            $woColor = optional($mkl)->woColor;
            $qtyBatchColor = optional($woColor)->qty ?? optional($wo)->qty_batch ?? optional($mo)->qty_batch;
            $qtyOrderStr = ($qtyBatchColor !== null && $qtyBatchColor !== '') ? ($qtyBatchColor) : '-';

            $greigeGroup = optional(optional($wo)->greige)->GreigeGroup;
            $qtyBatchVal = (float) ($qtyBatchColor ?? 0);
            $qtyPerBatch = (float) (optional($greigeGroup)->qty_per_batch ?? 0);
            $penyusutan = (float) (optional($greigeGroup)->nilai_penyusutan ?? 0);

            if ($qtyBatchVal > 0 && $qtyPerBatch > 0) {
                $totalGreigeMeter = $qtyBatchVal * $qtyPerBatch;
                $finishMeterCalc = $totalGreigeMeter * (1 - ($penyusutan / 100));

                if ($unitFinishLabel === 'Y') {
                    $finishQty = $finishMeterCalc * 1.09361;
                } else {
                    $finishQty = $finishMeterCalc;
                }
            } else {
                $scGreige = optional($wo)->scGreige;
                $rawFinish = (float) (optional($scGreige)->qty ?? optional($wo)->qty ?? optional($mo)->qty ?? 0);
                if ($unitFinishLabel === 'Y') {
                    $finishQty = $rawFinish;
                } else {
                    $finishQty = $rawFinish / 1.09361;
                }
            }

            $tglKartu = $mkl->tgl_inspeksi ? Carbon::parse($mkl->tgl_inspeksi)->format('d-m-Y') : '-';
            $noKartuStr = $mkl->no ?? '-';

            $batch = !empty($mkl->no_lot) && $mkl->no_lot !== '-' ? $mkl->no_lot : '-';

            $greyQty = (float) $mkl->inspectingMklbjItem->sum('qty');
            $asalGreige = optional($wo)->asal_greige ?? optional(optional($wo)->greige)->asal_greige;
            $greigeGroup = optional(optional($wo)->greige)->GreigeGroup ?? optional(optional($wo)->greige)->greigeGroup;
            $greige = optional($wo)->greige;

            $jenisKain = optional($greigeGroup)->jenis_kain;
            $namaKainGroup = optional($greigeGroup)->nama_kain;
            $namaKainGreige = optional($greige)->nama_kain;

            $textSearch = strtolower((string)$jenisKain . ' ' . (string)$asalGreige . ' ' . (string)$namaKainGroup . ' ' . (string)$namaKainGreige);

            if ($asalGreige == 2 || $jenisKain == 2 || strpos($textSearch, 'beli') !== false) {
                $wjlTsdKl = 'K LUAR';
            } elseif ($jenisKain == 3 || strpos($textSearch, 'rapier') !== false || strpos($textSearch, 'tsd') !== false) {
                $wjlTsdKl = 'TSD';
            } elseif ($jenisKain == 1 || strpos($textSearch, 'water') !== false || strpos($textSearch, 'jet') !== false || strpos($textSearch, 'wjl') !== false) {
                $wjlTsdKl = 'WJL';
            } elseif ($asalGreige == 3) {
                $wjlTsdKl = 'TSD';
            } elseif ($asalGreige == 1) {
                $wjlTsdKl = 'WJL';
            } else {
                $wjlTsdKl = 'K LUAR';
            }
            $counterTenter = '-';

            $tglInspect = $mkl->tgl_inspeksi ? Carbon::parse($mkl->tgl_inspeksi)->format('d-m-Y') : '-';
            $noMesin = $mkl->inspection_table ? ('MC ' . $mkl->inspection_table) : '-';
            $operator = optional($mkl->createdBy)->name ?? optional($mkl->createdBy)->full_name ?? '-';

            $gradeKeys = [
                1 => 'grade_a',
                2 => 'grade_b',
                3 => 'grade_c',
                4 => 'grade_pk',
                5 => 'grade_sample',
                7 => 'grade_a_plus',
                8 => 'grade_a_asterisk',
            ];

            $gradeData = [
                'grade_a'          => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_b'          => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_c'          => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_pk'         => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_sample'     => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_a_plus'     => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_a_asterisk' => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
                'grade_other'      => ['panjang' => 0, 'total_defect' => 0, 'codes' => []],
            ];

            foreach ($mkl->inspectingMklbjItem as $item) {
                $itemQty = (float) $item->qty;
                if ($unitLabel === 'M') {
                    $itemQty = $itemQty * 1.09361;
                }

                $g = (int) $item->grade;
                $targetKey = $gradeKeys[$g] ?? 'grade_other';
                $gradeData[$targetKey]['panjang'] += $itemQty;

                if (!empty($item->defect)) {
                    $rawCodes = array_filter(explode(',', (string)$item->defect));
                    foreach ($rawCodes as $c) {
                        $cTrim = trim($c);
                        if ($cTrim !== '' && $cTrim !== '-') {
                            $gradeData[$targetKey]['codes'][] = $cTrim;
                        }
                    }
                }

                if ($item->relationLoaded('defect_item') && count($item->defect_item) > 0) {
                    foreach ($item->defect_item as $dItem) {
                        $dMeterage = (float) ($dItem->meterage ?? 0);
                        if ($unitLabel === 'M') {
                            $dMeterage = $dMeterage * 1.09361;
                        }
                        $gradeData[$targetKey]['total_defect'] += $dMeterage;
                    }
                }
            }

            $gradingFormatted = [];
            foreach ($gradeData as $gKey => $gInfo) {
                $uniqueCodes = array_values(array_unique($gInfo['codes']));
                natsort($uniqueCodes);
                $kodeStr = !empty($uniqueCodes) ? implode(',', array_values($uniqueCodes)) : '-';

                $gradingFormatted[$gKey] = [
                    'panjang' => round($gInfo['panjang'], 2),
                    'total_defect' => round($gInfo['total_defect'], 2),
                    'kode_defect' => $kodeStr,
                ];
            }

            $totalYardInspected = 0;
            foreach ($gradeData as $gInfo) {
                $totalYardInspected += $gInfo['panjang'];
            }

            $passYard = $gradeData['grade_a']['panjang'] + $gradeData['grade_a_plus']['panjang'];
            $persenPass = $totalYardInspected > 0 ? round(($passYard / $totalYardInspected) * 100, 2) : 0;

            $gradingFormatted['total_yard'] = round($totalYardInspected, 2);
            $gradingFormatted['persen_pass'] = $persenPass;

            $summaryTotals['total_qty_finish'] += $finishQty;
            $summaryTotals['total_grey'] += $greyQty;
            $summaryTotals['total_grade_a'] += $gradeData['grade_a']['panjang'];
            $summaryTotals['total_grade_b'] += $gradeData['grade_b']['panjang'];
            $summaryTotals['total_grade_c'] += $gradeData['grade_c']['panjang'];
            $summaryTotals['total_grade_pk'] += $gradeData['grade_pk']['panjang'];
            $summaryTotals['total_grade_sample'] += $gradeData['grade_sample']['panjang'];
            $summaryTotals['total_grade_a_plus'] += $gradeData['grade_a_plus']['panjang'];
            $summaryTotals['total_grade_a_asterisk'] += $gradeData['grade_a_asterisk']['panjang'];
            $summaryTotals['grand_total_yard'] += $totalYardInspected;

            $tglWoStr = optional($wo)->date ? Carbon::parse($wo->date)->format('d-m-Y') : '-';
            $noWoStr = optional($wo)->no ?? '-';

            $reportData[] = [
                'id' => $mkl->id,
                'no_wo' => $noWoStr,
                'tgl_wo' => $tglWoStr,
                'tgl_masuk_do' => $tglMasukDoFormatted,
                'no_do' => $doDoc,
                'unit_do' => $unitDoLabel,
                'motif_greige' => $motifGreige,
                'artikel' => $artikel,
                'kode_warna' => $kodeWarna,
                'warna' => $warna,
                'qty_order' => $qtyOrderStr,
                'qty_finish' => round($finishQty, 2),
                'unit_finish' => $unitFinishLabel,
                'tgl_kartu' => $tglKartu,
                'no_kartu' => $noKartuStr,
                'batch' => $batch,
                'grey' => round($greyQty, 2),
                'wjl_tsd_kl' => $wjlTsdKl,
                'counter_tenter' => $counterTenter,
                'tgl_inspect' => $tglInspect,
                'no_mesin' => $noMesin,
                'operator' => $operator,
                'grading' => $gradingFormatted,
            ];
        }

        // Urutkan data berdasarkan no_wo terkecil (ascending) secara natural
        usort($reportData, function ($a, $b) {
            if ($a['no_wo'] === '-') return 1;
            if ($b['no_wo'] === '-') return -1;
            $cmp = strnatcmp($a['no_wo'], $b['no_wo']);
            if ($cmp === 0) {
                return $a['id'] <=> $b['id'];
            }
            return $cmp;
        });

        $summaryTotals['total_qty_finish'] = round($summaryTotals['total_qty_finish'], 2);
        $summaryTotals['total_grey'] = round($summaryTotals['total_grey'], 2);
        $summaryTotals['total_grade_a'] = round($summaryTotals['total_grade_a'], 2);
        $summaryTotals['total_grade_b'] = round($summaryTotals['total_grade_b'], 2);
        $summaryTotals['total_grade_c'] = round($summaryTotals['total_grade_c'], 2);
        $summaryTotals['total_grade_pk'] = round($summaryTotals['total_grade_pk'], 2);
        $summaryTotals['total_grade_sample'] = round($summaryTotals['total_grade_sample'], 2);
        $summaryTotals['total_grade_a_plus'] = round($summaryTotals['total_grade_a_plus'], 2);
        $summaryTotals['total_grade_a_asterisk'] = round($summaryTotals['total_grade_a_asterisk'], 2);
        $summaryTotals['grand_total_yard'] = round($summaryTotals['grand_total_yard'], 2);
        $summaryTotals['average_pass_rate'] = $summaryTotals['grand_total_yard'] > 0
            ? round((($summaryTotals['total_grade_a'] + $summaryTotals['total_grade_a_plus']) / $summaryTotals['grand_total_yard']) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'message' => 'Laporan Rekap Printing berhasil diambil',
            'total_records' => count($reportData),
            'summary_totals' => $summaryTotals,
            'data' => $reportData,
        ]);
    }
}
