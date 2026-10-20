<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Measurements — Oneyard Outfitters</title>
</head>
<body style="margin:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#334155;">
    <div style="max-width:680px;margin:32px auto;padding:0 16px;">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:28px;">
            <img src="{{ asset('images/oneyard/oneyard_logo.png') }}" alt="Oneyard Outfitters" style="height:48px;width:auto;display:block;margin-bottom:20px;">
            <h1 style="margin:0 0 8px;font-size:24px;color:#0f172a;">Measurements for order {{ $order->order_number }}</h1>
            <p style="margin:0 0 20px;font-size:15px;line-height:1.7;">
                Hello {{ $contactName }}, {{ $organization->name }} needs to provide measurements for the items below.
                This link is personal to you and expires on {{ $expiresAt->format('j F Y') }}.
            </p>

            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <thead>
                    <tr style="text-align:left;color:#64748b;font-size:12px;text-transform:uppercase;">
                        <th style="padding:8px 0;">Item</th><th>Quantity</th><th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr style="border-top:1px solid #e2e8f0;">
                            <td style="padding:10px 0;color:#0f172a;font-weight:600;">{{ $item->item_name }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.') }}</td>
                            <td>{{ $item->unit }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p style="margin:24px 0 0;padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;line-height:1.6;">
                Measurement entry is being prepared. You will be able to submit measurements on this page shortly.
            </p>
        </div>
    </div>
</body>
</html>
