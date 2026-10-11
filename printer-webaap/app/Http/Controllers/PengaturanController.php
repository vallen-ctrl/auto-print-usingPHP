<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PengaturanController extends Controller
{
    public function get(){
        return view("Dashboard.selection.pengaturan");
    }
}
