<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi pesanan')
                ->columns(2)
                ->schema([
                    TextEntry::make('order_number')
                        ->label('Nomor pesanan'),

                    TextEntry::make('user.name')
                        ->label('Pelanggan'),

                    TextEntry::make('status')
                        ->label('Status pesanan')
                        ->badge()
                        ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                        ->color(fn (OrderStatus $state) => self::statusColor($state)),

                    TextEntry::make('payment_status')
                        ->label('Status pembayaran')
                        ->badge()
                        ->formatStateUsing(fn (PaymentStatus $state) => $state->label())
                        ->color(fn (PaymentStatus $state) => self::paymentColor($state)),

                    TextEntry::make('fulfillment_type')
                        ->label('Jenis layanan')
                        ->formatStateUsing(fn ($state) => $state === 'pickup_delivery' ? 'Antar-jemput' : 'Datang sendiri'),

                    TextEntry::make('scheduled_at')
                        ->label('Jadwal')
                        ->dateTime('d M Y H:i')
                        ->placeholder('-'),

                    TextEntry::make('address')
                        ->label('Alamat')
                        ->placeholder('-')
                        ->columnSpanFull(),

                    TextEntry::make('notes')
                        ->label('Catatan')
                        ->placeholder('-')
                        ->columnSpanFull(),

                    TextEntry::make('ordered_at')
                        ->label('Waktu pemesanan')
                        ->dateTime('d M Y H:i'),

                    TextEntry::make('total')
                        ->label('Total')
                        ->money('IDR', locale: 'id'),
                ]),

            Section::make('Isi pesanan')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('service_name')->label('Layanan'),
                            TextEntry::make('unit_price')->label('Harga satuan')->money('IDR', locale: 'id'),
                            TextEntry::make('quantity')->label('Jumlah'),
                            TextEntry::make('subtotal')->label('Subtotal')->money('IDR', locale: 'id'),
                        ]),
                ]),
        ]);
    }

    public static function statusColor(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => 'gray',
            OrderStatus::Confirmed => 'info',
            OrderStatus::Processing => 'warning',
            OrderStatus::Ready => 'primary',
            OrderStatus::Completed => 'success',
            OrderStatus::Cancelled => 'danger',
        };
    }

    public static function paymentColor(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::Unpaid => 'gray',
            PaymentStatus::Pending => 'warning',
            PaymentStatus::Paid => 'success',
            PaymentStatus::Failed, PaymentStatus::Expired => 'danger',
        };
    }
}