<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Membuka invoice cetak (berisi QR) di tab baru
            Action::make('printInvoice')
                ->label('Cetak invoice')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('invoice.print', $this->record->getKey()))
                ->openUrlInNewTab(),

            Action::make('changeStatus')
                ->label('Ubah status')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => count($this->record->status->nextStatuses()) > 0)
                ->schema([
                    Select::make('status')
                        ->label('Status baru')
                        ->required()
                        ->options(fn () => collect($this->record->status->nextStatuses())
                            ->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])
                            ->all()),
                ])
                ->action(function (array $data): void {
                    $new = OrderStatus::from($data['status']);

                    if (! $this->record->status->canTransitionTo($new)) {
                        Notification::make()
                            ->title('Perubahan status tidak valid')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record->update(['status' => $new]);
                    $this->refreshFormData(['status']);

                    Notification::make()
                        ->title('Status pesanan diperbarui')
                        ->success()
                        ->send();
                }),
        ];
    }
}