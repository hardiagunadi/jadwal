<?php

namespace App\Filament\Resources\DpaResource\Pages;

use App\Filament\Resources\DpaResource;
use App\Models\Personil;
use App\Services\DpaImportService;
use Filament\Actions\Action;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListDpas extends ListRecords
{
    protected static string $resource = DpaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_pdf')
                ->label('Import dari PDF')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    Select::make('seksi_akronim')
                        ->label('Seksi / Bidang Pemilik DPA')
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
                                return 'Pilih seksi pemilik DPA ini agar pengguna seksi tersebut dapat memilihnya saat membuat SPJ.';
                            }

                            return 'Seksi otomatis diisi sesuai akun Anda.';
                        }),

                    FileUpload::make('pdf_files')
                        ->label('File PDF DPA (dari SIPD)')
                        ->acceptedFileTypes(['application/pdf'])
                        ->required()
                        ->multiple()
                        ->disk('local')
                        ->directory('dpa-imports')
                        ->helperText('Upload satu atau beberapa file PDF DPA yang diunduh dari SIPD Kemendagri.'),
                ])
                ->action(function (array $data): void {
                    /** @var DpaImportService $service */
                    $service = app(DpaImportService::class);

                    $berhasil = 0;
                    $gagal = 0;
                    $messages = [];

                    foreach ($data['pdf_files'] as $file) {
                        $path = Storage::disk('local')->path($file);

                        try {
                            $result = $service->parseAndImport($path);

                            $dpa = $result['dpa'];

                            if (! empty($data['seksi_akronim'])) {
                                $dpa->update(['seksi_akronim' => strtolower(trim($data['seksi_akronim']))]);
                            }
                            $isNew = $result['is_new'];
                            $subBaru = $result['sub_baru'];
                            $subUpdate = $result['sub_update'];
                            $totalSub = $dpa->subKegiatans()->count();
                            $totalRincian = $dpa->rincianBelanjas()->count();

                            if ($isNew) {
                                $messages[] = "✓ {$dpa->nomor_dpa} — baru ({$subBaru} sub | {$totalRincian} rincian)";
                            } elseif ($subBaru > 0) {
                                $messages[] = "✓ {$dpa->nomor_dpa} — +{$subBaru} sub baru"
                                    . ($subUpdate > 0 ? ", {$subUpdate} diperbarui" : '');
                            } else {
                                $messages[] = "↺ {$dpa->nomor_dpa} — sudah ada ({$subUpdate} sub diperbarui)";
                            }

                            $berhasil++;
                        } catch (\Throwable $e) {
                            $messages[] = "✗ " . basename($file) . ": " . $e->getMessage();
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
                ->modalHeading('Import DPA dari PDF SIPD')
                ->modalDescription('Upload satu atau beberapa file PDF DPA dari SIPD Kemendagri. Beberapa sub kegiatan dalam satu unit memiliki nomor DPA yang sama — sistem akan otomatis menggabungkannya ke dalam satu DPA dan menambahkan sub kegiatan baru.')
                ->modalSubmitActionLabel('Import'),
        ];
    }
}
