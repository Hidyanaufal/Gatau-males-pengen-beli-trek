<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('No. pesanan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Pelanggan')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                    ->color(fn (OrderStatus $state) => OrderInfolist::statusColor($state)),

                TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state) => $state->label())
                    ->color(fn (PaymentStatus $state) => OrderInfolist::paymentColor($state)),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('ordered_at')
                    ->label('Waktu pesan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status pesanan')
                    ->options(
                        collect(OrderStatus::cases())
                            ->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])
                            ->all()
                    ),

                SelectFilter::make('payment_status')
                    ->label('Status pembayaran')
                    ->options(
                        collect(PaymentStatus::cases())
                            ->mapWithKeys(fn (PaymentStatus $s) => [$s->value => $s->label()])
                            ->all()
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('ordered_at', 'desc');
    }
}