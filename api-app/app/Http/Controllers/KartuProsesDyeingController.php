<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\KartuProsesDyeing;
use App\KartuProsesPrinting;
use App\User;
use App\Wo;
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
            'woColor' => function ($query) {
                $query->select('id', 'wo_id', 'mo_color_id')->with([
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



}
