<?php
// app/Http/Controllers/DropdownController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DropdownController extends Controller
{
    /**
     * Menampilkan halaman dropdown
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('history');
    }
}