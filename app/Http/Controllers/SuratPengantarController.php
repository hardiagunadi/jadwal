<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SuratPengantarController extends Controller
{
    public function print(Request $request)
    {
        $key = $request->get('key');

        if (! $key) {
            abort(404, 'Key tidak ditemukan.');
        }

        $data = session('surat_pengantar_' . $key);

        if (! $data) {
            abort(404, 'Data surat pengantar tidak ditemukan atau sudah kadaluarsa.');
        }

        session()->forget('surat_pengantar_' . $key);

        return view('surat-pengantar.print', $data);
    }
}
