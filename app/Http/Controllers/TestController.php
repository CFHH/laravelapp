<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function TestRoute(Request $request)
    {
        echo 'TestController@TestRoute </br>';
    }

    public function TestRoute2(Request $request)
    {
        echo 'TestController@TestRoute2 </br>';
    }
}
