<?php

namespace App\Filament\Resources\GajResource\RelationManagers;

use App\Models\Personil;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class GajPegawaisRelationManager extends RelationManager
{
    protected static string $relationship = 'pegawais';

    protected static ?string $title = 'Daftar Pegawai';

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        $money = fn ($state) => number_format((int) $state, 0, ',', '.');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_urut')
                    ->label('No')
                    ->alignCenter()
                    ->width('3%'),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->width('20%'),

                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('golongan')
                    ->label('Gol / Status')
                    ->limit(30)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status_kawin')
                    ->label('STS')
                    ->alignCenter()
                    ->placeholder('-'),

                // ── Penghasilan ──────────────────────────────────────────
                Tables\Columns\TextColumn::make('gaji_pokok')
                    ->label('Gaji Pokok')
                    ->alignRight()
                    ->formatStateUsing($money),

                Tables\Columns\TextColumn::make('tunj_eselon')
                    ->label('Tunj. Eselon')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tunj_fungsional')
                    ->label('Tunj. Fungsional')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tunj_fung_umum')
                    ->label('Tunj. Fung. Umum')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tunj_istri')
                    ->label('Tunj. Istri/SMI')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tunj_anak')
                    ->label('Tunj. Anak')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tunj_beras')
                    ->label('Tunj. Beras')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tunj_terpencil')
                    ->label('Tunj. Terpencil')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tkd')
                    ->label('TKD')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tunj_khusus')
                    ->label('Tunj. Khusus')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tunj_bpjskes_4')
                    ->label('BPJSKES 4%')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tunj_jkk')
                    ->label('Tunj. JKK')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tunj_jkm')
                    ->label('Tunj. JKM')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tunj_pajak')
                    ->label('Tunj. Pajak')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tapera_pk_pgh')
                    ->label('Tapera PK (Pgh)')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pembulatan')
                    ->label('Pembulatan')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('jumlah_penghasilan')
                    ->label('Jml Penghasilan')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('jml_kotor')
                    ->label('Jml Kotor')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── Potongan ─────────────────────────────────────────────
                Tables\Columns\TextColumn::make('pot_pajak')
                    ->label('Pot. Pajak')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pot_bpjs_kes')
                    ->label('Pot. BPJS Kes')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pot_iwp_1')
                    ->label('IWP 1%')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pot_iwp_8')
                    ->label('IWP 8%')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tapera_pk_pot')
                    ->label('Tapera PK (Pot)')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tapera_peg')
                    ->label('Tapera Peg')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pot_taperum')
                    ->label('Pot. Taperum')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pot_jkk')
                    ->label('Pot. JKK')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pot_jkm')
                    ->label('Pot. JKM')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('hutang_lain2')
                    ->label('Hutang/Lain2')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('bulog')
                    ->label('Bulog')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sewa_rumah')
                    ->label('Sewa Rumah')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('jml_potongan')
                    ->label('Jml Potongan')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── Hasil akhir ───────────────────────────────────────────
                Tables\Columns\TextColumn::make('jumlah_bersih')
                    ->label('Jml Bersih')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->color('success'),

                Tables\Columns\TextColumn::make('personil.no_rekening')
                    ->label('No Rekening')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('personil.kode_bank')
                    ->label('Bank')
                    ->formatStateUsing(fn ($state) => Personil::BANK_OPTIONS[$state] ?? '-')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('no_urut')
            ->searchPlaceholder('Cari nama atau NIP...')
            ->filters([
                Tables\Filters\SelectFilter::make('status_kawin')
                    ->label('Status Kawin')
                    ->options(fn () => \App\Models\GajPegawai::query()
                        ->whereHas('gaj', fn ($q) => $q->where('id', $this->ownerRecord->id))
                        ->distinct()
                        ->orderBy('status_kawin')
                        ->pluck('status_kawin', 'status_kawin')
                        ->filter()
                        ->toArray()),

                Tables\Filters\SelectFilter::make('golongan')
                    ->label('Golongan')
                    ->options(fn () => \App\Models\GajPegawai::query()
                        ->whereHas('gaj', fn ($q) => $q->where('id', $this->ownerRecord->id))
                        ->distinct()
                        ->orderBy('golongan')
                        ->pluck('golongan', 'golongan')
                        ->filter()
                        ->toArray()),

                Tables\Filters\SelectFilter::make('kode_bank')
                    ->label('Bank')
                    ->options(Personil::BANK_OPTIONS)
                    ->query(fn ($query, array $data) => $data['value']
                        ? $query->whereHas('personil', fn ($q) => $q->where('kode_bank', $data['value']))
                        : $query),
            ])
            ->actions([
                Action::make('setBank')
                    ->label(fn (\App\Models\GajPegawai $record) => $record->personil?->kode_bank ? 'Edit Bank' : 'Set Bank')
                    ->icon('heroicon-m-building-library')
                    ->fillForm(fn (\App\Models\GajPegawai $record) => ['kode_bank' => $record->personil?->kode_bank])
                    ->form([
                        \Filament\Forms\Components\Select::make('kode_bank')
                            ->label('Bank')
                            ->options(Personil::BANK_OPTIONS)
                            ->required(),
                    ])
                    ->action(function (\App\Models\GajPegawai $record, array $data) {
                        Personil::where('nip', $record->nip)->update(['kode_bank' => $data['kode_bank']]);
                        \Filament\Notifications\Notification::make()
                            ->title('Bank berhasil diset')
                            ->body($record->nama . ' → ' . (Personil::BANK_OPTIONS[$data['kode_bank']] ?? $data['kode_bank']))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('setBankBulk')
                    ->label('Set Bank')
                    ->icon('heroicon-m-building-library')
                    ->form([
                        \Filament\Forms\Components\Select::make('kode_bank')
                            ->label('Bank')
                            ->options(Personil::BANK_OPTIONS)
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Support\Collection $records, array $data) {
                        $nips = $records->pluck('nip')->filter()->unique()->values();
                        Personil::whereIn('nip', $nips)->update(['kode_bank' => $data['kode_bank']]);
                        \Filament\Notifications\Notification::make()
                            ->title('Bank berhasil diset')
                            ->body($records->count() . ' pegawai → ' . (Personil::BANK_OPTIONS[$data['kode_bank']] ?? $data['kode_bank']))
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->paginated([25, 50, 100]);
    }
}
