<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.welcome_title') }} | Alfa Beauty</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #FAFAFA; color: #0A0A0A;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #FAFAFA;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; background: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <tr>
                        <td style="background: #0A0A0A; padding: 32px 40px; text-align: center;">
                            <span style="font-family: Georgia, serif; font-size: 24px; color: #FFFFFF; letter-spacing: 0.02em;">Alfa Beauty</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="height: 3px; background: #C9A962;"></td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <h1 style="margin: 0 0 24px; font-family: Georgia, serif; font-size: 28px; font-weight: 400; color: #0A0A0A;">{{ __('emails.welcome_title') }} 🎉</h1>
                            
                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #4B5563;">{{ __('emails.welcome_greeting') }} <strong>{{ $user->name }}</strong>,</p>
                            
                            <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #4B5563;">{{ __('emails.welcome_message') }}</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #F9FAFB; border-radius: 6px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px solid #E5E7EB;">
                                                    <span style="font-size: 13px; color: #6B7280;">{{ __('emails.welcome_name') }}</span><br>
                                                    <span style="font-size: 15px; font-weight: 500; color: #0A0A0A;">{{ $user->name }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px solid #E5E7EB;">
                                                    <span style="font-size: 13px; color: #6B7280;">{{ __('emails.welcome_company') }}</span><br>
                                                    <span style="font-size: 15px; font-weight: 500; color: #0A0A0A;">{{ $user->company_name ?? '-' }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px solid #E5E7EB;">
                                                    <span style="font-size: 13px; color: #6B7280;">{{ __('emails.welcome_email') }}</span><br>
                                                    <span style="font-size: 15px; font-weight: 500; color: #0A0A0A;">{{ $user->email }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <span style="font-size: 13px; color: #6B7280;">{{ __('emails.welcome_membership') }}</span><br>
                                                    <span style="font-size: 15px; font-weight: 500; color: #C9A962;">{{ $user->loyaltyTier?->name ?? 'Guest' }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <h3 style="margin: 0 0 16px; font-size: 16px; font-weight: 600; color: #0A0A0A;">{{ __('emails.welcome_benefits') }}</h3>
                            
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr><td style="padding: 6px 0; font-size: 14px; color: #4B5563;">✨ {{ __('emails.welcome_benefit_1') }}</td></tr>
                                <tr><td style="padding: 6px 0; font-size: 14px; color: #4B5563;">🎁 {{ __('emails.welcome_benefit_2') }}</td></tr>
                                <tr><td style="padding: 6px 0; font-size: 14px; color: #4B5563;">🚚 {{ __('emails.welcome_benefit_3') }}</td></tr>
                                <tr><td style="padding: 6px 0; font-size: 14px; color: #4B5563;">📦 {{ __('emails.welcome_benefit_4') }}</td></tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 8px 0;">
                                        <a href="{{ route('products.index') }}" style="display: inline-block; padding: 14px 32px; background: #0A0A0A; color: #FFFFFF; text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 4px;">{{ __('emails.welcome_cta') }} →</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; background: #F9FAFB; border-top: 1px solid #E5E7EB;">
                            <p style="margin: 0 0 8px; font-size: 14px; color: #4B5563;">{{ __('emails.welcome_closing') }},<br><strong>{{ __('emails.team_name') }}</strong></p>
                            <p style="margin: 0; font-size: 12px; color: #9CA3AF;">© {{ date('Y') }} Alfa Beauty. {{ __('emails.rights_reserved') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
