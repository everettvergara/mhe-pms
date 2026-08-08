<?php

namespace App\Support\EagleEye;

class SqlTupleParser
{
    /**
     * @return array<int, string|null>
     */
    public static function parseRow(string $tuple): array
    {
        $tuple = trim($tuple);

        if (str_starts_with($tuple, '(')) {
            $tuple = substr($tuple, 1);
        }

        if (str_ends_with($tuple, '),')) {
            $tuple = substr($tuple, 0, -2);
        } elseif (str_ends_with($tuple, ')')) {
            $tuple = substr($tuple, 0, -1);
        }

        $values = [];
        $length = strlen($tuple);
        $i = 0;
        $current = '';
        $inString = false;

        while ($i < $length) {
            $ch = $tuple[$i];

            if ($inString) {
                if ($ch === '\\' && $i + 1 < $length) {
                    $current .= $ch.$tuple[$i + 1];
                    $i += 2;

                    continue;
                }

                if ($ch === "'") {
                    $inString = false;
                    $i++;

                    continue;
                }

                $current .= $ch;
                $i++;

                continue;
            }

            if ($ch === "'") {
                $inString = true;
                $i++;

                continue;
            }

            if ($ch === ',') {
                $values[] = self::normalizeValue($current);
                $current = '';
                $i++;

                continue;
            }

            $current .= $ch;
            $i++;
        }

        $values[] = self::normalizeValue($current);

        return $values;
    }

    private static function normalizeValue(string $value): ?string
    {
        $value = trim($value);

        if ($value === '' || strtoupper($value) === 'NULL') {
            return null;
        }

        return $value;
    }
}
