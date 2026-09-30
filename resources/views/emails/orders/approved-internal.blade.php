<p>Hello,</p>

<p>
    Order <strong>{{ $order->order_number }}</strong> for
    <strong>{{ $organization->name }}</strong> has been approved.
</p>

<p>
    <strong>Expected Delivery:</strong>
    {{ $order->expected_delivery_days }} calendar days<br>
    <strong>Expected Delivery Date:</strong>
    {{ $order->expected_delivery_date?->format('d M Y') ?? '—' }}
</p>

<p>
    <a href="{{ $url }}">View Order</a>
</p>

<p>
    Regards,<br>
    Oneyard Outfitters
</p>
