<p>Hello {{ $contact->first_name }},</p>

<p>
    Your order with {{ $organization->name }} has been approved and is now
    ready for production processing.
</p>

<p>
    <strong>Order:</strong> {{ $order->order_number }}<br>
    <strong>Expected Delivery:</strong>
    {{ $order->expected_delivery_days }} calendar days<br>
    <strong>Expected Delivery Date:</strong>
    {{ $order->expected_delivery_date?->format('d M Y') ?? '—' }}
</p>

<p>
    You can use the link below to view and track the progress of your order:
</p>

<p>
    <a href="{{ $url }}">View & Track Your Order</a>
</p>

<p>
    Please keep this link for future order updates.
</p>

<p>
    Regards,<br>
    Oneyard Outfitters
</p>
