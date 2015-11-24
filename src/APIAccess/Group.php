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
 * @since GIT: 0.1.0
 */
namespace FreeIPA\APIAccess;

/**
 * Class to access group resources
 *
 * @author Tobias Sette <contato@tobias.ws>
 * @copyright Copyright (c) 2015 Tobias Sette <contato@tobias.ws>
 * @license LGPLv3
 * @package php-freeipa
 * @since GIT: 0.1.0
 * @version GIT: 0.2.0
 */
class Group extends \FreeIPA\APIAccess\Base
{
    /**
     * Adds a group
     * The main fields in $data:
     *  'description' => group description
     * If $data is string will be a group description
     * 
     * @param string $name group name
     * @param array|string $data see above
     * @return object|bool Object with new group data or false if the group was not found
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     * @see ../../docs/return_samples/group_add.txt
     * @see \FreeIPA\APIAccess\Connection\buildRequest()
     */
    public function add($name = null, $data = array())
    {
        if (!$name || !$data) {
            return false;
        }

        // Obtained with the command: ipa -vv group-add group_one --desc="Group one" --all 
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

        // The buildRequest() method already checks the field 'error', which is the only relevant to this API method
        $response = $this->getConnection()->buildRequest('group_add', $args, $final_options); //returns json and http code of response
        if (!$response) {
            return false;
        }

        return $response[0]->result->result;
    }

    /**
     * Adds members (users or other groups) to group
     * The main fields in $data:
     *  'user' => array that contain users that will be added
     *  'group' => array that contain groups that will be added
     * If $data is a string, will be user uid
     * 
     * @param string $group_name group name
     * @param array|string $data See explanation above
     * @return mixed Array containing information about processing and group data OR false on error
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     * @see ../../docs/return_samples/group_add_member.txt
     * @see \FreeIPA\APIAccess\Connection\buildRequest()
     * @throws \Exception if the request was not completed successfully
     */
    public function addMember($group_name = null, $data = array())
    {
        if (!$group_name || !$data) {
            return false;
        }

        // Obtained with the command: ipa -vv group_add_member group_one --users="stallman" 
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

        $response = $this->getConnection()->buildRequest('group_add_member', $args, $final_options); //returns json and http code of response
        if (!$response) {
            return false;
        }
        $returned_json = $response[0];
        if (!$returned_json->result->completed) {
            $message = "Error while inserting members in group \"$group_name\".";
            if (!empty($returned_json->result->failed->member->group) ||
            !empty($returned_json->result->failed->member->user)) {
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

        // Unlike other methods, where $returned_json->result->result is returned,
        // here the $returned_json->result contain usefull information
        return $returned_json->result;
    }
}
