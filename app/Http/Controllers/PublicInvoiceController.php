<?php

namespace App\Http\Controllers;

use App\Models\Order;

class PublicInvoiceController extends Controller
{
    public function show(string $token)
    {
        // Token kita selalu 40 karakter; yang lain langsung dianggap tidak ada
        if (strlen($token) !== 40) {
            abort(404);
        }

        $order = Order::with('items')->where('public_token', $token)->first();

        if (! $order) {
            abort(404);
        }

        return view('invoice.public', ['order' => $order]);
    }
}