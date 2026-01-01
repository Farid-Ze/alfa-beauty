<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.order_confirmed') }} #{{ $order->order_number }} | Alfa Beauty</title>
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
                            <h1 style="margin: 0 0 24px; font-family: Georgia, serif; font-size: 26px; font-weight: 400; color: #0A0A0A;">{{ __('emails.order_confirmed') }} ✓</h1>
                            
                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #4B5563;">{{ __('emails.order_greeting') }} <strong>{{ $order->user->name }}</strong>,</p>
                            
                            <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #4B5563;">{{ __('emails.order_message') }}</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #F9FAFB; border-radius: 6px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px solid #E5E7EB;">
                                                    <span style="font-size: 13px; color: #6B7280;">{{ __('emails.order_number') }}</span><br>
                                                    <span style="font-size: 15px; font-weight: 600; color: #C9A962;">{{ $order->order_number }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px solid #E5E7EB;">
                                                    <span style="font-size: 13px; color: #6B7280;">{{ __('emails.order_date') }}</span><br>
                                                    <span style="font-size: 15px; font-weight: 500; color: #0A0A0A;">{{ $order->created_at->format('d M Y, H:i') }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px solid #E5E7EB;">
                                                    <span style="font-size: 13px; color: #6B7280;">{{ __('emails.order_status') }}</span><br>
                                                    <span style="font-size: 15px; font-weight: 500; color: #0A0A0A;">{{ ucfirst($order->status) }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <span style="font-size: 13px; color: #6B7280;">{{ __('emails.order_payment') }}</span><br>
                                                    <span style="font-size: 15px; font-weight: 500; color: #0A0A0A;">{{ $order->payment_method ?? 'Transfer Bank' }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <h3 style="margin: 0 0 12px; font-size: 15px; font-weight: 600; color: #0A0A0A;">{{ __('emails.order_products') }}</h3>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 16px;">
                                <tr style="background: #F9FAFB;">
                                    <td style="padding: 10px 12px; font-size: 12px; font-weight: 600; color: #6B7280; border-bottom: 1px solid #E5E7EB;">{{ __('emails.order_product') }}</td>
                                    <td style="padding: 10px 12px; font-size: 12px; font-weight: 600; color: #6B7280; text-align: center; border-bottom: 1px solid #E5E7EB;">{{ __('emails.order_qty') }}</td>
                                    <td style="padding: 10px 12px; font-size: 12px; font-weight: 600; color: #6B7280; text-align: right; border-bottom: 1px solid #E5E7EB;">{{ __('emails.order_price') }}</td>
                                </tr>
                                @foreach($order->orderItems as $item)
                                <tr>
                                    <td style="padding: 12px; font-size: 14px; color: #0A0A0A; border-bottom: 1px solid #E5E7EB;">{{ $item->product->name }}</td>
                                    <td style="padding: 12px; font-size: 14px; color: #4B5563; text-align: center; border-bottom: 1px solid #E5E7EB;">{{ $item->quantity }}</td>
                                    <td style="padding: 12px; font-size: 14px; color: #0A0A0A; text-align: right; border-bottom: 1px solid #E5E7EB;">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 6px 12px; font-size: 14px; color: #6B7280; text-align: right;">{{ __('emails.order_subtotal') }}</td>
                                    <td style="padding: 6px 12px; font-size: 14px; color: #0A0A0A; text-align: right; width: 120px;">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @if($order->discount_amount > 0)
                                <tr>
                                    <td style="padding: 6px 12px; font-size: 14px; color: #059669; text-align: right;">{{ __('emails.order_discount') }}</td>
                                    <td style="padding: 6px 12px; font-size: 14px; color: #059669; text-align: right;">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($order->tax_amount > 0)
                                <tr>
                                    <td style="padding: 6px 12px; font-size: 14px; color: #6B7280; text-align: right;">{{ __('emails.order_tax') }} ({{ $order->tax_rate }}%)</td>
                                    <td style="padding: 6px 12px; font-size: 14px; color: #0A0A0A; text-align: right;">Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                <tr style="background: #F9FAFB;">
                                    <td style="padding: 12px; font-size: 15px; font-weight: 600; color: #0A0A0A; text-align: right;">{{ __('emails.order_total') }}</td>
                                    <td style="padding: 12px; font-size: 15px; font-weight: 600; color: #0A0A0A; text-align: right;">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 8px 0;">
                                        <a href="{{ route('orders') }}" style="display: inline-block; padding: 14px 32px; background: #0A0A0A; color: #FFFFFF; text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 4px;">{{ __('emails.order_cta') }} →</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; background: #F9FAFB; border-top: 1px solid #E5E7EB;">
                            <p style="margin: 0 0 8px; font-size: 14px; color: #4B5563;">{{ __('emails.order_closing') }},<br><strong>{{ __('emails.team_name') }}</strong></p>
                            <p style="margin: 0; font-size: 12px; color: #9CA3AF;">© {{ date('Y') }} Alfa Beauty. {{ __('emails.rights_reserved') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
