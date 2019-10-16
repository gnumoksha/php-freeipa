<?php

declare(strict_types=1);

namespace Gnumoksha\FreeIpa\Infra\Json;

use function json_decode;
use function json_encode;

/**
 * This class is a wrapper around php built-in functions json_encode and json_decode.
 *
 * It is useful because it throws an exception if the operation fails, as consequence the
 * returned values will not be null.
 *
 * @internal
 */
final class Json
{
    /**
     * Encode a value as json.
     *
     * @param mixed $value
     *
     * @throws \Gnumoksha\FreeIpa\Infra\Json\JsonException
     * @see \json_encode()
     */
    public static function encode($value, int $options = 0, int $depth = 512): string
    {
        $encoded = json_encode($value, $options, $depth);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException(sprintf('Unable to encode json. Error was: "%s".', json_last_error_msg()));
        }

        return (string)$encoded;
    }

    /**
     * Decode a value from json.
     *
     * @return mixed[]|\stdClass
     * @throws \Gnumoksha\FreeIpa\Infra\Json\JsonException
     * @see \json_decode()
     */
    public static function decode(string $json, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        $decoded = json_decode($json, $assoc, $depth, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException(sprintf('Unable to decode json. Error was: "%s".', json_last_error_msg()));
        }

        return $decoded;
    }
}
