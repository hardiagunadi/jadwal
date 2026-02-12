<?php

namespace App\Http\Controllers;

use App\Models\Spj;
use Illuminate\View\View;

class SpjController extends Controller
{
    public function cetak(Spj $spj): View
    {
        $spj->load(['personil', 'dpaRincianBelanja.subKegiatan.dpa']);

        return view('spj.kwitansi', compact('spj'));
    }
}
