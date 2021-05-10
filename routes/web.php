<?php

use Illuminate\Support\Facades\Route;
use \App\Models\User;

Route::get('/', function () {

    return view('welcome');
});

Route::get('/ip-me', function() {
    return getHostByName(getHostName());
});

Route::get('/test', function() {

return phpinfo();

});
