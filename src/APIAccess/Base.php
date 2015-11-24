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

/**
 * Classes for access to FreeIPA API
 * @since GIT: 0.1.0
 */
namespace FreeIPA\APIAccess;

/**
 * This class defines common things for other classes that reuses an
 * received connection
 * 
 * @author Tobias Sette <contato@tobias.ws>
 * @copyright Copyright (c) 2015 Tobias Sette <contato@tobias.ws>
 * @license LGPLv3
 * @package php-freeipa
 * @since GIT: 0.1.0
 * @version GIT: 0.2.0
 */
class Base
{
    /**
     *
     * @var Connection stores a connection with the freeIPA server 
     * @since GIT: 0.1.0
     */
    protected $_connection = false;
    
    
    /**
     * @param \FreeIPA\APIAccess\Connection $connection
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function __construct(Connection $connection)
    {
        $this->setConnection($connection);
    }
    
    /**
     * @param \FreeIPA\APIAccess\Connection $connection
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function setConnection(Connection $connection)
    {
        $this->_connection = $connection;
    }
    
    /**
     * @return \FreeIPA\APIAccess\Connection
     * @since GIT: 0.1.0
     * @version GIT: 0.2.0
     */
    public function getConnection()
    {
        if (false === $this->_connection) {
            throw new \Exception('No connection');
        } else {
            return($this->_connection);
        }
    }
}
