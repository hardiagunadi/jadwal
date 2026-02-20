<?php

namespace App\Filament\Pages;

use App\Models\Personil;
use App\Models\SeksiModul;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class SeksiModulSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Modul per Seksi';
    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';
    protected static ?string $slug = 'seksi-modul-settings';
    protected static ?int $navigationSort = 215;

    protected string $view = 'filament.pages.seksi-modul-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $state = [];

        foreach ($this->getSeksiList() as $akronim) {
            $rows = SeksiModul::where('seksi_akronim', $akronim)->get()->keyBy('modul_key');

            // Label seksi — ambil dari DB atau default ke uppercase akronim
            $state["label_{$akronim}"] = $rows->first()?->seksi_label ?? strtoupper($akronim);

            // Modul yang aktif untuk seksi ini
            $state["moduls_{$akronim}"] = $rows
                ->filter(fn ($r) => $r->aktif)
                ->keys()
                ->toArray();
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $seksiList = $this->getSeksiList();
        $modulOptions = SeksiModul::availableModuls();
        $components = [];

        foreach ($seksiList as $akronim) {
            $components[] = Fieldset::make(strtoupper($akronim))
                ->schema([
                    TextInput::make("label_{$akronim}")
                        ->label('Nama tampilan di menu')
                        ->placeholder('Seksi ' . strtoupper($akronim))
                        ->required()
                        ->maxLength(100),

                    CheckboxList::make("moduls_{$akronim}")
                        ->label('Modul yang diaktifkan')
                        ->options($modulOptions)
                        ->bulkToggleable()
                        ->columns(3),
                ])
                ->columns(1);
        }

        return $schema
            ->components([
                Section::make('Pengaturan Modul per Seksi')
                    ->description('Tentukan modul apa saja yang dapat diakses oleh masing-masing seksi. Nama tampilan di menu akan muncul sebagai judul group navigasi.')
                    ->schema($components ?: [
                        \Filament\Schemas\Components\Component::make('empty')
                            ->view('filament.components.empty-state'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $seksiList = $this->getSeksiList();
        $modulKeys = array_keys(SeksiModul::availableModuls());

        foreach ($seksiList as $akronim) {
            $label = $state["label_{$akronim}"] ?? strtoupper($akronim);
            $activeModuls = $state["moduls_{$akronim}"] ?? [];

            foreach ($modulKeys as $modulKey) {
                SeksiModul::updateOrCreate(
                    ['seksi_akronim' => $akronim, 'modul_key' => $modulKey],
                    [
                        'seksi_label' => $label,
                        'aktif' => in_array($modulKey, $activeModuls, true),
                    ]
                );
            }
        }

        SeksiModul::clearCache();

        Notification::make()
            ->title('Pengaturan modul per seksi disimpan')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    /** Ambil daftar seksi dari jabatan_akronim unik di tabel personils. */
    protected function getSeksiList(): array
    {
        return Personil::query()
            ->whereNotNull('jabatan_akronim')
            ->where('jabatan_akronim', '!=', '')
            ->distinct()
            ->orderBy('jabatan_akronim')
            ->pluck('jabatan_akronim')
            ->map(fn ($v) => strtolower(trim($v)))
            ->unique()
            ->values()
            ->toArray();
    }
}
