<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @media only screen and (max-width: 560px) {
            .card {
                width: 100% !important;
            }

            .body-cell {
                padding: 24px !important;
            }

            .footer {
                padding: 16px 24px !important;
            }

            .outer-wrap {
                padding: 24px 16px !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#f4f4f4; font-family:Arial, sans-serif;">

    <table class="outer-wrap" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color:#f4f4f4; padding:40px 20px;">
        <tr>
            <td align="center" style="padding-bottom:20px;">
                @php
                    $logoSrc = file_exists(public_path('storage/eventy-black-logo.png'))
                        ? asset('storage/eventy-black-logo.png')
                        : null;
                @endphp
                @if ($logoSrc)
                    <a href="{{ config('app.frontend_url') }}" style="text-decoration:none;">
                        <img src="{{ $logoSrc }}" alt="{{ config('app.name') }}"
                            style="height:40px; max-width:100%; vertical-align:middle; display:block;">
                    </a>
                @else
                    <span style="font-size:22px; font-weight:bold; color:#1a1a1a;">
                        {{ config('app.name') }}
                    </span>
                @endif
            </td>
        </tr>
        <tr>
            <td align="center">

                <table class="card" width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="background-color:#ffffff; border-radius:8px; overflow:hidden; max-width:520px;">

                    {{-- Body --}}
                    <tr>
                        <td class="body-cell" style="padding:40px;">

                            {{-- Greeting --}}
                            @php $ticketCount = count($pdfAttachments); @endphp
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-size:18px; font-weight:bold; color:#1a1a1a; padding-bottom:6px;">
                                        Booking Confirmed!
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size:14px; color:#555555; padding-bottom:28px;">
                                        Hi {{ $buyer->name }}, your
                                        {{ $ticketCount === 1 ? 'ticket has' : $ticketCount . ' tickets have' }}
                                        been confirmed. Find your
                                        {{ $ticketCount === 1 ? 'ticket PDF' : 'ticket PDFs' }}
                                        attached to this email.
                                    </td>
                                </tr>
                            </table>

                            {{-- Event details --}}
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background-color:#f9f9f9; border-radius:6px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="90"
                                                    style="font-size:14px; font-weight:bold; color:#555555; padding-bottom:8px;">
                                                    Event</td>
                                                <td style="font-size:14px; color:#333333; padding-bottom:8px;">
                                                    {{ $event->name }}</td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="font-size:14px; font-weight:bold; color:#555555; padding-bottom:8px;">
                                                    Date</td>
                                                <td style="font-size:14px; color:#333333; padding-bottom:8px;">
                                                    {{ $event->event_date->format('F j, Y') }}</td>
                                            </tr>
                                            @if ($event->start_time)
                                                <tr>
                                                    <td
                                                        style="font-size:14px; font-weight:bold; color:#555555; padding-bottom:8px;">
                                                        Time</td>
                                                    <td style="font-size:14px; color:#333333; padding-bottom:8px;">
                                                        {{ \Carbon\Carbon::parse($event->start_time)->format('h:i A') }}
                                                    </td>
                                                </tr>
                                            @endif
                                            @if ($event->is_physical && $event->location)
                                                <tr>
                                                    <td
                                                        style="font-size:14px; font-weight:bold; color:#555555; padding-bottom:8px;">
                                                        Location</td>
                                                    <td style="font-size:14px; color:#333333; padding-bottom:8px;">
                                                        {{ $event->location }}</td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Note --}}
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-size:13px; color:#555555; padding-top:8px;">
                                        Please present your ticket PDF{{ $ticketCount > 1 ? 's' : '' }} at the entrance
                                        for scanning.
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" class="footer"
                            style="padding:20px 40px; border-top:1px solid #eeeeee; font-size:12px; color:#999999;">
                            &copy; {{ date('Y') }} {{ config('app.name') }}
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
