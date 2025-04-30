<?php

declare(strict_types=1);

namespace App;

function urlsafe_base64_decode(string $string): string
{
    $remainder = strlen($string) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $string .= str_repeat('=', $padlen);
    }

    return base64_decode(\strtr($string, '_-', '+/'), true);
}

/**
 * Encode a string using base64 and strip it of url unsafe characters.
 */
function urlsafe_base64_encode(string $string): string
{
    return str_replace('=', '', strtr(base64_encode($string), '+/', '_-'));
}
