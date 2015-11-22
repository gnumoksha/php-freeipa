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

/**
 * Class for test the core class
 * @since 0.4
 * @version 0.2
 */
class CoreTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var array login informacoes de login, com usuário administrativo, no servidor
     * @access public
     * @since 0.1
     */
    public $login = array(
        'user' => NULL,
        'pass' => NULL,
        'server' => NULL,
        'cert' => NULL,
    );

    /**
     * @var mixed instância da classe de acesso à api 
     * @access protected
     * @since 0.1
     */
    private $ipa = NULL;

    /**
     * @var string|int random_number amazenara um valor pseudo aleatório que será concatenado à outros valores
     * @access public
     * @since 0.1
     */
    public $random_number;

    /**
     * @var string user nome do user que sera utilizado em diversas tarefas
     * @access public
     * @since 0.1
     */
    public $user = '';

    /**
     * Inicializa definições dos testes e chama o método pai
     * 
     * @since 0.1
     * @todo possibilitar leitura de parâmetros
     */
    public function setUp()
    {
        $this->login['user'] = 'admin';
        $this->login['pass'] = 'Secret123';
        $this->login['host'] = 'ipa.demo1.freeipa.org';
        $this->login['cert'] = __DIR__ . "/../certs/ipa.demo1.freeipa.org_ca.crt";

        $this->random_number = rand(1, 99999);
        $this->user = 'testingUser' . $this->random_number;

        $ipa = new \FreeIPA\APIAccess\Core($this->login['host'], $this->login['cert']);
        $r = $ipa->authenticate($this->login['user'], $this->login['pass']);
        $this->assertEquals(TRUE, $r['autenticado']);
        $this->setInstance($ipa);
    }

    /**
     * It is not test
     */
    public function setInstance($instancia)
    {
        $this->ipa = $instancia;
    }

    /**
     * It is not test
     */
    public function getInstance()
    {
        return $this->ipa;
    }

    /**
     * 
     */
    public function testInstanceWithoutParameters()
    {
        new \FreeIPA\APIAccess\Core();
    }

    /**
     * @expectedException \Exception
     */
    public function testLoginWithoutCertAndHost()
    {
        $ipa = new \FreeIPA\APIAccess\Core();
        $ipa->authenticate($this->login['user'], $this->login['pass']);
    }

    public function testLoginWithoutCredentials()
    {
        $ipa = new \FreeIPA\APIAccess\Core();
        $r = $ipa->authenticate(NULL, NULL);
        $this->assertEquals(FALSE, $r);
    }

    public function testUserNotLogged()
    {
        $ipa = new \FreeIPA\APIAccess\Core();
        $r = $ipa->userLogged();
        $this->assertEquals(FALSE, $r);
    }

    /**
     * Testa o login e atribuiu a instancia da classe a variável $ipa, que sera utilizada em outros locais
     */
    public function testLoginFirstMethod()
    {
        $ipa = new \FreeIPA\APIAccess\Core($this->login['host'], $this->login['cert']);
        $r = $ipa->authenticate($this->login['user'], $this->login['pass']);
        $this->assertArrayHasKey('autenticado', $r);
        $this->assertArrayHasKey('motivo', $r);
        $this->assertArrayHasKey('mensagem', $r);
        $this->assertArrayHasKey('codigo_http', $r);
        $this->assertEquals(TRUE, $r['autenticado']);
    }

    /**
     * Apenas um teste local.
     * @see testeLoginMetodoUm()
     */
    public function testLoginSecondMethod()
    {
        $ipa = new \FreeIPA\APIAccess\Core();
        $ipa->setIPAServer($this->login['host']);
        $ipa->setCertificateFile($this->login['cert']);
        $ipa->authenticate($this->login['user'], $this->login['pass']);
        unset($ipa);
    }

    public function testLoggedUser()
    {
        $r = $this->getInstance()->userLogged();
        $this->assertEquals(TRUE, $r);
    }

    public function testBadJsonOne()
    {
        $r = $this->getInstance()->buildJsonRequest(NULL);
        $this->assertEquals(FALSE, $r);
    }

    public function testBadJsonTwo()
    {
        $r = $this->getInstance()->buildJsonRequest('metodo', FALSE, array());
        $this->assertEquals(FALSE, $r);
    }

    public function testBadJsonTree()
    {
        $r = $this->getInstance()->buildJsonRequest('metodo', array(), array(1, 2, 3));
        $this->assertEquals(FALSE, $r);
    }

    public function testBadJsonFour()
    {
        $r = $this->getInstance()->buildJsonRequest('metodo', array(), array());
        $this->assertJson($r);
    }

    public function testJsonOKOne()
    {
        $r = $this->getInstance()->buildJsonRequest('metodo');
        $this->assertJson($r);
    }

    public function testJsonOKTwo()
    {
        $argumentos = array(
            'argumento1' => 'valorArg1',
            'argumento2' => 'valorArg2',
            'argumento3' => 'valorArg3',
        );
        $opcoes = array(
            'opcao1' => 'valorOp1',
            'opcao2' => 'valorOp2',
            'opcao3' => 'valorOp3',
        );
        $r = $this->getInstance()->buildJsonRequest('metodo', $argumentos, $opcoes);
        $this->assertJson($r);
    }

    public function testPingToServer()
    {
        $r = $this->getInstance()->pingToServer();
        $this->assertEquals(TRUE, $r);
    }

}
