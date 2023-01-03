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

use JetBrains\PhpStorm\ArrayShape;
use stdClass;

class CommonBody implements Body
{
    private string $method;

    private ?string $methodVersion;

    private array $arguments;

    private stdClass $options;

    private mixed $id;

    /**
     * @param mixed|null $id
     */
    public function __construct(
        string $method = '',
        array $arguments = [],
        stdClass $options = null,
        ?string $methodVersion = null,
        mixed $id = null
    ) {
        $this->method        = $method;
        $this->arguments     = $arguments;
        $this->options       = $options ?? new stdClass();
        $this->methodVersion = $methodVersion;
        $this->id            = $id ?? uniqid(sprintf('%s.', 'php-FreeIPA'), true);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * {@inheritDoc}
     */
    public function withMethod(string $method, string $version = null): Body
    {
        $new         = clone $this;
        $new->method = $method;

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * {@inheritDoc}
     */
    public function withArgument(string $argument): Body
    {
        $new              = clone $this;
        $new->arguments[] = $argument;

        return $new;
    }

    /**
     * {@inheritDoc}
     * #TODO assert array is not associative
     */
    public function withArguments(array $arguments): Body
    {
        $new            = clone $this;
        $new->arguments = $arguments;

        return $new;
    }

    public function getOptions(): stdClass
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function withOption(string $name, ?string $value): Body
    {
        $new                   = clone $this;
        $new->options->{$name} = $value;

        return $new;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-suppress PropertyTypeCoercion
     */
    public function withOptions(array $options): Body
    {
        $new          = clone $this;
        $new->options = (object)$options;

        return $new;
    }

    /**
     * @param array $options
     * @return Body
     *
     * @psalm-suppress PropertyTypeCoercion
     */
    public function withAddedOptions(array $options): Body
    {
        $new          = clone $this;
        $new->options = (object)array_merge($options, (array)$new->options);

        return $new;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    #[ArrayShape(['method' => "string", 'params' => "array", 'id' => "mixed|string"])]
    public function jsonSerialize(): array
    {
        return [
            'method' => $this->method . ($this->methodVersion !== null ? sprintf('/%s', $this->methodVersion) : ''),
            'params' => [$this->arguments, $this->options],
            'id'     => $this->id,
        ];
    }
}
