<?php
// FreeIPA library for PHP
// Copyright (C) 2015 Tobias Sette <contato@tobias.ws>
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program.If not, see <http://www.gnu.org/licenses/>.


namespace FreeIPA;

/**
 * Classe para testes da biblioteca
 * @since 0.1
 */
class APIAcessTest extends \PHPUnit_Framework_TestCase {
  
  /**
   * @var array login informacoes de login, com usuário administrativo, no servidor
   * @access public
   * @since 0.1
   */
  public $login = array(
    'user'     => NULL,
    'pass'     => NULL,
    'server'   => NULL,
    'cert'     => NULL,
  );
  
  /**
   * @var mixed instância da classe de acesso à api 
   * @access protected
   * @since 0.1
   */
  private $ipa = NULL;


  /**
   * @var string|int aleatorio amazenara um valor pseudo aleatório que será concatenado à outros valores
   * @access public
   * @since 0.1
   */
  public $aleatorio;
  
  /**
   * @var string usuario nome do usuario que sera utilizado em diversas tarefas
   * @access public
   * @since 0.1
   */
  public $usuario = '';
  
  
  /**
   * Inicializa definições dos testes e chama o método pai
   * 
   * @since 0.1
   * @todo possibilitar leitura de parâmetros
   */
  public function setUp() {
    $this->login['user']   = 'admin';
    $this->login['pass']   = '01234567';
    $this->login['host'] = 'fedora.ipateste.com.br';
    $this->login['cert']   = getcwd() . "/../certs/testes_ipa.crt";
    
    $this->aleatorio = rand( 1, 99999 );
    $this->usuario = 'testador' . $this->aleatorio;
    
    $ipa = new APIAccess ( $this->login['host'], $this->login['cert'] );
    $r = $ipa->autenticar( $this->login['user'], $this->login['pass'] );
    $this->assertEquals( TRUE, $r['autenticado'] );
    $this->setInstancia( $ipa );
  }
  
  
  /**
   * Não é um teste.
   */
  public function setInstancia( $instancia ) {
    $this->ipa = $instancia;
  }
  
  
  /**
   * Não é um teste.
   */
  public function getInstancia() {
    return $this->ipa;
  }
  
  
  /**
   * 
   */
  public function testInstanciarSemParametros() {
    new APIAccess();
  }
  
  
  /**
   * @expectedException \Exception
   */
  public function testLoginSemCertificadoHost() {
    $ipa = new APIAccess();
    $ipa->autenticar( $this->login['user'], $this->login['pass'] );
  }
  
  
  public function testLoginSemCredenciais() {
    $ipa = new APIAccess();
    $r = $ipa->autenticar( NULL, NULL );
    $this->assertEquals( FALSE, $r );
  }
  
  
  public function testUsuarioNaoLogado() {
    $ipa = new APIAccess();
    $r = $ipa->usuarioEstaLogado();
    $this->assertEquals( FALSE, $r );
  }
  
  /**
   * Testa o login e atribuiu a instancia da classe a variável $ipa, que sera utilizada em outros locais
   */
  public function testLoginMetodoUm() {
    $ipa = new APIAccess( $this->login['host'], $this->login['cert'] );
    $r = $ipa->autenticar( $this->login['user'], $this->login['pass'] );
    $this->assertArrayHasKey( 'autenticado' , $r);
    $this->assertArrayHasKey( 'motivo' ,      $r);
    $this->assertArrayHasKey( 'mensagem' ,    $r);
    $this->assertArrayHasKey( 'codigo_http' , $r);
    $this->assertEquals( TRUE, $r['autenticado'] );
  }
  
  
  /**
   * Apenas um teste local.
   * @see testeLoginMetodoUm()
   */
  public function testLoginMetodoDois() {
    $ipa = new APIAccess();
    $ipa->setServidorIPA( $this->login['host'] );
    $ipa->setArquivoCertificado( $this->login['cert'] );
    $ipa->autenticar( $this->login['user'], $this->login['pass'] );
    unset( $ipa );
  }
  
  
  public function testUsuarioLogado() {
    $r = $this->getInstancia()->usuarioEstaLogado();
    $this->assertEquals( TRUE, $r );
  }
  
  
  public function testCriarJsonErradoUm() {
    $r = $this->getInstancia()->criarRequisicaoJson( NULL );
    $this->assertEquals( FALSE, $r );
  }
  
  public function testCriarJsonErradoDois() {
    $r = $this->getInstancia()->criarRequisicaoJson( 'metodo', FALSE, array() );
    $this->assertEquals( FALSE, $r );
  }
  
  public function testCriarJsonErradoTres() {
    $r = $this->getInstancia()->criarRequisicaoJson( 'metodo', array(), array(1,2,3) );
    $this->assertEquals( FALSE, $r );
  }
  
  
  public function testCriarJsonErradoQuatro() {
    $r = $this->getInstancia()->criarRequisicaoJson( 'metodo', array(), array() );
    $this->assertJson( $r );
  }
  
  
  public function testCriarJsonCorretoUm() {
    $r = $this->getInstancia()->criarRequisicaoJson( 'metodo' );
    $this->assertJson( $r );
  }
  
  public function testCriarJsonCorretoDois() {
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
    $r = $this->getInstancia()->criarRequisicaoJson( 'metodo', $argumentos, $opcoes );
    $this->assertJson( $r );
  }
  
  
  public function testPing() {
    $r = $this->getInstancia()->pingar();
    $this->assertEquals( TRUE, $r );
  }
  
  
  public function testGetUsuarioInexistente() {
    $r = $this->getInstancia()->getUsuario( 'usuarioInexistenteNaBase159' );
    $this->assertEquals( FALSE,  $r );
  }
  
  
  public function testGetUsuarioQueLogou() {
    $r = $this->getInstancia()->getUsuario( $this->login['user'] );
    $this->assertInstanceOf( 'stdClass', $r );
    $this->assertTrue( is_array ( $r->cn ) );
    $this->assertTrue( is_string( $r->dn ) );
    $this->assertTrue( is_array ( $r->memberof_group ) );
    $this->assertTrue( is_array ( $r->uid ) );
    $this->assertEquals( $this->login['user'], $r->uid[0] );
  }
  
}