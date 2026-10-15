<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Pesanan terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Order::query()->with('user')->latest('ordered_at')->limit(5))
            ->columns([
                TextColumn::make('order_number')
                    ->label('No. pesanan'),

                TextColumn::make('user.name')
                    ->label('Pelanggan'),

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
                    ->money('IDR', locale: 'id'),

                TextColumn::make('ordered_at')
                    ->label('Waktu pesan')
                    ->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
