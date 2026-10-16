<?php

namespace Solr;

/**
 * Builds facet values of the form "<sort key>||<original value>", so that
 * Solr's index order (binary) is insensitive to case, accents, and leading
 * whitespace or invisible characters, while the original value can still be
 * displayed.
 */
class SortKey
{
    public const SEPARATOR = '||';

    /**
     * "Œuvres d'Émile" => "oeuvres d'emile||Œuvres d'Émile"
     */
    public static function prefix(string $value): string
    {
        return self::key($value) . self::SEPARATOR . $value;
    }

    public static function strip(string $value): string
    {
        $pos = strpos($value, self::SEPARATOR);

        return $pos === false ? $value : substr($value, $pos + strlen(self::SEPARATOR));
    }

    /**
     * Removes whitespace (including non-breaking spaces) and invisible
     * characters (zero width space, BOM, soft hyphen, control characters, ...)
     * at the beginning and at the end of the value.
     */
    public static function trim(string $value): string
    {
        return preg_replace('/^[\s\p{Z}\p{C}]+|[\s\p{Z}\p{C}]+$/u', '', $value) ?? trim($value);
    }

    public static function key(string $value): string
    {
        static $transliterator;
        if (!isset($transliterator)) {
            $transliterator = class_exists(\Transliterator::class)
                ? (\Transliterator::create('Any-Latin; Latin-ASCII; NFD; [:Nonspacing Mark:] Remove; NFC; Lower()') ?: false)
                : false;
        }

        // Any whitespace becomes a regular space, invisible characters are removed
        $key = preg_replace('/[\s\p{Z}]+/u', ' ', $value) ?? $value;
        $key = preg_replace('/\p{C}+/u', '', $key) ?? $key;

        $key = $transliterator ? $transliterator->transliterate($key) : mb_strtolower($key);

        // The separator must never appear in the key, so that strip() splits on its first occurrence
        $key = str_replace('|', ' ', $key);

        return trim(preg_replace('/ +/', ' ', $key));
    }
}
