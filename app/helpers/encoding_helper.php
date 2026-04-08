<?php
/**
 * Encoding Helper
 *
 * Provides functions to fix encoding issues like mojibake (UTF-8 misinterpreted as Windows-1252 or Latin-1).
 */

/**
 * Count mojibake markers inside a string.
 *
 * @param string $str
 * @return int
 */
function mojibake_marker_count(string $str): int
{
    $markerPattern = '/[ÃÂÄÅÆÂ¡Â¢Â£Â¤Â¥Â¦Â§Â¨Â©ÂªÂ«Â¬Â®Â¯¡¢£¤¥¦§¨©ª«¬®¯°±²³´µ¶·¸¹º»¼½¾¿]/u';
    $count = preg_match_all($markerPattern, $str, $matches);
    if ($count === false) {
        return 0;
    }

    return $count + substr_count($str, '�');
}

/**
 * Fix Vietnamese mojibake in a string.
 *
 * @param mixed $str
 * @return mixed
 */
function fix_vietnamese_encoding($str)
{
    if (!is_string($str) || $str === '') {
        return $str;
    }

    if (!preg_match('/[ÃÂÄÅÆ]/u', $str)) {
        return $str;
    }

    $best = $str;
    $bestScore = mojibake_marker_count($str);

    $sources = ['Windows-1252', 'ISO-8859-1'];
    foreach ($sources as $source) {
        $candidate = @mb_convert_encoding($str, 'UTF-8', $source);
        if ($candidate === false || $candidate === $str) {
            continue;
        }

        if (!mb_check_encoding($candidate, 'UTF-8')) {
            continue;
        }

        $score = mojibake_marker_count($candidate);
        if ($score < $bestScore) {
            $best = $candidate;
            $bestScore = $score;
        }
    }

    return $best;
}

/**
 * Recursively fix encoding in arrays and objects.
 *
 * @param mixed $data
 * @return mixed
 */
function deep_fix_vietnamese_encoding($data)
{
    if (is_string($data)) {
        return fix_vietnamese_encoding($data);
    }

    if (is_array($data)) {
        $fixed = [];
        foreach ($data as $key => $value) {
            $fixed[fix_vietnamese_encoding((string) $key)] = deep_fix_vietnamese_encoding($value);
        }

        return $fixed;
    }

    if (is_object($data)) {
        $fixed = new stdClass();
        foreach ($data as $key => $value) {
            $fixed->{fix_vietnamese_encoding((string) $key)} = deep_fix_vietnamese_encoding($value);
        }

        return $fixed;
    }

    return $data;
}
