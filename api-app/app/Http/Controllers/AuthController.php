<?php

namespace App\Http\Controllers;

use App\User;
use App\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use \Carbon\Carbon;


class AuthController extends Controller
{
    //
        public function login(Request $request)
    {
        try {
            DB::beginTransaction();
            $username = $request->json('username');
            $password = $request->json('password');
            $forceLogin = $request->json('force_login'); // Tambahkan parameter force_login
            $options = [
                'cost' => 12
            ];

            $user = UserModel::where('username', $username)->first();

            if ($user) {
                // Periksa apakah password cocok
                if (!password_verify($password, $user->password_hash)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Password salah!',
                        'data' => [],
                    ], 200);
                }

                // Periksa apakah pengguna sudah login
                if (!is_null($user->verification_token) && $user->token_expired > Carbon::now()) {
                    if ($forceLogin) {
                        // Reset token jika force_login diaktifkan
                        $user->verification_token = null;
                        $user->token_expired = null;
                        $user->save();
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Maaf User Anda sedang login di perangkat lain. Gunakan opsi centang jika Anda yakin ingin mengganti perangkat.',
                            'data' => [],
                        ], 200);
                    }
                }

                // Buat token JWT baru
                $timeExpired = Carbon::now()->addDays(7);
                $payload = [
                    'sub' => $user->id,
                    'name' => $user->username,
                    'expired' => $timeExpired,
                ];

                $jwtSecret = env('JWT_SECRET');
                $token = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])) . '.' .
                    base64_encode(json_encode($payload)) . '.' .
                    base64_encode(hash_hmac('sha256', 'header.payload', $jwtSecret, true));

                // Simpan token baru ke database
                $user->verification_token = $token;
                $user->token_expired = $timeExpired;
                $user->save();

                Auth::setUser($user);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Login Success!',
                    'data' => $user,
                    'token' => $token,
                ], 200);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Username atau Password salah!',
                    'data' => [],
                ], 200);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Login Failed! ' . $th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    public function changePassword(Request $request)
    {
        // Ambil user berdasarkan token (custom auth manual, bukan Auth::user())
        $token = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $token);

        $user = User::where('verification_token', $token)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan atau token tidak valid',
            ], 401);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verifikasi password lama
        if (!password_verify($request->old_password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama salah',
            ], 200);
        }

        // Update password baru
        $user->password_hash = password_hash($request->new_password, PASSWORD_BCRYPT, ['cost' => 12]);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah',
        ], 200);
    }

    public function profile()
    {
        try {
            $token = request()->header('Authorization');
            $token = str_replace('Bearer ', '', $token);

            $user = User::where('verification_token', $token)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan atau token tidak valid',
                    'data' => null,
                ], 404);
            }

            // Ambil hanya field yang dibutuhkan
            $data = [
                'id' => $user->id,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'verification_token' => $user->verification_token,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Data profil berhasil diambil',
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $th->getMessage(),
                'data' => null,
            ], 500);
        }
    }






    public function logout(Request $request)
    {
        try {
            DB::beginTransaction();

            $token = $request->header('Authorization');
            $token = str_replace('Bearer ', '', $token);

            $user = UserModel::where('verification_token', $token)->update([
                'verification_token' => null,
                'token_expired' => null,
            ]);

            if ($user) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Logout Success!',
                    'data' => [],
                ], 200);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Logout Failed!',
                    'data' => [],
                ], 200);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Login Failed! ' . $th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    function UpdateFCMToken(Request $request){
        try {
            DB::beginTransaction();
            $id=$request->json()->get('id');
            $fcm_token=$request->json()->get('fcm_token');
            $sql="UPDATE
                            public.user
                        SET
                            fcm_token = '$fcm_token'
                        WHERE
                            id = '$id'";

                    $update_token = DB::UPDATE($sql);

            if ($update_token) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Update FCM Token Berhasil! ',
                    'data' => $fcm_token,
                ], 200);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal update fcm token! ',
                    'data' => [],
                ], 200);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal update fcm token! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    public static function quickRandom($length = 100)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=';

        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

    function GetRoleUser(Request $request){
        try {
            $id=$request->json()->get('id');

            $sql = "SELECT * FROM public.auth_assignment WHERE user_id='$id' AND lower(item_name) LIKE '%tablet%'";

            $data = DB::SELECT($sql);

            // $header=array("Content-Type:application/json","Authorization: key=AAAA7rHxAxw:APA91bEX6eI2Oj-oWsOGCRqmshQip-FT2TOr9n2L0X0U-ExYRalg_ZgtVcw1sEotmZkapjJ4dkeumVNGMGCiCfElhIPrntoAMTdt_ypn2HOXQPaciaP10nPNqVDlzqL-HbfsUVUvOJFA");

            // $fcm=json_encode(
            //     array(
            //     //   "to"              => "ceblGaxMQE-gPjveJrnZsa:APA91bF69crnaO2AFIyhuamAGNfJKxxxbtGIGFp1YdiyohH52j9kCFNo9a4qfCY_jVgCU7vrpiU9Bz9yTbwkrnaEEoNarslk2t1T7xl9Qx4m7OHnJuncfNXMqrhzORyiH21ddxBUJ03D",
            //       "to"              =>"/topics/penerimaan",//allDevice
            //       "notification"    => array(
            //         "title"         => "Pemberitahuan",
            //         "message"       => "Penerimaan Inspecting",
            //         "body"          => "Penerimaan Inspecting",
            //         "id"            => "45",
            //         "no_kartu"  => "JP JF 03/0003/20",
            //         'vibrate'       => 1,
            //         'sound'         => 1,
            //         "click_action"  => "OPEN_ACTIVITY"
            //         ),
            //       "data"  => array(
            //         "title"     => "Pemberitahuan",
            //         "message"   => "Penerimaan Inspecting",
            //         "body"          => "Penerimaan Inspecting",
            //         "id"        => "45",
            //         "no_kartu"  => "JP JF 03/0003/20",
            //         'vibrate'   => 1,
            //         'sound'     => 1
            //       )
            //     )
            //   );


            //   $curl = curl_init();

            //   curl_setopt_array($curl, array(
            //       CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
            //       CURLOPT_RETURNTRANSFER => true,
            //       CURLOPT_SSL_VERIFYPEER => false,
            //       CURLOPT_ENCODING => "",
            //       CURLOPT_MAXREDIRS => 10,
            //       CURLOPT_TIMEOUT => 30,
            //       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //       CURLOPT_CUSTOMREQUEST => "POST",
            //       CURLOPT_POSTFIELDS => $fcm,
            //       CURLOPT_HTTPHEADER =>$header,
            //   ));

            //   $response = curl_exec($curl);
            //   $err = curl_error($curl);
            //   $data_curl = json_decode($response,true);

            // if (!$err) {

            // } else {
            // }

            if (count($data) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data role user kosong!',
                    'data' => [],
                ], 200);
            } else {
                if ($data) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil menampilkan data role user!',
                        'data' => $data,
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengambil data role user! ',
                        'data' => [],
                    ], 200);
                }
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil role user! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }
}
