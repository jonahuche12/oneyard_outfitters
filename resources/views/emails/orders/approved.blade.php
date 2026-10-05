<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Approved — Oneyard Outfitters</title>
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
                        <div style="display:inline-block;margin-bottom:14px;padding:7px 12px;background-color:#ecfdf5;color:#047857;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;">
                            Order Update
                        </div>

                        <h1 style="margin:0;font-size:27px;line-height:1.3;color:#0f172a;font-weight:700;">
                            Your Order Has Been Approved
                        </h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:18px 32px 36px 32px;">

                        <p style="margin:0 0 18px 0;font-size:15px;line-height:1.7;">
                            Hello {{ $contact->first_name }},
                        </p>

                        <p style="margin:0 0 24px 0;font-size:15px;line-height:1.7;">
                            Your order with <strong style="color:#0f172a;">{{ $organization->name }}</strong> has been approved and is now ready for production processing.
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;">
                            <tr>
                                <td style="padding:18px 20px;border-bottom:1px solid #e2e8f0;">
                                    <span style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Order</span><br>
                                    <strong style="font-size:16px;color:#0f172a;line-height:1.8;">{{ $order->order_number }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:18px 20px;border-bottom:1px solid #e2e8f0;">
                                    <span style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Expected Delivery</span><br>
                                    <strong style="font-size:15px;color:#0f172a;line-height:1.8;">{{ $order->expected_delivery_days }} calendar days</strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:18px 20px;">
                                    <span style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Expected Delivery Date</span><br>
                                    <strong style="font-size:15px;color:#0f172a;line-height:1.8;">{{ $order->expected_delivery_date?->format('d M Y') ?? '—' }}</strong>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 20px 0;font-size:15px;line-height:1.7;">
                            You can view and track your order using the button below.
                        </p>

                        <a
                            href="{{ $url }}"
                            style="display:inline-block;padding:14px 24px;background-color:#0f172a;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;line-height:1.3;border-radius:8px;border:1px solid #0f172a;"
                        >
                            View &amp; Track Your Order
                        </a>

                        <p style="margin:26px 0 0 0;font-size:13px;line-height:1.6;color:#64748b;">
                            Please keep this email for future order updates.
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
