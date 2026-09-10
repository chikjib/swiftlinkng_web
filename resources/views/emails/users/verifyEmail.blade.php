<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Verify your Swiftlink email</title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-shell { width: 100% !important; }
            .mobile-padding { padding-left: 22px !important; padding-right: 22px !important; }
            .hero-title { font-size: 30px !important; line-height: 38px !important; }
            .verify-button { display: inline-block !important; width: auto !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; width:100%; background:#f7f3f4; color:#2b2022; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; -webkit-text-size-adjust:100%;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;">
        Verify your email to unlock Swiftlink referral rewards and protect your account.
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; background:#f7f3f4;">
        <tr>
            <td align="center" style="padding:34px 14px;">
                <table class="email-shell" role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px; max-width:600px; background:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 18px 55px rgba(75,15,25,.13);">
                    <tr>
                        <td align="center" style="padding:16px 30px; background:#ffffff; border-bottom:1px solid #f4e7e9;">
                            <a href="{{ config('app.siteUrl', config('app.url')) }}" style="display:inline-block; text-decoration:none;">
                                <img src="{{ asset('frontend/images/swiftlogo.png') }}" width="82" height="82" alt="Swiftlinkng" style="display:block; width:82px; height:82px; border:0; outline:none; text-decoration:none; object-fit:contain;">
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-padding" align="center" bgcolor="#d20b2d" style="padding:44px 48px 38px; background-color:#d20b2d; background-image:linear-gradient(145deg,#a90020 0%,#d70b2d 55%,#f04459 100%); color:#ffffff;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" style="width:72px; height:72px; border-radius:50%; background:#ffffff; color:#d20b2d; font-size:35px; font-weight:700; box-shadow:0 10px 25px rgba(85,0,15,.25);">
                                        &#9993;
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:24px 0 8px; color:#ffdce2; font-size:12px; font-weight:800; letter-spacing:2px; text-transform:uppercase;">One quick step</p>
                            <h1 class="hero-title" style="margin:0; color:#ffffff; font-size:38px; line-height:46px; font-weight:850; letter-spacing:-1px;">Verify your email</h1>
                            <p style="margin:14px 0 0; color:#fff4f6; font-size:16px; line-height:25px;">Secure your account and become eligible for Swiftlink referral rewards.</p>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-padding" style="padding:40px 48px 15px;">
                            <p style="margin:0 0 18px; color:#2b2022; font-size:18px; line-height:28px;">Hi {{ $firstname }},</p>
                            <p style="margin:0; color:#66575a; font-size:16px; line-height:27px;">
                                Welcome to {{ config('app.siteName', 'Swiftlink') }}. Click the button below to confirm that this email address belongs to you.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:30px 0;">
                                <tr>
                                    <td class="button-cell" align="center">
                                        <a class="verify-button" href="{{ $verificationUrl }}" style="display:inline-block; width:auto; padding:14px 24px; border-radius:10px; background:#d20b2d; color:#ffffff; font-size:16px; line-height:20px; font-weight:800; text-align:center; text-decoration:none; white-space:nowrap; box-shadow:0 8px 20px rgba(210,11,45,.23);">
                                            Verify my email
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 26px; border:1px solid #f2dfb2; border-radius:14px; background:#fff9e9;">
                                <tr>
                                    <td style="padding:20px; color:#5d4818;">
                                        <p style="margin:0 0 6px; font-size:16px; font-weight:800;">&#127873; Verification unlocks your rewards</p>
                                        <p style="margin:0; font-size:14px; line-height:23px;">
                                            You may continue using Swiftlink without verifying, but the &#8358;50 welcome bonus and other fixed referral rewards remain unavailable until verification is completed.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 12px; color:#7b6d70; font-size:13px; line-height:21px;">
                                This secure link expires in <strong>24 hours</strong>. If the button does not work, copy and paste this address into your browser:
                            </p>
                            <p style="margin:0; padding:13px 15px; border-radius:9px; background:#f7f3f4; color:#b20b27; font-size:12px; line-height:19px; word-break:break-all;">
                                <a href="{{ $verificationUrl }}" style="color:#b20b27; text-decoration:underline;">{{ $verificationUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-padding" style="padding:20px 48px 40px;">
                            <p style="margin:0; color:#8a7d80; font-size:13px; line-height:22px;">
                                If you did not create a Swiftlink account, you can safely ignore this email. No action will be taken.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:26px 30px; background:#271e20; color:#cfc4c6;">
                            <p style="margin:0 0 7px; color:#ffffff; font-size:14px; font-weight:700;">Swiftlink Services</p>
                            <p style="margin:0; font-size:12px; line-height:19px;">Fast, reliable digital services whenever you need them.</p>
                            <p style="margin:12px 0 0; font-size:11px;">&copy; {{ $year }} {{ config('app.siteName', 'Swiftlink') }}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
