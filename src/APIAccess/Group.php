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
 * Class to access group resources
 *
 * @author Tobias Sette <contato@tobias.ws>
 * @copyright Copyright (c) 2015 Tobias Sette <contato@tobias.ws>
 * @license LGPLv3
 * @package FreeIPA
 * @since 0.4
 * @version 0.1
 */
class Group extends \FreeIPA\APIAccess\Base
{
    /**
     * Adiciona um grupo no FreeIPA
     * Principais parâmetros de $data:
     *  'description' => descrição do grupo
     * Se $data for uma string, será encarada como sendo a descrição do grupo
     * 
     * @param string $name nome do grupo
     * @param array|string $data contém as informações que serão adicionadas. Ver exemplo acima
     * @return object|bool Objeto contendo os dados do grupo criado ou false em caso de erro
     * @since 0.2
     * @see ../docs/return_samples/group_add.txt
     * @see buildRequest()
     */
    public function add($name = null, $data = array())
    {
        if (!$name || !$data) {
            return false;
        }

        // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv group-add blube_bolinha --desc="Grupo tal" --all 
        $args = array($name);
        $default_options = array(
            'all' => false,
            'external' => false,
            'no_members' => false,
            'nonposix' => false,
            'raw' => false,
        );
        if (is_array($data)) {
            $final_options = array_merge($default_options, $data);
        } else if (is_string($data)) {
            $final_options = array_merge($default_options, array('description' => $data));
        } else {
            return false;
        }

        // O método buildRequest() já verifica o campo 'error', que é o único relevante para este método da API
        $response = $this->getConnection()->buildRequest('group_add', $args, $final_options); // retorna json e codigo http da resposta
        if (!$response) {
            return false;
        }

        return $response[0]->result->result;
    }

    /**
     * Adiciona membros (usuários ou outros grupos) a um grupo
     * Parâmetros principais de $data:
     *  'user' => array contendo os usuários a serem adicionados
     *  'group' => array contendo os grupos a serem adicionados
     * Se $data for uma string, será encarado como sendo o uid de um usuário
     * 
     * @param string $group_name Nome do grupo no qual os membros serão adicionados
     * @param array|string $data contém as informações que serão adicionadas. Ver exemplo acima
     * @return mixed Array contendo informações sobre o processamento e os dados do grupo em questão. Ou false em caso de erro
     * @since 0.2
     * @see ../docs/return_samples/group_add_member.txt
     * @see buildRequest()
     * @throws \Exception se a requisição não foi completada com sucesso
     */
    public function addMember($group_name = null, $data = array())
    {
        if (!$group_name || !$data) {
            return false;
        }

        // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv group_add_member clube_bolinha --users="stallman" 
        $args = array($group_name);
        $default_options = array(
            'all' => true,
            'no_members' => false,
            'raw' => false,
        );
        if (is_array($data)) {
            $final_options = array_merge($default_options, $data);
        } else if (is_string($data)) {
            $final_options = array_merge($default_options, array('user' => array($data)));
        } else {
            return false;
        }

        $response = $this->getConnection()->buildRequest('group_add_member', $args, $final_options); // retorna json e codigo http da resposta
        if (!$response) {
            return false;
        }
        $returned_json = $response[0];
        if (!$returned_json->result->completed) {
            $message = "Error while inserting members in group \"$group_name\".";
            if (!empty($returned_json->result->failed->member->group) || !empty($returned_json->result->failed->member->user)) {
                $message .= 'Details: ';
            }

            if (!empty($returned_json->result->failed->member->group)) {
                $message .= implode(' ', $returned_json->result->failed->member->group[0]);
            }

            if (!empty($returned_json->result->failed->member->user)) {
                $message .= implode(' ', $returned_json->result->failed->member->user[0]);
            }

            throw new \Exception($message);
        }

        // ao contrário os outros métodos, onde é retornado $returned_json->result->result, o $returned_json->result deste contém informações que podem ser úteis
        return $returned_json->result;
    }
}
