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
 * Class to access group resources
 *
 * @author Tobias Sette <contato@tobias.ws>
 * @copyright Copyright (c) 2015 Tobias Sette <contato@tobias.ws>
 * @license LGPLv3
 * @package FreeIPA
 * @since 0.4
 * @version 0.1
 */
class Group extends \FreeIPA\APIAccess\Core
{
    /**
     * Adiciona um grupo no FreeIPA
     * Principais parâmetros de $dados:
     *  'description' => descrição do grupo
     * Se $dados for uma string, será encarada como sendo a descrição do grupo
     * 
     * @param string $nome nome do grupo
     * @param array|string $dados contém as informações que serão adicionadas. Ver exemplo acima
     * @return object|bool Objeto contendo os dados do grupo criado ou false em caso de erro
     * @since 0.2
     * @see ../docs/return_samples/group_add.txt
     * @see buildRequest()
     */
    public function adicionarGrupo($nome = null, $dados = array())
    {
        if (!$nome || !$dados) {
            return false;
        }

        // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv group-add blube_bolinha --desc="Grupo tal" --all 
        $argumentos = array($nome);
        $opcoes_padrao = array(
            'all' => false,
            'external' => false,
            'no_members' => false,
            'nonposix' => false,
            'raw' => false,
        );
        if (is_array($dados)) {
            $opcoes_final = array_merge($opcoes_padrao, $dados);
        } else if (is_string($dados)) {
            $opcoes_final = array_merge($opcoes_padrao, array('description' => $dados));
        } else {
            return false;
        }

        // O método buildRequest() já verifica o campo 'error', que é o único relevante para este método da API
        $retorno_requisicao = $this->buildRequest('group_add', $argumentos, $opcoes_final); // retorna json e codigo http da resposta
        if (!$retorno_requisicao) {
            return false;
        }

        return $retorno_requisicao[0]->result->result;
    }

    /**
     * Adiciona membros (usuários ou outros grupos) a um grupo
     * Parâmetros principais de $dados:
     *  'user' => array contendo os usuários a serem adicionados
     *  'group' => array contendo os grupos a serem adicionados
     * Se $dados for uma string, será encarado como sendo o uid de um usuário
     * 
     * @param string $nome_grupo Nome do grupo no qual os membros serão adicionados
     * @param array|string $dados contém as informações que serão adicionadas. Ver exemplo acima
     * @return mixed Array contendo informações sobre o processamento e os dados do grupo em questão. Ou false em caso de erro
     * @since 0.2
     * @see ../docs/return_samples/group_add_member.txt
     * @see buildRequest()
     * @throws \Exception se a requisição não foi completada com sucesso
     */
    public function adicionarMembroGrupo($nome_grupo = null, $dados = array())
    {
        if (!$nome_grupo || !$dados) {
            return false;
        }

        // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv group_add_member clube_bolinha --users="stallman" 
        $argumentos = array($nome_grupo);
        $opcoes_padrao = array(
            'all' => true,
            'no_members' => false,
            'raw' => false,
        );
        if (is_array($dados)) {
            $opcoes_final = array_merge($opcoes_padrao, $dados);
        } else if (is_string($dados)) {
            $opcoes_final = array_merge($opcoes_padrao, array('user' => array($dados)));
        } else {
            return false;
        }

        $retorno_requisicao = $this->buildRequest('group_add_member', $argumentos, $opcoes_final); // retorna json e codigo http da resposta
        if (!$retorno_requisicao) {
            return false;
        }
        $json_retorno = $retorno_requisicao[0];
        if (!$json_retorno->result->completed) {
            $mensagem = "Erro ao inserir membros no grupo \"$nome_grupo\".";
            if (!empty($json_retorno->result->failed->member->group) || !empty($json_retorno->result->failed->member->user)) {
                $mensagem .= 'Detalhes: ';
            }

            if (!empty($json_retorno->result->failed->member->group)) {
                $mensagem .= implode(' ', $json_retorno->result->failed->member->group[0]);
            }

            if (!empty($json_retorno->result->failed->member->user)) {
                $mensagem .= implode(' ', $json_retorno->result->failed->member->user[0]);
            }

            throw new \Exception($mensagem);
        }

        // ao contrário os outros métodos, onde é retornado $json_retorno->result->result, o $json_retorno->result deste contém informações que podem ser úteis
        return $json_retorno->result;
    }
}