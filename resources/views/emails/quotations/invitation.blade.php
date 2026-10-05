<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Invitation — Oneyard Outfitters</title>
</head>

<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#334155;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;padding:32px 16px;background-color:#f1f5f9;">
    <tr>
        <td align="center">

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:620px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">

                <tr>
                    <td style="padding:28px 32px;border-bottom:1px solid #e2e8f0;">
                        <img
                            src="{{ asset('images/oneyard/oneyard_logo.png') }}"
                            alt="Oneyard Outfitters"
                            style="display:block;height:52px;width:auto;max-width:210px;border:0;"
                        >
                    </td>
                </tr>

                <tr>
                    <td style="padding:36px 32px 12px 32px;">
                        <div style="display:inline-block;margin-bottom:14px;padding:7px 12px;background-color:#eff6ff;color:#1d4ed8;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;">
                            Quotation
                        </div>

                        <h1 style="margin:0;font-size:27px;line-height:1.3;color:#0f172a;font-weight:700;">
                            Your Quotation Is Ready
                        </h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:18px 32px 36px 32px;">

                        <p style="margin:0 0 18px 0;font-size:15px;line-height:1.7;">
                            Hello {{ $recipient->contact->first_name }},
                        </p>

                        <p style="margin:0 0 18px 0;font-size:15px;line-height:1.7;">
                            You have received quotation
                            <strong style="color:#0f172a;">{{ $quotation->quotation_number }}</strong>
                            from Oneyard Outfitters.
                        </p>

                        <p style="margin:0 0 24px 0;font-size:15px;line-height:1.7;">
                            Please review the quotation using the secure link below.
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;">
                            <tr>
                                <td style="padding:18px 20px;">
                                    <span style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Quotation Total</span><br>
                                    <strong style="font-size:19px;color:#0f172a;line-height:1.8;">₦{{ number_format((float) $quotation->total, 2) }}</strong>
                                </td>
                            </tr>
                        </table>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td>
                                    <a
                                        href="{{ $url }}"
                                        style="display:inline-block;padding:14px 24px;background-color:#0f172a;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;line-height:1.3;border-radius:8px;border:1px solid #0f172a;"
                                    >
                                        Review Quotation
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:26px 0 0 0;font-size:13px;line-height:1.6;color:#64748b;">
                            Please review the quotation carefully before responding.
                        </p>

                    </td>
                </tr>

                <tr>
                    <td style="padding:22px 32px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;">
                            Regards,<br>
                            <strong style="color:#0f172a;">Oneyard Outfitters</strong>
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
