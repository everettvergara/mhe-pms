<?php

namespace App\Services\FscWebImport;

use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use JsonException;

class FscDistrictImportToken
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function issue(array $payload): string
    {
        $payload['expires_at'] = now()->addMinutes((int) config('fsc_web_import.preview_ttl_minutes', 30))->getTimestamp();

        return Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    public static function parse(string $token): array
    {
        try {
            $decoded = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException|\Throwable) {
            throw new InvalidArgumentException('Preview is invalid. Run preview again.');
        }

        if (! is_array($decoded) || (int) ($decoded['expires_at'] ?? 0) < now()->getTimestamp()) {
            throw new InvalidArgumentException('Preview expired. Run preview again.');
        }

        return $decoded;
    }
}
