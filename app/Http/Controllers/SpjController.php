<?php

namespace App\Http\Controllers;

use App\Models\Personil;
use App\Models\Spj;
use Illuminate\View\View;

class SpjController extends Controller
{
    public function cetak(Spj $spj): View
    {
        $spj->load(['personil', 'dpaRincianBelanja.subKegiatan.dpa', 'penerimas']);
        $spj->loadCount('penerimas');

        $pa  = Personil::whereIn('jabatan_lainnya', ['PA', 'KPA'])->first();
        $bendahara = Personil::where('jabatan_lainnya', 'Bendahara Pengeluaran')->first();

        return view('spj.kwitansi', compact('spj', 'pa', 'bendahara'));
    }
}
