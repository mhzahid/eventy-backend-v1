<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Ticket PDF - {{ $ticket['id'] }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Joti+One&display=swap"
        rel="stylesheet">


    <?php
    
    // function hexToRgb($hex)
    // {
    //     $hex = str_replace('#', '', $hex);
    //     return [
    //         'r' => hexdec(substr($hex, 0, 2)),
    //         'g' => hexdec(substr($hex, 2, 2)),
    //         'b' => hexdec(substr($hex, 4, 2)),
    //     ];
    // }
    
    // function rgbToHsl($r, $g, $b)
    // {
    //     $r /= 255;
    //     $g /= 255;
    //     $b /= 255;
    
    //     $max = max($r, $g, $b);
    //     $min = min($r, $g, $b);
    
    //     $h = $s = 0;
    //     $l = ($max + $min) / 2;
    
    //     if ($max !== $min) {
    //         $d = $max - $min;
    
    //         $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
    
    //         switch ($max) {
    //             case $r:
    //                 $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
    //                 break;
    //             case $g:
    //                 $h = ($b - $r) / $d + 2;
    //                 break;
    //             case $b:
    //                 $h = ($r - $g) / $d + 4;
    //                 break;
    //         }
    
    //         $h /= 6;
    //     }
    
    //     return [$h, $s, $l];
    // }
    
    // function hslToRgb($h, $s, $l)
    // {
    //     if ($s == 0) {
    //         $v = $l * 255;
    //         return [$v, $v, $v];
    //     }
    
    //     $hue2rgb = function ($p, $q, $t) {
    //         if ($t < 0) {
    //             $t += 1;
    //         }
    //         if ($t > 1) {
    //             $t -= 1;
    //         }
    //         if ($t < 1 / 6) {
    //             return $p + ($q - $p) * 6 * $t;
    //         }
    //         if ($t < 1 / 2) {
    //             return $q;
    //         }
    //         if ($t < 2 / 3) {
    //             return $p + ($q - $p) * (2 / 3 - $t) * 6;
    //         }
    //         return $p;
    //     };
    
    //     $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
    //     $p = 2 * $l - $q;
    
    //     $r = $hue2rgb($p, $q, $h + 1 / 3);
    //     $g = $hue2rgb($p, $q, $h);
    //     $b = $hue2rgb($p, $q, $h - 1 / 3);
    
    //     return [round($r * 255), round($g * 255), round($b * 255)];
    // }
    
    // function generateLightColor($hex)
    // {
    //     [$r, $g, $b] = array_values(hexToRgb($hex));
    //     [$h, $s, $l] = rgbToHsl($r, $g, $b);
    
    //     // tuning for brand-like secondary color
    //     $s = min(1, $s + 0.18);
    //     $l = min(1, $l + 0.11);
    
    //     [$nr, $ng, $nb] = hslToRgb($h, $s, $l);
    
    //     return sprintf('#%02X%02X%02X', $nr, $ng, $nb);
    // }
    
    // 🎯 SET YOUR MAIN COLOR HERE
    // $mainColor = $ticket['brand_color'];
    // $secondaryColor = generateLightColor($mainColor);
    
    // /* --secondary-color: {{ $secondaryColor }}; */
    ?>

    <style>
        :root {
            --main-color: {{ $ticket['brand_color'] }};
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 106.098mm;
            height: 205mm;
            overflow: hidden;
            position: relative;
            font-family: "Inter", sans-serif;
            font-style: normal;
        }

        .ticket-container {
            background: #031503;
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            flex-direction: column;
            z-index: 1;
        }

        .top-block {
            width: 100%;
            height: 250px;
            position: relative;
        }

        .top-block::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(0deg, color-mix(in srgb, var(--main-color), #00000000) 1%, rgba(255, 255, 255, 0) 26%);
            filter: blur(12px);
        }

        .top-block .banner-block {
            display: block;
            width: 100%;
            height: 100%;
        }

        .top-block .banner-block img {
            width: 100%;
            aspect-ratio: 1.604 / 1;
            object-fit: cover;
            object-position: center;
        }

        .event-info {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .event-info .og {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: center;
            padding-top: 8px;
        }

        .event-info .og .lbl {
            color: #FFF;
            font-size: 12px;
            font-weight: 600;
            line-height: 83.333%;
        }

        .event-info .og .text {
            color: var(--main-color);
            font-family: "Joti One", serif;
            font-size: 14px;
            font-weight: 400;
            line-height: 71.429%;
        }

        .event-details {
            max-width: 345px;
            margin-inline: auto;
            display: grid;
            grid-template-columns: 1fr;
            justify-content: center;
            row-gap: 6px;
            padding-bottom: 16px;
        }

        .event-details .text {
            display: inline-flex;
            gap: 6px;
            align-items: center;
            justify-content: center;
            color: #FFF;
            font-size: 12px;
            font-weight: 500;
            line-height: 85%;
            position: relative;
        }

        .event-details .text .lbl {
            display: -webkit-inline-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.18;
            height: 28px;
        }

        .event-details .date-time {
            display: inline-flex;
            justify-content: center;
        }

        .event-details .date-time .text:not(:first-child) {
            padding-left: 12px;
        }

        .event-details .date-time .text:not(:last-child) {
            padding-right: 12px;
        }

        .event-details .date-time .text:not(:last-child)::after {
            content: "";
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 1px;
            height: 100%;
            background: #D7D7D7;
            border-radius: 3px;
        }

        .qr-block {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid #EBEBEB;
            background: #F5F4F0;
            margin-inline: 20px;
            padding: 16px;
        }

        .qr-block .type {
            padding: 10px 16px;
            border-radius: 24px;
            border: 0.5px solid #FFF;
            /* background: linear-gradient(90deg, var(--secondary-color) 0%, var(--main-color) 100%); */
            color: #FFF;
            font-size: 12px;
            font-weight: 600;
            line-height: 83.333%;
            letter-spacing: 0.96px;
            opacity: 0;
            visibility: hidden;
        }

        .qr-block .text-block {
            display: block;
            text-align: center;
        }

        .qr-block .text-block .text {
            color: #000;
            font-size: 24px;
            font-weight: 700;
            line-height: 100%;
            text-transform: capitalize;
            margin-bottom: 5px;
            opacity: 0;
            visibility: hidden;
        }

        .qr-block .text-block .t-text {
            color: #828282;
            font-size: 12px;
            font-weight: 500;
            line-height: 83.333%;
            opacity: 0;
            visibility: hidden;
        }

        .qr-block .qr {
            width: 225px;
            aspect-ratio: 1;
            border-radius: 8px;
            background: #FFF;
            box-shadow: 0 0 16px 0 rgba(0, 0, 0, 0.16);
        }

        .dd-by-block {
            display: flex;
            align-items: center;
            justify-content: center;
            padding-inline: 12px;
            padding-block-start: 12px;
            padding-block-end: 9px;
        }

        .dd-by-block img {
            max-width: 100%;
        }
    </style>
</head>

<body>
    <div class="ticket-container">
        <div class="top-block">
            <div class="banner-block">
                <img src="{{ $ticket['img'] }}" alt="{{ $ticket['name'] }}">
            </div>
        </div>

        <div class="event-info">
            <div class="og">
                <p class="lbl">Organized by</p>
                <p class="text">{{ $ticket['org_name'] }}</p>
            </div>
            <div class="event-details">
                <div class="date-time">
                    <p class="text">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14"
                                fill="none">
                                <path
                                    d="M12.4735 0H11.1651V1.30841C11.1651 1.57009 10.947 1.74455 10.729 1.74455C10.5109 1.74455 10.2928 1.57009 10.2928 1.30841V0H3.31464V1.30841C3.31464 1.57009 3.09657 1.74455 2.8785 1.74455C2.66044 1.74455 2.44237 1.57009 2.44237 1.30841V0H1.13396C0.479751 0 0 0.566978 0 1.30841V2.87851H13.9564V1.30841C13.9564 0.566978 13.1713 0 12.4735 0ZM0 3.79439V11.7757C0 12.5608 0.479751 13.0841 1.17757 13.0841H12.5171C13.215 13.0841 14 12.5171 14 11.7757V3.79439H0ZM3.88162 11.1215H2.83489C2.66044 11.1215 2.48598 10.9907 2.48598 10.7726V9.68225C2.48598 9.50779 2.61682 9.33333 2.83489 9.33333H3.92523C4.09969 9.33333 4.27414 9.46418 4.27414 9.68225V10.7726C4.23053 10.9907 4.09969 11.1215 3.88162 11.1215ZM3.88162 7.19626H2.83489C2.66044 7.19626 2.48598 7.06542 2.48598 6.84735V5.75701C2.48598 5.58256 2.61682 5.4081 2.83489 5.4081H3.92523C4.09969 5.4081 4.27414 5.53894 4.27414 5.75701V6.84735C4.23053 7.06542 4.09969 7.19626 3.88162 7.19626ZM7.37072 11.1215H6.28037C6.10592 11.1215 5.93146 10.9907 5.93146 10.7726V9.68225C5.93146 9.50779 6.06231 9.33333 6.28037 9.33333H7.37072C7.54517 9.33333 7.71963 9.46418 7.71963 9.68225V10.7726C7.71963 10.9907 7.58879 11.1215 7.37072 11.1215ZM7.37072 7.19626H6.28037C6.10592 7.19626 5.93146 7.06542 5.93146 6.84735V5.75701C5.93146 5.58256 6.06231 5.4081 6.28037 5.4081H7.37072C7.54517 5.4081 7.71963 5.53894 7.71963 5.75701V6.84735C7.71963 7.06542 7.58879 7.19626 7.37072 7.19626ZM10.8598 11.1215H9.76947C9.59502 11.1215 9.42056 10.9907 9.42056 10.7726V9.68225C9.42056 9.50779 9.5514 9.33333 9.76947 9.33333H10.8598C11.0343 9.33333 11.2087 9.46418 11.2087 9.68225V10.7726C11.2087 10.9907 11.0779 11.1215 10.8598 11.1215ZM10.8598 7.19626H9.76947C9.59502 7.19626 9.42056 7.06542 9.42056 6.84735V5.75701C9.42056 5.58256 9.5514 5.4081 9.76947 5.4081H10.8598C11.0343 5.4081 11.2087 5.53894 11.2087 5.75701V6.84735C11.2087 7.06542 11.0779 7.19626 10.8598 7.19626Z"
                                    fill="#fff" />
                            </svg>
                        </span> {{ $ticket['date'] }}
                    </p>
                    <p class="text">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14"
                                fill="none">
                                <path
                                    d="M7.00001 0C5.61554 0 4.26216 0.410543 3.11101 1.17971C1.95987 1.94888 1.06266 3.04213 0.532847 4.32122C0.00303302 5.6003 -0.13559 7.00776 0.134506 8.36563C0.404603 9.7235 1.07129 10.9708 2.05026 11.9497C3.02923 12.9287 4.27651 13.5954 5.63438 13.8655C6.99225 14.1356 8.39971 13.997 9.6788 13.4672C10.9579 12.9373 12.0511 12.0401 12.8203 10.889C13.5895 9.73785 14 8.38447 14 7C13.9978 5.14416 13.2596 3.36495 11.9473 2.05267C10.6351 0.74039 8.85585 0.00218939 7.00001 0ZM9.35901 9.359C9.23968 9.4783 9.07784 9.54532 8.9091 9.54532C8.74036 9.54532 8.57853 9.4783 8.45919 9.359L6.5501 7.44991C6.43075 7.33059 6.36368 7.16876 6.36365 7V3.18182C6.36365 3.01304 6.43069 2.85118 6.55003 2.73184C6.66937 2.6125 6.83124 2.54545 7.00001 2.54545C7.16878 2.54545 7.33065 2.6125 7.44999 2.73184C7.56933 2.85118 7.63637 3.01304 7.63637 3.18182V6.73655L9.35901 8.45918C9.47831 8.57852 9.54533 8.74035 9.54533 8.90909C9.54533 9.07783 9.47831 9.23966 9.35901 9.359Z"
                                    fill="#fff" />
                            </svg>
                        </span> {{ $ticket['time'] }}
                    </p>
                </div>

                <p class="text" style="align-items: start;">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="15" viewBox="0 0 12 15"
                            fill="none">
                            <path
                                d="M6 0C2.692 0 0 2.54125 0 5.66562C0 10.105 5.436 14.6887 5.66733 14.8812C5.76267 14.9606 5.88133 15 6 15C6.11867 15 6.23733 14.9606 6.33267 14.8819C6.564 14.6888 12 10.105 12 5.66562C12 2.54125 9.308 0 6 0ZM6 8.75C4.162 8.75 2.66667 7.34812 2.66667 5.625C2.66667 3.90188 4.162 2.5 6 2.5C7.838 2.5 9.33333 3.90188 9.33333 5.625C9.33333 7.34812 7.838 8.75 6 8.75Z"
                                fill="#fff" />
                        </svg>
                    </span>
                    <span class="lbl">{{ $ticket['location'] }}</span>
                </p>
            </div>
        </div>

        <div class="qr-block">
            <p class="type">Early Bird</p>
            <div class="text-block">
                <p class="text">Mh zahid</p>
                <p class="t-text">#GOE-26-3218</p>
            </div>

            <div class="qr">
                {{-- qr code placeholder --}}
            </div>

        </div>

        <div class="dd-by-block">
            <img src="{{ asset('storage/eventy-white-logo.svg') }}" alt="">
        </div>
    </div>
</body>

</html>
