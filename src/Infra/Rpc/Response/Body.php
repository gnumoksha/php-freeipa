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

use JsonSerializable;

/**
 * Represents the body for a JSON-RPC response from FreeIPA server.
 *
 * @see https://access.redhat.com/articles/2728021#request-response documentation
 */
interface Body extends JsonSerializable
{
    /**
     * The object returned by the invoked command. If an error occurred when invoking the command, result is null.
     * @return object|null
     */
    public function getResult(): ?object;

    /**
     * The Kerberos principal of the identity under which the request was performed.
     * The principal property is not part of the JSON-RPC v1.0 format.
     */
    public function getPrincipal(): string;

    /**
     * Whether or not the response contains errors.
     */
    public function hasError(): bool;

    /**
     * An error object if an error occurred when invoking the command. If no error occurred, error is null.
     * @return null|object
     */
    public function getError(): ?object;

    /**
     * The id property of the response matches the id property of the corresponding request.
     * @return mixed
     */
    public function getId(): mixed;

    public function getVersion(): ?string;
}
