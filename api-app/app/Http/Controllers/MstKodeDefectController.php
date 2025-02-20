<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MstKodeDefect;
use Illuminate\Support\Facades\Validator;

class MstKodeDefectController extends Controller
{
    public function index()
    {
        $mstKodeDefects = MstKodeDefect::all();

        return response()->json([
            'success' => true,
            'data' => $mstKodeDefects
        ]);
    }
}
