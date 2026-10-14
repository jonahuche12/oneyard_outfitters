<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Account Setup — Oneyard Outfitters</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#334155;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0;padding:32px 16px;background-color:#f1f5f9;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:620px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">

                <tr>
                    <td style="padding:28px 32px;border-bottom:1px solid #e2e8f0;background-color:#ffffff;text-align:left;">
                        <img
                            src="{{ asset('images/oneyard/oneyard_logo.png') }}"
                            alt="Oneyard Outfitters"
                            style="display:block;width:auto;height:52px;max-width:210px;border:0;outline:none;text-decoration:none;"
                        >
                    </td>
                </tr>

                <tr>
                    <td style="padding:36px 32px 12px 32px;">
                        <div style="display:inline-block;margin-bottom:14px;padding:7px 12px;background-color:#eff6ff;color:#1d4ed8;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;">
                            Staff Invitation
                        </div>
                        <h1 style="margin:0;color:#0f172a;font-size:27px;line-height:1.3;font-weight:700;">
                            Welcome to Oneyard Outfitters
                        </h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:18px 32px 36px 32px;">
                        <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
                            Hello {{ $staff->name }},
                        </p>

                        <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
                            A staff account has been created for you. To get started, set your own password using the secure link below.
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 28px;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;">
                            <tr>
                                <td style="padding:18px 20px;">
                                    <p style="margin:0 0 6px;font-size:11px;line-height:1.4;color:#64748b;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">
                                        Next Step
                                    </p>
                                    <p style="margin:0;font-size:15px;line-height:1.7;color:#0f172a;font-weight:700;">
                                        Create your password to activate your account
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#334155;">
                            Select the button below to securely set your password.
                        </p>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td align="left">
                                    <a href="{{ $setupUrl }}"
                                       style="display:inline-block;padding:14px 24px;background-color:#0f172a;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;line-height:1.3;border-radius:8px;border:1px solid #0f172a;">
                                        Set Up Your Password
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:26px 0 14px;font-size:13px;line-height:1.6;color:#64748b;">
                            This one-use password setup link expires in 24 hours. If it expires before you complete setup, contact your Oneyard administrator to request a new invitation.
                        </p>

                        <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
                            Your account will become active after you set your password, and you can then sign in. If you were not expecting this invitation, contact your Oneyard administrator.
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
