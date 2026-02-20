<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_gateway_settings', function (Blueprint $table) {
            $table->unsignedInteger('min_delay_ms')->default(3000)->after('finish_whitelist');
            $table->unsignedInteger('max_delay_ms')->default(7000)->after('min_delay_ms');
            $table->unsignedInteger('rate_limit_per_hour')->default(50)->after('max_delay_ms');
            $table->unsignedInteger('rate_limit_per_day')->default(300)->after('rate_limit_per_hour');
            $table->unsignedInteger('circuit_breaker_threshold')->default(3)->after('rate_limit_per_day');
            $table->unsignedInteger('circuit_breaker_cooldown_seconds')->default(900)->after('circuit_breaker_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('wa_gateway_settings', function (Blueprint $table) {
            $table->dropColumn([
                'min_delay_ms',
                'max_delay_ms',
                'rate_limit_per_hour',
                'rate_limit_per_day',
                'circuit_breaker_threshold',
                'circuit_breaker_cooldown_seconds',
            ]);
        });
    }
};
