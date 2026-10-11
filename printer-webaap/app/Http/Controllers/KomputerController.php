<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KomputerController extends Controller
{
    public function get(){
        return view("Dashboard.selection.komputer");
    }
}
