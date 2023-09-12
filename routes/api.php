<?php

use App\Http\Controllers\Api\Gestor\CategoryController;
use App\Http\Controllers\Api\Gestor\CompanyController;
use App\Http\Controllers\Api\Gestor\ConfigurationController;
use App\Http\Controllers\Api\Gestor\ProductController;
use App\Http\Controllers\Cardapio\CardapioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [CompanyController::class, 'login']);
Route::post('/register', [CompanyController::class, 'store']);
Route::post('/verify_email', [CompanyController::class, 'verifyIfEmailExist']);

Route::get('menu/{cardapioUrl}', [CardapioController::class, 'getCardapio']);
Route::get('menu/product/{id}', [CardapioController::class, 'detailProduct']);

Route::middleware('verifyTokenJWT')->group(function () {

    Route::get('/company', [CompanyController::class, 'show']);
    
    Route::post('/category/verify_order', [CategoryController::class, 'verifyOrder']);
    Route::apiResource('/configuration', ConfigurationController::class)->only(['index', 'store', 'update']);
    Route::post('/configuration/verify_url', [ConfigurationController::class, 'verifyIfUrlExist']);
    Route::apiResources([
        'category' => CategoryController::class,
        'product' => ProductController::class
    ]);

});