<?php

use App\Http\Controllers\FactoryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::view('/login','auth.login')->middleware('guest')->name('login');
Route::post('/login',[\App\Http\Controllers\AuthController::class,'login'])->middleware(['guest','throttle:5,1'])->name('login.submit');
Route::post('/logout',[\App\Http\Controllers\AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function(){
    Route::get('/documents/orders/{id}/invoice',[DocumentController::class,'invoice'])->middleware('role:Administrator / Owner,Manager,Accounts Staff')->name('documents.invoice');
    Route::get('/documents/orders/{id}/invoice.pdf',[DocumentController::class,'invoicePdf'])->middleware('role:Administrator / Owner,Manager,Accounts Staff')->name('documents.invoice-pdf');
    Route::get('/documents/payments/{type}/{paymentId}/receipt',[DocumentController::class,'receipt'])->middleware('role:Administrator / Owner,Manager,Accounts Staff')->name('documents.receipt');
    Route::get('/documents/payments/{type}/{paymentId}/receipt.pdf',[DocumentController::class,'receiptPdf'])->middleware('role:Administrator / Owner,Manager,Accounts Staff')->name('documents.receipt-pdf');
    Route::get('/documents/customers/{id}/statement',[DocumentController::class,'customerStatement'])->middleware('role:Administrator / Owner,Manager,Accounts Staff')->name('documents.customer-statement');
    Route::get('/documents/customers/{id}/statement.pdf',[DocumentController::class,'customerStatementPdf'])->middleware('role:Administrator / Owner,Manager,Accounts Staff')->name('documents.customer-statement-pdf');
    Route::get('/documents/suppliers/{id}/statement',[DocumentController::class,'supplierStatement'])->middleware('role:Administrator / Owner,Manager,Accounts Staff')->name('documents.supplier-statement');
    Route::get('/documents/suppliers/{id}/statement.pdf',[DocumentController::class,'supplierStatementPdf'])->middleware('role:Administrator / Owner,Manager,Accounts Staff')->name('documents.supplier-statement-pdf');
    Route::get('/', [FactoryController::class, 'index']);
    Route::post('/parties/{type}', [FactoryController::class, 'saveParty'])->middleware('role:Administrator / Owner,Manager');
    Route::post('/parties/{type}/{id}/archive',[FactoryController::class,'toggleParty'])->middleware('role:Administrator / Owner,Manager');
    Route::post('/orders', [FactoryController::class, 'saveOrder'])->middleware('role:Administrator / Owner,Manager');
    Route::post('/orders/{id}/edit',[FactoryController::class,'updateOrderDetails'])->middleware('role:Administrator / Owner,Manager');
    Route::post('/orders/{id}', [FactoryController::class, 'updateOrder'])->middleware('role:Administrator / Owner,Manager,Operational Staff');
    Route::post('/payments/{type}', [FactoryController::class, 'payment'])->middleware('role:Administrator / Owner,Manager,Accounts Staff');
    Route::post('/purchases', [FactoryController::class, 'savePurchase'])->middleware('role:Administrator / Owner,Manager,Accounts Staff');
    Route::post('/supplier-payments', [FactoryController::class, 'supplierPayment'])->middleware('role:Administrator / Owner,Manager,Accounts Staff');
    Route::post('/payments/{type}/{id}/void',[FactoryController::class,'voidPayment'])->middleware('role:Administrator / Owner,Accounts Staff');
    Route::post('/payments/{type}/{id}/allocations',[FactoryController::class,'allocatePayment'])->middleware('role:Administrator / Owner,Manager,Accounts Staff');
    Route::post('/settings',[FactoryController::class,'saveSetting'])->middleware('role:Administrator / Owner');
    Route::post('/users',[FactoryController::class,'saveUser'])->middleware('role:Administrator / Owner');
    Route::post('/imports/{type}/preview',[ImportController::class,'preview'])->middleware('role:Administrator / Owner');
    Route::post('/imports/{type}/commit',[ImportController::class,'commit'])->middleware('role:Administrator / Owner');
});
