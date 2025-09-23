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
use Illuminate\Support\Facades\DB;
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
                'sc.customer',
                'scGreige',
                'mo',
                'wo',
                'wo.greige.GreigeGroup',
                'kartuProcessDyeing',
                'kartuProcessDyeing.kartuProsesDyeingItem.stock',
                'kartuProcessPrinting',
                'kartuProcessPrinting.kartuProsesPrintingItem.stock',
                'createdBy',
                'updatedBy',
                'approvedBy',
                'deliveredBy',
                'k3l',
                'inspectingItem',
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
                'wo.scGreige',
                'wo.woColor.scGreige',
                'woColor',
                'woColor.moColor',
                'inspectingMklbjItem',
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

//    public function kalkulasi(Request $request, $id)
// {
//     // Validasi request langsung dari array utama
//     $request->validate([
//         '*.nilai_poin' => 'required|numeric',
//         '*.id' => 'required|array',
//         '*.id.*.inspecting_item_id' => 'required|integer',
//     ]);

//     // Loop melalui setiap item dalam array request
//     foreach ($request->all() as $item) {
//         foreach ($item['id'] as $inspectItem) {
//             $inspect = InspectingItem::where('id', $inspectItem['inspecting_item_id'])->first();

//             if ($inspect) {
//                 // Menentukan nilai grade berdasarkan nilai_poin
//                 $inspect->grade = ($item['nilai_poin'] < 28) ? 1 : 2;
//                 $inspect->save();
//             }
//         }
//     }

//     return response()->json([
//         'success' => true,
//         'message' => 'Data berhasil diperbarui'
//     ]);
//     return response()->json(['message' => 'Data berhasil diperbarui'], 200);
// }

    public function kalkulasi(Request $request, $id)
    {
        // Validasi request langsung dari array utama
        $request->validate([
            '*.nilai_poin' => 'required|numeric',
            '*.id' => 'required|array',
            '*.id.*.inspecting_item_id' => 'required|integer',
        ]);

        // Loop melalui setiap item dalam array request
        foreach ($request->all() as $item) {
            foreach ($item['id'] as $inspectItem) {
                $inspect = InspectingItem::where('id', $inspectItem['inspecting_item_id'])->first();

                if ($inspect) {
                    // Cek jika grade sebelumnya adalah 1, 2, 7, atau 8
                    if (in_array($inspect->grade, [1, 2, 7, 8])) {
                        // Menentukan nilai grade berdasarkan nilai_poin
                        $inspect->grade = ($item['nilai_poin'] < 28) ? 1 : 2;
                        $inspect->save();
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui'
        ], 200);
    }


    public function kalkulasiMklbj(Request $request, $id)
    {
        // Validasi request langsung dari array utama
        $request->validate([
            '*.nilai_poin' => 'required|numeric',
            '*.id' => 'required|array',
            '*.id.*.inspecting_item_id' => 'required|integer',
        ]);

        // Loop melalui setiap item dalam array request
        foreach ($request->all() as $item) {
            foreach ($item['id'] as $inspectItem) {
                $inspect = InspectingMklbjItem::where('id', $inspectItem['inspecting_item_id'])->first();

                if ($inspect) {
                    // Cek jika grade sebelumnya adalah 1, 2, 7, atau 8
                    if (in_array($inspect->grade, [1, 2, 7, 8])) {
                        // Menentukan nilai grade berdasarkan nilai_poin
                        $inspect->grade = ($item['nilai_poin'] < 28) ? 1 : 2;
                        $inspect->save();
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui'
        ], 200);
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
                    // 'qty_sum' => null,
                    // 'is_head' => 0,
                    // 'qr_code' => 'INS-' . $inspecting->id . '-' . (InspectingItem::latest('id')->first()->id + 1),
                    'qr_code' => null,
                    // 'qty_count' => 0,
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


                        $getItemBasedOnInspectingId = InspectingItem::where('inspecting_id', $inspecting->id)->get();
            foreach ($getItemBasedOnInspectingId as $gIBOII) {
                // Hitung ulang qty_sum untuk setiap grup join_piece
                $qty_sum = InspectingItem::where('join_piece', $gIBOII->join_piece)->sum('qty');

                // Reset semua is_head menjadi 0
                InspectingItem::where('join_piece', $gIBOII->join_piece)->update(['is_head' => 0]);

                // Ambil item dengan ID terkecil dalam grup join_piece sebagai is_head
                $is_head = InspectingItem::where('join_piece', $gIBOII->join_piece)->orderBy('id', 'asc')->first();

                $gIBOII->qty_sum = ($is_head && ($is_head->id != $gIBOII->id)) ? null : $qty_sum;
                $gIBOII->is_head = ($is_head && $is_head->id == $gIBOII->id) ? 1 : 0;
                $gIBOII->qty_count = ($gIBOII->join_piece == null || $gIBOII->join_piece == "") ? 1 : InspectingItem::where('join_piece', $gIBOII->join_piece)->count();
                $gIBOII->qr_code = 'INS-' . $gIBOII->inspecting_id . '-' . $gIBOII->id;
                $gIBOII->save();
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

//     public function update(Request $request, $id)
// {
//     try {
//         DB::beginTransaction();
//         // Validasi data yang dikirimkan
//         $validator = Validator::make($request->all(), [
//             'qty' => 'required|numeric',
//             'grade' => 'required',
//             'join_piece' => 'nullable',
//             'defect' => 'nullable|array',
//             'stock_id' => 'nullable',
//             'qty_bit' => 'nullable|numeric',
//             'lot_no' => 'nullable',
//             'gsm_item' => 'nullable',
//             'defect.*.mst_kode_defect_id' => 'nullable|exists:mst_kode_defect,id',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Validasi data gagal',
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         $validatedData = $validator->validated();
//         $joinPiece = !empty($validatedData['join_piece']) ? $validatedData['join_piece'] : null;

//         $inspecting = InspectingItem::find($id);

//         if (!$inspecting) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Data Inspecting tidak ditemukan',
//             ], 404);
//         }

//         $inspectingItem = InspectingItem::updateOrCreate(
//             [
//                 'id' => $inspecting->id,
//             ],
//             [
//                 'grade' => $validatedData['grade'] ?? '-',
//                 'join_piece' => $joinPiece,
//                 'qty' => (int) $validatedData['qty'],
//                 'note' => null,
//                 'qr_code' => 'INS-' . $inspecting->id . '-' . (InspectingItem::latest('id')->first()->id + 1),
//                 'qty_count' => 0,
//                 'qr_code_desc' => null,
//                 'qr_print_at' => null,
//                 'stock_id' => $validatedData['stock_id'] ?? '',
//                 'qty_bit' => $validatedData['qty_bit'] ?? null,
//                 'lot_no' => $validatedData['lot_no'] ?? null,
//                 'gsm_item' => $validatedData['gsm_item'] ?? null
//             ]
//         );

//         // Proses defect
//         $defectIdsToUpdate = [];
//         if (isset($validatedData['defect']) && is_array($validatedData['defect']) && count($validatedData['defect']) > 0) {
//             foreach ($validatedData['defect'] as $defect) {
//                 if (isset($defect['id'])) {
//                     $defectIdsToUpdate[] = $defect['id'];
//                 }
//             }
//         }

//         // Hapus defect yang tidak ada dalam array defect yang dikirimkan
//         DefectInspectingItem::where('inspecting_item_id', $inspecting->id)
//             ->whereNotIn('id', $defectIdsToUpdate)
//             ->delete();

//         // Update atau Tambah defect baru
//         foreach ($validatedData['defect'] ?? [] as $defect) {
//             if (isset($defect['id'])) {
//                 $existingDefect = DefectInspectingItem::find($defect['id']);
//                 if ($existingDefect) {
//                     $existingDefect->update([
//                         'mst_kode_defect_id' => $defect['mst_kode_defect_id'] ?? null,
//                         'meterage' => $defect['meterage'] ?? null,
//                         'point' => $defect['point'] ?? null,
//                     ]);
//                 }
//             } else {
//                 DefectInspectingItem::create([
//                     'inspecting_item_id' => $inspecting->id,
//                     'mst_kode_defect_id' => $defect['mst_kode_defect_id'],
//                     'meterage' => $defect['meterage'] ?? null,
//                     'point' => $defect['point'] ?? null,
//                 ]);
//             }
//         }

//         if (isset($validatedData['no_lot'])) {
//             $inspectingItem->update([
//                 'no_lot' => $validatedData['no_lot']
//             ]);
//         }


//     // Update the inspecting item data for qty_sum, qty_count, etc.
//             // $getItemBasedOnInspectingId = InspectingItem::where('inspecting_id', $inspecting->id)->get();
//             // foreach ($getItemBasedOnInspectingId as $gIBOII) {
//             //     $qty_sum = InspectingItem::where('join_piece', $gIBOII->join_piece)
//             //         ->where('inspecting_id', $inspecting->id)
//             //         ->sum('qty');
//             //     $qty_count = InspectingItem::where('join_piece', $gIBOII->join_piece)
//             //         ->where('inspecting_id', $inspecting->id)
//             //         ->count();
//             //     $is_head = InspectingItem::where('join_piece', $gIBOII->join_piece)
//             //         ->where('inspecting_id', $inspecting->id)
//             //         ->where('join_piece', '!=', '')
//             //         ->orderBy('is_head', 'desc')
//             //         ->first();

//             //     $gIBOII->qty_sum = ($is_head && ($is_head->id != $gIBOII->id)) ? null : ($gIBOII->join_piece == null || $gIBOII->join_piece == "" ? $gIBOII->qty : $qty_sum);
//             //     $gIBOII->is_head = ($is_head && ($is_head->id != $gIBOII->id)) ? 0 : 1;
//             //     $gIBOII->qty_count = ($is_head && ($is_head->id != $gIBOII->id)) ? 0 : ($gIBOII->join_piece == null || $gIBOII->join_piece == "" ? 1 : $qty_count);
//             //     $gIBOII->qr_code = 'INS-' . $gIBOII->inspecting_id . '-' . $gIBOII->id;
//             //     $gIBOII->save();
//             // }



//             return response()->json([
//                 'success' => true,
//                 'message' => 'Data Inspecting berhasil diperbarui',
//                 'data' => $inspecting->load('defect_item'),
//             ]);
//         } catch (\Exception $e) {
//             Log::error('Exception: ' . $e->getMessage());
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Terjadi kesalahan saat memperbarui data',
//                 'error' => $e->getMessage(),
//             ], 500);
//         }
// }


// public function update(Request $request, $id)
// {
//     try {
//         DB::beginTransaction();

//         // Validasi data
//         $validator = Validator::make($request->all(), [
//             'qty' => 'required|numeric',
//             'grade' => 'required',
//             'join_piece' => 'nullable',
//             'defect' => 'nullable|array',
//             'stock_id' => 'nullable',
//             'qty_bit' => 'nullable|numeric',
//             'lot_no' => 'nullable',
//             'gsm_item' => 'nullable',
//             'defect.*.mst_kode_defect_id' => 'nullable|exists:mst_kode_defect,id',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Validasi data gagal',
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         $validatedData = $validator->validated();
//         $joinPiece = $validatedData['join_piece'] ?? null;

//         // Ambil data berdasarkan ID
//         $inspecting = InspectingItem::find($id);

//         if (!$inspecting) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Data Inspecting tidak ditemukan',
//             ], 404);
//         }

//         // Update data utama
//         $inspecting->update([
//             'grade' => $validatedData['grade'],
//             'join_piece' => $joinPiece,
//             'qty' => (int) $validatedData['qty'],
//             'stock_id' => $validatedData['stock_id'] ?? '',
//             'qty_bit' => $validatedData['qty_bit'] ?? null,
//             'lot_no' => $validatedData['lot_no'] ?? null,
//             'gsm_item' => $validatedData['gsm_item'] ?? null
//         ]);

//         // **PROSES DEFECT**
//         $defectIdsToUpdate = [];
//         if (!empty($validatedData['defect'])) {
//             foreach ($validatedData['defect'] as $defect) {
//                 if (!empty($defect['id'])) {
//                     $defectIdsToUpdate[] = $defect['id'];
//                     DefectInspectingItem::where('id', $defect['id'])
//                         ->update([
//                             'mst_kode_defect_id' => $defect['mst_kode_defect_id'] ?? null,
//                             'meterage' => $defect['meterage'] ?? null,
//                             'point' => $defect['point'] ?? null,
//                         ]);
//                 } else {
//                     DefectInspectingItem::create([
//                         'inspecting_item_id' => $inspecting->id,
//                         'mst_kode_defect_id' => $defect['mst_kode_defect_id'],
//                         'meterage' => $defect['meterage'] ?? null,
//                         'point' => $defect['point'] ?? null,
//                     ]);
//                 }
//             }
//         }

//         // Hapus defect yang tidak termasuk dalam update
//         DefectInspectingItem::where('inspecting_item_id', $inspecting->id)
//             ->whereNotIn('id', $defectIdsToUpdate)
//             ->delete();

//         // **UPDATE JOIN_PIECE & QTY_SUM**
//         if ($joinPiece) {
//             // Ambil semua item dengan join_piece yang sama
//             $itemsWithSameJoinPiece = InspectingItem::where('join_piece', $joinPiece)
//                 ->where('inspecting_id', $inspecting->inspecting_id)
//                 ->orderBy('id')
//                 ->get();

//             if ($itemsWithSameJoinPiece->isNotEmpty()) {
//                 // Item pertama sebagai `is_head`
//                 $isHeadItem = $itemsWithSameJoinPiece->first();

//                 // Hitung total qty untuk join_piece yang sama
//                 $totalQtySum = $itemsWithSameJoinPiece->sum('qty');

//                 foreach ($itemsWithSameJoinPiece as $item) {
//                     $item->update([
//                         'is_head' => ($item->id === $isHeadItem->id) ? 1 : 0,
//                         'qty_sum' => ($item->id === $isHeadItem->id) ? $totalQtySum : null,
//                         'qty_count' => ($item->id === $isHeadItem->id) ? $itemsWithSameJoinPiece->count() : 0,
//                         'qr_code' => $item->qr_code ?: 'INS-' . $item->inspecting_id . '-' . $item->id,
//                     ]);
//                 }
//             }
//         } else {
//             // Jika join_piece dihapus, cek apakah ada item lain dengan join_piece yang sama
//             $remainingItems = InspectingItem::where('join_piece', $inspecting->join_piece)
//                 ->where('inspecting_id', $inspecting->inspecting_id)
//                 ->orderBy('id')
//                 ->get();

//             if ($remainingItems->isEmpty()) {
//                 // Jika tidak ada item lain, update item yang tersisa menjadi is_head dan hapus qty_sum
//                 $inspecting->update([
//                     'is_head' => 1,
//                     'qty_sum' => null,
//                     'qty_count' => 0,
//                 ]);
//             } else {
//                 // Jika masih ada item lain, perbarui item pertama menjadi is_head baru
//                 $newHeadItem = $remainingItems->first();
//                 $totalQtySum = $remainingItems->sum('qty');

//                 foreach ($remainingItems as $item) {
//                     $item->update([
//                         'is_head' => ($item->id === $newHeadItem->id) ? 1 : 0,
//                         'qty_sum' => ($item->id === $newHeadItem->id) ? $totalQtySum : null,
//                         'qty_count' => ($item->id === $newHeadItem->id) ? $remainingItems->count() : 0,
//                     ]);
//                 }
//             }
//         }

//         DB::commit();

//         return response()->json([
//             'success' => true,
//             'message' => 'Data Inspecting berhasil diperbarui',
//             'data' => $inspecting->load('defect_item'),
//         ]);
//     } catch (\Exception $e) {
//         DB::rollBack();
//         Log::error('Exception: ' . $e->getMessage());
//         return response()->json([
//             'success' => false,
//             'message' => 'Terjadi kesalahan saat memperbarui data',
//             'error' => $e->getMessage(),
//         ], 500);
//     }
// }


    public function updateInspecting(Request $request, $id)
    {
        // Validasi input menggunakan Validator
        $validator = Validator::make($request->all(), [
            'wo_id' => 'required|integer',
            'no_lot' => 'required|string',
            'unit' => 'required|integer',
            'jenis_inspek' => 'nullable',
            'no_memo' => 'nullable',
            'warna' => 'required|string',
            'mo_id' => 'nullable',
            'sc_greige_id' => 'nullable',
            'sc_id' => 'nullable',
            'note' => 'nullable',
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
        $inspecting->jenis_inspek = $request->jenis_inspek;
        $inspecting->no_memo = $request->no_memo;
        $inspecting->unit = $request->unit;
        $inspecting->kombinasi = $request->warna;
        $inspecting->mo_id = $request->mo_id;
        $inspecting->sc_greige_id = $request->sc_greige_id;
        $inspecting->sc_id = $request->sc_id;
        $inspecting->note = $request->note;
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
                'jenis_inspek' => 'nullable',
                'no_memo' => 'nullable',
                'note' => 'nullable',
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
                'jenis_inspek' => $validatedData['jenis_inspek'],
                'no_memo' => $validatedData['no_memo'],
                'note' => $validatedData['note'] ?? null,
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
                            // 'qr_code' => 'INS-' . $inspectingMklbj->id . '-' . (InspectingMklbjItem::latest('id')->first()->id + 1),
                            'qr_code' => null,
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
                'qty_bit' => 'nullable|integer',
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

            // Cari data InspectingMklbjItem
            $inspectingItem = InspectingMklbjItem::find($id);
            if (!$inspectingItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Inspecting Item tidak ditemukan',
                ], 404);
            }

            // Update InspectingMklbjItem
            $inspectingItem->update([
                'qty'        => $validatedData['qty'],
                'grade'      => $validatedData['grade'] ?? null,
                'join_piece' => $validatedData['join_piece'] ?? '',
                'lot_no'     => $validatedData['lot_no'] ?? '',
                'gsm_item'   => $validatedData['gsm_item'] ?? null,
                'qty_bit'    => $validatedData['qty_bit'] ?? null,
            ]);

            // 🔑 Sinkronisasi defect
            $defectIds = [];

            if (!empty($validatedData['defect'])) {
                foreach ($validatedData['defect'] as $defect) {
                    $defectData = [
                        'mst_kode_defect_id'        => $defect['mst_kode_defect_id'],
                        'meterage'                  => $defect['meterage'] ?? null,
                        'point'                     => $defect['point'] ?? null,
                        'inspecting_mklbj_item_id'  => $inspectingItem->id,
                    ];

                    if (!empty($defect['id'])) {
                        DefectInspectingItem::where('id', $defect['id'])->update($defectData);
                        $defectIds[] = $defect['id'];
                    } else {
                        $newDefect = DefectInspectingItem::create($defectData);
                        $defectIds[] = $newDefect->id;
                    }
                }

                // Hapus defect lama yang tidak ada di request
                DefectInspectingItem::where('inspecting_mklbj_item_id', $inspectingItem->id)
                    ->whereNotIn('id', $defectIds)
                    ->delete();
            } else {
                // Kalau request tidak bawa defect sama sekali
                DefectInspectingItem::where('inspecting_mklbj_item_id', $inspectingItem->id)->delete();
            }

            // Hitung ulang join_piece dll (sesuai skrip lama Anda)
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

                $item->qty_sum = ($isHead && $isHead->id != $item->id)
                    ? null
                    : ($item->join_piece == null || $item->join_piece == "" ? $item->qty : $qtySum);

                $item->qr_code = 'MKL-' . $item->inspecting_id . '-' . $item->id;
                $item->is_head = ($isHead && $isHead->id != $item->id) ? 0 : 1;
                $item->qty_count = ($isHead && $isHead->id != $item->id)
                    ? 0
                    : ($item->join_piece == null || $item->join_piece == "" ? 1 : $qtyCount);

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
