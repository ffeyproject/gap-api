<?php

namespace App\Http\Controllers;

use App\DefectInspectingItem;
use Illuminate\Http\Request;
use App\Inspecting;
use App\InspectingItem;
use App\InspectingMklbj;
use App\InspectingMklbjItem;
use App\KartuProsesDyeing;
use App\KartuProsesPrinting;
use App\MstKodeDefect;
use App\User;
use App\Wo;
use App\WoColor;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class InspectingController extends Controller
{
    //

    public function index($id)
    {
        try {
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

            // Fetch the inspecting data with the associated relationships
            $inspecting = Inspecting::with([
                'sc',
                'scGreige',
                'mo',
                'wo',
                'wo.greige',
                'kartuProcessDyeing',
                'kartuProcessDyeing.kartuProsesDyeingItem.stock',
                'kartuProcessPrinting',
                'kartuProcessPrinting.kartuProsesPrintingItem.stock',
                'createdBy',
                'updatedBy',
                'approvedBy',
                'deliveredBy',
                'k3l',
                'inspectingItem.defect_item.mstKodeDefect',
                'inspectingItem.stock'
            ])->findOrFail($id);

            // Return the fetched data as JSON response
            return response()->json([
                'success' => true,
                'data' => $inspecting
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // If the record is not found
            return response()->json([
                'success' => false,
                'message' => 'Inspecting data not found'
            ], 404);
        } catch (\Exception $e) {
            // Catch any other exceptions and log the error
            Log::error('Error fetching inspecting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the data'
            ], 500);
        }
    }



    public function indexMklbj($id)
    {
        try {
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

            // Fetch the inspecting data with the associated relationships
            $inspecting = InspectingMklbj::with([
                'wo',
                'woColor',
                'woColor.moColor',
                'inspectingMklbjItem.defect_item.mstKodeDefect'
            ])->findOrFail($id);

            // Return the fetched data as JSON response
            return response()->json([
                'success' => true,
                'data' => $inspecting
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // If the record is not found
            return response()->json([
                'success' => false,
                'message' => 'Inspecting Mklbj data not found'
            ], 404);
        } catch (\Exception $e) {
            // Catch any other exceptions and log the error
            Log::error('Error fetching inspecting mklbj: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the data'
            ], 500);
        }
    }



    public function show($id)
    {
        try {
            $inspecting = Inspecting::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $inspecting
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching inspecting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ], 404);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            // Validasi data yang dikirimkan
            $validator = Validator::make($request->all(), [
                'qty' => 'required|numeric',
                'grade' => 'required',
                'join_piece' => 'nullable',
                'defect' => 'nullable|array',
                'stock_id' => 'nullable',
                'qty_bit' => 'nullable|numeric',
                'lot_no' => 'nullable',
                'gsm_item' => 'nullable',
                'defect.*.mst_kode_defect_id' => 'nullable|exists:mst_kode_defect,id',
            ]);

            // Cek validasi
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi data gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();

            $joinPiece = !empty($validatedData['join_piece']) ? $validatedData['join_piece'] : null;

            $inspecting = InspectingItem::find($id);

            if (!$inspecting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Inspecting tidak ditemukan',
                ], 404);
            }

            $inspectingItem = InspectingItem::updateOrCreate(
                [
                    'id' => $inspecting->id,
                ],
                [
                    'grade' => $validatedData['grade'] ?? '-',
                    'join_piece' => $joinPiece,
                    'qty' => (int) $validatedData['qty'],
                    'note' => null,
                    'qty_sum' => null,
                    'is_head' => 0,
                    'qr_code' => 'INS-' . $inspecting->id . '-' . (InspectingItem::latest('id')->first()->id + 1),
                    'qty_count' => 0,
                    'qr_code_desc' => null,
                    'qr_print_at' => null,
                    'stock_id' => $validatedData['stock_id'] ?? '',
                    'qty_bit' => $validatedData['qty_bit'] ?? null,
                    'lot_no' => $validatedData['lot_no'] ?? null,
                    'gsm_item' => $validatedData['gsm_item'] ?? null
                ]
            );

            // Ambil ID defect yang ada dalam array defect
            $defectIdsToUpdate = [];
            if (isset($validatedData['defect']) && is_array($validatedData['defect']) && count($validatedData['defect']) > 0) {
                foreach ($validatedData['defect'] as $defect) {
                    // Ambil ID defect dari array defect
                    if (isset($defect['id'])) {
                        $defectIdsToUpdate[] = $defect['id'];
                    }
                }
            }

            // Hapus defect yang tidak ada dalam array defect yang dikirimkan
            DefectInspectingItem::where('inspecting_item_id', $inspecting->id)
                ->whereNotIn('id', $defectIdsToUpdate)
                ->delete();

            if (isset($validatedData['defect']) && is_array($validatedData['defect']) && count($validatedData['defect']) > 0) {
                foreach ($validatedData['defect'] as $defect) {
                    if (isset($defect['id'])) {
                        $existingDefect = DefectInspectingItem::where('id', $defect['id'])->first();
                        if ($existingDefect) {
                            $existingDefect->update([
                                'mst_kode_defect_id' => $defect['mst_kode_defect_id'] ?? null,
                                'meterage' => $defect['meterage'] ?? null,
                                'point' => $defect['point'] ?? null,
                            ]);
                        }
                    } else {
                        DefectInspectingItem::create([
                            'inspecting_item_id' => $inspecting->id,
                            'mst_kode_defect_id' => $defect['mst_kode_defect_id'],
                            'meterage' => $defect['meterage'] ?? null,
                            'point' => $defect['point'] ?? null,
                        ]);
                    }
                }
            }

            if (isset($validatedData['no_lot'])) {
                $inspectingItem->update([
                    'no_lot' => $validatedData['no_lot']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data Inspecting berhasil diperbarui',
                'data' => $inspecting->load('defect_item'),
            ]);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function updateInspecting(Request $request, $id)
    {
        // Validasi input menggunakan Validator
        $validator = Validator::make($request->all(), [
            'wo_id' => 'required|integer',
            'no_lot' => 'required|string',
            'unit' => 'required|integer',
            'warna' => 'required|string',
            'mo_id' => 'nullable',
            'sc_greige_id' => 'nullable',
            'sc_id' => 'nullable',
            'inspection_table' => 'nullable',
            'kartu_proses_dyeing_id' => 'nullable|integer',
            'kartu_proses_printing_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        // Ambil token dari header Authorization
        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization token is missing',
            ], 400);
        }

        $token = str_replace('Bearer ', '', $token);

        // Cari user berdasarkan token
        $user = User::where('verification_token', $token)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
            ], 401);
        }

        // Cari data berdasarkan inspecting_id (id dalam URL)
        $inspecting = Inspecting::find($id);
        if (!$inspecting) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found',
            ], 404);
        }

        // Update data di tabel inspecting
        $inspecting->wo_id = $request->wo_id;
        $inspecting->no_lot = $request->no_lot;
        $inspecting->unit = $request->unit;
        $inspecting->kombinasi = $request->warna;
        $inspecting->mo_id = $request->mo_id;
        $inspecting->sc_greige_id = $request->sc_greige_id;
        $inspecting->sc_id = $request->sc_id;
        $inspecting->inspection_table = $request->inspection_table;
        $inspecting->save();

        // Cek apakah kartu_proses_printing_id ada di request, jika ada, cari berdasarkan kartu_proses_printing_id
        if ($request->filled('kartu_proses_printing_id')) {
            $kartuProsesPrinting = KartuProsesPrinting::find($request->kartu_proses_printing_id);
            if (!$kartuProsesPrinting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kartu Proses Printing data not found',
                ], 404);
            }

            // Update data KartuProsesPrinting
            $kartuProsesPrinting->wo_id = $request->wo_id;
            $kartuProsesPrinting->mo_id = $request->mo_id;
            $kartuProsesPrinting->sc_id = $request->sc_id;
            $kartuProsesPrinting->sc_greige_id = $request->sc_greige_id;
            $kartuProsesPrinting->wo_color_id = $request->wo_color_id;
            $kartuProsesPrinting->updated_by = $user->id;
            $kartuProsesPrinting->updated_at = \Carbon\Carbon::now()->timestamp;
            $kartuProsesPrinting->save();

            // Kembalikan response dengan data kartu_proses_printing
            return response()->json([
                'success' => true,
                'message' => 'Data updated successfully',
                'data' => [
                    'inspecting' => $inspecting,
                    'kartu_proses_printing' => $kartuProsesPrinting,
                ],
            ]);
        } else {
            // Jika tidak ada kartu_proses_printing_id, gunakan kartu_proses_dyeing_id
            if ($request->has('kartu_proses_dyeing_id')) {
                // Cari data KartuProsesDyeing berdasarkan kartu_proses_dyeing_id
                $kartuProsesDyeing = KartuProsesDyeing::find($request->kartu_proses_dyeing_id);
                if (!$kartuProsesDyeing) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kartu Proses Dyeing data not found',
                    ], 404);
                }

                // Update data KartuProsesDyeing
                $kartuProsesDyeing->wo_id = $request->wo_id;
                $kartuProsesDyeing->mo_id = $request->mo_id;
                $kartuProsesDyeing->sc_id = $request->sc_id;
                $kartuProsesDyeing->sc_greige_id = $request->sc_greige_id;
                $kartuProsesDyeing->wo_color_id = $request->wo_color_id; // Asumsikan ada wo_color_id di request
                $kartuProsesDyeing->updated_by = $user->id;
                $kartuProsesDyeing->updated_at = \Carbon\Carbon::now()->timestamp;
                $kartuProsesDyeing->save();

                // Kembalikan response dengan data kartu_proses_dyeing
                return response()->json([
                    'success' => true,
                    'message' => 'Data updated successfully',
                    'data' => [
                        'inspecting' => $inspecting,
                        'kartu_proses_dyeing' => $kartuProsesDyeing,
                    ],
                ]);
            } else {
                // Jika tidak ada kartu_proses_printing_id dan kartu_proses_dyeing_id di request
                return response()->json([
                    'success' => false,
                    'message' => 'Kartu Proses data not found',
                ], 400);
            }
        }
    }


    public function updateInspectingMklbj(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'wo_id' => 'required|integer',
                'mo_color_id' => 'required|integer',
                'no_lot' => 'required|max:255',
                'unit' => 'required|max:255',
                'inspection_table' => 'nullable',
                'jenis_makloon' => 'required|max:255',
                'inspect_result' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi data gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();

            // Cari data InspectingMklbj berdasarkan ID
            $inspectingMklbj = InspectingMklbj::find($id);

            if (!$inspectingMklbj) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Inspecting tidak ditemukan',
                ], 404);
            }

            // Update data InspectingMklbj
            $inspectingMklbj->update([
                'wo_id' => $validatedData['wo_id'],
                'wo_color_id' => $validatedData['mo_color_id'],
                'no_lot' => $validatedData['no_lot'],
                'satuan' => $validatedData['unit'],
                'jenis' => $validatedData['jenis_makloon'],
                'tgl_inspeksi' => \Carbon\Carbon::now()->format('Y-m-d'),
                'tgl_kirim' => \Carbon\Carbon::now()->format('Y-m-d'),
                'updated_at' => \Carbon\Carbon::now()->timestamp,
                'inspection_table' => $validatedData['inspection_table'],
            ]);

            // Proses pembaruan inspect_result
            if (isset($validatedData['inspect_result'])) {
                foreach ($validatedData['inspect_result'] as $item) {
                    // Cari item InspectingMklbjItem yang akan diperbarui
                    $inspectingItem = InspectingMklbjItem::updateOrCreate(
                        ['id' => $item['id']],
                        [
                            'inspecting_id' => $inspectingMklbj->id,
                            'grade' => $item['grade'] ?? '-',
                            'join_piece' => $item['join_piece'] ?? null,
                            'qty' => $item['qty'] ?? 0,
                            'lot_no' => $item['lot_no'] ?? '',
                            'qr_code' => 'INS-' . $inspectingMklbj->id . '-' . (InspectingMklbjItem::latest('id')->first()->id + 1),
                            'gsm_item' => $item['gsm_item'] ?? null,
                        ]
                    );

                    // Proses pembaruan defect jika ada
                    if (isset($item['defect']) && is_array($item['defect'])) {
                        foreach ($item['defect'] as $defect) {
                            // Cari data defect yang terkait
                            $defectItem = DefectInspectingItem::updateOrCreate(
                                [
                                    'inspecting_mklbj_item_id' => $inspectingItem->id,
                                    'mst_kode_defect_id' => $defect['kode_defect'],
                                ],
                                [
                                    'meterage' => $defect['meter_defect'] ?? null,
                                    'point' => $defect['point'] ?? null,
                                ]
                            );
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Data Inspecting berhasil diperbarui',
                'data' => $inspectingMklbj->load('inspectingMklbjItem'),
            ]);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function updateInspectingMklbjItem(Request $request, $id)
    {
        try {
            // Validasi data input
            $validator = Validator::make($request->all(), [
                'qty' => 'required|numeric',
                'grade' => 'nullable|integer',
                'join_piece' => 'nullable|string|max:255',
                'lot_no' => 'nullable|string|max:255',
                'gsm_item' => 'nullable',
                'defect' => 'nullable|array',
                'defect.*.id' => 'nullable|integer',
                'defect.*.mst_kode_defect_id' => 'required|integer',
                'defect.*.meterage' => 'nullable|numeric',
                'defect.*.point' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi data gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();

            // Cari data utama InspectingMklbjItem berdasarkan ID dari URL
            $inspectingItem = InspectingMklbjItem::find($id);
            if (!$inspectingItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Inspecting Item tidak ditemukan',
                ], 404);
            }

            // Update data InspectingMklbjItem
            $inspectingItem->update([
                'qty' => $validatedData['qty'],
                'grade' => $validatedData['grade'] ?? null,
                'join_piece' => $validatedData['join_piece'] ?? '',
                'lot_no' => $validatedData['lot_no'] ?? '',
                'gsm_item' => $validatedData['gsm_item'] ?? null,
            ]);

            foreach ($validatedData['defect'] as $defect) {
                // Prepare the data for updating or creating a defect record
                $defectData = [
                    'mst_kode_defect_id' => $defect['mst_kode_defect_id'],
                    'meterage' => $defect['meterage'] ?? null,
                    'point' => $defect['point'] ?? null,
                    'inspecting_mklbj_item_id' => $inspectingItem->id,
                ];

                // If there's an ID for the defect, update the existing record
                if (!empty($defect['id'])) {
                    $defectData['id'] = $defect['id']; // Include the id to update the record
                    DefectInspectingItem::where('id', $defect['id'])->update($defectData); // Update existing defect record
                } else {
                    // Otherwise, create a new defect record
                    DefectInspectingItem::create($defectData); // Create new defect record
                }
            }

            // Tambahan: Proses berdasarkan skrip baru
            $items = InspectingMklbjItem::where('inspecting_id', $inspectingItem->inspecting_id)->get();
            foreach ($items as $item) {
                $qtySum = InspectingMklbjItem::where('join_piece', $item->join_piece)
                    ->where('inspecting_id', $inspectingItem->inspecting_id)
                    ->sum('qty');
                $qtyCount = InspectingMklbjItem::where('join_piece', $item->join_piece)
                    ->where('inspecting_id', $inspectingItem->inspecting_id)
                    ->count();
                $isHead = InspectingMklbjItem::where('join_piece', $item->join_piece)
                    ->where('inspecting_id', $inspectingItem->inspecting_id)
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
                'message' => 'Data Inspecting Item berhasil diperbarui dan dihitung ulang',
                'data' => $inspectingItem->load('defect_item'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating InspectingMklbjItem: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function destroy($id)
    {
        try {
            $inspecting = InspectingItem::findOrFail($id);
            $inspecting->delete();
            return response()->json([
                'success' => true,
                'message' => 'Data deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting inspecting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting data'
            ], 500);
        }
    }
}