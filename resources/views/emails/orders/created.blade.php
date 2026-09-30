<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Order Created</title>
</head>
<body>
    <h1>New Order Created</h1>

    <p>A new order has been created and is ready for internal processing.</p>

    <p>
        <strong>Order:</strong> {{ $order->order_number }}<br>
        <strong>Organization:</strong> {{ $organization->name }}<br>
        <strong>Quotation:</strong> {{ $quotation->quotation_number }}<br>
        <strong>Amount Paid:</strong>
        ₦{{ number_format((float) ($payment?->amount ?? 0), 2) }}<br>
        <strong>Order Total:</strong>
        ₦{{ number_format((float) $order->total, 2) }}
    </p>

    <p>
        The order can now proceed through the internal fulfillment workflow.
    </p>

    <p>
        Oneyard Outfitters
    </p>
</body>
</html>
