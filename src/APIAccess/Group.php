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
     * Delete a group
     *
     * @param string $name group name
     * @param array|string $data see above
     * @return object|bool Object with new group data or false if the group was not found
     * @see \FreeIPA\APIAccess\Connection\buildRequest()
     */
    public function del($name = null, $data = array())
    {
        if (!$name || !$data) {
            return false;
        }

        // Obtained with the command: ipa -vv group-add group_one --desc="Group one" --all
        $args = array($name);
        $default_options = array(
            'continue' => false,
        );
        if (is_array($data)) {
            $final_options = array_merge($default_options, $data);
        } else {
            return false;
        }

        // The buildRequest() method already checks the field 'error', which is the only relevant to this API method
        $response = $this->getConnection()->buildRequest('group_del', $args, $final_options); //returns json and http code of response
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

    /**
     * Deletes members (users or other groups) to group
     * The main fields in $data:
     *  'user' => array that contain users that will be added
     *  'group' => array that contain groups that will be added
     * If $data is a string, will be user uid
     *
     * @param string $group_name group name
     * @param array|string $data See explanation above
     * @return mixed Array containing information about processing and group data OR false on error
     * @see \FreeIPA\APIAccess\Connection\buildRequest()
     * @throws \Exception if the request was not completed successfully
     */
    public function removeMember($group_name = null, $data = array())
    {
        if (!$group_name || !$data) {
            return false;
        }

        // Obtained with the command: ipa -vv group_remove_member group_one --users="stallman"
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

        $response = $this->getConnection()->buildRequest('group_remove_member', $args, $final_options); //returns json and http code of response
        if (!$response) {
            return false;
        }
        $returned_json = $response[0];
        if (!$returned_json->result->completed) {
            $message = "Error while removing members in group \"$group_name\".";
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

    /**
     * Search group through of group_find method.
     *
     * @param array $args arguments for user_find method
     * @param array $options options for user_find method
     * @return array|bool false if the group was not found
     * @throws \Exception if error in json return
     * @see \FreeIPA\APIAccess\Connection\buildRequest()
     */
    public function find($args = array(), $options = array())
    {
        if (!is_array($args) || !is_array($options)) {
            return false;
        }

        // Obtained with the command ipa -vv group-find --all
        $default_options = array(
            'all' => true,
            'raw' => false,
            'private' => false,
            'posix' => false,
            'external' => false,
            'nonposix' => false,
            'no_members'    => false,
            'timelimit' => 0,
            'sizelimit' => 0,
        );
        $final_options = array_merge($default_options, $options);

        $return_request = $this->getConnection()->buildRequest('group_find', $args, $final_options); //returns json and http code of response
        $json = $return_request[0];

        if (empty($json->result) || !isset($json->result->count)) {
            throw new \Exception('Malformed json');
        }

        if ($json->result->count < 1) {
            return false;
        }

        return $json->result->result;
    }

    /**
     * Search group by field
     *
     * @param array $field field name.
     * @param string $value field value
     * @return array|bool false if the group was not found
     * @see find()
     */
    public function findBy($field = null, $value = null)
    {
        if (!$field || !$value) {
            return false;
        }

        $options = array($field => $value);
        return $this->find(array(), $options);
    }

    /**
     * Get group data by cn through group_show method
     *
     * @param string|array $params cn or some parameters
     * @param array $options options for group_show method
     * @return array|bool false if the group was not found
     * @throws \Exception se houver erro no retorno json
     * @see \FreeIPA\APIAccess\Connection\buildRequest()
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

        // Obtained with the command ipa -vv user-show admin
        $default_options = array(
            'all' => true,
            'no_members' => false,
            'raw' => false,
            'rights'    => false,

        );
        $final_options = array_merge($options, $default_options);

        $return_request = $this->getConnection()->buildRequest('group_show', $final_params, $final_options, false);
        $json = $return_request[0];

        if (!empty($json->error) && strtolower($json->error->name) == 'notfound') {
            // group not found
            return false;
        }

        if (empty($json->result)) {
            throw new \Exception('Malformed json');
        }

        // #TODO erase this code?
        if (!isset($json->result->result)) {
            return false;
        }

        return $json->result->result;
    }

    /**
     * Change group data
     *
     * If group does not exists, the \FreeIPA\APIAccess\Connection\buildRequest() method will return \Exception.
     *
     * @param string $cn of group that will be changed
     * @param array $data See above
     * @return object|bool Object with new group data or false if the group was not found
     * @see \FreeIPA\APIAccess\Connection\buildRequest()
     */
    public function modify($cn = null, $data = array())
    {
        if (!$cn || !$data) {
            return false;
        }

        // Obtained with the command: ipa -vv group_mod tobias --first="testaaaaaa"
        $args = array($cn);
        $default_options = array(
            'all' => false,
            'no_members' => false,
            'posix' => false,
            'raw' => false,
            'external' => false,
            'rights' => false,
        );
        $final_options = array_merge($default_options, $data);

        // The buildRequest() method already checks the field 'error', which is the only relevant to this API method
        $return_request = $this->getConnection()->buildRequest('group_mod', $args, $final_options); //returns json and http code of response
        if (!$return_request) {
            return false;
        }

        return $return_request[0]->result->result;
    }
}
