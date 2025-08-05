<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });



Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', 'AuthController@login');
        Route::post('update-fcm-token', 'AuthController@UpdateFCMToken');
        Route::post('logout', ['middleware' => 'api', 'uses' => 'AuthController@logout']);
        Route::post('role-user', 'AuthController@GetRoleUser');
        Route::post('change-password', 'AuthController@changePassword');
        Route::get('profile', 'AuthController@profile');
    });

    Route::group(['prefix' => 'dashboard', 'middleware' => 'api'], function () {
        Route::get('index', 'DashboardController@index');
        Route::get('grafik', 'DashboardController@grafik');
        Route::get('get-kartu-dyeing', 'DashboardController@kartuDyeing');
        Route::get('get-kartu-printing', 'DashboardController@kartuPrinting');
    });

     // Master Defect Routes (Authenticated)
    Route::group(['prefix' => 'master-defect', 'middleware' => 'api'], function () {
        Route::get('get-master-defect', 'MstKodeDefectController@index');
    });

    Route::group(['prefix' => 'customer', 'middleware' => 'api'], function () {
        Route::get('get-customer', 'CustomerController@index');
    });

    // Defect Inspecting Item Routes (Authenticated)
    Route::group(['prefix' => 'defect-item', 'middleware' => 'api'], function () {
        Route::get('get-defect-item', 'DefectItemController@countByNoUrut');
        Route::get('get-defect-tgl-kirim', 'DefectItemController@getDefectWithTglKirim');

    });

    // Work Order (WO) Routes (Authenticated)
    Route::group(['prefix' => 'wo', 'middleware' => 'api'], function () {
        Route::get('get-wo', 'KartuProsesDyeingController@getWo');
        Route::get('search-wo', 'KartuProsesDyeingController@searchGetWo');
    });

    // Kartu Proses Dyeing Routes (Authenticated)
    Route::group(['prefix' => 'kartu-proses-dyeing', 'middleware' => 'api' ], function () {
        Route::get('get-kartu-proses-dyeing/{id}', 'KartuProsesDyeingController@getKartuProsesDyeingById');
    });

    // Kartu Proses Printing Routes (Authenticated)
    Route::group(['prefix' => 'kartu-proses-printing', 'middleware' => 'api'], function () {
        Route::get('get-kartu-proses-printing/{id}', 'KartuProsesDyeingController@getKartuProsesPrintingById');
    });

    // Inspecting Routes (Authenticated)
    Route::group(['prefix' => 'inspecting', 'middleware' => 'api'], function () {
        Route::get('get-inspecting/{id}', 'InspectingController@index');
        Route::get('get-inspecting-mkl-bj/{id}', 'InspectingController@indexMklbj');
        Route::put('get-inspecting/update/{id}', 'InspectingController@update');
        Route::put('kalkukasi/{id}', 'InspectingController@kalkulasi');
        Route::put('kalkukasi/mkl-bj/{id}', 'InspectingController@kalkulasiMklbj');
        Route::put('update-inspecting/{id}', 'InspectingController@updateInspecting');
        Route::put('update-inspecting-mklbj/{id}', 'InspectingController@updateInspectingMklbj');
        Route::put('update-inspecting-mklbj/item/{id}', 'InspectingController@updateInspectingMklbjItem');
        Route::delete('delete-inspecting-item/{id}', 'InspectingController@destroy');
    });

    // Inspecting Store & Update Routes (Authenticated)
    Route::group(['prefix' => 'inspecting', 'middleware' => 'api'], function () {
        Route::post('store-inspecting', 'DashboardController@store');
        Route::post('store-mkl-bj-inspecting', 'DashboardController@storeMklbj');
        Route::put('inspecting-item/update/{id}', 'DashboardController@updateInspectingItem');
        Route::post('store-printing-inspecting', 'DashboardController@storePrinting');
        Route::post('add-inspect-result', 'DashboardController@storeItem');
        Route::post('add-inspect-mklbj-result', 'DashboardController@storeItemMklbj');
    });

    Route::group(['prefix' => 'greige', 'middleware' => 'api'], function () {
        Route::get('rekap-stock-greige', 'GreigeController@rekapStockGreige');
    });

    Route::group(['prefix' => 'verpacking', 'middleware' => 'api'], function () {
        Route::get('daftar-pengiriman-produksi', 'VerpackingController@daftarPengirimanProduksi');
        Route::get('daftar-pengiriman-produksi-mklbj', 'VerpackingController@daftarPengirimanProduksiMklbj');
        Route::get('rekap-pengiriman-produksi', 'VerpackingController@rekapPengirimanProduksi');
        Route::get('analisa-pengiriman-produksi', 'VerpackingController@analisaPengirimanProduksi');
        Route::get('analisa-pengiriman-produksi-mklbj', 'VerpackingController@analisaPengirimanProduksiMklbj');
        Route::get('rekap-pengiriman-harian', 'VerpackingController@rekapPengirimanHarian');
    });

    Route::group(['prefix' => 'marketing', 'middleware' => 'api'], function () {
        Route::get('outstanding', 'MarketingController@outstanding');
    });


});