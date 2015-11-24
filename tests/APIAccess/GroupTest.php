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

namespace FreeIPA\APIAccess;

require_once('data.php');

/**
 * Class for test the user class
 * @since GIT: 0.1.0
 * @version GIT: 0.1.0
 */
class GroupTest extends \PHPUnit_Framework_TestCase
{   
    /**
     * @var mixed class instance
     * @access protected
     * @since GIT: 0.1.0
     */
    protected $ipa = NULL;
    
    /**
     *
     * @var type the content of var data in data.php
     */
    public $data = null;
    
    
    /**
     * Inicializa definições dos testes e chama o método pai
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
    public function setInstance($instance)
    {
        $this->ipa = $instance;
    }

    /**
     * Returns a instance of \FreeIPA\APIAccess\Group with connection
     * 
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function getInstance()
    {
        return $this->ipa->group();
    }
    
    /**
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function getRandom()
    {
        return rand(1, 99999);
    }
    
    /**
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function testAddGroupFirstMethod()
    {
        $r = $this->getRandom();
        $add_group = $this->getInstance()->add("Group$r", "Description for Group$r");
        $this->assertInstanceOf('stdClass', $add_group);
        $this->assertTrue(is_array($add_group->cn));
        $this->assertFalse(empty($add_group->dn));
    }
    
    /**
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function testAddGroupSecondMethod()
    {
        $r = $this->getRandom();
        $a = array(
            'description' => "Description for Group$r",
        );
        $add_group = $this->getInstance()->add("Group$r", $a);
        $this->assertInstanceOf('stdClass', $add_group);
        $this->assertTrue(is_array($add_group->cn));
        $this->assertFalse(empty($add_group->dn));
    }
    
//    public function testGroupDoesNotExist()
//    {
//        $r = $this->getInstance()->get(rand(11111, 99999) . rand(11111, 99999));
//        $this->assertEquals(FALSE, $r);
//    }

    /**
     * @expectedException \Exception
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function testAddMemberToNonexistentGroup()
    {
        $r = $this->getInstance()->addMember('groupDoesNotExist', 'userDoesNotExist');
    }
    
    /**
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function testAddGroupMember()
    {
        $r = $this->getRandom();
        $group_name = "Group$r";
        
        $add_group = $this->getInstance()->add($group_name, "Description for $group_name");
        if (! $add_group instanceof \stdClass) {
            $this->markTestIncomplete('This test needs a group');
        }
        
        $user_data = array(
            'givenname'    => 'Richard',
            'sn'           => "Stallman$r",
            'uid'          => "stallman$r",
            'mail'         => "rms$r@fsf.org",
            'userpassword' => $r,
        );
        $add_user = $this->ipa->user()->add($user_data);
        if (! $add_user) {
            $this->markTestIncomplete('This test needs a user');
        }

        $add_group_member = $this->getInstance()->addMember($group_name, $user_data['uid']);
        $this->assertInstanceOf('stdClass', $add_group_member);
    }   
}
