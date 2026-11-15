<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/register', Register::class)->name('register');
    Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');
