<?php

namespace App\Filament\Resources\SocialAccounts;

use App\Filament\Resources\SocialAccounts\Pages\CreateSocialAccount;
use App\Filament\Resources\SocialAccounts\Pages\EditSocialAccount;
use App\Filament\Resources\SocialAccounts\Pages\ListSocialAccounts;
use App\Models\SocialAccount;
use App\Services\InstagramPublisher;
use App\Support\RoleAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use UnitEnum;

class SocialAccountResource extends Resource
{
    protected static ?string $model = SocialAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-share';
    protected static ?string $navigationLabel = 'Akun Sosial';
    protected static ?string $pluralModelLabel = 'Akun Sosial';
    protected static ?string $modelLabel = 'Akun Sosial';
    protected static ?string $slug = 'social-accounts';
    protected static string|UnitEnum|null $navigationGroup = 'Integrasi';
    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama Akun')
                ->required()
                ->maxLength(150)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('instagram_business_account_id')
                ->label('Instagram Business Account ID')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('page_access_token')
                ->label('Page Access Token')
                ->rows(3)
                ->required()
                ->columnSpanFull(),
            Forms\Components\DateTimePicker::make('token_expires_at')
                ->label('Kedaluwarsa Token')
                ->seconds(false),
            Forms\Components\Textarea::make('request_logs')
                ->label('Log Permintaan (terbaru)')
                ->formatStateUsing(fn (?array $state) => collect($state)->take(-5)->values()->map(fn ($log) => sprintf(
                    "%s — %s: %s",
                    $log['timestamp'] ?? '-',
                    strtoupper($log['status'] ?? 'unknown'),
                    $log['error'] ?? ($log['action'] ?? '-')
                ))->implode(PHP_EOL))
                ->rows(5)
                ->disabled()
                ->dehydrated(false)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('instagram_business_account_id')
                    ->label('IG Business ID')
                    ->copyable()
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('token_expires_at')
                    ->label('Kedaluwarsa')
                    ->since()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('request_logs')
                    ->label('Status Terakhir')
                    ->colors([
                        'success' => fn (?array $state) => (collect($state)->last()['status'] ?? null) === 'success',
                        'danger' => fn (?array $state) => (collect($state)->last()['status'] ?? null) === 'failed',
                    ])
                    ->formatStateUsing(function (?array $state) {
                        $last = collect($state)->last();

                        return $last
                            ? sprintf('%s (%s)', $last['action'] ?? 'log', $last['status'] ?? 'unknown')
                            : 'Belum ada log';
                    }),
            ])
            ->actions([
                Action::make('test_connection')
                    ->label('Test Koneksi')
                    ->icon('heroicon-o-wifi')
                    ->action(function (SocialAccount $record) {
                        try {
                            /** @var InstagramPublisher $publisher */
                            $publisher = app(InstagramPublisher::class);
                            $publisher->testConnection($record);

                            Notification::make()
                                ->title('Koneksi berhasil')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal menguji koneksi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
                Action::make('refresh_token')
                    ->label('Refresh Token')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (SocialAccount $record) {
                        try {
                            /** @var InstagramPublisher $publisher */
                            $publisher = app(InstagramPublisher::class);
                            $publisher->refreshToken($record);

                            Notification::make()
                                ->title('Token berhasil diperbarui')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal me-refresh token')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
                Action::make('lihat_log')
                    ->label('Lihat Log')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->modalHeading('Log Permintaan Instagram')
                    ->modalContent(fn (SocialAccount $record) => view('filament.social-account-log', [
                        'logs' => $record->request_logs ?? [],
                    ]))
                    ->visible(fn (SocialAccount $record) => ! empty($record->request_logs)),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return RoleAccess::canSeeNav(auth()->user(), 'filament.admin.resources.social-accounts');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSocialAccounts::route('/'),
            'create' => CreateSocialAccount::route('/create'),
            'edit' => EditSocialAccount::route('/{record}/edit'),
        ];
    }
}
