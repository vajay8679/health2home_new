<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/privacy_policy', function() {
    return view('privacy_policy');
});

Route::get('/paywithpaypal/{amount}', array('as' => 'paywithpaypal','uses' => 'App\Http\Controllers\PaypalController@payWithPaypal',));
Route::post('/paypal', array('as' => 'paypal','uses' => 'App\Http\Controllers\PaypalController@postPaymentWithpaypal',));
Route::get('/paypal', array('as' => 'status','uses' => 'App\Http\Controllers\PaypalController@getPaymentStatus',));
Route::get('/paypal_success',  "App\Http\Controllers\PaypalController@success");
Route::get('/paypal_failed',  "App\Http\Controllers\PaypalController@failed");