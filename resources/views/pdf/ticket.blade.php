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
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">

    <?php
    
    if (!function_exists('hexToRgb')) {
        function hexToRgb($hex)
        {
            $hex = str_replace('#', '', $hex);
            return [
                'r' => hexdec(substr($hex, 0, 2)),
                'g' => hexdec(substr($hex, 2, 2)),
                'b' => hexdec(substr($hex, 4, 2)),
            ];
        }
    }
    
    if (!function_exists('rgbToHsl')) {
        function rgbToHsl($r, $g, $b)
        {
            $r /= 255;
            $g /= 255;
            $b /= 255;
    
            $max = max($r, $g, $b);
            $min = min($r, $g, $b);
    
            $h = $s = 0;
            $l = ($max + $min) / 2;
    
            if ($max !== $min) {
                $d = $max - $min;
    
                $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
    
                switch ($max) {
                    case $r:
                        $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                        break;
                    case $g:
                        $h = ($b - $r) / $d + 2;
                        break;
                    case $b:
                        $h = ($r - $g) / $d + 4;
                        break;
                }
    
                $h /= 6;
            }
    
            return [$h, $s, $l];
        }
    }
    
    if (!function_exists('hslToRgb')) {
        function hslToRgb($h, $s, $l)
        {
            if ($s == 0) {
                $v = $l * 255;
                return [$v, $v, $v];
            }
    
            $hue2rgb = function ($p, $q, $t) {
                if ($t < 0) {
                    $t += 1;
                }
                if ($t > 1) {
                    $t -= 1;
                }
                if ($t < 1 / 6) {
                    return $p + ($q - $p) * 6 * $t;
                }
                if ($t < 1 / 2) {
                    return $q;
                }
                if ($t < 2 / 3) {
                    return $p + ($q - $p) * (2 / 3 - $t) * 6;
                }
                return $p;
            };
    
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
    
            $r = $hue2rgb($p, $q, $h + 1 / 3);
            $g = $hue2rgb($p, $q, $h);
            $b = $hue2rgb($p, $q, $h - 1 / 3);
    
            return [round($r * 255), round($g * 255), round($b * 255)];
        }
    }
    
    if (!function_exists('generateLightColor')) {
        function generateLightColor($hex)
        {
            [$r, $g, $b] = array_values(hexToRgb($hex));
            [$h, $s, $l] = rgbToHsl($r, $g, $b);
    
            $s = min(1, $s + 0.18);
            $l = min(1, $l + 0.11);
    
            [$nr, $ng, $nb] = hslToRgb($h, $s, $l);
    
            return sprintf('#%02X%02X%02X', $nr, $ng, $nb);
        }
    }
    
    $mainColor = $ticket['brand_color'];
    $secondaryColor = generateLightColor($mainColor);
    
    ?>

    <style>
        :root {
            --main-color: {{ $ticket['brand_color'] }};
            --secondary-color: {{ $secondaryColor }};
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


        .qr-block {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, 1.7%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .qr-block .type {
            padding: 10px 18px;
            border-radius: 24px;
            border: 0.5px solid #FFF;
            background: linear-gradient(90deg, var(--secondary-color) 0%, var(--main-color) 100%);
            color: #FFF;
            /* color: contrast-color(var(--main-color)); */
            font-size: 12px;
            font-weight: 600;
            line-height: 83.333%;
            letter-spacing: 0.96px;
        }

        .qr-block .text-block {
            display: block;
            text-align: center;
            white-space: nowrap;
        }

        .qr-block .text-block .text {
            color: #000;
            font-size: 22px;
            font-weight: 700;
            line-height: 25px;
            text-transform: capitalize;
            white-space: nowrap;
            max-width: 325px;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 5px;
        }

        .qr-block .text-block .t-text {
            color: #828282;
            font-size: 12px;
            font-weight: 500;
            line-height: 83.333%;
        }

        .qr-block .qr {
            width: 218px;
            display: flex;
            align-items: center;
            justify-content: center;
            aspect-ratio: 1/1;
            padding-top: 12px;
        }
    </style>
</head>

<body>
    <div
        style="background-image: url('{{ $ticket['img'] }}'); background-size: cover; background-repeat: no-repeat; background-position: center; position: absolute; inset: 0;">
        <div class="qr-block">
            <p class="type">{{ $ticket['type'] }}</p>
            <div class="text-block">
                <p class="text">{{ $ticket['name'] }}</p>
                <p class="t-text">#{{ $ticket['id'] }}</p>
            </div>
            <div class="qr">
                {!! $ticket['qr'] !!}
            </div>
        </div>

    </div>
</body>

</html>
