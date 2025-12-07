<?php

namespace App\Filament\Resources\InstagramPostResource\Pages;

use App\Filament\Resources\InstagramPostResource;
use App\Models\InstagramPost;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditInstagramPost extends EditRecord
{
    protected static string $resource = InstagramPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('schedulePublish')
                ->label('Schedule publish')
                ->icon('heroicon-m-clock')
                ->color('primary')
                ->form([
                    DateTimePicker::make('scheduled_at')
                        ->label('Jadwalkan pada')
                        ->seconds(false)
                        ->default(Carbon::now()->addHour())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var InstagramPost $record */
                    $record = $this->getRecord();

                    $record->update([
                        'scheduled_at' => $data['scheduled_at'],
                        'status' => InstagramPost::STATUS_SCHEDULED,
                    ]);

                    Notification::make()
                        ->title('Postingan dijadwalkan')
                        ->success()
                        ->send();
                }),
        ];
    }
}
