<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Enums\ServiceUnit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama layanan')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('price')
                    ->label('Harga')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp'),

                Select::make('unit')
                    ->label('Satuan harga')
                    ->required()
                    ->default(ServiceUnit::Kg->value)
                    ->options(
                        collect(ServiceUnit::cases())
                            ->mapWithKeys(fn (ServiceUnit $unit) => [$unit->value => $unit->label()])
                            ->all()
                    ),

                TextInput::make('estimated_hours')
                    ->label('Estimasi pengerjaan')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->default(24)
                    ->suffix('jam'),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}