<?php

namespace App\Http\Controllers;

use App\Exports\GajJmlPegExport;
use App\Exports\GajPemindahbukuanExport;
use App\Exports\GajPerbedaanExport;
use App\Exports\GajRincianExport;
use App\Models\Gaj;

class GajExportController extends Controller
{
    public function perbedaan(Gaj $gaj)
    {
        return app(GajPerbedaanExport::class)->download($gaj);
    }

    public function rincian(Gaj $gaj)
    {
        return app(GajRincianExport::class)->download($gaj);
    }

    public function jmlPeg(Gaj $gaj)
    {
        return app(GajJmlPegExport::class)->download($gaj);
    }

    public function pemindahbukuan(Gaj $gaj)
    {
        return app(GajPemindahbukuanExport::class)->download($gaj);
    }
}
