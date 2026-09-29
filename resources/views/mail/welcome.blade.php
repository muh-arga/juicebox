<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Welcome to Juicebox</title>
    <style>
        body,
        table,
        td,
        p,
        h1 {
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        body {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        @media only screen and (max-width: 620px) {
            .outer-padding {
                padding: 24px 16px !important;
            }

            .brand-padding {
                padding: 28px !important;
            }

            .content-padding {
                padding: 36px 28px 40px !important;
            }

            .welcome-heading {
                font-size: 36px !important;
                line-height: 42px !important;
            }
        }
    </style>
</head>

<body style="margin: 0; padding: 0; width: 100%; background-color: #f4f4f4; color: #000000;">
    <div
        style="display: none; font-size: 1px; line-height: 1px; color: #f4f4f4; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all;">
        Your account is ready. Welcome to Juicebox.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f4f4">
        <tr>
            <td class="outer-padding" align="center" style="padding: 56px 24px;">
                <!--[if mso]>
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td>
                <![endif]-->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="width: 100%; max-width: 600px; table-layout: fixed;">
                    <tr>
                        <td class="brand-padding" bgcolor="#000000"
                            style="padding: 32px 44px; background-color: #000000;">
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; font-size: 30px; line-height: 36px; font-weight: 700; letter-spacing: -1px; color: #ffffff;">
                                juicebox<span style="color: #ffffff;">.</span></p>
                        </td>
                    </tr>
                    <tr>
                        <td class="content-padding" bgcolor="#ffffff"
                            style="padding: 48px 44px 48px; background-color: #ffffff;">
                            <h1 class="welcome-heading"
                                style="margin: 0 0 28px; font-family: Georgia, 'Times New Roman', serif; font-size: 44px; line-height: 50px; font-weight: 400; letter-spacing: -1px; color: #000000; overflow-wrap: anywhere; word-wrap: break-word;">
                                Welcome,<br>{{ $user->name }}!
                            </h1>
                            <p
                                style="margin: 0 0 12px; font-family: Arial, Helvetica, sans-serif; font-size: 20px; line-height: 30px; font-weight: 700; color: #000000;">
                                Your account is ready.</p>
                            <p
                                style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 16px; line-height: 26px; color: #555555;">
                                Thank you for joining Juicebox.</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="margin-top: 36px;">
                                <tr>
                                    <td style="padding-top: 24px; border-top: 1px solid #dddddd;">
                                        <p
                                            style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 22px; color: #555555;">
                                            The Juicebox team</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 24px 16px 0;">
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 20px; color: #666666;">
                                Juicebox &middot; Account registration</p>
                        </td>
                    </tr>
                </table>
                <!--[if mso]>
                </td></tr></table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>

</html>