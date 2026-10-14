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
                    ->label('Nama Layanan')
                    ->placeholder('Contoh: Cuci Kiloan Reguler')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Deskripsi Layanan')
                    ->placeholder('Masukkan deskripsi layanan laundry')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('price')
                    ->label('Harga')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->placeholder('7000'),

                Select::make('unit')
                    ->label('Satuan Layanan')
                    ->options(ServiceUnit::class)
                    ->default('kg')
                    ->required(),

                TextInput::make('estimated_hours')
                    ->label('Estimasi Pengerjaan')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->default(24)
                    ->suffix('jam'),

                Toggle::make('is_active')
                    ->label('Layanan Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }
}
