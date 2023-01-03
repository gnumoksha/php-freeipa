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

use JsonSerializable;
use stdClass;

/**
 * Represents the body for a JSON-RPC request to FreeIPA server.
 *
 * @see https://access.redhat.com/articles/2728021#request-response
 */
interface Body extends JsonSerializable
{
    public function getMethod(): string;

    /**
     * Return an instance with the provided RPC argument.
     *
     * @param string $method Case-sensitive method.
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod(string $method, string $version = null): Body;

    /**
     * @return mixed[]
     */
    public function getArguments(): array;

    /**
     * Return an instance with the provided RPC argument.
     *
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withArgument(string $argument): Body;

    /**
     * Return an instance with the provided RPC argument.
     *
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withArguments(array $arguments): Body;

    public function getOptions(): stdClass;

    /**
     * Return an instance with the provided RPC argument.
     *
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withOption(string $name, ?string $value): Body;

    /**
     * Return an instance with the provided RPC argument.
     *
     * @throws \InvalidArgumentException for invalid HTTP methods.
     * @TODO options pode ser \stdClass
     */
    public function withOptions(array $options): Body;

    public function withAddedOptions(array $options): Body;

    public function getId(): string;
}
