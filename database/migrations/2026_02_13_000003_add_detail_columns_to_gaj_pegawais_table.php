<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gaj_pegawais', function (Blueprint $table) {
            // Penghasilan
            $table->unsignedInteger('tunj_eselon')->default(0)->after('gaji_pokok');
            $table->unsignedInteger('tunj_terpencil')->default(0)->after('tunj_eselon');
            $table->unsignedInteger('tunj_bpjskes_4')->default(0)->after('tunj_terpencil')->comment('TUNJ BPJSKES 4%');
            $table->unsignedInteger('tunj_istri')->default(0)->after('tunj_bpjskes_4')->comment('TUNJ. ISTRI/SMI');
            $table->unsignedInteger('tunj_fung_umum')->default(0)->after('tunj_istri');
            $table->unsignedInteger('tkd')->default(0)->after('tunj_fung_umum')->comment('T K D');
            $table->unsignedInteger('tunj_jkk')->default(0)->after('tkd');
            $table->unsignedInteger('tunj_anak')->default(0)->after('tunj_jkk');
            $table->unsignedInteger('tunj_fungsional')->default(0)->after('tunj_anak');
            $table->unsignedInteger('tunj_beras')->default(0)->after('tunj_fungsional');
            $table->unsignedInteger('tunj_jkm')->default(0)->after('tunj_beras');
            $table->unsignedInteger('tunj_khusus')->default(0)->after('tunj_jkm');
            $table->unsignedInteger('tunj_pajak')->default(0)->after('tunj_khusus');
            $table->unsignedInteger('tapera_pk_pgh')->default(0)->after('tunj_pajak')->comment('TAPERA PK sisi penghasilan');
            $table->unsignedInteger('pembulatan')->default(0)->after('tapera_pk_pgh');

            // Potongan
            $table->unsignedInteger('pot_pajak')->default(0)->after('pembulatan');
            $table->unsignedInteger('tapera_pk_pot')->default(0)->after('pot_pajak')->comment('TAPERA PK sisi potongan');
            $table->unsignedInteger('tapera_peg')->default(0)->after('tapera_pk_pot');
            $table->unsignedInteger('pot_iwp_1')->default(0)->after('tapera_peg')->comment('POT IWP 1%');
            $table->unsignedInteger('hutang_lain2')->default(0)->after('pot_iwp_1')->comment('HUTANG/LAIN2');
            $table->unsignedInteger('pot_iwp_8')->default(0)->after('hutang_lain2')->comment('POT IWP 8%');
            $table->unsignedInteger('bulog')->default(0)->after('pot_iwp_8');
            $table->unsignedInteger('pot_taperum')->default(0)->after('bulog');
            $table->unsignedInteger('sewa_rumah')->default(0)->after('pot_taperum');
            $table->unsignedInteger('pot_jkk')->default(0)->after('sewa_rumah');
            $table->unsignedInteger('pot_jkm')->default(0)->after('pot_jkk');
        });
    }

    public function down(): void
    {
        Schema::table('gaj_pegawais', function (Blueprint $table) {
            $table->dropColumn([
                'tunj_eselon', 'tunj_terpencil', 'tunj_bpjskes_4',
                'tunj_istri', 'tunj_fung_umum', 'tkd', 'tunj_jkk',
                'tunj_anak', 'tunj_fungsional', 'tunj_beras', 'tunj_jkm',
                'tunj_khusus', 'tunj_pajak', 'tapera_pk_pgh', 'pembulatan',
                'pot_pajak', 'tapera_pk_pot', 'tapera_peg', 'pot_iwp_1',
                'hutang_lain2', 'pot_iwp_8', 'bulog', 'pot_taperum',
                'sewa_rumah', 'pot_jkk', 'pot_jkm',
            ]);
        });
    }
};
