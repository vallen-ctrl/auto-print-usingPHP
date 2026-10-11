<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function get(){
        return view("Dashboard.selection.order");
    }

    public function detail(string $id){
        return view("Dashboard.selection.order-detail", compact("id"));
    }
}
