<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('sorting-center-login');
});

Route::get('/register', function () {
    return view('sorting-center-registration');
});

Route::get('/sorting-center/register', function () {
    return view('sorting-center-registration');
});

Route::get('/login', function () {
    return view('sorting-center-login');
});

Route::get('/sorting-center/login', function () {
    return view('sorting-center-login');
});

Route::get('/sorting-center/order-management', function () {
    return view('sorting-center.order-management');
});

Route::get('/sorting-center/delivery-management', function () {
    return view('sorting-center.delivery-management');
});

Route::get('/sorting-center/rider-assignment', function () {
    return view('sorting-center.rider-assignment');
});

Route::get('/sorting-center/delivery-monitoring', function () {
    return view('sorting-center.delivery-monitoring');
});

Route::get('/sorting-center/dashboard', function () {
    return view('sorting-center.dashboard');
});

Route::get('/sorting-center/delivery-reports', function () {
    return view('sorting-center.delivery-reports');
});

Route::get('/sorting-center/messages', function () {
    return view('sorting-center.messages');
});

Route::get('/sorting-center/pickup-requests', function () {
    return view('sorting-center.pickup-requests');
});

