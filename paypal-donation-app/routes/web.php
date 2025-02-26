<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\DonationController;

Route::get('/donate', [DonationController::class, 'showDonationForm'])->name('donation.form');
Route::post('/donate', [DonationController::class, 'processDonation'])->name('donation.process');
Route::get('/donation/success', [DonationController::class, 'donationSuccess'])->name('donation.success');
Route::get('/donation/cancel', [DonationController::class, 'donationCancel'])->name('donation.cancel');