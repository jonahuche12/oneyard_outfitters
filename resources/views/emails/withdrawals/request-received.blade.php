<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Request Received — Oneyard Outfitters</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#334155;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="width:100%;padding:32px 16px;background-color:#f1f5f9;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="width:100%;max-width:620px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">

                    <tr>
                        <td style="padding:28px 32px;border-bottom:1px solid #e2e8f0;background-color:#ffffff;">
                            <img src="{{ asset('images/oneyard/oneyard_logo.png') }}"
                                 alt="Oneyard Outfitters"
                                 width="180"
                                 style="display:block;width:180px;max-width:100%;height:auto;border:0;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <div style="display:inline-block;margin-bottom:14px;padding:7px 12px;background-color:#ecfdf5;color:#047857;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;">
                                Request Submitted
                            </div>

                            <h1 style="margin:0 0 20px 0;font-size:27px;line-height:1.3;color:#0f172a;font-weight:700;">
                                Your withdrawal request was received
                            </h1>

                            <p style="margin:0 0 18px 0;font-size:15px;line-height:1.7;color:#334155;">
                                Hello {{ $name }},
                            </p>

                            <p style="margin:0 0 22px 0;font-size:15px;line-height:1.7;color:#334155;">
                                Your withdrawal request has been submitted successfully and is now being processed.
                                Thank you for your patience while we review and process your request.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                   style="margin:0 0 24px 0;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <span style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">
                                            Withdrawal Amount
                                        </span>
                                        <br>
                                        <strong style="font-size:22px;color:#0f172a;line-height:1.8;">
                                            ₦{{ number_format((float) $amount, 2) }}
                                        </strong>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                   style="margin:0 0 24px 0;background-color:#eff6ff;border:1px solid #bfdbfe;border-radius:9px;">
                                <tr>
                                    <td style="padding:17px 20px;">
                                        <p style="margin:0 0 7px 0;font-size:14px;line-height:1.5;font-weight:700;color:#1e40af;">
                                            Expected processing timeframe
                                        </p>
                                        <p style="margin:0;font-size:14px;line-height:1.7;color:#1e3a8a;">
                                            If processing is completed successfully, you should receive your funds within
                                            <strong>48 hours</strong>. This timeframe is subject to successful processing.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 24px 0;font-size:15px;line-height:1.7;color:#334155;">
                                You can visit your wallet to review your withdrawal history and check for status updates.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" bgcolor="#0f172a" style="border-radius:8px;">
                                        <a href="{{ $walletUrl }}"
                                           style="display:inline-block;padding:14px 24px;background-color:#0f172a;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;line-height:1.3;border-radius:8px;border:1px solid #0f172a;">
                                            View My Wallet
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:26px 0 0 0;font-size:13px;line-height:1.7;color:#64748b;">
                                If you have questions about this request, please contact an Oneyard Outfitters administrator.
                                If you did not make this request, notify an administrator promptly.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px 32px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;line-height:1.7;color:#64748b;">
                                This is an automated account notification. Please do not reply directly to this email.
                                <br>
                                <strong style="color:#0f172a;">Oneyard Outfitters</strong>
                                <br>
                                Institutional Supply &amp; Uniform Management
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
