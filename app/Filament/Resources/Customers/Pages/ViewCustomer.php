<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleActive')
                ->label(fn (): string => $this->record->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun')
                ->color(fn (): string => $this->record->is_active ? 'danger' : 'success')
                ->icon(fn (): string => $this->record->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->is_active = ! $this->record->is_active;
                    $this->record->save();

                    Notification::make()
                        ->title($this->record->is_active ? 'Akun diaktifkan' : 'Akun dinonaktifkan')
                        ->success()
                        ->send();
                }),
        ];
    }
}
