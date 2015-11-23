<?php
/**
FreeIPA library for PHP
Copyright (C) 2015  Tobias Sette <contato@tobias.ws>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Dependencies:
//require_once('Base.php');

/**
 * Classes for access to FreeIPA API
 * @since 0.1
 */
namespace FreeIPA\APIAccess;

/**
 * Class to access user resources
 *
 * @author Tobias Sette <contato@tobias.ws>
 * @copyright Copyright (c) 2015 Tobias Sette <contato@tobias.ws>
 * @license LGPLv3
 * @package FreeIPA
 * @since 0.4
 * @version 0.1
 */
class User extends \FreeIPA\APIAccess\Base
{
 
    /**
     * Procura usuários através do método user_find e retorna suas informações
     * Se uma string for especificada em $args, o servidor irá fazer uma busca genérica
     * procurando a string nos campos login, first_name e last_name.
     *
     * @param array $args argumentos para o método user_find.
     * @param array $options parâmetros para o método user_find
     * @return array|bool um indice para cada resultado, em cada resultado um objeto. OU false se não encontrar o usuário
     * @since 0.1
     * @throws \Exception se houver erro no retorno json
     * @see ../docs/return_samples/user_find.txt
     * @see buildRequest()
     */
    public function find($args = array(), $options = array())
    {
        if (!is_array($args) || !is_array($options)) {
            return false;
        }

        // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv user-find --all
        $default_options = array(
            'all' => true,
            'no_members' => false,
            'pkey_only' => false,
            'raw' => false,
            'whoami' => false,
        );
        $final_options = array_merge($default_options, $options);

        $return_request = $this->getConnection()->buildRequest('user_find', $args, $final_options); // retorna json e codigo http da resposta
        $json = $return_request[0];
        $json_string = json_encode($json);

        if (empty($json->result) || !isset($json->result->count)) {
            throw new \Exception("Um ou mais elementos necessários não foram encontrados na resposta json. Resposta foi \"$json_string\".");
        }

        if ($json->result->count < 1) {
            return false;
        }

        return $json->result->result;
    }

    /**
     * Procura usuário através de um campo especificado e retorna suas informações
     * Principais campos são:
     *  'givenname' => primeiro nome
     *  'sn' => sobrenome
     *  'cn' => nome completo
     *  'in_group' => usuário está no grupo
     *  'not_in_group' => usuário não está no grupo
     *  'mail' => endereço de e-mail
     *  'uid' => nome de usuário
     *  'uidnumber' => identificador único do usuário
     *
     * @param array $field nome do campo. Ver exemplos acima
     * @param string $value valor para $field
     * @return array|bool um indice para cada resultado, em cada resultado um objeto. OU false se não encontrar o usuário
     * @since 0.2
     * @see findUser()
     */
    public function findBy($field = null, $value = null)
    {
        if (!$field || !$value) {
            return false;
        }

  //    $options = array( $field_ipa => $value );
        $options = array($field => $value);
        return $this->find(array(), $options);
    }

    /**
     * Obtém os dados de um usuário identificado pelo seu login através
     * do método user_show da API.
     *
     * @param string|array $params login do usuário ou array com parâmetros para o método user_show
     * @param array $options opções para o método user_show
     * @return array|bool um indice para cada resultado, em cada resultado um objeto. OU false se não encontrar o usuário
     * @since 0.1
     * @since 0.2 $params pode ser uma string
     * @throws \Exception se houver erro no retorno json
     * @see ../docs/return_samples/user_show.txt
     * @see buildRequest()
     */
    public function get($params = null, $options = array())
    {
        if (!is_array($options)) {
            return false;
        }

        if (is_string($params)) {
            $final_params = array($params);
        } else if (is_array($params)) {
            $final_params = $params;
        } else {
            return false;
        }

        // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando [root@fedora ~]# ipa -vv user-show admin
        $default_options = array(
            'all' => true,
            'no_members' => false,
            'raw' => false,
            'rights' => false,
        );
        $final_options = array_merge($options, $default_options);

        // retorna json e codigo http da resposta
        $return_request = $this->getConnection()->buildRequest('user_show', $final_params, $final_options, false);
        $json = $return_request[0];
        $json_string = json_encode($json);

        if (!empty($json->error) && strtolower($json->error->name) == 'notfound') {
            // usuário não encontrado
            return false;
        }

        if (empty($json->result)) {
            throw new \Exception("Um ou mais elementos necessários não foram encontrados na resposta json. Resposta foi \"$json_string\".");
        }

        // #TODO remover este trecho?
        if (!isset($json->result->result)) {
            return false;
        }

        return $json->result->result;
    }

    /**
     * Adiciona usuário no FreeIPA
     * Principais campos em $data:
     *  'givenname' => primeiro nome
     *  'sn' => sobrenome
     *  'cn' => nome completo
     *  'mail' => endereço de e-mail
     *  'uid' => nome de usuário (login). Campo obrigatório
     *  'userpassword' => senha do usuario
     * 
     * @param array $data contém as informações do usuário. Ver exemplo acima
     * @return object|bool Objeto contendo os dados do usuário criado ou false em caso de erro
     * @since 0.2
     * @see buildRequest()
     */
    public function add($data)
    {
        if (!$data || !isset($data['uid']) || empty($data['uid'])) {
            return false;
        }

        // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando
        // ipa -vv user_add tobias --first="Tobias" --last="Sette" --email="contato@tobias.ws" --password
        $args = array($data['uid']);
        $default_options = array(
            'all' => false,
            'no_members' => false,
            'noprivate' => false,
            'random' => false,
            'raw' => false,
        );
        unset($data['uid']);
        $final_options = array_merge($default_options, $data);

        // O método buildRequest() já verifica o campo 'error', que é o único relevante para este método da API
        $return_request = $this->getConnection()->buildRequest('user_add', $args, $final_options); // retorna json e codigo http da resposta
        if (!$return_request) {
            return false;
        }

        return $return_request[0]->result->result;
    }

    /**
     * Altera os dados de um usuário no FreeIPA.
     * Principais campos em $data:
     *  'givenname' => primeiro nome
     *  'sn' => sobrenome
     *  'cn' => nome completo
     *  'mail' => endereço de e-mail
     *  'userpassword' => senha do usuario
     *  'krbprincipalexpiration' '__datetime__' => Data da expiração da senha. Exemplo: 20150816010101Z
     * 
     * Caso o usuário não exista, o método buildRequest() irá retornar uma \Exception.
     * Note que ao alterar a senha o usuário estará sujeito as políticas do servidor, tais como
     * tamanho e data de expiração da senha, alem da politica do FreeIPA de invalidar a primeira senha.
     * Se a senha for invalidada o usuário não conseguirá fazer login através do método authenticate()
     *
     * @param string $login login (uid) do usuário que será alterado.
     * @param array $data contém as informações que serão alteradas. Ver exemplo acima
     * @return object|bool Objeto contendo os dados do usuário criado ou false em caso de erro
     * @since 0.2
     * @see ../docs/return_samples/user_mod.txt
     * @see buildRequest()
     * @link https://www.freeipa.org/page/New_Passwords_Expired
     * @link https://access.redhat.com/documentation/en-US/Red_Hat_Enterprise_Linux/6/html/Identity_Management_Guide/changing-pwds.html
     * @link http://docs.fedoraproject.org/en-US/Fedora/17/html/FreeIPA_Guide/pwd-expiration.html
     */
    public function modify($login = null, $data = array())
    {
        if (!$login || !$data) {
            return false;
        }

        // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv user_mod tobias --first="testeaaaaaa"
        $args = array($login);
        $default_options = array(
            'all' => false,
            'no_members' => false,
            'random' => false,
            'raw' => false,
            'rights' => false,
        );
        $final_options = array_merge($default_options, $data);

        // O método buildRequest() já verifica o campo 'error', que é o único relevante para este método da API
        $return_request = $this->getConnection()->buildRequest('user_mod', $args, $final_options); // retorna json e codigo http da resposta
        if (!$return_request) {
            return false;
        }

        return $return_request[0]->result->result;
    }
    
}
