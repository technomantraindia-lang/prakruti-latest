<?php

namespace App\Support;

/**
 * Works out how much food is in a pack.
 *
 * Priority: (1) explicit net quantity + unit saved by the admin,
 *           (2) a quantity written with a unit in the label ("500g", "1 Kg", "1 L"),
 *           (3) a bare number in the label ("250", "500"), which is only a guess and is reported as such.
 * A pack with none of these is UNKNOWN. We never assume "1 pack = 1 kg".
 *
 * Returns null, or: ['grams' => ?float, 'ml' => ?float, 'basis' => 'explicit'|'label'|'inferred', 'text' => string]
 */
class PackSize
{
    private const MASS = ['mg' => 0.001, 'g' => 1.0, 'gm' => 1.0, 'gms' => 1.0, 'gram' => 1.0, 'grams' => 1.0, 'kg' => 1000.0, 'kgs' => 1000.0, 'kilo' => 1000.0, 'kilogram' => 1000.0];
    private const VOLUME = ['ml' => 1.0, 'l' => 1000.0, 'lt' => 1000.0, 'ltr' => 1000.0, 'litre' => 1000.0, 'liter' => 1000.0, 'litres' => 1000.0, 'liters' => 1000.0];

    public static function parse(?string $label, null|int|float|string $netQty = null, ?string $netUnit = null): ?array
    {
        $qty = (float) ($netQty ?? 0);
        $unit = strtolower(trim((string) $netUnit));
        if ($qty > 0 && $unit !== '') {
            $r = self::fromUnit($qty, $unit, 'explicit');
            if ($r !== null) {
                return $r;
            }
        }

        $text = strtolower(trim((string) $label));
        if ($text === '') {
            return null;
        }

        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(kilogram|kilo|kgs|kg|mg|grams|gram|gms|gm|g|litres|liters|litre|liter|ltr|lt|ml|l)\b/', $text, $m)) {
            $r = self::fromUnit((float) str_replace(',', '.', $m[1]), $m[2], 'label');
            if ($r !== null) {
                return $r;
            }
        }

        if (preg_match('/^\s*(\d+(?:[.,]\d+)?)\s*$/', $text, $m)) {
            $n = (float) str_replace(',', '.', $m[1]);
            if ($n <= 0) {
                return null;
            }
            // Bare numbers are ambiguous. In a grocery catalogue 20 and above is overwhelmingly grams.
            return $n >= 20
                ? self::fromUnit($n, 'g', 'inferred')
                : self::fromUnit($n, 'kg', 'inferred');
        }

        return null;
    }

    private static function fromUnit(float $qty, string $unit, string $basis): ?array
    {
        if ($qty <= 0) {
            return null;
        }
        if (isset(self::MASS[$unit])) {
            $g = $qty * self::MASS[$unit];

            return ['grams' => round($g, 3), 'ml' => null, 'basis' => $basis, 'text' => self::format($g, 'g')];
        }
        if (isset(self::VOLUME[$unit])) {
            $ml = $qty * self::VOLUME[$unit];

            return ['grams' => null, 'ml' => round($ml, 3), 'basis' => $basis, 'text' => self::format($ml, 'ml')];
        }

        return null; // pieces, packs, tablets, ... are not food weights
    }

    private static function format(float $amount, string $base): string
    {
        if ($amount >= 1000) {
            $v = rtrim(rtrim(number_format($amount / 1000, 2, '.', ''), '0'), '.');

            return $v . ($base === 'g' ? ' kg' : ' L');
        }

        return rtrim(rtrim(number_format($amount, 1, '.', ''), '0'), '.') . ' ' . $base;
    }
}
