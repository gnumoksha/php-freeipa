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

namespace FreeIPA\APIAccess\Tests;

require_once('data.php');

/**
 * Class for test the main class
 * @since GIT: 0.1.0
 * @version GIT: 0.2.0
 */
class MainTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var mixed class instance
     * @access protected
     * @since GIT: 0.1.0
     */
    protected $ipa = NULL;
    
    /**
     * @var type the content of var data in data.php
     * @since GIT: 0.1.0
     */
    public $data = null;
    
    
    /**
     * Initialization of tests definitions
     * 
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function setUp()
    {
        global $data;
        $this->data = $data;
        $ipa = new \FreeIPA\APIAccess\Main($data['host'], $data['cert']);
        $r = $ipa->connection()->authenticate($data['user'], $data['pass']);
        if (false === $r) {
            $this->markTestIncomplete('This test need a connection with the server');
        }
        $this->setInstance($ipa);
    }
    
    /**
     * Set a instance of \FreeIPA\APIAccess\Main with connection
     * 
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function setInstance($instancia)
    {
        $this->ipa = $instancia;
    }

    /**
     * Get a instance of \FreeIPA\APIAccess\Main with connection
     * 
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function getInstance()
    {
        return $this->ipa;
    }
    
    /**
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function testMainClassHasConnectionMethod()
    {
        $this->assertTrue(method_exists(new \FreeIPA\APIAccess\Main(), 'connection'));
    }
    
    /**
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function testMainClassHasUserMethod()
    {
        $this->assertTrue(method_exists(new \FreeIPA\APIAccess\Main(), 'user'));
    }
    
    /**
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function testMainClassHasGroupMethod()
    {
        $this->assertTrue(method_exists(new \FreeIPA\APIAccess\Main(), 'group'));
    }
}