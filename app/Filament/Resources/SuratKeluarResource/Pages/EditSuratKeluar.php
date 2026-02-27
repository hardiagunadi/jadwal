<?php

namespace App\Filament\Resources\SuratKeluarResource\Pages;

use App\Filament\Resources\SuratKeluarResource;
use App\Models\KodeSurat;
use App\Models\SuratKeluar;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditSuratKeluar extends EditRecord
{
    protected static string $resource = SuratKeluarResource::class;
    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ubah_klasifikasi')
                ->label('Ubah Klasifikasi')
                ->icon('heroicon-o-tag')
                ->color('warning')
                ->visible(fn () => auth()->user()?->isAdmin() === true || auth()->user()?->isArsiparis() === true)
                ->form([
                    Select::make('kode_surat_id')
                        ->label('Kode Klasifikasi')
                        ->options(fn () => KodeSurat::query()
                            ->orderBy('kode')
                            ->get()
                            ->mapWithKeys(fn (KodeSurat $kode) => [
                                $kode->id => $kode->kode . ' - ' . $kode->keterangan,
                            ])
                            ->all())
                        ->getSearchResultsUsing(fn (string $search): array => KodeSurat::query()
                            ->where(fn ($q) => $q
                                ->where('kode', 'like', '%' . trim($search) . '%')
                                ->orWhere('keterangan', 'like', '%' . trim($search) . '%'))
                            ->orderBy('kode')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (KodeSurat $kode) => [
                                $kode->id => $kode->kode . ' - ' . $kode->keterangan,
                            ])
                            ->all())
                        ->getOptionLabelUsing(fn ($value): ?string => ($kode = KodeSurat::find($value))
                            ? $kode->kode . ' - ' . $kode->keterangan
                            : null)
                        ->default(fn () => $this->record->kode_surat_id)
                        ->searchable()
                        ->preload()
                        ->required()
                        ->placeholder('Ketik kode atau keterangan'),
                ])
                ->action(function (array $data): void {
                    $this->record->update(['kode_surat_id' => $data['kode_surat_id']]);
                    $this->refreshFormData(['kode_surat_id']);
                    Notification::make()
                        ->title('Klasifikasi surat berhasil diubah.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        if ($user?->isInFinishWhitelist() !== true) {
            unset($data['requested_by_personil_id']);
        }

        if (($this->record->status ?? SuratKeluar::STATUS_ISSUED) === SuratKeluar::STATUS_BOOKED) {
            $perihal = trim((string) ($data['perihal'] ?? ''));
            $tanggalSurat = $data['tanggal_surat'] ?? null;

            if ($perihal !== '' && $perihal !== SuratKeluar::BOOKED_PLACEHOLDER && ! empty($tanggalSurat)) {
                $data['status'] = SuratKeluar::STATUS_ISSUED;
            }
        }

        return $data;
    }
}
