<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'instagram_business_account_id',
        'page_access_token',
        'token_expires_at',
        'request_logs',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'request_logs' => 'array',
    ];

    public function appendRequestLog(array $log): void
    {
        $logs = Collection::make($this->request_logs ?? []);

        $logs->push(array_merge([
            'timestamp' => now()->toIso8601String(),
        ], $log));

        // Simpan maksimal 50 log terakhir agar kolom tidak membengkak.
        $this->request_logs = $logs->take(-50)->values()->all();
        $this->save();
    }

    public function maskSensitivePayload(array $payload): array
    {
        return Collection::make($payload)
            ->map(fn ($value, string $key) => $this->isSensitiveKey($key) ? '[REDACTED]' : $value)
            ->toArray();
    }

    protected function isSensitiveKey(string $key): bool
    {
        return collect(['token', 'secret'])->contains(fn (string $needle) => str_contains($key, $needle));
    }
}
