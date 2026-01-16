<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RepetitorController extends Controller
{
    /**
     * Интерактивная визуализация: Угол между векторами
     */
    public function vectorAngle()
    {
        return view('repetitor.vector');
    }
}
