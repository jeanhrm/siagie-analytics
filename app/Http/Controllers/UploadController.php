<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function index() { return view('uploads.index'); }
    public function store(Request $request) {}
    public function destroy($id) {}
}