<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImprovementPlanController extends Controller
{
    public function index() { return view('plans.index'); }
    public function generate($report) {}
    public function show($plan) {}
    public function update(Request $request, $plan) {}
}