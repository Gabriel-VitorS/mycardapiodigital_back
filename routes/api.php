<?php

use App\Http\Controllers\Api\Gestor\CategoryController;
use App\Http\Controllers\Api\Gestor\CompanyController;
use App\Http\Controllers\Api\Gestor\ConfigurationController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [CompanyController::class, 'login']);
Route::post('/register', [CompanyController::class, 'store']);


Route::middleware('verifyTokenJWT')->group(function () {

Route::post('/category/verify_order', [CategoryController::class, 'verifyOrder']);

Route::apiResources([
    // 'configuration' => ConfigurationController::class,
    'category' => CategoryController::class,
]);

});