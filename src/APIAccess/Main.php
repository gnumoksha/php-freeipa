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
//require_once('Connection.php');
//require_once('User.php');
//require_once('Group.php');

/**
 * Classes for access to FreeIPA API
 * @since 0.1
 */
namespace FreeIPA\APIAccess;

/**
 * Façade class to access all implemented methods and resources on this lib
 *
 * @author Tobias Sette <contato@tobias.ws>
 * @copyright Copyright (c) 2015 Tobias Sette <contato@tobias.ws>
 * @license LGPLv3
 * @package php-freeipa
 * @since GIT 0.1.0
 * @version GIT: 0.2.0
 */
class Main
{
    /**
     *
     * @var \FreeIPA\APIAccess\Connection
     */
    protected $_connection = null;
    
    /**
     *
     * @var \FreeIPA\APIAccess\User
     */
    protected $_user = null;
    
    /**
     *
     * @var \FreeIPA\APIAccess\Group
     */
    protected $_group = null;

    
    public function __construct($server = null, $certificate = null)
    {
        $this->_connection = $this->connection($server, $certificate);
        $this->_user = new \FreeIPA\APIAccess\User($this->_connection);
        $this->_group = $this->_group = new \FreeIPA\APIAccess\Group($this->_connection);
    }
    
    /**
     * 
     * @param string $server
     * @param string $certificate
     * @return \FreeIPA\APIAccess\Connection
     */
    public function connection($server = null, $certificate = null)
    {
        if (! $this->_connection) {
            $this->_connection = \FreeIPA\APIAccess\Connection::getInstance($server, $certificate);
        }
        return($this->_connection);
    }

    /**
     * 
     * @return \FreeIPA\APIAccess\User
     */
    public function user()
    {
        return($this->_user);
    }

    /**
     * 
     * @return \FreeIPA\APIAccess\Group
     */
    public function group()
    {
        return($this->_group);
    }
}
