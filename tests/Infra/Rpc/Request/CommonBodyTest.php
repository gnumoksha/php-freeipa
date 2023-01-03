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

namespace Gnumoksha\FreeIpa\Infra\Rpc\Request;

use Gnumoksha\FreeIpa\Infra\Json\Json;
use PHPUnit\Framework\TestCase;

class CommonBodyTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $body = new CommonBody('fooBar');

        $obj = (object)Json::decode(Json::encode($body));
        $this->assertEquals('fooBar', $obj->method);
    }

    public function testJsonSerializeWithMethodVersion(): void
    {
        $body = new CommonBody('foo', [], null, '123');

        $obj = (object)Json::decode(Json::encode($body));
        $this->assertEquals('foo/123', $obj->method);
    }
}
