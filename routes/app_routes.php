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

Route::get('/test_route', [/*action*/'uses' => 'TestController@TestRoute', /*middleware*/'middleware' => ['throttle:60,1'], /*done*/]);
Route::post('/test_route', [/*action*/'uses' => 'TestController@TestRoute', /*middleware*/'middleware' => ['throttle:60,1', 'vpt:passport1'], /*done*/]);

/*
User
*/
Route::get('/register', 'Auth\ApiAuthController@register');
Route::post('/register', 'Auth\ApiAuthController@register');

Route::get('/login', 'Auth\ApiAuthController@login');
Route::post('/login', 'Auth\ApiAuthController@login');

Route::get('/refresh', 'Auth\ApiAuthController@refresh');
Route::post('/refresh', 'Auth\ApiAuthController@refresh');

Route::get('/loginex', 'Auth\ApiAuthController@loginex')->middleware('vpt:passport1');
Route::post('/loginex', 'Auth\ApiAuthController@loginex')->middleware('vpt:passport1');

Route::get('/logout', 'Auth\ApiAuthController@logout')->middleware('vpt:passport1');
Route::post('/logout', 'Auth\ApiAuthController@logout')->middleware('vpt:passport1');

Route::get('/behave', 'Auth\ApiAuthController@behave')->middleware('vpt:passport1');
Route::post('/behave', 'Auth\ApiAuthController@behave')->middleware('vpt:passport1');

Route::get('/behave2', 'Auth\ApiAuthController@behave2')->middleware('auth:passport1');  //需要使用app/Http/Kernel.php中'auth'这个中间件，App\Http\Middleware\Authenticate
Route::post('/behave2', 'Auth\ApiAuthController@behave2')->middleware('auth:passport1');

Route::get('/behaveex', 'Auth\ApiAuthController@behaveex')->middleware('vptex:passport1');
Route::post('/behaveex', 'Auth\ApiAuthController@behaveex')->middleware('vptex:passport1');


/*
User2
*/
Route::get('/register_user2', 'Auth\ApiAuthController2@register');
Route::post('/register_user2', 'Auth\ApiAuthController2@register');

Route::get('/login_user2', 'Auth\ApiAuthController2@login');
Route::post('/login_user2', 'Auth\ApiAuthController2@login');

Route::get('/behave_user2', 'Auth\ApiAuthController2@behave')->middleware('vpt:passport2');
Route::post('/behave_user2', 'Auth\ApiAuthController2@behave')->middleware('vpt:passport2');
