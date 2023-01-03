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

namespace Gnumoksha\FreeIpa\Model\Group;

use BadMethodCallException;
use Gnumoksha\FreeIpa\Infra\Json\JsonException;
use Gnumoksha\FreeIpa\Infra\Repository\BaseRepository;
use Gnumoksha\FreeIpa\Infra\Rpc\Client;
use Gnumoksha\FreeIpa\Infra\Rpc\Request\Body as RequestBodyInterface;
use Gnumoksha\FreeIpa\Infra\Rpc\Response\Body as ResponseBodyInterface;
use Psr\Http\Client\ClientExceptionInterface;

use function strlen;

class GroupRepository extends BaseRepository
{
    /** @var string */
    private const TOPIC = 'group';

    private Client $client;

    private RequestBodyInterface $body;

    public function __construct(Client $client, RequestBodyInterface $body)
    {
        $this->client = $client;
        $this->body   = $body;
    }

    /**
     * @TODO document string-only argument
     * @throws JsonException
     * @throws ClientExceptionInterface
     */
    public function find(array $arguments, array $options): ResponseBodyInterface
    {
        $defaultOptions = [
            'all'           => true,
            'private'       => false,
            'posix'         => false,
            'external'      => false,
            'nonposix'      => true,
            'no_members'    => true,
            'raw'           => false,
        ];

        $body = $this->body->withMethod(self::TOPIC . '_find')
            ->withArguments($arguments)
            ->withAddedOptions(array_merge($defaultOptions, $options));

        return $this->client->sendRequest($body);
    }

    /**
     * @throws JsonException
     * @throws ClientExceptionInterface
     *
     * @see \Gnumoksha\FreeIpa\Model\Group\GroupRepository::find() base method
     */
    public function findBy(string $field, string $value): ResponseBodyInterface
    {
        return $this->find([], [$field => $value]);
    }

    /**
     * @param string $group
     * @param string $uid
     * @return ResponseBodyInterface
     * @throws JsonException
     * @throws ClientExceptionInterface
     */
    public function addMember(string $group, string $uid): ResponseBodyInterface
    {
        $defaultOptions = [
            'all'           => false,
            'raw'           => false,
            'no_members'    => true,
        ];

        $body = $this->body->withMethod(self::TOPIC . '_add_member')
            ->withArguments([
                $group,
            ])
            ->withAddedOptions(array_merge($defaultOptions, [
                'user' => [$uid]
            ]));

        return $this->client->sendRequest($body);
    }

    public function __call(string $name, array $arguments): ResponseBodyInterface
    {
        if (strncmp($name, 'findBy', 6) === 0 && strlen($name) > 6) {
            $field = str_replace('findBy', '', $name);
            $field = strtolower($field); // Sn => sn
            // #TODO camelCase to snake_case em alguns casos (givenname excecao)
            return $this->findBy($field, $arguments[0]);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s', __CLASS__, $name));
    }
}
