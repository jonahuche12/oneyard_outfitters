<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Measurements — Oneyard Outfitters</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#334155;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0;padding:32px 16px;background-color:#f1f5f9;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:620px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="padding:28px 32px;border-bottom:1px solid #e2e8f0;">
                        <img src="{{ asset('images/oneyard/oneyard_logo.png') }}" alt="Oneyard Outfitters" style="display:block;width:auto;height:52px;max-width:210px;border:0;">
                    </td>
                </tr>
                <tr>
                    <td style="padding:36px 32px 12px 32px;">
                        <h1 style="margin:0;color:#0f172a;font-size:25px;line-height:1.3;font-weight:700;">{{ $correctionReason ? 'Please correct your measurements' : 'We need your measurements' }}</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 32px 36px 32px;">
                        <p style="margin:0 0 18px;font-size:15px;line-height:1.7;">Hello {{ trim($contact->first_name . ' ' . $contact->last_name) }},</p>
                        <p style="margin:0 0 18px;font-size:15px;line-height:1.7;">
                            To begin production of order <strong>{{ $order->order_number }}</strong>, please provide the measurements for the garments on this order using the secure link below.
                        </p>
                        @if($correctionReason)
                        <p style="margin:0 0 18px;font-size:15px;line-height:1.7;"><strong>What needs correcting:</strong><br>{!! nl2br(e($correctionReason)) !!}</p>
                        <p style="margin:0 0 18px;font-size:15px;line-height:1.7;">Your previous entries have been cleared so you can start fresh. Open the link and enter your measurements again. You can enter each person's measurements, upload a spreadsheet, or choose our commercial sizes.</p>
                        @endif
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td align="left">
                                    <a href="{{ $measurementUrl }}" style="display:inline-block;padding:14px 24px;background-color:#0f172a;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;border-radius:8px;">
                                        Provide Measurements
                                    </a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:26px 0 14px;font-size:13px;line-height:1.6;color:#64748b;">
                            This link is personal to you and expires on {{ $expiresAt->format('j F Y') }}. Please do not forward it.
                        </p>
                        <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
                            If you were not expecting this message, you can ignore it or contact Oneyard Outfitters.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px 32px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;">Regards,<br><strong style="color:#0f172a;">Oneyard Outfitters</strong></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
