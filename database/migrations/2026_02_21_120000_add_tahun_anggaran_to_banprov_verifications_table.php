<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banprov_verifications', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun_anggaran')->nullable()->after('tahap');
            $table->index('tahun_anggaran');
        });
    }

    public function down(): void
    {
        Schema::table('banprov_verifications', function (Blueprint $table) {
            $table->dropIndex(['tahun_anggaran']);
            $table->dropColumn('tahun_anggaran');
        });
    }
};
