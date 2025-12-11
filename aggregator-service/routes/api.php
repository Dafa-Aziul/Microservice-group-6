<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserProductAggregationController;


// ------------------------------
//  PUBLIC AUTH ROUTES
// ------------------------------
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);


// ------------------------------
//  PROTECTED ROUTES (TOKEN WAJIB)
// ------------------------------
Route::middleware(['auth.token'])->group(function () {

    // --------------------------
    // USER AUTH SESSION
    // --------------------------
    Route::get('/auth/me',     [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);


    // --------------------------
    // USER MANAGEMENT
    // --------------------------
    Route::get('/users',        [UserController::class, 'index']);
    Route::get('/users/{id}',   [UserController::class, 'show']);
    Route::put('/users/{id}',   [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);


    // --------------------------
    // PRODUCT ROUTES
    // --------------------------
    Route::get('/products',       [ProductController::class, 'index']);
    Route::get('/products/{id}',  [ProductController::class, 'show']);
    Route::post('/products',       [ProductController::class, 'store']);
    Route::put('/products/{id}',  [ProductController::class, 'update']);
    Route::delete('/products/{id}',  [ProductController::class, 'destroy']);


    // --------------------------
    // CATEGORY ROUTES
    // --------------------------
    Route::get('/categories',      [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::post('/categories',      [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);


    Route::get(
        '/user-with-products/{userId}',
        [UserProductAggregationController::class, 'getUserWithProducts']
    );
    Route::get(
        '/my-products',
        [UserProductAggregationController::class, 'myProducts']
    );
});
