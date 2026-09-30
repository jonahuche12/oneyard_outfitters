<?php

namespace App\Http\Controllers;

use App\Models\OrderNotificationRecipient;
use Illuminate\View\View;

class PublicOrderController extends Controller
{
    public function show(string $token): View
    {
        $recipient = OrderNotificationRecipient::query()
            ->where('access_token', $token)
            ->firstOrFail();

        $recipient->load([
            'contact',
            'order.organization',
            'order.items.productSpecification',
        ]);

        return view('public.orders.show', [
            'recipient' => $recipient,
            'order' => $recipient->order,
        ]);
    }
}
