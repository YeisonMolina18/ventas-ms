<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SaleController;

Route::apiResource('ventas', SaleController::class)->parameters([
    'ventas' => 'sale'
]);
