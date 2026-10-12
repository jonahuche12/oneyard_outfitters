<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procurement Offer Accepted — Oneyard Outfitters</title>
</head>
<body style="margin:0;padding:24px 12px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#334155;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="max-width:620px;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;">
                    <tr>
                        <td style="padding:28px 30px;border-bottom:1px solid #e2e8f0;">
                            <img src="{{ asset('images/oneyard/oneyard_logo.png') }}"
                                 alt="Oneyard Outfitters"
                                 style="display:block;height:48px;width:auto;max-width:210px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px;">
                            <p style="margin:0 0 10px;color:#64748b;font-size:12px;font-weight:bold;letter-spacing:.6px;text-transform:uppercase;">
                                Procurement Update
                            </p>
                            <h1 style="margin:0 0 18px;color:#0f172a;font-size:25px;line-height:1.3;">
                                Your offer has been accepted
                            </h1>
                            <p style="font-size:15px;line-height:1.7;">
                                Hello {{ $staff->name }},
                            </p>
                            <p style="font-size:15px;line-height:1.7;">
                                Your procurement offer has been accepted by Oneyard Outfitters.
                                Please review the details below.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                   style="margin:22px 0;background:#f8fafc;border:1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding:14px 16px;border-bottom:1px solid #e2e8f0;">
                                        <span style="font-size:12px;color:#64748b;">Item</span><br>
                                        <strong style="color:#0f172a;">{{ $procurement->item_name }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 16px;border-bottom:1px solid #e2e8f0;">
                                        <span style="font-size:12px;color:#64748b;">Accepted quantity</span><br>
                                        <strong>{{ rtrim(rtrim(number_format((float) $offer->quantity, 2, '.', ''), '0'), '.') }} {{ $procurement->unit }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 16px;border-bottom:1px solid #e2e8f0;">
                                        <span style="font-size:12px;color:#64748b;">Unit price</span><br>
                                        <strong>₦{{ number_format((float) $offer->unit_price, 2) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 16px;border-bottom:1px solid #e2e8f0;">
                                        <span style="font-size:12px;color:#64748b;">Total offer value</span><br>
                                        <strong style="font-size:18px;color:#0f172a;">₦{{ number_format((float) $offer->total_price, 2) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <span style="font-size:12px;color:#64748b;">Required by</span><br>
                                        <strong>{{ $procurement->required_by?->format('d M Y') ?? 'Not specified' }}</strong>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:14px;line-height:1.7;">
                                Please review the procurement details and follow any instructions from the Oneyard team.
                            </p>

                            <p style="margin:26px 0;">
                                <a href="{{ $url }}"
                                   style="display:inline-block;padding:13px 20px;background:#0f172a;color:#ffffff;text-decoration:none;font-weight:bold;border-radius:7px;">
                                    View Procurement
                                </a>
                            </p>

                            <p style="font-size:13px;line-height:1.7;color:#64748b;">
                                Regards,<br><strong style="color:#0f172a;">Oneyard Outfitters</strong>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
