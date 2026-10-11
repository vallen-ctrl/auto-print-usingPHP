<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KertasController extends Controller
{
    public function get(){
        return view("Dashboard.selection.kertas");
    }
}
