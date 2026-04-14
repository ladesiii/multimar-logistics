<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

Route::get('/editarperfil', function () {
    return view('editar-perfil');
});

Route::redirect('/admin', '/dashboard');
