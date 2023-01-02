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

namespace Gnumoksha\FreeIpa\EndToEnd;

use Gnumoksha\FreeIpa\FreeIpa;
use Gnumoksha\FreeIpa\Infra\Rpc\Request\CommonBody;
use Gnumoksha\FreeIpa\Options;
use PHPUnit\Framework\TestCase;

class FreeIpaTest extends TestCase
{
    /** @var \Gnumoksha\FreeIpa\FreeIpa */
    private $ipa;

    public function setUp(): void
    {
        $this->ipa = new FreeIpa(
            new Options('ipa.demo1.freeipa.org', __DIR__ . '/../resource/ipa.demo1.freeipa.org_ca.crt')
        );
    }

    public function testMustIdentifyIncorrectUserPassword(): void
    {
        $this->expectException(\Psr\Http\Client\ClientExceptionInterface::class);
        $this->expectExceptionMessage('401 Unauthorized');

        $this->ipa->login('incorrectUser', 'incorrectPassword');
    }

    public function testMustFindUser(): void
    {
        $this->ipa->login('admin', 'Secret123');

        $response = $this->ipa->getUserRepository()->findBySn('Admin');

        $this->assertNull($response->getError());
        $this->assertInstanceOf(\stdClass::class, $response->getResult());
        $this->assertEquals(1, $response->getResult()->count);
    }

    public function testMustFindGroup(): void
    {
        $this->ipa->login('admin', 'Secret123');

        $response = $this->ipa->getGroupRepository()->findBy('cn', 'ipausers');

        $this->assertNull($response->getError());
        $this->assertInstanceOf(\stdClass::class, $response->getResult());
        $this->assertEquals(1, $response->getResult()->count);
    }

    public function testMustSendCustomRequest(): void
    {
        $this->ipa->login('admin', 'Secret123');

        $response = $this->ipa->sendRequest(new CommonBody('ping'));

        $this->assertNull($response->getError());
        $this->assertEquals('admin@DEMO1.FREEIPA.ORG', $response->getPrincipal());
        $this->assertInstanceOf(\stdClass::class, $response->getResult());
        $this->assertStringStartsWith('IPA server version', $response->getResult()->summary);
    }
}
