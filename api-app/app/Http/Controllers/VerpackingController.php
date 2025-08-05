<?php

namespace App\Http\Controllers;

use App\Inspecting;
use App\InspectingMklbj;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VerpackingController extends Controller
{

    /**
     * Retrieve a list of production shipments based on the given filters.
     *
     * This function validates the input request parameters and constructs a query
     * to retrieve production shipment records from the Inspecting model. It applies
     * various filters such as shipment number, buyer, design, date range, lot number,
     * combination, order type, work order number, motif, stamping, and piece length.
     * The filtered results are then transformed into a structured output and returned
     * as a JSON response.
     *
     * @param Request $request The request object containing input parameters for filtering.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the filtered production
     * shipment data structured in a specific format.
     */
        public function daftarPengirimanProduksi(Request $request)
    {
        // Validasi input
        $request->validate([
            'no_kirim' => 'nullable|string',
            'buyer' => 'nullable|string',
            'design' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'no_lot' => 'nullable|string',
            'kombinasi' => 'nullable|string',
            'jenis_order' => 'nullable|string',
            'no_wo' => 'nullable|string',
            'motif' => 'nullable|string',
            'stamping' => 'nullable|string',
            'piece_length' => 'nullable|string'
        ]);

        // Ambil parameter dari request
        $no_kirim = $request->input('no_kirim');
        $buyer = $request->input('buyer');
        $design = $request->input('design');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $no_lot = $request->input('no_lot');
        $kombinasi = $request->input('kombinasi');
        $jenis_order = $request->input('jenis_order');
        $no_wo = $request->input('no_wo');
        $motif = $request->input('motif');
        $stamping = $request->input('stamping');
        $piece_length = $request->input('piece_length');

        // Bangun query untuk Inspecting berdasarkan filter
        $query = Inspecting::with([
            'sc.customer',
            'mo',
            'wo',
            'kartuProcessDyeing',
            'kartuProcessDyeing.kartuProsesDyeingItem',
            'kartuProcessPrinting',
            'kartuProcessPrinting.kartuProsesPrintingItem',
        ]);

        $query->where('status', 4);

        if ($no_kirim) {
            $query->where('no', 'like', '%' . $no_kirim . '%');
        }

        if ($buyer) {
            $query->whereHas('sc.customer', function($q) use ($buyer) {
                $q->where('cust_no', 'like', '%' . $buyer . '%');
            });
        }

        if ($design) {
            $query->whereHas('mo', function($q) use ($design) {
                $q->where('design', 'like', '%' . $design . '%')
                  ->orWhere('article', 'like', '%' . $design . '%');
            });
        }

        if ($start_date && $end_date) {
            $query->whereBetween('date', [$start_date, $end_date]);
        }

        if ($no_lot) {
            $query->where('no_lot', 'like', '%' . $no_lot . '%');
        }

        if ($kombinasi) {
            $query->where('kombinasi', 'like', '%' . $kombinasi . '%');
        }

        if ($jenis_order) {
            $query->where('jenis_process', 'like', '%' . $jenis_order . '%');
        }

        if ($no_wo) {
             $query->whereHas('wo', function($q) use ($no_wo) {
                $q->where('no', 'like', '%' . $no_wo . '%');
            });
        }

        if ($motif) {
             $query->whereHas('wo.greige', function($q) use ($motif) {
                $q->where('nama_kain', 'like', '%' . $motif . '%');
            });
        }

        if ($stamping) {
             $query->whereHas('mo', function($q) use ($stamping) {
                $q->where('selvedge_stamping', 'like', '%' . $stamping . '%')
                  ->orWhere('selvedge_continues', 'like', '%' . $stamping . '%');
            });
        }

        if ($piece_length) {
             $query->whereHas('mo', function($q) use ($piece_length) {
                $q->where('piece_length', 'like', '%' . $piece_length . '%');
            });
        }

        // Ambil data Inspecting yang sudah difilter
        $inspectings = $query->with(['inspectingItem'])->get();

        // Struktur output yang diinginkan
        $output = [];

        foreach ($inspectings as $inspection) {
            $data = [
                'no_kirim' => $inspection->no,
                'tgl_kirim' => $inspection->date,
                'no_wo' => $inspection->wo->no,
                'no_kartu' => $inspection->jenis_process == 1
                            ? optional($inspection->kartuProcessDyeing)->no
                            : optional($inspection->kartuProcessPrinting)->no,
                'buyer' => $inspection->sc->customer->cust_no,
                'tgl_inspeksi' => $inspection->tanggal_inspeksi,
                'motif' => $inspection->wo->greige->nama_kain,
                'no_lot' => $inspection->no_lot,
                'design' => $inspection->kartuProcessDyeing ? $inspection->mo->article : ($inspection->kartuProcessPrinting ? $inspection->mo->design : ''),
                'kombinasi' => $inspection->kombinasi,
                'piece_length' => $inspection->mo->piece_length,
                'stamping' => $inspection->mo->selvedge_stamping,
                'satuan' => [
                    1 => 'Yard',
                    2 => 'Meter',
                    3 => 'Pcs',
                    4 => 'Kilogram',
                ][$inspection->unit],
                'jenis_order' => $inspection->jenis_process,
                'details' => [],

            ];

            $total_qty = 0;
            $total_karung = 0;

            foreach ($inspection->inspectingItem as $item) {
                $data['details'][] = [
                    'grade' => $item->grade,
                    'join_piece' => $item->join_piece,
                    'qty' => $item->qty,
                    'note' => $item->note,
                    'qty_sum' => $item->qty_sum,
                    'is_head' => $item->is_head,
                    'qty_count' => $item->qty_count
                ];

                // $total_qty += $item->pcs;
                // $total_karung += $item->roll; // Misalnya jika roll adalah ukuran pengukuran karung
            }

            $data['total_qty'] = $total_qty;


            $output[] = $data;
        }

        // Kirim hasilnya sebagai response
        return response()->json([
            'data' => $output
        ]);
    }




    /**
     * Fungsi untuk mengambil data pengiriman produksi MKLBJ berdasarkan filter
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function daftarPengirimanProduksiMklbj(Request $request)
    {
          // Validasi input
        $request->validate([
            'no_kirim' => 'nullable|string',
            'buyer' => 'nullable|string',
            'design' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'no_lot' => 'nullable|string',
            'kombinasi' => 'nullable|string',
            'jenis_order' => 'nullable|string',
            'no_wo' => 'nullable|string',
            'motif' => 'nullable|string',
            'stamping' => 'nullable|string',
            'piece_length' => 'nullable|string'
        ]);

        // Ambil parameter dari request
        $no_kirim = $request->input('no_kirim');
        $buyer = $request->input('buyer');
        $design = $request->input('design');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $no_lot = $request->input('no_lot');
        $kombinasi = $request->input('kombinasi');
        $jenis_order = $request->input('jenis_order');
        $no_wo = $request->input('no_wo');
        $motif = $request->input('motif');
        $stamping = $request->input('stamping');
        $piece_length = $request->input('piece_length');

        // Bangun query untuk Inspecting berdasarkan filter
        $query = InspectingMklbj::with([
                'wo',
                'wo.sc.customer',
                'wo.woColor',
                'wo.mo',
                'woColor',
                'woColor.moColor',
                'inspectingMklbjItem',
        ]);

        $query->where('status', 3);

        if ($no_kirim) {
            $query->where('no', 'like', '%' . $no_kirim . '%');
        }

        if ($buyer) {
            $query->whereHas('wo.sc.customer', function($q) use ($buyer) {
                $q->where('cust_no', 'like', '%' . $buyer . '%');
            });
        }

        if ($design) {
            $query->whereHas('wo.mo', function($q) use ($design) {
                $q->where('design', 'like', '%' . $design . '%')
                  ->orWhere('article', 'like', '%' . $design . '%');
            });
        }

        if ($start_date && $end_date) {
            $query->whereBetween('tgl_kirim', [$start_date, $end_date]);
        }

        if ($no_lot) {
            $query->where('no_lot', 'like', '%' . $no_lot . '%');
        }

        if ($kombinasi) {
             $query->whereHas('wocolor.mocolor', function($q) use ($kombinasi) {
                $q->where('color', 'like', '%' . $kombinasi . '%');
            });
        }

        if ($jenis_order) {
            $query->where('jenis', 'like', '%' . $jenis_order . '%');
        }

        if ($no_wo) {
             $query->whereHas('wo', function($q) use ($no_wo) {
                $q->where('no', 'like', '%' . $no_wo . '%');
            });
        }

        if ($motif) {
            $query->whereHas('wo.greige', function($q) use ($motif) {
                $q->where('nama_kain', 'like', '%' . $motif . '%');
            });
        }

        if ($stamping) {
            $query->whereHas('wo.mo', function($q) use ($stamping) {
                $q->where('selvedge_stamping', 'like', '%' . $stamping . '%');
            });
        }

        if ($piece_length) {
            $query->whereHas('wo.mo', function($q) use ($piece_length) {
                $q->where('piece_length', 'like', '%' . $piece_length . '%');
            });
        }

        // Ambil data Inspecting yang sudah difilter
        $inspectings = $query->with(['inspectingMklbjItem'])->get();

        // Struktur output yang diinginkan
        $output = [];

        foreach ($inspectings as $inspection) {
            $data = [
                'no_kirim' => $inspection->no,
                'tgl_kirim' => $inspection->tgl_kirim,
                'no_wo' => $inspection->wo->no,
                'buyer' => $inspection->wo->sc->customer->cust_no,
                'tgl_inspeksi' => $inspection->tgl_inspeksi,
                'motif' => $inspection->wo->greige->nama_kain,
                'no_lot' => $inspection->no_lot,
                'design' => $inspection->wo->mo->process == 1 ? $inspection->wo->mo->article : ($inspection->wo->mo->process == 2 ? $inspection->wo->mo->design : ''),
                'kombinasi' => $inspection->wocolor->mocolor->color,
                'piece_length' => $inspection->wo->mo->piece_length,
                'stamping' => $inspection->wo->mo->selvedge_stamping == '-' ? $inspection->wo->mo->selvedge_continues : $inspection->wo->mo->selvedge_stamping,
                'satuan' => [
                    1 => 'Yard',
                    2 => 'Meter',
                    3 => 'Pcs',
                    4 => 'Kilogram'
                ][$inspection->satuan],
                'jenis' => [
                    1 => 'Makloon Proses',
                    2 => 'Makloon Finish',
                    3 => 'Barang Jadi',
                    4 => 'Fresh'
                ][$inspection->jenis],
                'details' => [],

            ];

            $total_qty = 0;
            $total_karung = 0;

            foreach ($inspection->inspectingMklbjItem as $item) {
                $data['details'][] = [
                    'grade' => $item->grade,
                    'join_piece' => $item->join_piece,
                    'qty' => $item->qty,
                    'note' => $item->note,
                    'qty_sum' => $item->qty_sum,
                    'is_head' => $item->is_head,
                    'qty_count' => $item->qty_count
                ];

                // $total_qty += $item->pcs;
                // $total_karung += $item->roll; // Misalnya jika roll adalah ukuran pengukuran karung
            }

            $data['total_qty'] = $total_qty;


            $output[] = $data;
        }

        // Kirim hasilnya sebagai response
        return response()->json([
            'data' => $output
        ]);

    }



//Summary Grade Date Range Tanggal Pengiriman
/**
 * Get rekap pengiriman produksi berdasarkan tanggal
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function rekapPengirimanProduksi(Request $request)
{
    $request->validate([
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date'
    ]);

    $start_date = $request->input('start_date');
    $end_date = $request->input('end_date');

    $result = [];

    // Konversi satuan ke Yard
    $unitLabels = [
        1 => 'Yard',
        2 => 'Meter',
        3 => 'Pcs',
        4 => 'Kilogram',
    ];

    $unitKonversi = [
        'Yard' => 1,
        'Meter' => 1.09361,
        'Kilogram' => 3,
        'Pcs' => 1,
    ];

    // Mapping grade label
    $gradeLabels = [
        1 => 'Grade A',
        2 => 'Grade B',
        3 => 'Grade C',
        4 => 'Piece Kecil',
        5 => 'Sample',
        7 => 'Grade A+',
        8 => 'Grade A*',
        9 => 'Putih',
    ];

    // Mapping jenis_order
    $jenisOrderMapInspecting = [
        1 => 'Dyeing',
        2 => 'Printing',
    ];

    $jenisOrderMapMklbj = [
        1 => 'Makloon Proses',
        2 => 'Makloon Finish',
        3 => 'Barang Jadi',
        4 => 'Fresh',
    ];

    // Mapping jenis_inspek
    $jenisInspekMap = [
        1 => 'Fresh Order',
        2 => 'Re-Packing',
        3 => 'Hasil Perbaikan',
    ];

    // =========================
    // 1. Inspecting
    // =========================
    $query1 = Inspecting::with([
        'sc.customer',
        'mo',
        'wo',
        'kartuProcessDyeing.kartuProsesDyeingItem',
        'kartuProcessPrinting.kartuProsesPrintingItem',
        'inspectingItem'
    ])->where('status', 4);

    if ($start_date && $end_date) {
        $query1->whereBetween('date', [$start_date, $end_date]);
    }

    $inspectings = $query1->get();

    foreach ($inspectings as $inspection) {
        $unitLabel = $unitLabels[$inspection->unit] ?? 'Tidak Ada';
        $konversi = $unitKonversi[$unitLabel] ?? 1;

        $gradeTotals = [];
        foreach ($inspection->inspectingItem as $item) {
            $gradeName = $gradeLabels[$item->grade] ?? 'Tidak Ada Grade';
            $convertedQty = round($item->qty * $konversi, 2);
            $gradeTotals[$gradeName] = round(($gradeTotals[$gradeName] ?? 0) + $convertedQty, 2);
        }

        $jenisOrder = $jenisOrderMapInspecting[$inspection->jenis_process] ?? 'Tidak Ada';
        $jenisInspek = $jenisInspekMap[$inspection->jenis_inspek] ?? 'Tidak Ada';

        $result[$jenisOrder][$jenisInspek][] = [
            'tgl_kirim' => $inspection->date,
            'jenis_order' => $jenisOrder,
            'jenis_inspek' => $jenisInspek,
            'satuan' => $unitLabel,
            'total_per_grade' => $gradeTotals,
            'total_qty' => round(array_sum($gradeTotals), 2),
        ];
    }

    // =========================
    // 2. InspectingMklbj
    // =========================
    $query2 = InspectingMklbj::with([
        'wo',
        'wo.sc.customer',
        'wo.woColor',
        'wo.mo',
        'woColor.moColor',
        'inspectingMklbjItem'
    ])->where('status', 3);

    if ($start_date && $end_date) {
        $query2->whereBetween('tgl_kirim', [$start_date, $end_date]);
    }

    $inspectingMklbjs = $query2->get();

    foreach ($inspectingMklbjs as $inspection) {
        $unitLabel = $unitLabels[$inspection->satuan] ?? 'Tidak Ada';
        $konversi = $unitKonversi[$unitLabel] ?? 1;

        $gradeTotals = [];
        foreach ($inspection->inspectingMklbjItem as $item) {
            $gradeName = $gradeLabels[$item->grade] ?? 'Tidak Ada Grade';
            $convertedQty = round($item->qty * $konversi, 2);
            $gradeTotals[$gradeName] = ($gradeTotals[$gradeName] ?? 0) + round($convertedQty, 2);
        }

        $jenisInspek = $jenisInspekMap[$inspection->jenis_inspek] ?? 'Tidak Ada';

        if ($inspection->jenis == 4) {
            // Jika Fresh, gabungkan ke Dyeing atau Printing berdasarkan process
            $process = optional(optional($inspection->wo)->mo)->process;

            $jenisOrder = $jenisOrderMapInspecting[$process] ?? 'Tidak Ada';

            $result[$jenisOrder][$jenisInspek][] = [
                'tgl_kirim' => $inspection->tgl_kirim,
                'jenis_order' => $jenisOrder,
                'jenis_inspek' => $jenisInspek,
                'satuan' => $unitLabel,
                'total_per_grade' => $gradeTotals,
                'total_qty' => round(array_sum($gradeTotals), 2),
            ];
        } else {
            // Selain Fresh tetap masuk ke Makloon
            $jenisOrder = $jenisOrderMapMklbj[$inspection->jenis] ?? 'Tidak Ada';

            $result[$jenisOrder][$jenisInspek][] = [
                'tgl_kirim' => $inspection->tgl_kirim,
                'jenis_order' => $jenisOrder,
                'jenis_inspek' => $jenisInspek,
                'satuan' => $unitLabel,
                'total_per_grade' => $gradeTotals,
                'total_qty' => round(array_sum($gradeTotals), 2),
            ];
        }
    }

    return response()->json([
        'data' => $result
    ]);
}


 public function analisaPengirimanProduksi(Request $request)
    {
        // Validasi input
        $request->validate([
            'no_kirim' => 'nullable|string',
            'buyer' => 'nullable|string',
            'design' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'no_lot' => 'nullable|string',
            'kombinasi' => 'nullable|string',
            'jenis_order' => 'nullable|string',
            'no_wo' => 'nullable|string',
            'motif' => 'nullable|string',
            'stamping' => 'nullable|string',
            'piece_length' => 'nullable|string'
        ]);

        // Ambil parameter dari request
        $no_kirim = $request->input('no_kirim');
        $buyer = $request->input('buyer');
        $design = $request->input('design');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $no_lot = $request->input('no_lot');
        $kombinasi = $request->input('kombinasi');
        $jenis_order = $request->input('jenis_order');
        $no_wo = $request->input('no_wo');
        $motif = $request->input('motif');
        $stamping = $request->input('stamping');
        $piece_length = $request->input('piece_length');

        // Bangun query untuk Inspecting berdasarkan filter
        $query = Inspecting::with([
            'sc.customer',
            'mo',
            'wo',
            'kartuProcessDyeing',
            'kartuProcessDyeing.kartuProsesDyeingItem',
            'kartuProcessPrinting',
            'kartuProcessPrinting.kartuProsesPrintingItem',
        ]);

        $query->where('status', 4);

        if ($no_kirim) {
            $query->where('no', 'like', '%' . $no_kirim . '%');
        }

        if ($buyer) {
            $query->whereHas('sc.customer', function($q) use ($buyer) {
                $q->where('cust_no', 'like', '%' . $buyer . '%');
            });
        }

        if ($design) {
            $query->whereHas('mo', function($q) use ($design) {
                $q->where('design', 'like', '%' . $design . '%')
                  ->orWhere('article', 'like', '%' . $design . '%');
            });
        }

        if ($start_date && $end_date) {
            $query->whereBetween('date', [$start_date, $end_date]);
        }

        if ($no_lot) {
            $query->where('no_lot', 'like', '%' . $no_lot . '%');
        }

        if ($kombinasi) {
            $query->where('kombinasi', 'like', '%' . $kombinasi . '%');
        }

        if ($jenis_order) {
            $query->where('jenis_process', 'like', '%' . $jenis_order . '%');
        }

        if ($no_wo) {
             $query->whereHas('wo', function($q) use ($no_wo) {
                $q->where('no', 'like', '%' . $no_wo . '%');
            });
        }

        if ($motif) {
             $query->whereHas('wo.greige', function($q) use ($motif) {
                $q->where('nama_kain', 'like', '%' . $motif . '%');
            });
        }

        if ($stamping) {
             $query->whereHas('mo', function($q) use ($stamping) {
                $q->where('selvedge_stamping', 'like', '%' . $stamping . '%')
                  ->orWhere('selvedge_continues', 'like', '%' . $stamping . '%');
            });
        }

        if ($piece_length) {
             $query->whereHas('mo', function($q) use ($piece_length) {
                $q->where('piece_length', 'like', '%' . $piece_length . '%');
            });
        }

        // Ambil data Inspecting yang sudah difilter
        $inspectings = $query->with(['inspectingItem'])->get();

        // Struktur output yang diinginkan
        $groupedOutput = [];

        foreach ($inspectings as $inspection) {
            $buyer = $inspection->sc->customer->cust_no;
            $no_wo = $inspection->wo->no;
            $kombinasi = $inspection->kombinasi;
            $design = $inspection->kartuProcessDyeing ? $inspection->mo->article : ($inspection->kartuProcessPrinting ? $inspection->mo->design : '');

            $data = [
                'no_kirim' => $inspection->no,
                'tgl_kirim' => $inspection->date,
                'no_wo' => $no_wo,
                'no_kartu' => $inspection->jenis_process == 1
                            ? optional($inspection->kartuProcessDyeing)->no
                            : optional($inspection->kartuProcessPrinting)->no,
                'buyer' => $buyer,
                'tgl_inspeksi' => $inspection->tanggal_inspeksi,
                'motif' => $inspection->wo->greige->nama_kain,
                'no_lot' => $inspection->no_lot,
                'design' => $design,
                'kombinasi' => $kombinasi,
                'piece_length' => $inspection->mo->piece_length,
                'stamping' => $inspection->mo->selvedge_stamping,
                'satuan' => [
                    1 => 'Yard',
                    2 => 'Meter',
                    3 => 'Pcs',
                    4 => 'Kilogram',
                ][$inspection->unit],
                'jenis_order' => $inspection->jenis_process,
                'details' => [],
            ];

            $total_qty = 0;

            foreach ($inspection->inspectingItem as $item) {
                $data['details'][] = [
                    'grade' => $item->grade,
                    'join_piece' => $item->join_piece,
                    'qty' => $item->qty,
                    'note' => $item->note,
                    'qty_sum' => $item->qty_sum,
                    'is_head' => $item->is_head,
                    'qty_count' => $item->qty_count
                ];

                $total_qty += $item->qty;
            }

            $data['total_qty'] = $total_qty;

            $groupedOutput[$buyer][$no_wo][$design][$kombinasi][] = $data;
        }

        // Kirim hasilnya sebagai response
        return response()->json([
            'data' => $groupedOutput
        ]);
    }



    public function analisaPengirimanProduksiMklbj(Request $request)
    {
          // Validasi input
        $request->validate([
            'no_kirim' => 'nullable|string',
            'buyer' => 'nullable|string',
            'design' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'no_lot' => 'nullable|string',
            'kombinasi' => 'nullable|string',
            'jenis_order' => 'nullable|string',
            'no_wo' => 'nullable|string',
            'motif' => 'nullable|string',
            'stamping' => 'nullable|string',
            'piece_length' => 'nullable|string'
        ]);

        // Ambil parameter dari request
        $no_kirim = $request->input('no_kirim');
        $buyer = $request->input('buyer');
        $design = $request->input('design');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $no_lot = $request->input('no_lot');
        $kombinasi = $request->input('kombinasi');
        $jenis_order = $request->input('jenis_order');
        $no_wo = $request->input('no_wo');
        $motif = $request->input('motif');
        $stamping = $request->input('stamping');
        $piece_length = $request->input('piece_length');

        // Bangun query untuk Inspecting berdasarkan filter
        $query = InspectingMklbj::with([
                'wo',
                'wo.sc.customer',
                'wo.woColor',
                'wo.mo',
                'woColor',
                'woColor.moColor',
                'inspectingMklbjItem',
        ]);

        $query->where('status', 3);

        if ($no_kirim) {
            $query->where('no', 'like', '%' . $no_kirim . '%');
        }

        if ($buyer) {
            $query->whereHas('wo.sc.customer', function($q) use ($buyer) {
                $q->where('cust_no', 'like', '%' . $buyer . '%');
            });
        }

        if ($design) {
            $query->whereHas('wo.mo', function($q) use ($design) {
                $q->where('design', 'like', '%' . $design . '%')
                  ->orWhere('article', 'like', '%' . $design . '%');
            });
        }

        if ($start_date && $end_date) {
            $query->whereBetween('tgl_kirim', [$start_date, $end_date]);
        }

        if ($no_lot) {
            $query->where('no_lot', 'like', '%' . $no_lot . '%');
        }

        if ($kombinasi) {
             $query->whereHas('wocolor.mocolor', function($q) use ($kombinasi) {
                $q->where('color', 'like', '%' . $kombinasi . '%');
            });
        }

        if ($jenis_order) {
            $query->where('jenis', 'like', '%' . $jenis_order . '%');
        }

        if ($no_wo) {
             $query->whereHas('wo', function($q) use ($no_wo) {
                $q->where('no', 'like', '%' . $no_wo . '%');
            });
        }

        if ($motif) {
            $query->whereHas('wo.greige', function($q) use ($motif) {
                $q->where('nama_kain', 'like', '%' . $motif . '%');
            });
        }

        if ($stamping) {
            $query->whereHas('wo.mo', function($q) use ($stamping) {
                $q->where('selvedge_stamping', 'like', '%' . $stamping . '%');
            });
        }

        if ($piece_length) {
            $query->whereHas('wo.mo', function($q) use ($piece_length) {
                $q->where('piece_length', 'like', '%' . $piece_length . '%');
            });
        }

        // Ambil data Inspecting yang sudah difilter
        $inspectings = $query->with(['inspectingMklbjItem'])->get();

        // Struktur output yang diinginkan
        $output = [];

        foreach ($inspectings as $inspection) {
            $buyer = $inspection->wo->sc->customer->cust_no;
            $no_wo = $inspection->wo->no;
            $kombinasi = $inspection->wocolor->mocolor->color;
            $design = $inspection->wo->mo->process == 1 ? $inspection->wo->mo->article : ($inspection->wo->mo->process == 2 ? $inspection->wo->mo->design : '');

            $data = [
                'no_kirim' => $inspection->no,
                'tgl_kirim' => $inspection->tgl_kirim,
                'no_wo' => $no_wo,
                'buyer' => $buyer,
                'tgl_inspeksi' => $inspection->tgl_inspeksi,
                'motif' => $inspection->wo->greige->nama_kain,
                'no_lot' => $inspection->no_lot,
                'design' => $design,
                'kombinasi' => $kombinasi,
                'piece_length' => $inspection->wo->mo->piece_length,
                'stamping' => $inspection->wo->mo->selvedge_stamping == '-' ? $inspection->wo->mo->selvedge_continues : $inspection->wo->mo->selvedge_stamping,
                'satuan' => [
                    1 => 'Yard',
                    2 => 'Meter',
                    3 => 'Pcs',
                    4 => 'Kilogram'
                ][$inspection->satuan],
                'jenis' => [
                    1 => 'Makloon Proses',
                    2 => 'Makloon Finish',
                    3 => 'Barang Jadi',
                    4 => 'Fresh'
                ][$inspection->jenis],
                'details' => [],

            ];

            $total_qty = 0;

            foreach ($inspection->inspectingMklbjItem as $item) {
                $data['details'][] = [
                    'grade' => $item->grade,
                    'join_piece' => $item->join_piece,
                    'qty' => $item->qty,
                    'note' => $item->note,
                    'qty_sum' => $item->qty_sum,
                    'is_head' => $item->is_head,
                    'qty_count' => $item->qty_count
                ];

            }

            $data['total_qty'] = $total_qty;


            $output[$buyer][$no_wo][$design][$kombinasi][] = $data;
        }

        // Kirim hasilnya sebagai response
        return response()->json([
            'data' => $output
        ]);

    }

    public function rekapPengirimanHarian(Request $request)
{
    $request->validate([
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date'
    ]);

    $start_date = $request->input('start_date');
    $end_date = $request->input('end_date');

    $result = [];

    // Konversi satuan ke Yard
    $unitLabels = [
        1 => 'Yard',
        2 => 'Meter',
        3 => 'Pcs',
        4 => 'Kilogram',
    ];

    $unitKonversi = [
        'Yard' => 1,
        'Meter' => 1.09361,
        'Kilogram' => 3,
        'Pcs' => 1,
    ];

    // Mapping grade label
    $gradeLabels = [
        1 => 'Grade A',
        2 => 'Grade B',
        3 => 'Grade C',
        4 => 'Piece Kecil',
        5 => 'Sample',
        7 => 'Grade A+',
        8 => 'Grade A*',
        9 => 'Putih',
    ];

    // Mapping jenis_order
    $jenisOrderMapInspecting = [
        1 => 'Dyeing',
        2 => 'Printing',
    ];

    $jenisOrderMapMklbj = [
        1 => 'Makloon Proses',
        2 => 'Makloon Finish',
        3 => 'Barang Jadi',
        4 => 'Fresh',
    ];

    // Mapping jenis_inspek
    $jenisInspekMap = [
        1 => 'Fresh Order',
        2 => 'Re-Packing',
        3 => 'Hasil Perbaikan',
    ];

    // =========================
    // 1. Inspecting
    // =========================
    $query1 = Inspecting::with([
        'sc.customer',
        'mo',
        'wo',
        'kartuProcessDyeing.kartuProsesDyeingItem',
        'kartuProcessPrinting.kartuProsesPrintingItem',
        'inspectingItem'
    ])->where('status', 4);

    if ($start_date && $end_date) {
        $query1->whereBetween('date', [$start_date, $end_date]);
    }

    $inspectings = $query1->get();

    foreach ($inspectings as $inspection) {
        $unitLabel = $unitLabels[$inspection->unit] ?? 'Tidak Ada';
        $konversi = $unitKonversi[$unitLabel] ?? 1;

        $gradeTotals = [];
        foreach ($inspection->inspectingItem as $item) {
            $gradeName = $gradeLabels[$item->grade] ?? 'Tidak Ada Grade';
            $convertedQty = round($item->qty * $konversi, 2);
            $gradeTotals[$gradeName] = round(($gradeTotals[$gradeName] ?? 0) + $convertedQty, 2);
        }

        $jenisOrder = $jenisOrderMapInspecting[$inspection->jenis_process] ?? 'Tidak Ada';
        $jenisInspek = $jenisInspekMap[$inspection->jenis_inspek] ?? 'Tidak Ada';
        $tanggal = $inspection->date;

        $result[$tanggal][$jenisOrder][$jenisInspek][] = [
            'jenis_order' => $jenisOrder,
            'jenis_inspek' => $jenisInspek,
            'satuan' => $unitLabel,
            'total_per_grade' => $gradeTotals,
            'total_qty' => round(array_sum($gradeTotals), 2),
        ];
    }

    // =========================
    // 2. InspectingMklbj
    // =========================
    $query2 = InspectingMklbj::with([
        'wo',
        'wo.sc.customer',
        'wo.woColor',
        'wo.mo',
        'woColor.moColor',
        'inspectingMklbjItem'
    ])->where('status', 3);

    if ($start_date && $end_date) {
        $query2->whereBetween('tgl_kirim', [$start_date, $end_date]);
    }

    $inspectingMklbjs = $query2->get();

    foreach ($inspectingMklbjs as $inspection) {
        $unitLabel = $unitLabels[$inspection->satuan] ?? 'Tidak Ada';
        $konversi = $unitKonversi[$unitLabel] ?? 1;

        $gradeTotals = [];
        foreach ($inspection->inspectingMklbjItem as $item) {
            $gradeName = $gradeLabels[$item->grade] ?? 'Tidak Ada Grade';
            $convertedQty = round($item->qty * $konversi, 2);
            $gradeTotals[$gradeName] = round(($gradeTotals[$gradeName] ?? 0) + $convertedQty, 2);
        }

        $jenisInspek = $jenisInspekMap[$inspection->jenis_inspek] ?? 'Tidak Ada';
        $tanggal = $inspection->tgl_kirim;

        if ($inspection->jenis == 4) {
            // Fresh, gabungkan ke Dyeing atau Printing berdasarkan process
            $process = optional(optional($inspection->wo)->mo)->process;
            $jenisOrder = $jenisOrderMapInspecting[$process] ?? 'Tidak Ada';
        } else {
            // Selain Fresh masuk ke Makloon
            $jenisOrder = $jenisOrderMapMklbj[$inspection->jenis] ?? 'Tidak Ada';
        }

        $result[$tanggal][$jenisOrder][$jenisInspek][] = [
            'jenis_order' => $jenisOrder,
            'jenis_inspek' => $jenisInspek,
            'satuan' => $unitLabel,
            'total_per_grade' => $gradeTotals,
            'total_qty' => round(array_sum($gradeTotals), 2),
        ];
    }


    ksort($result);

    return response()->json([
        'data' => $result
    ]);
}







}