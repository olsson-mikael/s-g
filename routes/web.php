<?php

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


Route::get('login', function () {
	return Redirect::to('/');
});
// Authentication Routes...
Route::get('/', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('/', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

// Registration Routes...
Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
Route::post('register', 'Auth\RegisterController@register');

// Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset');


Route::get('/home', 'HomeController@index')->middleware('auth')->name('home');

Route::get('accounts', 'TransactionController@index')->middleware('auth')->name('accounts');

Route::get('details/partner/{id}', 'TransactionController@getDetails')->name('accounts_details');

Route::get('partners', 'PartnerController@index')->middleware('auth')->name('partners');

Route::get('payout', 'PayoutController@index')->middleware('auth')->name('payout');

Route::post('payout/store', 'PayoutController@store')->middleware('auth')->name('payout.store');

Route::get('/faq', function () {
	return view('faq');
})->middleware('auth')->name('faq');;
