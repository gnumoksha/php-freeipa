<?php

declare(strict_types=1);

namespace Gnumoksha\FreeIpa\Infra\Json;

use stdClass;

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
     * @param int $options
     * @param int $depth
     * @return string
     * @throws JsonException
     */
    public static function encode(mixed $value, int $options = 0, int $depth = 512): string
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
     * @param string $json
     * @param bool $assoc
     * @param int $depth
     * @param int $options
     * @return array|stdClass
     * @throws JsonException
     */
    public static function decode(string $json, bool $assoc = false, int $depth = 512, int $options = 0): array|stdClass
    {
        $decoded = json_decode($json, $assoc, $depth, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException(sprintf('Unable to decode json. Error was: "%s".', json_last_error_msg()));
        }

        return $decoded;
    }
}
