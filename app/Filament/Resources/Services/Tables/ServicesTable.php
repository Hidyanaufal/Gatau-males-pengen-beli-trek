<?php

namespace App\Filament\Resources\Services\Tables;

use App\Enums\ServiceUnit;
use App\Models\Service;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Layanan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('unit')
                    ->label('Satuan')
                    ->formatStateUsing(fn ($state) => $state instanceof ServiceUnit ? $state->label() : $state),

                TextColumn::make('estimated_hours')
                    ->label('Estimasi')
                    ->suffix(' jam')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading('Hapus layanan?')
                    ->modalDescription('Layanan akan dihapus permanen. Layanan yang pernah dipakai pesanan tidak bisa dihapus, cukup nonaktifkan.')
                    ->hidden(fn (Service $record): bool => $record->orderItems()->exists()),
            ])
            ->defaultSort('name');
    }
}