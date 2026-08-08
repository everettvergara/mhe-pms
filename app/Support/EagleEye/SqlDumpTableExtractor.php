<?php

namespace App\Support\EagleEye;

use Generator;
use RuntimeException;

class SqlDumpTableExtractor
{
    /**
     * @return Generator<int, array<int, string|null>>
     */
    public function extract(string $sqlPath, string $table): Generator
    {
        if (! is_readable($sqlPath)) {
            throw new RuntimeException("SQL dump not readable: {$sqlPath}");
        }

        $handle = fopen($sqlPath, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open SQL dump: {$sqlPath}");
        }

        $needle = "INSERT INTO `{$table}` VALUES";
        $capturing = false;

        try {
            while (($line = fgets($handle)) !== false) {
                if (str_contains($line, $needle)) {
                    $capturing = true;
                }

                if (! $capturing) {
                    continue;
                }

                foreach (self::splitTuples($line) as $tuple) {
                    if ($tuple === '') {
                        continue;
                    }

                    yield SqlTupleParser::parseRow($tuple);
                }

                if (self::lineEndsInsertStatement($line)) {
                    $capturing = false;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<int, string>
     */
    public static function splitTuples(string $chunk): array
    {
        $tuples = [];
        $length = strlen($chunk);
        $i = 0;
        $start = null;
        $depth = 0;
        $inString = false;

        while ($i < $length) {
            $ch = $chunk[$i];

            if ($inString) {
                if ($ch === '\\' && $i + 1 < $length) {
                    $i += 2;

                    continue;
                }

                if ($ch === "'") {
                    $inString = false;
                }

                $i++;

                continue;
            }

            if ($ch === "'") {
                $inString = true;
                $i++;

                continue;
            }

            if ($ch === '(') {
                if ($depth === 0) {
                    $start = $i;
                }

                $depth++;
                $i++;

                continue;
            }

            if ($ch === ')') {
                $depth--;

                if ($depth === 0 && $start !== null) {
                    $tuples[] = substr($chunk, $start, $i - $start + 1);
                    $start = null;
                }

                $i++;

                continue;
            }

            $i++;
        }

        return $tuples;
    }

    public static function lineEndsInsertStatement(string $line): bool
    {
        $inString = false;
        $length = strlen($line);

        for ($i = 0; $i < $length; $i++) {
            $ch = $line[$i];

            if ($inString) {
                if ($ch === '\\' && $i + 1 < $length) {
                    $i++;

                    continue;
                }

                if ($ch === "'") {
                    $inString = false;
                }

                continue;
            }

            if ($ch === "'") {
                $inString = true;

                continue;
            }

            if ($ch === ';') {
                return true;
            }
        }

        return false;
    }
}
