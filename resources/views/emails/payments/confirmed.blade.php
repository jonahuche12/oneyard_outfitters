<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Confirmed</title>
</head>
<body>
    <h1>Payment Confirmed</h1>

    <p>Dear Customer,</p>

    <p>
        We have successfully confirmed your payment for quotation
        <strong>{{ $quotation->quotation_number }}</strong>.
    </p>

    <p>
        Payment amount:
        <strong>₦{{ number_format((float) $payment->amount, 2) }}</strong>
    </p>

    <p>
        Order number:
        <strong>{{ $order?->order_number ?? 'Processing' }}</strong>
    </p>

    <p>
        Your quotation has been accepted and the order has been created.
        Our team will proceed with the next stage of fulfillment.
    </p>

    <p>
        Thank you,<br>
        Oneyard Outfitters
    </p>
</body>
</html>
