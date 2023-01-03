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

namespace Gnumoksha\FreeIpa\Model\User;

use BadMethodCallException;
use Gnumoksha\FreeIpa\Infra\Json\JsonException;
use Gnumoksha\FreeIpa\Infra\Repository\BaseRepository;
use Gnumoksha\FreeIpa\Infra\Rpc\Client;
use Gnumoksha\FreeIpa\Infra\Rpc\Request\Body as RequestBodyInterface;
use Gnumoksha\FreeIpa\Infra\Rpc\Response\Body as ResponseBodyInterface;
use Psr\Http\Client\ClientExceptionInterface;

use function strlen;

/**
 * @method ResponseBodyInterface findByGivenName($value) first name
 * @method ResponseBodyInterface findBySn($value) last name
 * @method ResponseBodyInterface findByCn($value) full name
 * @method ResponseBodyInterface findByInGroup($value)
 * @method ResponseBodyInterface findByNotInGroup($value)
 * @method ResponseBodyInterface findByMail($value)
 * @method ResponseBodyInterface findByUid($value) unique name
 * @method ResponseBodyInterface findByUidNumber($value) unique number
 */
class UserRepository extends BaseRepository
{
    /** @var string */
    private const TOPIC = 'user';

    private Client $client;

    private RequestBodyInterface $body;

    public function __construct(Client $client, RequestBodyInterface $body)
    {
        $this->client = $client;
        $this->body   = $body;
    }

    /**
     * @param $user
     * @param array $arguments
     * @param array $options
     * @return ResponseBodyInterface
     * @throws JsonException
     * @throws ClientExceptionInterface
     */
    public function add($user, array $arguments = [], array $options = []): ResponseBodyInterface
    {
        if (\is_object($user)) {
            $user = (array)$user;
        }

        $defaultOptions = [
            'all'        => false,
            'no_members' => false,
            'noprivate'  => false,
            'random'     => false,
            'raw'        => false,
        ];

        $arguments = array_merge([$user['uid']], $arguments);
        unset($user['uid']);

        $body = $this->body->withMethod(self::TOPIC . '_add')
                           ->withArguments($arguments)
                           ->withAddedOptions(array_merge($defaultOptions, $user, $options));

        return $this->client->sendRequest($body);
    }

    public function show(array $arguments, array $options = []): ResponseBodyInterface
    {
        $defaultOptions = [
            'all'        => true,
            'no_members' => false,
            'raw'        => false,
            'rights'     => false,
        ];

        $body = $this->body->withMethod(self::TOPIC . '_show')
                           ->withArguments($arguments)
                           ->withAddedOptions(array_merge($defaultOptions, $options));

        return $this->client->sendRequest($body);
    }

    /**
     * @TODO document string-only argument
     * @throws JsonException
     * @throws ClientExceptionInterface
     */
    public function find(array $arguments, array $options): ResponseBodyInterface
    {
        $defaultOptions = [
            'all'        => true,
            'no_members' => false,
            'pkey_only'  => false,
            'raw'        => false,
            'whoami'     => false,
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
     * @see \Gnumoksha\FreeIpa\Model\User\UserRepository::find() base method
     */
    public function findBy(string $field, string $value): ResponseBodyInterface
    {
        return $this->find([], [$field => $value]);
    }

    public function mod(string $uid, array $newData, array $arguments = [], array $options = []): ResponseBodyInterface
    {
        $defaultOptions = [
            'all'        => false,
            'no_members' => false,
            'random'     => false,
            'raw'        => false,
            'rights'     => false,
        ];

        $arguments = array_merge([$uid], $arguments);

        $body = $this->body->withMethod(self::TOPIC . '_mod')
                           ->withArguments($arguments)
                           ->withAddedOptions(array_merge($defaultOptions, $newData, $options));

        return $this->client->sendRequest($body);
    }

    public function del(array $arguments, array $options = []): ResponseBodyInterface
    {
        $defaultOptions = [
        ];

        $body = $this->body->withMethod(self::TOPIC . '_del')
                           ->withArguments($arguments)
                           ->withAddedOptions(array_merge($defaultOptions, $options));

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
