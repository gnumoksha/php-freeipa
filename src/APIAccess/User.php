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
class User extends \FreeIPA\APIAccess\Core
{
 
    /**
     * Procura usuários através do método user_find e retorna suas informações
     * Se uma string for especificada em $argumentos, o servidor irá fazer uma busca genérica
     * procurando a string nos campos login, first_name e last_name.
     *
     * @param array $argumentos argumentos para o método user_find.
     * @param array $opcoes parâmetros para o método user_find
     * @return array|bool um indice para cada resultado, em cada resultado um objeto. OU false se não encontrar o usuário
     * @since 0.1
     * @throws \Exception se houver erro no retorno json
     * @see ../docs/return_samples/user_find.txt
     * @see buildRequest()
     */
    public function findUser($argumentos = array(), $opcoes = array())
    {
        if (!is_array($argumentos) || !is_array($opcoes)) {
            return false;
        }

        // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv user-find --all
        $opcoes_padrao = array(
            'all' => true,
            'no_members' => false,
            'pkey_only' => false,
            'raw' => false,
            'whoami' => false,
        );
        $opcoes_final = array_merge($opcoes_padrao, $opcoes);

        $retorno_requisicao = $this->buildRequest('user_find', $argumentos, $opcoes_final); // retorna json e codigo http da resposta
        $json = $retorno_requisicao[0];
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
     * @param array $campo nome do campo. Ver exemplos acima
     * @param string $valor valor para $campo
     * @return array|bool um indice para cada resultado, em cada resultado um objeto. OU false se não encontrar o usuário
     * @since 0.2
     * @see findUser()
     */
    public function findUserBy($campo = null, $valor = null)
    {
        if (!$campo || !$valor) {
            return false;
        }

  //    $opcoes = array( $campo_ipa => $valor );
        $opcoes = array($campo => $valor);
        return $this->findUser(array(), $opcoes);
    }

    /**
     * Obtém os dados de um usuário identificado pelo seu login através
     * do método user_show da API.
     *
     * @param string|array $parametros login do usuário ou array com parâmetros para o método user_show
     * @param array $opcoes opções para o método user_show
     * @return array|bool um indice para cada resultado, em cada resultado um objeto. OU false se não encontrar o usuário
     * @since 0.1
     * @since 0.2 $parametros pode ser uma string
     * @throws \Exception se houver erro no retorno json
     * @see ../docs/return_samples/user_show.txt
     * @see buildRequest()
     */
    public function getUser($parametros = null, $opcoes = array())
    {
        if (!is_array($opcoes)) {
            return false;
        }

        if (is_string($parametros)) {
            $parametros_final = array($parametros);
        } else if (is_array($parametros)) {
            $parametros_final = $parametros;
        } else {
            return false;
        }

        // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando [root@fedora ~]# ipa -vv user-show admin
        $opcoes_padrao = array(
            'all' => true,
            'no_members' => false,
            'raw' => false,
            'rights' => false,
        );
        $opcoes_final = array_merge($opcoes, $opcoes_padrao);

        $retorno_requisicao = $this->buildRequest('user_show', $parametros_final, $opcoes_final, false); // retorna json e codigo http da resposta
        $json = $retorno_requisicao[0];
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
     * Principais campos em $dados:
     *  'givenname' => primeiro nome
     *  'sn' => sobrenome
     *  'cn' => nome completo
     *  'mail' => endereço de e-mail
     *  'uid' => nome de usuário (login). Campo obrigatório
     *  'userpassword' => senha do usuario
     * 
     * @param array $dados contém as informações do usuário. Ver exemplo acima
     * @return object|bool Objeto contendo os dados do usuário criado ou false em caso de erro
     * @since 0.2
     * @see buildRequest()
     */
    public function adicionarUsuario($dados)
    {
        if (!$dados || !isset($dados['uid']) || empty($dados['uid'])) {
            return false;
        }

        // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv user_add tobias --first="Tobias" --last="Sette" --email="contato@tobias.ws" --password
        $argumentos = array($dados['uid']);
        $opcoes_padrao = array(
            'all' => false,
            'no_members' => false,
            'noprivate' => false,
            'random' => false,
            'raw' => false,
        );
        unset($dados['uid']);
        $opcoes_final = array_merge($opcoes_padrao, $dados);

        // O método buildRequest() já verifica o campo 'error', que é o único relevante para este método da API
        $retorno_requisicao = $this->buildRequest('user_add', $argumentos, $opcoes_final); // retorna json e codigo http da resposta
        if (!$retorno_requisicao) {
            return false;
        }

        return $retorno_requisicao[0]->result->result;
    }

    /**
     * Altera os dados de um usuário no FreeIPA.
     * Principais campos em $dados:
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
     * @param array $dados contém as informações que serão alteradas. Ver exemplo acima
     * @return object|bool Objeto contendo os dados do usuário criado ou false em caso de erro
     * @since 0.2
     * @see ../docs/return_samples/user_mod.txt
     * @see buildRequest()
     * @link https://www.freeipa.org/page/New_Passwords_Expired
     * @link https://access.redhat.com/documentation/en-US/Red_Hat_Enterprise_Linux/6/html/Identity_Management_Guide/changing-pwds.html
     * @link http://docs.fedoraproject.org/en-US/Fedora/17/html/FreeIPA_Guide/pwd-expiration.html
     */
    public function modificarUsuario($login = null, $dados = array())
    {
        if (!$login || !$dados) {
            return false;
        }

        // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv user_mod tobias --first="testeaaaaaa"
        $argumentos = array($login);
        $opcoes_padrao = array(
            'all' => false,
            'no_members' => false,
            'random' => false,
            'raw' => false,
            'rights' => false,
        );
        $opcoes_final = array_merge($opcoes_padrao, $dados);

        // O método buildRequest() já verifica o campo 'error', que é o único relevante para este método da API
        $retorno_requisicao = $this->buildRequest('user_mod', $argumentos, $opcoes_final); // retorna json e codigo http da resposta
        if (!$retorno_requisicao) {
            return false;
        }

        return $retorno_requisicao[0]->result->result;
    }
    
}