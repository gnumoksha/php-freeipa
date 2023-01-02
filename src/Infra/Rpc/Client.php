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

namespace Gnumoksha\FreeIpa\Infra\Rpc;

use Gnumoksha\FreeIpa\Infra\Json\Json;
use Gnumoksha\FreeIpa\Infra\Json\JsonException;
use Gnumoksha\FreeIpa\Infra\Rpc\Request\Body as RequestBodyInterface;
use Gnumoksha\FreeIpa\Infra\Rpc\Response\Body as ResponseBodyInterface;
use Gnumoksha\FreeIpa\Infra\Rpc\Response\BodyBuilder as ResponseBodyBuilder;
use Gnumoksha\FreeIpa\Options;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /** @var \Gnumoksha\FreeIpa\Options */
    private $options;
    /** @var \Psr\Http\Client\ClientInterface */
    private $httpClient;
    /** @var \Psr\Http\Message\RequestFactoryInterface */
    private $requestFactory;
    /** @var \Gnumoksha\FreeIpa\Infra\Rpc\Response\BodyBuilder */
    private $responseBodyBuilder;
    /** @var bool */
    private $connected;

    public function __construct(
        Options $options,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        ResponseBodyBuilder $responseBodyBuilder
    ) {
        $this->options             = $options;
        $this->httpClient          = $httpClient;
        $this->requestFactory      = $requestFactory;
        $this->responseBodyBuilder = $responseBodyBuilder;
        $this->connected           = false;
    }

    /**
     * Authenticate using an username and password.
     *
     * @see https://access.redhat.com/articles/2728021#end-point-pwd
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function login(string $username, string $password): void
    {
        $request = $this->requestFactory->createRequest('POST', '/session/login_password')
                                        ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded')
                                        ->withAddedHeader('Accept', 'text/plain');

        $request->getBody()->write(http_build_query([
            'user'     => $username,
            'password' => $password,
        ]));

        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            $this->throwLoginError($response);
        }

        $this->connected = true;
    }

    /**
     * @throws \Gnumoksha\FreeIpa\Infra\Json\JsonException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     *
     * @see https://access.redhat.com/articles/2728021#end-points documentation
     */
    public function sendRequest(RequestBodyInterface $body): ResponseBodyInterface
    {
        if ($this->connected === false) {
            throw new ClientException('You are not authenticated.');
        }

        if ($this->options->getApiVersion() !== null) {
            $body = $body->withOption('version', $this->options->getApiVersion());
        }

        $request = $this->requestFactory->createRequest('POST', '/session/json')
                                        ->withAddedHeader('Content-Type', 'application/json')
                                        ->withAddedHeader('Accept', 'application/json');

        $request->getBody()
                ->write(Json::encode($body));

        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            // #TODO better error message (i.e. auth fails)
            throw new ClientException(sprintf('Error: received HTTP status code "%s".', $response->getStatusCode()));
        }

        $responseBody = $this->responseBodyBuilder->build($response);
        if ($responseBody->hasError()) {
            try {
                $error = Json::encode($responseBody->getError());
            } catch (JsonException $e) {
                $error = 'The response\'s error field is an invalid json.';
            }
            throw new ClientException($error);
        }

        return $responseBody;
    }

    private function throwLoginError(ResponseInterface $response): void
    {
        $content = $response->getBody()->getContents();
        preg_match("/<title>(.*)<\/title>/siU", $content, $titleMatches);

        if (count($titleMatches) === 2) {
            $errorMessage = $titleMatches[1];
        } else {
            $errorMessage = 'Unknown login error.';
        }

        throw new ClientException($errorMessage, $response->getStatusCode());
    }
}
