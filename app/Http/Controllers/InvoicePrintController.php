<?php

namespace App\Http\Controllers;

use App\Models\Order;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Filament\Facades\Filament;

class InvoicePrintController extends Controller
{
    public function show(Order $order)
    {
        // Hanya admin yang boleh membuka invoice (sama dengan aturan masuk panel admin)
        $user = auth()->user();
        abort_unless($user && $user->canAccessPanel(Filament::getDefaultPanel()), 403);

        $order->load(['items', 'user']);

        // Isi QR = alamat halaman publik pesanan. Token tidak pernah berubah,
        // jadi QR di kertas selalu sama untuk pesanan yang sama.
        $url = route('invoice.public', $order->public_token);

        $writer = new Writer(
            new ImageRenderer(new RendererStyle(220, 2), new SvgImageBackEnd())
        );

        // Buang deklarasi XML di awal supaya SVG bisa ditempel langsung di HTML
        $qrSvg = preg_replace('/^<\?xml.*?\?>\s*/', '', $writer->writeString($url));

        return view('invoice.print', [
            'order' => $order,
            'qrSvg' => $qrSvg,
            'qrUrl' => $url,
        ]);
    }
}