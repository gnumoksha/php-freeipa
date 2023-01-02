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

use JetBrains\PhpStorm\ArrayShape;

class CommonBody implements Body
{
    public ?object $result;

    public string $principal;

    public mixed $error;

    public ?string $id;

    public ?string $version;

    public function __construct($result, string $principal, $error, ?string $id = null, ?string $version = null)
    {
        $this->result    = $result;
        $this->principal = $principal;
        $this->error     = $error;
        $this->id        = $id;
        $this->version   = $version;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult(): null|object
    {
        return $this->result;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrincipal(): string
    {
        return $this->principal;
    }

    /**
     * {@inheritDoc}
     */
    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function getError(): ?object
    {
        return $this->error;
    }

    /**
     * {@inheritDoc}
     * @see \Gnumoksha\FreeIpa\Infra\Rpc\Request\CommonBody will send id as string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * {@inheritDoc}
     */
    #[ArrayShape([
        'result' => "null|object",
        'principal' => "string",
        'error' => "mixed",
        'id' => "null|string",
        'version' => "null|string"
    ])]
    public function jsonSerialize(): array
    {
        return [
            'result'    => $this->result,
            'principal' => $this->principal,
            'error'     => $this->error,
            'id'        => $this->id,
            'version'   => $this->version,
        ];
    }
}
