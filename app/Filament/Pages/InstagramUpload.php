<?php

namespace App\Filament\Pages;

use App\Models\InstagramPost;
use App\Models\InstagramTemplate;
use App\Services\GeminiCaptionService;
use App\Services\ImageTemplateService;
use App\Services\InstagramPublisher;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class InstagramUpload extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-camera';

    protected static ?string $navigationLabel = 'Upload Instagram';

    protected static UnitEnum|string|null $navigationGroup = 'Instagram';

    protected static ?string $slug = 'instagram/upload';

    protected string $view = 'filament.pages.instagram-upload';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'type' => 'photo',
            'publish_at' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konten Instagram')
                    ->description('Unggah foto/video feed. Stories dan Reels tidak didukung API terjadwal.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('type')
                                ->label('Jenis Konten')
                                ->options([
                                    'photo' => 'Foto Feed',
                                    'video' => 'Video Feed',
                                ])
                                ->required(),
                            DateTimePicker::make('publish_at')
                                ->label('Jadwalkan Publikasi')
                                ->helperText('Opsional. Gunakan publish_time untuk menjadwalkan feed (max 75 hari).')
                                ->seconds(false)
                                ->minDate(now()),
                        ]),
                        FileUpload::make('photo_path')
                            ->label('Foto')
                            ->directory('instagram/uploads')
                            ->disk('public')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->visible(fn ($get) => $get('type') === 'photo')
                            ->required(fn ($get) => $get('type') === 'photo')
                            ->helperText('Gunakan JPEG; pastikan rasio sesuai feed.'),
                        FileUpload::make('video_path')
                            ->label('Video')
                            ->directory('instagram/uploads')
                            ->disk('public')
                            ->visibility('public')
                            ->acceptedFileTypes(['video/mp4', 'video/quicktime'])
                            ->maxSize(51200)
                            ->visible(fn ($get) => $get('type') === 'video')
                            ->required(fn ($get) => $get('type') === 'video')
                            ->helperText('Durasi maksimal 10 menit sesuai batasan API.'),
                        Select::make('instagram_template_id')
                            ->label('Frame/Template (hanya foto)')
                            ->options(InstagramTemplate::query()->pluck('name', 'id'))
                            ->visible(fn ($get) => $get('type') === 'photo')
                            ->searchable()
                            ->helperText('PNG transparan. Foto akan ditempatkan ke dalam template.'),
                    ]),
                Section::make('Narasi')
                    ->schema([
                        TextInput::make('keywords')
                            ->label('Kata Kunci Narasi')
                            ->placeholder('mis: pelayanan publik, inovasi desa')
                            ->helperText('Klik "Buat Narasi dengan Gemini" untuk menghasilkan caption otomatis.'),
                        Textarea::make('caption')
                            ->label('Caption Instagram')
                            ->rows(4)
                            ->hint('Dapat diedit sebelum dikirim ke Instagram.')
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function generateCaption(GeminiCaptionService $service): void
    {
        $keywords = $this->data['keywords'] ?? '';

        if (blank($keywords)) {
            Notification::make()
                ->title('Kata kunci belum diisi')
                ->danger()
                ->body('Tambahkan kata kunci terlebih dahulu untuk meminta narasi dari Gemini.')
                ->send();

            return;
        }

        $caption = $service->generate($keywords);

        if (! $caption) {
            Notification::make()
                ->title('Gagal membuat narasi')
                ->danger()
                ->body('Pastikan API key Gemini terpasang dan coba lagi.')
                ->send();

            return;
        }

        $this->form->fill(array_merge($this->data ?? [], ['caption' => $caption]));

        Notification::make()
            ->title('Narasi dibuat')
            ->success()
            ->body('Caption dari Gemini berhasil dimuat dan bisa diedit sebelum dikirim.')
            ->send();
    }

    public function submit(ImageTemplateService $templateService, InstagramPublisher $publisher): void
    {
        $state = $this->form->getState();
        $type = $state['type'] ?? 'photo';
        $caption = $state['caption'] ?? '';
        $publishAt = isset($state['publish_at']) && $state['publish_at'] ? Carbon::parse($state['publish_at']) : null;

        $mediaPath = $type === 'photo' ? ($state['photo_path'] ?? null) : ($state['video_path'] ?? null);

        if (empty($mediaPath)) {
            Notification::make()
                ->title('File belum dipilih')
                ->danger()
                ->body('Silakan unggah foto atau video terlebih dahulu.')
                ->send();

            return;
        }

        if (blank(config('services.instagram.access_token')) || blank(config('services.instagram.ig_user_id'))) {
            Notification::make()
                ->title('Konfigurasi Instagram belum lengkap')
                ->danger()
                ->body('Isi INSTAGRAM_IG_USER_ID dan INSTAGRAM_ACCESS_TOKEN di environment sebelum melanjutkan.')
                ->send();

            return;
        }

        $post = InstagramPost::create([
            'type' => $type,
            'media_path' => $mediaPath,
            'instagram_template_id' => $state['instagram_template_id'] ?? null,
            'caption' => $caption,
            'keywords' => $state['keywords'] ?? null,
            'publish_at' => $publishAt,
            'status' => 'draft',
        ]);

        $payload = [
            'caption' => $caption,
        ];

        if ($publishAt) {
            $payload['publish_time'] = $publishAt->timestamp;
        }

        if ($type === 'photo') {
            $processedPath = $mediaPath;

            if (! empty($state['instagram_template_id'])) {
                $template = InstagramTemplate::find($state['instagram_template_id']);

                if ($template) {
                    $processedPath = $templateService->applyTemplate($mediaPath, $template);
                }
            }

            $post->update(['processed_path' => $processedPath]);

            $payload['image_url'] = url(Storage::disk('public')->url($processedPath));

            $container = $publisher->createMediaContainer($payload);
            $containerId = $container['id'] ?? null;
            $post->update([
                'container_id' => $containerId,
                'response_payload' => $container,
            ]);

            if (! $containerId) {
                $post->update(['status' => 'failed']);

                Notification::make()
                    ->title('Gagal membuat container')
                    ->danger()
                    ->body('Periksa token, izin instagram_business_content_publish, dan URL file yang dapat diakses publik.')
                    ->send();

                return;
            }

            if ($containerId) {
                $publishResponse = $publisher->publish($containerId);
                $post->update([
                    'publish_id' => $publishResponse['id'] ?? null,
                    'status' => $publishAt ? 'scheduled' : 'published',
                    'response_payload' => array_merge($container ?? [], ['publish' => $publishResponse]),
                ]);
            }
        } else {
            $payload = array_merge($payload, [
                'upload_type' => 'resumable',
                'media_type' => 'VIDEO',
            ]);

            $container = $publisher->createMediaContainer($payload);
            $containerId = $container['id'] ?? null;
            $post->update([
                'container_id' => $containerId,
                'response_payload' => $container,
            ]);

            if (! $containerId) {
                $post->update(['status' => 'failed']);

                Notification::make()
                    ->title('Gagal membuat container video')
                    ->danger()
                    ->body('Pastikan upload_type=resumable diizinkan dan token valid.')
                    ->send();

                return;
            }

            $publisher->uploadVideo($containerId, Storage::disk('public')->path($mediaPath));
            $publishResponse = $publisher->publish($containerId);
            $post->update([
                'publish_id' => $publishResponse['id'] ?? null,
                'status' => $publishAt ? 'scheduled' : 'published',
                'response_payload' => array_merge($container ?? [], ['publish' => $publishResponse]),
            ]);
        }

        Notification::make()
            ->title('Permintaan dikirim')
            ->success()
            ->body('Konten diteruskan ke Graph API. Cek status di tabel riwayat di bawah.')
            ->send();
    }

    protected function getViewData(): array
    {
        return [
            'recentPosts' => InstagramPost::with('template')->latest()->limit(8)->get(),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() === true;
    }
}
