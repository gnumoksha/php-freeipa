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

use stdClass;

class CommonBody implements Body
{
    /** @var string */
    private $method;
    /** @var string|null */
    private $methodVersion;
    /** @var mixed[] */
    private $arguments;
    /** @var \stdClass */
    private $options;
    /** @var string|mixed */
    private $id;

    /**
     * @param mixed $id
     */
    public function __construct(
        string $method = '',
        array $arguments = [],
        stdClass $options = null,
        ?string $methodVersion = null,
        $id = null
    ) {
        $this->method        = $method;
        $this->arguments     = $arguments;
        $this->options       = $options ?? new stdClass();
        $this->methodVersion = $methodVersion;
        $this->id            = $id ?? uniqid(sprintf('%s.', 'php-FreeIPA'), true);
    }

    /**
     * {@inheritDoc}
     */
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
    public function withArgument($argument): Body
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

    /**
     * {@inheritDoc}
     */
    public function getOptions(): stdClass
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function withOption($name, $value): Body
    {
        $new                   = clone $this;
        $new->options->{$name} = $value;

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withOptions(array $options): Body
    {
        $new          = clone $this;
        $new->options = (object)$options;

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withAddedOptions(array $options): Body
    {
        $new          = clone $this;
        $new->options = (object)array_merge($options, (array)$new->options);

        return $new;
    }

    /**
     * @return string|mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'method' => $this->method . ($this->methodVersion !== null ? sprintf('/%s', $this->methodVersion) : null),
            'params' => [$this->arguments, $this->options],
            'id'     => $this->id,
        ];
    }
}
