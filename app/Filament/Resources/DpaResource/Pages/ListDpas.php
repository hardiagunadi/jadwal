<?php

namespace App\Filament\Resources\DpaResource\Pages;

use App\Filament\Resources\DpaResource;
use App\Services\DpaImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
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
                    FileUpload::make('pdf_file')
                        ->label('File PDF DPA (dari SIPD)')
                        ->acceptedFileTypes(['application/pdf'])
                        ->required()
                        ->disk('local')
                        ->directory('dpa-imports')
                        ->helperText('Upload file PDF DPA yang diunduh dari SIPD Kemendagri.'),
                ])
                ->action(function (array $data): void {
                    $path = Storage::disk('local')->path($data['pdf_file']);

                    try {
                        /** @var DpaImportService $service */
                        $service = app(DpaImportService::class);
                        $result = $service->parseAndImport($path);

                        $dpa = $result['dpa'];
                        $isNew = $result['is_new'];
                        $subBaru = $result['sub_baru'];
                        $subUpdate = $result['sub_update'];
                        $totalSub = $dpa->subKegiatans()->count();
                        $totalRincian = $dpa->rincianBelanjas()->count();

                        if ($isNew) {
                            $title = 'DPA baru berhasil diimport';
                            $body = "Nomor: {$dpa->nomor_dpa}\n"
                                . "{$subBaru} sub kegiatan | {$totalRincian} rincian belanja";
                        } elseif ($subBaru > 0) {
                            $title = 'Sub kegiatan baru ditambahkan ke DPA';
                            $body = "Nomor: {$dpa->nomor_dpa}\n"
                                . "+{$subBaru} sub kegiatan baru ditambahkan"
                                . ($subUpdate > 0 ? ", {$subUpdate} diperbarui" : '') . "\n"
                                . "Total: {$totalSub} sub kegiatan | {$totalRincian} rincian belanja";
                        } else {
                            $title = 'DPA sudah ada — tidak ada perubahan';
                            $body = "Nomor: {$dpa->nomor_dpa}\n"
                                . "Sub kegiatan dalam file ini sudah tersimpan sebelumnya ({$subUpdate} sub diperbarui).\n"
                                . "Total: {$totalSub} sub kegiatan | {$totalRincian} rincian belanja";
                        }

                        Notification::make()
                            ->title($title)
                            ->body($body)
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal import DPA')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        // Hapus file upload sementara
                        if (isset($data['pdf_file'])) {
                            Storage::disk('local')->delete($data['pdf_file']);
                        }
                    }
                })
                ->modalHeading('Import DPA dari PDF SIPD')
                ->modalDescription('Upload file PDF DPA dari SIPD Kemendagri. Beberapa sub kegiatan dalam satu unit memiliki nomor DPA yang sama — sistem akan otomatis menggabungkannya ke dalam satu DPA dan menambahkan sub kegiatan baru.')
                ->modalSubmitActionLabel('Import'),

            CreateAction::make(),
        ];
    }
}
