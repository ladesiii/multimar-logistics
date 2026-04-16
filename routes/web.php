<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'login')->name('login');
Route::view('/dashboard', 'dashboard')->name('dashboard');
Route::view('/editar-perfil', 'editar-perfil')->name('profile.edit');
