<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Update — Oneyard Outfitters</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;color:#334155;line-height:1.7;padding:24px">
    <p>Hello {{ $withdrawal->user->name }},</p>

    <h2>Withdrawal request update</h2>

    <p>
        Your withdrawal request for
        <strong>₦{{ number_format((float) $withdrawal->amount, 2) }}</strong>
        is now <strong>{{ ucfirst($withdrawal->status) }}</strong>.
    </p>

    @if($withdrawal->status === 'rejected')
        <p><strong>Reason:</strong> {{ $withdrawal->reject_reason }}</p>
    @elseif($withdrawal->status === 'paid')
        <p><strong>Payment reference:</strong> {{ $withdrawal->payment_reference }}</p>
        <p>Your payment has been recorded by Oneyard Outfitters.</p>
    @elseif($withdrawal->status === 'approved')
        <p>Your request has been approved and is awaiting payment processing.</p>
    @endif

    <p><a href="{{ $walletUrl }}">View your wallet and withdrawal history</a></p>
    <p>Oneyard Outfitters</p>
</body>
</html>
