<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FilesController extends Controller
{
    public function get(){
        return view("Dashboard.selection.files");
    }
}
