<?php

namespace App\Filament\Resources\SpjResource\Pages;

use App\Filament\Resources\BkuResource;
use App\Filament\Resources\SpjResource;
use App\Models\Bku;
use App\Models\DpaSubKegiatan;
use App\Models\Spj;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSpjs extends ListRecords
{
    protected static string $resource = SpjResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('generate_bku')
                ->label('Generate BKU')
                ->icon('heroicon-o-book-open')
                ->color('success')
                ->form([
                    Select::make('dpa_sub_kegiatan_id')
                        ->label('Sub Kegiatan DPA')
                        ->options(function () {
                            $query = DpaSubKegiatan::query()
                                ->with('dpa')
                                ->orderBy('kode');

                            $groups = [];
                            foreach ($query->get() as $sub) {
                                $dpa = $sub->dpa;
                                $groupKey = $dpa
                                    ? ($dpa->seksi_akronim ? '[' . strtoupper($dpa->seksi_akronim) . '] ' : '') . $dpa->nama . ' (' . $dpa->tahun . ')'
                                    : 'DPA Tidak Diketahui';
                                $groups[$groupKey][$sub->id] = '[' . $sub->kode . '] ' . $sub->nama;
                            }

                            return $groups;
                        })
                        ->searchable()
                        ->required(),

                    Textarea::make('pekerjaan')
                        ->label('Pekerjaan')
                        ->rows(2),

                    Select::make('bulan')
                        ->label('Bulan')
                        ->options([
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                            4 => 'April', 5 => 'Mei', 6 => 'Juni',
                            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                        ])
                        ->required(),

                    TextInput::make('tahun')
                        ->label('Tahun')
                        ->numeric()
                        ->required()
                        ->default(now()->year),

                ])
                ->action(function (array $data) {
                    $subKegiatanId = $data['dpa_sub_kegiatan_id'];
                    $tahun = (int) $data['tahun'];

                    // Find SPJs not yet in any BKU, matching sub kegiatan
                    $spjs = Spj::query()
                        ->whereDoesntHave('bkus')
                        ->whereHas('dpaRincianBelanja', fn ($q) => $q->where('dpa_sub_kegiatan_id', $subKegiatanId))
                        ->get(['id', 'jumlah']);

                    if ($spjs->isEmpty()) {
                        Notification::make()
                            ->title('Tidak ada SPJ')
                            ->body('Semua SPJ untuk sub kegiatan ini sudah masuk BKU.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $totalPengeluaran = $spjs->sum('jumlah');
                    $maxUrut = Bku::where('tahun', $tahun)->max('nomor_urut') ?? 0;

                    $bku = Bku::create([
                        'nomor_urut' => $maxUrut + 1,
                        'tanggal_generate' => now()->toDateString(),
                        'dpa_sub_kegiatan_id' => $subKegiatanId,
                        'pekerjaan' => $data['pekerjaan'] ?? null,
                        'bulan' => $data['bulan'],
                        'tahun' => $tahun,
                        'penerimaan' => (int) $totalPengeluaran,
                        'catatan' => null,
                    ]);

                    $bku->spjs()->attach($spjs->pluck('id'));

                    Notification::make()
                        ->title('BKU berhasil dibuat')
                        ->body($bku->nama_label . ' — ' . $spjs->count() . ' SPJ dimasukkan.')
                        ->success()
                        ->send();

                    $this->redirect(BkuResource::getUrl('view', ['record' => $bku]));
                }),
        ];
    }
}
