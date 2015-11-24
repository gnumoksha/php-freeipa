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
 * Class to access user resources
 *
 * @author Tobias Sette <contato@tobias.ws>
 * @copyright Copyright (c) 2015 Tobias Sette <contato@tobias.ws>
 * @license LGPLv3
 * @package php-freeipa
 * @since GIT: 0.1.0
 * @version GIT: 0.2.0
 */
class User extends \FreeIPA\APIAccess\Base
{
 
    /**
     * Search user through of user_find method.
     * If $args is a string, the server will search in login, first_name and
     * last_name fields.
     *
     * @param array $args arguments for user_find method
     * @param array $options options for user_find method
     * @return array|bool false if the user was not found
     * @since GIT: 0.1.0
     * @version GIT: 0.2.0
     * @throws \Exception if error in json return
     * @see ../../docs/return_samples/user_find.txt
     * @see \FreeIPA\APIAccess\Connection\buildRequest()
     */
    public function find($args = array(), $options = array())
    {
        if (!is_array($args) || !is_array($options)) {
            return false;
        }

        // Obtained with the command ipa -vv user-find --all
        $default_options = array(
            'all' => true,
            'no_members' => false,
            'pkey_only' => false,
            'raw' => false,
            'whoami' => false,
        );
        $final_options = array_merge($default_options, $options);

        $return_request = $this->getConnection()->buildRequest('user_find', $args, $final_options); //returns json and http code of response
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
     * Search user by field
     * Principal fields are:
     *  'givenname' => first name
     *  'sn' => last name
     *  'cn' => full name
     *  'in_group' => user is in group
     *  'not_in_group' => user it not in group
     *  'mail' => e-mail address
     *  'uid' => user unique name
     *  'uidnumber' => user unique number
     *
     * @param array $field field name. See examples above
     * @param string $value field value
     * @return array|bool false if the user was not found
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
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
     * Get user data by login through user_show method
     *
     * @param string|array $params user login or some parameters
     * @param array $options options for user_show method
     * @return array|bool false if the user was not found
     * @since GIT: 0.1.0
     * @version GIT: 0.2.0
     * @throws \Exception se houver erro no retorno json
     * @see ../../docs/return_samples/user_show.txt
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
            'rights' => false,
        );
        $final_options = array_merge($options, $default_options);

        $return_request = $this->getConnection()->buildRequest('user_show', $final_params, $final_options, false);
        $json = $return_request[0];

        if (!empty($json->error) && strtolower($json->error->name) == 'notfound') {
            // user not found
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
     * Adds a user
     * The main fields in $data:
     *  'givenname' => first name
     *  'sn' => last name
     *  'cn' => full name
     *  'mail' => e-mail address
     *  'uid' => login (required field)
     *  'userpassword' => user password
     * 
     * @param array $data user data. See example above
     * @return object|bool Object with new user data or false if the user was not found
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     * @see \FreeIPA\APIAccess\Connection\buildRequest()
     */
    public function add($data)
    {
        if (!$data || !isset($data['uid']) || empty($data['uid'])) {
            return false;
        }

        // Obtained with the command:
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

        // The buildRequest() method already checks the field 'error', which is the only relevant to this API method
        $return_request = $this->getConnection()->buildRequest('user_add', $args, $final_options); //returns json and http code of response
        if (!$return_request) {
            return false;
        }

        return $return_request[0]->result->result;
    }

    /**
     * Change user data
     * The main fields in $data:
     *  'givenname' => first name
     *  'sn' => last name
     *  'cn' => full name
     *  'mail' => e-mail address
     *  'userpassword' => user password
     *  'krbprincipalexpiration' => Date of password expiration (Python __datetime__). Example: 20150816010101Z
     * 
     * If user does not exists, the \FreeIPA\APIAccess\Connection\buildRequest() method will return \Exception.
     * Please, note that change the user password will be subject to server policies, such as
     * length, expiration date and freeIPA behavior that will invalidate the first password.
     * If password was invalidated the user don't will be able to make login through authenticate() method
     *
     * @param string $login uid user that will be changed
     * @param array $data See above
     * @return object|bool Object with new 1user data or false if the user was not found
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     * @see ../../docs/return_samples/user_mod.txt
     * @see \FreeIPA\APIAccess\Connection\buildRequest()
     * @link https://www.freeipa.org/page/New_Passwords_Expired
     * @link https://access.redhat.com/documentation/en-US/Red_Hat_Enterprise_Linux/6/html/Identity_Management_Guide/changing-pwds.html
     * @link http://docs.fedoraproject.org/en-US/Fedora/17/html/FreeIPA_Guide/pwd-expiration.html
     */
    public function modify($login = null, $data = array())
    {
        if (!$login || !$data) {
            return false;
        }

        // Obtained with the command: ipa -vv user_mod tobias --first="testaaaaaa"
        $args = array($login);
        $default_options = array(
            'all' => false,
            'no_members' => false,
            'random' => false,
            'raw' => false,
            'rights' => false,
        );
        $final_options = array_merge($default_options, $data);

        // The buildRequest() method already checks the field 'error', which is the only relevant to this API method
        $return_request = $this->getConnection()->buildRequest('user_mod', $args, $final_options); //returns json and http code of response
        if (!$return_request) {
            return false;
        }

        return $return_request[0]->result->result;
    }
    
}
