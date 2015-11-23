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
 * @package FreeIPA
 * @since 0.4
 * @version 0.1
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
        return($this->connection($server, $certificate));
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
            //$this->_connection->setIPAServer($server);
            //$this->_connection->setCertificateFile($certificate);
        }
        return($this->_connection);
    }

    /**
     * 
     * @return \FreeIPA\APIAccess\User
     */
    public function user()
    {
        if (! $this->_user) {
            $this->_user = new \FreeIPA\APIAccess\User($this->_connection);
        }
        return($this->_user);
    }

    /**
     * 
     * @return \FreeIPA\APIAccess\Group
     */
    public function group()
    {
        if (! $this->_group) {
            $this->_group = new \FreeIPA\APIAccess\Group($this->_connection);
        }
        return($this->_group);
    }
}
