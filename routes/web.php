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

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Auth::routes();

Route::get('/home', 'App\Http\Controllers\HomeController@index')->name('home');

Route::group(['middleware' => 'auth'], function () {
	Route::resource('user', 'App\Http\Controllers\UserController', ['except' => ['show']]);
	Route::get('profile', ['as' => 'profile.edit', 'uses' => 'App\Http\Controllers\ProfileController@edit']);
	Route::put('profile', ['as' => 'profile.update', 'uses' => 'App\Http\Controllers\ProfileController@update']);
	Route::get('upgrade', function () {return view('pages.upgrade');})->name('upgrade'); 
	 Route::get('map', function () {return view('pages.maps');})->name('map');
	 Route::get('icons', function () {return view('pages.icons');})->name('icons'); 
	 Route::get('table-list', function () {return view('pages.tables');})->name('table');
	Route::put('profile/password', ['as' => 'profile.password', 'uses' => 'App\Http\Controllers\ProfileController@password']);
});

//RUTA PARA TESTEAR VISTAS DE CORREOS
Route::get('testmail', function () {
	return view('mails.viewMailUserRegistered', ['name'=>'Administrador','email'=>'correo@villatours.com','password'=>'123456']);
});

//ROUTES USUARIOS
Route::get('/user', [App\Http\Controllers\UserController::class, 'index'])->name('user.index');//Ver listado
Route::post('/user', [App\Http\Controllers\UserController::class, 'index']);//Ver listado recibiendo parámetros

Route::get('/user/create', [App\Http\Controllers\UserController::class, 'create'])->name('user.create');//Ver formulario registro
Route::post('/user/create', [App\Http\Controllers\UserController::class, 'store'])->name('user.store');//Enviar formulario registro

Route::get('/user/{user}/edit', [App\Http\Controllers\UserController::class, 'edit']);//Ver formulario edición
Route::put('/user/edit/{user}', [App\Http\Controllers\UserController::class, 'update']);//Enviar formulario edición

Route::delete('/user/{user}', [App\Http\Controllers\UserController::class, 'destroy']);//Enviar formulario edición

//ROUTES CONCILIACIONES
Route::get('/conciliation', [App\Http\Controllers\ConciliationController::class, 'index'])->name('conciliation.index');//Ver listado
Route::post('/conciliation', [App\Http\Controllers\ConciliationController::class, 'index']);//Ver listado recibiendo parámetros

Route::get('/conciliation/create', [App\Http\Controllers\ConciliationController::class, 'create'])->name('conciliation.create');//Ver formulario registro
Route::post('/conciliation/analysis', [App\Http\Controllers\ConciliationController::class, 'store'])->name('conciliation.analysis');//Enviar formulario registro

Route::get('/conciliation/{conciliation}/show', [App\Http\Controllers\ConciliationController::class, 'show']);//Ver formulario edición
Route::put('/conciliation/edit/{conciliation}', [App\Http\Controllers\ConciliationController::class, 'update']);//Enviar formulario edición

Route::delete('/conciliation/{conciliation}', [App\Http\Controllers\ConciliationController::class, 'destroy']);//Enviar formulario edición

//ROUTES ROLES
Route::get('/role', [App\Http\Controllers\RoleController::class, 'index'])->name('role.index');//Ver listado
Route::post('/role', [App\Http\Controllers\RoleController::class, 'index']);//Ver listado recibiendo parámetros

Route::get('/role/create', [App\Http\Controllers\RoleController::class, 'create'])->name('role.create');//Ver formulario registro
Route::post('/role/create', [App\Http\Controllers\RoleController::class, 'store'])->name('role.store');//Enviar formulario registro

Route::get('/role/{role}/edit', [App\Http\Controllers\RoleController::class, 'edit']);//Ver formulario edición
Route::put('/role/edit/{role}', [App\Http\Controllers\RoleController::class, 'update']);//Enviar formulario edición

Route::delete('/role/{role}', [App\Http\Controllers\RoleController::class, 'destroy']);//Enviar formulario edición

//ROUTES SUCURSALES
Route::get('/subsidiary', [App\Http\Controllers\SubsidiaryController::class, 'index'])->name('subsidiary.index');//Ver listado
Route::post('/subsidiary', [App\Http\Controllers\SubsidiaryController::class, 'index']);//Ver listado recibiendo parámetros

Route::get('/subsidiary/create', [App\Http\Controllers\SubsidiaryController::class, 'create'])->name('subsidiary.create');//Ver formulario registro
Route::post('/subsidiary/create', [App\Http\Controllers\SubsidiaryController::class, 'store'])->name('subsidiary.store');//Enviar formulario registro

Route::get('/subsidiary/{subsidiary}/edit', [App\Http\Controllers\SubsidiaryController::class, 'edit']);//Ver formulario edición
Route::put('/subsidiary/edit/{subsidiary}', [App\Http\Controllers\SubsidiaryController::class, 'update']);//Enviar formulario edición

Route::delete('/subsidiary/{subsidiary}', [App\Http\Controllers\SubsidiaryController::class, 'destroy']);//Enviar formulario edición

//ROUTES MENUS
Route::get('/menu', [App\Http\Controllers\MenuController::class, 'index'])->name('menu.index');//Ver listado
Route::post('/menu', [App\Http\Controllers\MenuController::class, 'index']);//Ver listado recibiendo parámetros

Route::get('/menu/create', [App\Http\Controllers\MenuController::class, 'create'])->name('menu.create');//Ver formulario registro
Route::post('/menu/create', [App\Http\Controllers\MenuController::class, 'store'])->name('menu.store');//Enviar formulario registro

Route::get('/menu/{menu}/edit', [App\Http\Controllers\MenuController::class, 'edit']);//Ver formulario edición
Route::put('/menu/edit/{menu}', [App\Http\Controllers\MenuController::class, 'update']);//Enviar formulario edición

Route::delete('/menu/{menu}', [App\Http\Controllers\MenuController::class, 'destroy']);//Enviar formulario edición

//ROUTES IATAS
Route::get('/iata', [App\Http\Controllers\DocumentController::class, 'indexIata'])->name('iata.index');//Vista de asignación de IATAS a periodos

Route::post('/iata/validatePeriod', [App\Http\Controllers\DocumentController::class, 'validatePeriodIata'])->name('iata.validate.period');//Enviar formulario registro
Route::post('/iata/storePeriod', [App\Http\Controllers\DocumentController::class, 'storePeriodIata'])->name('iata.store.period');//Enviar formulario registro

Route::get('/iata/{url}/downloadFromPeriod', [App\Http\Controllers\DocumentController::class, 'downloadIataFromPeriod']);//Ver formulario edición

