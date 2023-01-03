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

namespace Gnumoksha\FreeIpa\Infra\Rpc\Response;

use Gnumoksha\FreeIpa\Infra\Json\Json;
use Gnumoksha\FreeIpa\Infra\Json\JsonException;
use Psr\Http\Message\ResponseInterface;

class CommonBodyBuilder implements BodyBuilder
{
    /**
     * @throws JsonException
     */
    public function build(ResponseInterface $response): Body
    {
        $body = $response->getBody();
        $body->rewind();
        $jsonResponse = (object)Json::decode($body->getContents());

        // #TODO handle results fields: count, total, summary, result
        return new CommonBody(
            $jsonResponse->result,
            $jsonResponse->principal,
            $jsonResponse->error,
            $jsonResponse->id,
            $jsonResponse->version
        );
    }
}
