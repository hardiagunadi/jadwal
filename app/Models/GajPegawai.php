<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GajPegawai extends Model
{
    protected $fillable = [
        'gaj_id',
        'no_urut',
        'nama',
        'tanggal_lahir',
        'nip',
        'npwp',
        'status_kawin',
        'golongan',
        // Penghasilan
        'gaji_pokok',
        'tunj_eselon',
        'tunj_terpencil',
        'tunj_bpjskes_4',
        'tunj_istri',
        'tunj_fung_umum',
        'tkd',
        'tunj_jkk',
        'tunj_anak',
        'tunj_fungsional',
        'tunj_beras',
        'tunj_jkm',
        'jumlah_penghasilan',
        'tunj_khusus',
        'tunj_pajak',
        'tapera_pk_pgh',
        'pembulatan',
        'jml_kotor',
        // Potongan
        'pot_pajak',
        'tapera_pk_pot',
        'pot_bpjs_kes',
        'tapera_peg',
        'pot_iwp_1',
        'hutang_lain2',
        'pot_iwp_8',
        'bulog',
        'pot_taperum',
        'sewa_rumah',
        'pot_jkk',
        'jml_potongan',
        'pot_jkm',
        'jumlah_bersih',
        'no_rekening',
    ];

    protected $casts = [
        'gaji_pokok'         => 'integer',
        'tunj_eselon'        => 'integer',
        'tunj_terpencil'     => 'integer',
        'tunj_bpjskes_4'     => 'integer',
        'tunj_istri'         => 'integer',
        'tunj_fung_umum'     => 'integer',
        'tkd'                => 'integer',
        'tunj_jkk'           => 'integer',
        'tunj_anak'          => 'integer',
        'tunj_fungsional'    => 'integer',
        'tunj_beras'         => 'integer',
        'tunj_jkm'           => 'integer',
        'jumlah_penghasilan' => 'integer',
        'tunj_khusus'        => 'integer',
        'tunj_pajak'         => 'integer',
        'tapera_pk_pgh'      => 'integer',
        'pembulatan'         => 'integer',
        'jml_kotor'          => 'integer',
        'pot_pajak'          => 'integer',
        'tapera_pk_pot'      => 'integer',
        'pot_bpjs_kes'       => 'integer',
        'tapera_peg'         => 'integer',
        'pot_iwp_1'          => 'integer',
        'hutang_lain2'       => 'integer',
        'pot_iwp_8'          => 'integer',
        'bulog'              => 'integer',
        'pot_taperum'        => 'integer',
        'sewa_rumah'         => 'integer',
        'pot_jkk'            => 'integer',
        'jml_potongan'       => 'integer',
        'pot_jkm'            => 'integer',
        'jumlah_bersih'      => 'integer',
    ];

    public function gaj(): BelongsTo
    {
        return $this->belongsTo(Gaj::class, 'gaj_id');
    }
}
