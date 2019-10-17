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

namespace Gnumoksha\FreeIpa;

use Gnumoksha\FreeIpa\Infra\Rpc\ClientBuilder;
use Gnumoksha\FreeIpa\Infra\Rpc\PluginClientBuilder;
use Gnumoksha\FreeIpa\Infra\Rpc\Request\Body as RequestBodyInterface;
use Gnumoksha\FreeIpa\Infra\Rpc\Request\CommonBody as CommonRequestBody;
use Gnumoksha\FreeIpa\Infra\Rpc\Response\Body as ResponseBodyInterface;
use Gnumoksha\FreeIpa\Model\User\UserRepository;

/**
 * Façade providing easy bootstrapping and convenient methods.
 */
class FreeIpa
{
    /** @var \Gnumoksha\FreeIpa\Infra\Rpc\Client */
    private $client;
    /** @var \Gnumoksha\FreeIpa\Infra\Rpc\Request\Body */
    private $requestBody;
    /** @var \Gnumoksha\FreeIpa\Model\User\UserRepository|null */
    private $userRepository;

    public function __construct(
        Options $options,
        ClientBuilder $clientBuilder = null,
        RequestBodyInterface $requestBody = null
    ) {
        $clientBuilder = $clientBuilder ?? new PluginClientBuilder($options);

        $this->client      = $clientBuilder->build();
        $this->requestBody = $requestBody ?? new CommonRequestBody();
    }

    /**
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function login(string $username, string $password): void
    {
        $this->client->login($username, $password);
    }

    public function getUserRepository(): UserRepository
    {
        if ($this->userRepository === null) {
            $this->userRepository = new UserRepository($this->client, $this->requestBody);
        }

        return $this->userRepository;
    }

    /**
     * Sends a raw request.
     *
     * @throws \Gnumoksha\FreeIpa\Infra\Json\JsonException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function sendRequest(RequestBodyInterface $body): ResponseBodyInterface
    {
        return $this->client->sendRequest($body);
    }
}
