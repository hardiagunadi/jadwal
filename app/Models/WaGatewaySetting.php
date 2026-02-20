<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class WaGatewaySetting extends Model
{
    protected $fillable = [
        'base_url',
        'token',
        'key',
        'secret_key',
        'provider',
        'finish_whitelist',
        'min_delay_ms',
        'max_delay_ms',
        'rate_limit_per_hour',
        'rate_limit_per_day',
        'circuit_breaker_threshold',
        'circuit_breaker_cooldown_seconds',
    ];

    public static function current(): self
    {
        if (! Schema::hasTable('wa_gateway_settings')) {
            return new self(static::defaults());
        }

        return static::firstOrCreate(['id' => 1], static::defaults());
    }

    public static function defaults(): array
    {
        return [
            'base_url' => config('wa_gateway.base_url'),
            'token' => config('wa_gateway.token'),
            'key' => config('wa_gateway.key'),
            'secret_key' => config('wa_gateway.secret_key'),
            'provider' => config('wa_gateway.provider', 'wa-gateway'),
            'finish_whitelist' => config('wa_gateway.finish_whitelist'),
            'min_delay_ms' => 3000,
            'max_delay_ms' => 7000,
            'rate_limit_per_hour' => 50,
            'rate_limit_per_day' => 300,
            'circuit_breaker_threshold' => 3,
            'circuit_breaker_cooldown_seconds' => 900,
        ];
    }

    public function groupMappings(): array
    {
        return [];
    }
}
