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
    });

    Route::group(['prefix' => 'dashboard', 'middleware' => 'api'], function () {
        Route::get('index', 'DashboardController@index');
        Route::get('get-kartu-dyeing', 'DashboardController@kartuDyeing');
        Route::get('get-kartu-printing', 'DashboardController@kartuPrinting');
    });

     // Master Defect Routes (Authenticated)
    Route::group(['prefix' => 'master-defect', 'middleware' => 'api'], function () {
        Route::get('get-master-defect', 'MstKodeDefectController@index');
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
});
