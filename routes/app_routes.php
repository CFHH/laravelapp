<?php

use Illuminate\Http\Request;

Route::get('/test', function (Request $request)
{
    echo "get app test";
});

Route::post('/test', function (Request $request)
{
    echo "post app test";
});

Route::get('/testvft', function (Request $request)
{
    echo "get app test vft";
})->middleware('vpt');

Route::get('/register', 'Auth\ApiAuthController@register');

Route::post('/register', 'Auth\ApiAuthController@register');

Route::get('/login', 'Auth\ApiAuthController@login');

Route::post('/login', 'Auth\ApiAuthController@login');

Route::get('/logout', 'Auth\ApiAuthController@logout');

Route::post('/logout', 'Auth\ApiAuthController@logout');

Route::get('/behave', 'Auth\ApiAuthController@behave')->middleware('vpt');

Route::post('/behave', 'Auth\ApiAuthController@behave')->middleware('vpt');

Route::post('/behave2', 'Auth\ApiAuthController@behave')->middleware('auth:api');
