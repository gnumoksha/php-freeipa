<?php

/**
 * FreeIPA library for PHP
 * Copyright (C) 2015-2019 Tobias Sette <me@tobias.ws>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Gnumoksha\FreeIpa\Infra\Json;

use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testShouldEncode(): void
    {
        $result = Json::encode(['foo' => 'bar']);
        $this->assertEquals('{"foo":"bar"}', $result);
    }

    public function testEncodeThrowsIfValueIsResource(): void
    {
        $resource = fopen('php://memory', 'rb');
        $this->assertNotFalse($resource);
        fclose($resource);

        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Unable to encode json. Error was: "Type is not supported".');

        Json::encode($resource);
    }

    public function testShouldDecode(): void
    {
        $result = (array)Json::decode('{"foo":"bar"}');
        $this->assertEquals(['foo' => 'bar'], $result);
    }

    public function testDecodeThrowsIfValueIsNotValidJson(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Unable to decode json. Error was: "Syntax error".');

        Json::decode('fooBar');
    }
}
