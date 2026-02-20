<?php

namespace App\Filament\Pages;

use App\Models\WaGatewaySetting;
use App\Services\WaGatewayService;
use App\Support\RoleAccess;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

class AntiSpamSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $title = 'Pengaturan Anti-Spam WA';
    protected static ?string $navigationLabel = 'Anti-Spam WA';
    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';
    protected static ?string $slug = 'anti-spam-settings';
    protected static ?int $navigationSort = 27;

    protected string $view = 'filament.pages.anti-spam-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = WaGatewaySetting::current();

        $this->form->fill([
            'min_delay_ms'                    => $setting->min_delay_ms ?? 3000,
            'max_delay_ms'                    => $setting->max_delay_ms ?? 7000,
            'rate_limit_per_hour'             => $setting->rate_limit_per_hour ?? 50,
            'rate_limit_per_day'              => $setting->rate_limit_per_day ?? 300,
            'circuit_breaker_threshold'       => $setting->circuit_breaker_threshold ?? 3,
            'circuit_breaker_cooldown_seconds' => $setting->circuit_breaker_cooldown_seconds ?? 900,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Delay Antar Pesan')
                    ->description('Jeda waktu acak antar pengiriman pesan untuk menghindari deteksi spam.')
                    ->schema([
                        TextInput::make('min_delay_ms')
                            ->label('Delay Minimum (ms)')
                            ->helperText('Delay minimum dalam milidetik. Default: 3000 (3 detik).')
                            ->numeric()
                            ->minValue(500)
                            ->maxValue(60000)
                            ->required(),

                        TextInput::make('max_delay_ms')
                            ->label('Delay Maksimum (ms)')
                            ->helperText('Delay maksimum dalam milidetik. Default: 7000 (7 detik).')
                            ->numeric()
                            ->minValue(500)
                            ->maxValue(60000)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Rate Limit')
                    ->description('Batasan jumlah pesan yang dapat dikirim dalam periode tertentu.')
                    ->schema([
                        TextInput::make('rate_limit_per_hour')
                            ->label('Batas Per Jam')
                            ->helperText('Jumlah maksimum pesan per jam. Default: 50.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->required(),

                        TextInput::make('rate_limit_per_day')
                            ->label('Batas Per Hari')
                            ->helperText('Jumlah maksimum pesan per hari. Default: 300.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10000)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Circuit Breaker')
                    ->description('Mekanisme pemutus otomatis jika terjadi kegagalan pengiriman berturut-turut.')
                    ->schema([
                        TextInput::make('circuit_breaker_threshold')
                            ->label('Ambang Kegagalan')
                            ->helperText('Jumlah kegagalan berturut-turut sebelum circuit breaker aktif. Default: 3.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->required(),

                        TextInput::make('circuit_breaker_cooldown_seconds')
                            ->label('Waktu Cooldown (detik)')
                            ->helperText('Durasi circuit breaker aktif dalam detik. Default: 900 (15 menit).')
                            ->numeric()
                            ->minValue(60)
                            ->maxValue(86400)
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        if ((int) $state['min_delay_ms'] >= (int) $state['max_delay_ms']) {
            Notification::make()
                ->title('Delay minimum harus lebih kecil dari delay maksimum')
                ->danger()
                ->send();
            return;
        }

        $setting = WaGatewaySetting::current();
        $setting->fill($state);
        $setting->save();

        Notification::make()
            ->title('Pengaturan Anti-Spam disimpan')
            ->success()
            ->send();
    }

    public function resetCircuitBreaker(): void
    {
        app(WaGatewayService::class)->resetCircuitBreaker();

        Notification::make()
            ->title('Circuit breaker di-reset')
            ->body('Status kegagalan berturut-turut telah direset.')
            ->success()
            ->send();
    }

    public function resetRateLimitCounters(): void
    {
        Cache::forget('wa_gateway_rate_limit_' . date('Y-m-d-H'));
        Cache::forget('wa_gateway_rate_limit_daily_' . date('Y-m-d'));

        Notification::make()
            ->title('Counter rate limit di-reset')
            ->body('Hitungan pesan jam ini dan hari ini telah direset ke nol.')
            ->success()
            ->send();
    }

    public function getAntiSpamStatus(): array
    {
        try {
            return app(WaGatewayService::class)->getAntiSpamStatus();
        } catch (\Throwable) {
            return [
                'messages_sent_session'  => 0,
                'consecutive_failures'   => 0,
                'circuit_breaker_active' => false,
                'circuit_breaker_until'  => null,
                'hourly_count'           => 0,
                'hourly_limit'           => 50,
                'daily_count'            => 0,
                'daily_limit'            => 300,
                'can_send'               => true,
            ];
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return RoleAccess::canSeeNav(auth()->user(), 'filament.admin.pages.anti-spam-settings');
    }
}
