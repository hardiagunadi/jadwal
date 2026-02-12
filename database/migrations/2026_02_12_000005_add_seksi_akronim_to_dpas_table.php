<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dpas', function (Blueprint $table) {
            $table->string('seksi_akronim', 50)->nullable()->after('catatan')
                ->comment('Akronim seksi/jabatan pemilik DPA, contoh: ekbang, umum, pmd');
        });
    }

    public function down(): void
    {
        Schema::table('dpas', function (Blueprint $table) {
            $table->dropColumn('seksi_akronim');
        });
    }
};
