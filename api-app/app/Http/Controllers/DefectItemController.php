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



    public function getDefectWithTglKirim(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $data = DefectInspectingItem::with([
            'mstKodeDefect',
            'inspectingItem.inspecting.wo.greige',
            'inspectingMklbjItem.inspectingMklbj.wo.greige',
        ])
        ->where(function ($query) {
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
        ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
            $query->whereHas('inspectingItem.inspecting', function ($sub) use ($startDate, $endDate) {
                $sub->whereBetween('date', [$startDate, $endDate]);
            })->whereHas('inspectingMklbjItem.inspectingMklbj', function ($sub) use ($startDate, $endDate) {
                $sub->whereBetween('tgl_kirim', [$startDate, $endDate]);
            });
        })
        ->get()
        ->groupBy(function ($item) {
            $noUrut = optional($item->mstKodeDefect)->no_urut;
            $namaDefect = optional($item->mstKodeDefect)->nama_defect;
            return $noUrut . '|' . $namaDefect;
        })
        ->map(function ($groupedItems, $key) {
            [$noUrut, $namaDefect] = explode('|', $key);

            $grade2 = [];
            $grade3 = [];

            foreach ($groupedItems as $item) {
                $grade = optional($item->inspectingItem)->grade ?? optional($item->inspectingMklbjItem)->grade;
                $namaKain = optional(optional(optional($item->inspectingItem)->inspecting)->wo)->greige->nama_kain
                    ?? optional(optional(optional($item->inspectingMklbjItem)->inspectingMklbj)->wo)->greige->nama_kain;

                if (!$namaKain) {
                    continue;
                }

                if ($grade == 2) {
                    if (!isset($grade2[$namaKain])) {
                        $grade2[$namaKain] = 0;
                    }
                    $grade2[$namaKain] += $item->meterage;
                } elseif ($grade == 3) {
                    if (!isset($grade3[$namaKain])) {
                        $grade3[$namaKain] = 0;
                    }
                    $grade3[$namaKain] += $item->meterage;
                }
            }

            $grade2Arr = collect($grade2)->map(function ($meterage, $namaKain) {
                return [
                    'nama_kain' => $namaKain,
                    'meterage' => $meterage,
                ];
            })->values();

            $grade3Arr = collect($grade3)->map(function ($meterage, $namaKain) {
                return [
                    'nama_kain' => $namaKain,
                    'meterage' => $meterage,
                ];
            })->values();

            return [
                'no_urut' => (int)$noUrut,
                'nama_defect' => $namaDefect,
                'total_grade_2' => $grade2Arr->sum('meterage'),
                'total_grade_3' => $grade3Arr->sum('meterage'),
                'grade_2' => $grade2Arr,
                'grade_3' => $grade3Arr,
            ];
        })
        ->sortByDesc(function ($item) {
            return $item['total_grade_2'] + $item['total_grade_3'];
        })
        ->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }







}
