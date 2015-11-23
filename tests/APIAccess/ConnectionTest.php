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
 * Class for test the core class.
 * Many connections are made in this test
 * @since 0.4
 * @version 0.2
 */
class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var mixed class instance
     * @access protected
     * @since 0.1
     */
    protected $ipa = null;
    
    /**
     *
     * @var type the content of var data in data.php
     */
    public $data = null;
    
    /**
     * @var string|int random_number a random number
     * @access public
     * @since 0.1
     */
    public $random_number;

    /**
     * @var string user name
     * @access public
     * @since 0.1
     */
    public $user = '';
    
    /**
     * Initialization of tests definitions
     * 
     * @since 0.1
     * @todo possibilitar leitura de parâmetros
     */
    public function setUp()
    {
        global $data;
        $this->data = $data;
        $ipa = new \FreeIPA\APIAccess\Main($data['host'], $data['cert']);
        $r = $ipa->connection()->authenticate($data['user'], $data['pass']);
        if (false === $r['authenticate']) {
            $this->markTestIncomplete('This test needs a connection with the server');
        }
        $this->setInstance($ipa);
        
        $this->random_number = rand(1, 99999);
        $this->user = 'testingUser' . $this->random_number;
    }
    
    /**
     * Set a instance of \FreeIPA\APIAccess\Main with connection
     */
    public function setInstance($instance)
    {
        $this->ipa = $instance;
    }

    /**
     * Get a instance of \FreeIPA\APIAccess\Connection with connection
     */
    public function getInstance()
    {
        return $this->ipa->connection();
    }

    public function testSingleton()
    {
        $reflection = new \ReflectionClass('\FreeIPA\APIAccess\Connection');
        $constructor = $reflection->getConstructor();
        $this->assertFalse($constructor->isPublic());
        
        // this instance can not be logged in
        $new_instance = \FreeIPA\APIAccess\Connection::getInstance(null, null, true);
        if (true === $new_instance->userLogged()) {
          $this->markTestIncomplete('There are some problem with the singleton class Connection');
        }
    }
    
    /**
     * 
     */
    public function testInstanceWithoutParameters()
    {
        $r = \FreeIPA\APIAccess\Connection::getInstance(null, null, true);
    }

    /**
     * @expectedException \Exception
     */
    public function testLoginWithoutCertAndHost()
    {
        $ipa = \FreeIPA\APIAccess\Connection::getInstance(null, null, true);
        $ipa->authenticate($this->data['user'], $this->data['pass']);
    }

    public function testLoginWithoutCredentials()
    {
        $ipa = \FreeIPA\APIAccess\Connection::getInstance(null, null, true);
        $r = $ipa->authenticate(null, null);
        $this->assertEquals(false, $r);
    }

    public function testUserNotLogged()
    {
        $ipa = \FreeIPA\APIAccess\Connection::getInstance(null, null, true);
        $r = $ipa->userLogged();
        $this->assertEquals(false, $r);
    }

    /**
     * Login test
     */
    public function testLoginFirstMethod()
    {
        $ipa = \FreeIPA\APIAccess\Connection::getInstance($this->data['host'], $this->data['cert']);
        $r = $ipa->authenticate($this->data['user'], $this->data['pass']);
        $this->assertArrayHasKey('authenticate', $r);
        $this->assertArrayHasKey('reason', $r);
        $this->assertArrayHasKey('message', $r);
        $this->assertArrayHasKey('http_code', $r);
        $this->assertEquals(true, $r['authenticate']);
    }

    /**
     * Login test
     * @see testLoginFirstMethod()
     */
    public function testLoginSecondMethod()
    {
        $ipa = \FreeIPA\APIAccess\Connection::getInstance(null, null, true);
        $ipa->setIPAServer($this->data['host']);
        $ipa->setCertificateFile($this->data['cert']);
        $ipa->authenticate($this->data['user'], $this->data['pass']);
        unset($ipa);
    }

    public function testLoggedUser()
    {
        $r = $this->getInstance()->userLogged();
        $this->assertEquals(true, $r);
    }

    public function testBadJsonOne()
    {
        $r = $this->getInstance()->buildJsonRequest(null);
        $this->assertEquals(false, $r);
    }

    public function testBadJsonTwo()
    {
        $r = $this->getInstance()->buildJsonRequest('method', false, array());
        $this->assertEquals(false, $r);
    }

    public function testBadJsonTree()
    {
        $r = $this->getInstance()->buildJsonRequest('method', array(), array(1, 2, 3));
        $this->assertEquals(false, $r);
    }

    public function testBadJsonFour()
    {
        $r = $this->getInstance()->buildJsonRequest('method', array(), array());
        $this->assertJson($r);
    }

    public function testJsonOKOne()
    {
        $r = $this->getInstance()->buildJsonRequest('method');
        $this->assertJson($r);
    }

    public function testJsonOKTwo()
    {
        $args = array(
            'arg1' => 'valueArg1',
            'arg2' => 'valueArg2',
            'arg3' => 'valueArg3',
        );
        $options = array(
            'option1' => 'valueOp1',
            'option2' => 'valueOp2',
            'option3' => 'valueOp3',
        );
        $r = $this->getInstance()->buildJsonRequest('method', $args, $options);
        $this->assertJson($r);
    }

    public function testPingToServer()
    {
        $r = $this->getInstance()->ping();
        $this->assertEquals(true, $r);
    }

}
