<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    public function index() { return view('analysis.index'); }
    public function generate($upload) {}
    public function show($report) {}
}