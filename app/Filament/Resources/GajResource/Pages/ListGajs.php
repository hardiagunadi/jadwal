<?php

namespace App\Filament\Resources\GajResource\Pages;

use App\Filament\Resources\GajKorpriResource;
use App\Filament\Resources\GajResource;
use App\Models\Personil;
use App\Services\GajImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListGajs extends ListRecords
{
    protected static string $resource = GajResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dkk_korpri')
                ->label('DKK Korpri')
                ->icon('heroicon-o-building-office-2')
                ->color('gray')
                ->url(fn () => GajKorpriResource::getUrl('index'))
                ->visible(fn () => GajKorpriResource::canAccess()),

            Action::make('import_pdf')
                ->label('Import dari PDF')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    Select::make('seksi_akronim')
                        ->label('Seksi / Bidang Pemilik Data')
                        ->options(function () {
                            return Personil::query()
                                ->whereNotNull('jabatan_akronim')
                                ->where('jabatan_akronim', '!=', '')
                                ->distinct()
                                ->orderBy('jabatan_akronim')
                                ->pluck('jabatan_akronim', 'jabatan_akronim')
                                ->mapWithKeys(fn ($v) => [strtolower(trim($v)) => strtoupper(trim($v))])
                                ->toArray();
                        })
                        ->default(function () {
                            $user = auth()->user();
                            if ($user && ! $user->isAdmin()) {
                                return strtolower(trim((string) ($user->jabatan_akronim ?? ''))) ?: null;
                            }
                            return null;
                        })
                        ->disabled(fn () => ! auth()->user()?->isAdmin())
                        ->dehydrated()
                        ->searchable()
                        ->nullable()
                        ->helperText(function () {
                            if (auth()->user()?->isAdmin()) {
                                return 'Pilih seksi pemilik data gaji ini.';
                            }
                            return 'Seksi otomatis diisi sesuai akun Anda.';
                        }),

                    FileUpload::make('pdf_files')
                        ->label('File PDF Daftar Gaji')
                        ->acceptedFileTypes(['application/pdf'])
                        ->required()
                        ->multiple()
                        ->disk('local')
                        ->directory('gaji-imports')
                        ->helperText('Upload file PDF Daftar Gaji Induk PNS atau PPPK dari SIPD.'),
                ])
                ->action(function (array $data): void {
                    /** @var GajImportService $service */
                    $service  = app(GajImportService::class);
                    $berhasil = 0;
                    $gagal    = 0;
                    $messages = [];

                    foreach ($data['pdf_files'] as $file) {
                        $path = Storage::disk('local')->path($file);

                        try {
                            $result = $service->parseAndImport($path, $data['seksi_akronim'] ?? null);

                            $gaj    = $result['gaj'];
                            $jumlah = $result['jumlah'];
                            $isNew  = $result['is_new'];

                            $label = strtoupper($gaj->jenis) . ' — ' . $gaj->periode;
                            $messages[] = ($isNew ? '✓ Baru: ' : '↺ Diperbarui: ') . $label . " ({$jumlah} pegawai)";
                            $berhasil++;
                        } catch (\Throwable $e) {
                            $messages[] = '✗ ' . basename($file) . ': ' . $e->getMessage();
                            $gagal++;
                        } finally {
                            Storage::disk('local')->delete($file);
                        }
                    }

                    $total = count($data['pdf_files']);
                    $title = $gagal === 0
                        ? "Import selesai — {$berhasil} dari {$total} file berhasil"
                        : "Import selesai — {$berhasil} berhasil, {$gagal} gagal";

                    Notification::make()
                        ->title($title)
                        ->body(implode("\n", $messages))
                        ->color($gagal === 0 ? 'success' : ($berhasil === 0 ? 'danger' : 'warning'))
                        ->send();
                })
                ->modalHeading('Import Daftar Gaji dari PDF SIPD')
                ->modalDescription('Upload file PDF Daftar Pembayaran Gaji Induk PNS atau PPPK dari SIPD. Jika data bulan yang sama sudah ada, akan digantikan.')
                ->modalSubmitActionLabel('Import'),
        ];
    }
}
