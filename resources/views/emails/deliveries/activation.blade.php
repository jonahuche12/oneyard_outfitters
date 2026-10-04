<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Action Required</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">

    <table
        role="presentation"
        width="100%"
        cellpadding="0"
        cellspacing="0"
        border="0"
        style="width: 100%; margin: 0; padding: 32px 16px; background-color: #f4f6f8;"
    >
        <tr>
            <td align="center">

                <table
                    role="presentation"
                    width="100%"
                    cellpadding="0"
                    cellspacing="0"
                    border="0"
                    style="width: 100%; max-width: 600px; background-color: #ffffff; border-radius: 10px; overflow: hidden;"
                >
                    <tr>
                        <td style="padding: 32px 36px 20px 36px; text-align: center; border-bottom: 1px solid #e5e7eb;">

                            <div
                                style="display: inline-block; margin-bottom: 18px; padding: 8px 14px; background-color: #ecfdf5; color: #047857; border-radius: 999px; font-size: 12px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;"
                            >
                                Delivery Update
                            </div>

                            <h1
                                style="margin: 0; font-size: 26px; line-height: 1.3; color: #111827; font-weight: 700;"
                            >
                                Your Delivery Is Ready
                            </h1>

                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 30px 36px 36px 36px;">

                            <p style="margin: 0 0 18px 0; font-size: 16px; line-height: 1.7;">
                                Hello {{ $contact->first_name }},
                            </p>

                            <p style="margin: 0 0 18px 0; font-size: 16px; line-height: 1.7;">
                                Your order with
                                <strong>{{ $organization->name }}</strong>
                                has reached the delivery stage.
                            </p>

                            <p style="margin: 0 0 24px 0; font-size: 16px; line-height: 1.7;">
                                Please take a moment to confirm your delivery arrangements so we can proceed with the next step.
                            </p>

                            <table
                                role="presentation"
                                width="100%"
                                cellpadding="0"
                                cellspacing="0"
                                border="0"
                                style="margin: 0 0 28px 0; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;"
                            >
                                <tr>
                                    <td style="padding: 18px 20px;">

                                        <p style="margin: 0 0 6px 0; font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.4px;">
                                            Order
                                        </p>

                                        <p style="margin: 0; font-size: 17px; font-weight: 700; color: #111827;">
                                            {{ $order->order_number }}
                                        </p>

                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 28px 0; font-size: 16px; line-height: 1.7;">
                                <strong>Action is required to activate the delivery.</strong>
                                Please use the button below to continue.
                            </p>

                            <table
                                role="presentation"
                                cellpadding="0"
                                cellspacing="0"
                                border="0"
                                width="100%"
                            >
                                <tr>
                                    <td align="center">

                                        <a
                                            href="{{ $url }}"
                                            style="display: inline-block; padding: 15px 30px; background-color: #047857; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; line-height: 1.2; border-radius: 7px; border: 1px solid #047857;"
                                        >
                                            Activate Delivery
                                        </a>

                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 28px 0 0 0; font-size: 13px; line-height: 1.6; color: #6b7280; text-align: center;">
                                Please complete this step promptly to avoid delays in your delivery.
                            </p>

                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 20px 36px; background-color: #f9fafb; border-top: 1px solid #e5e7eb;">

                            <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #6b7280; text-align: center;">
                                Regards,<br>
                                <strong style="color: #374151;">Oneyard Outfitters</strong>
                            </p>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
