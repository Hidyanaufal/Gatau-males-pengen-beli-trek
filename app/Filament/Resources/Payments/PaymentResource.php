<?php

namespace App\Filament\Resources\Payments;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $modelLabel = 'Pembayaran';

    protected static ?string $pluralModelLabel = 'Pembayaran';

    protected static ?string $navigationLabel = 'Pembayaran';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_number')
                    ->label('No. pesanan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('method')
                    ->label('Metode')
                    ->placeholder('-'),

                TextColumn::make('provider')
                    ->label('Penyedia')
                    ->placeholder('-'),

                TextColumn::make('provider_transaction_id')
                    ->label('ID transaksi')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state) => $state->label())
                    ->color(fn (PaymentStatus $state) => OrderInfolist::paymentColor($state)),

                TextColumn::make('paid_at')
                    ->label('Dibayar pada')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status pembayaran')
                    ->options(
                        collect(PaymentStatus::cases())
                            ->mapWithKeys(fn (PaymentStatus $s) => [$s->value => $s->label()])
                            ->all()
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
        ];
    }
}
