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


/**
 * Este arquivo contém exemplos de utilização da biblioteca de integração com o FreeIPA
 */


// Pedações de código úteis apenas para este arquivo
require_once( 'snippet_debug.php' );
require_once( 'functions.utils.php' );

// Variáveis mais utilizadas ao longo do código
$host          = 'fedora.ipateste.com.br';
// O certificado pode ser obtido em https://$host/ipa/config/ca.crt
$certificado   = getcwd() ."/../certs/testes_ipa.crt";
$usuario       = 'admin';
$senha         = 'senhaAqui';
$procurar      = 'teste';
$random        = rand( 1, 9999 );

// Instancia a classe
require_once( '../src/APIAccess.php' );
try {
  $ipa = new \FreeIPA\APIAccess( $host, $certificado );
} catch ( Exception $e ) {
  _print( "[instancia] Excessao. Mensagem: {$e->getMessage()} Código: {$e->getCode()}" );
  die();
}

// Se você quiser forçar o uso de uma determinada versão da API (por exemplo: após
// testar o código e quiser definir que ele não trabalhe com versões diferentes do servidor),
// defina uma versão desta API.
//$ipa->setVersaoAPI( '2.112' );

// Neste momento você pode definir parâmetros de debug para o cURL.
// Note que este método substitui o iniciarCurl() que é chamado automaticamente
// pela classe
//$ipa->debugarCurl();

// É possível usar o manipulador (handler do curl)
//$ipa->iniciarCurl();
//curl_exec( $ipa->manipulador_curl );


// Faz autenticação
try {
  $ret_aut = $ipa->autenticar( $usuario, $senha );
  if ( TRUE === $ret_aut['autenticado'] ) { // usuário está autenticado
    _print( $ret_aut['mensagem'] );
  }
  else {
    _print( $ret_aut['mensagem'] );
    // Para debug:
    var_dump($ret_aut);
    // Para debug mais detalhado:
    //$ret_curl = $ipa->getErroCurl();
    //print "Usuario nao autenticado. Retorno eh: <br/>\n";
    //print "Retorno do curl: " . $ret_curl[0] . " (" . $ret_curl[1] . ")<br/>\n";
    //print "String de retorno do curl: " . $ipa->getRetornoCurl() . "<br/>\n";
    die();
  }
} catch ( Exception $e ) {
  _print( "[login] Excessao. Mensagem: {$e->getMessage()} Código: {$e->getCode()}" );
  die();
}


// Faz um teste de conexão com o servidor
_print( 'Fazendo um ping' );
try {
  $ret_ping = $ipa->pingar();
  if ( $ret_ping ) {
    _print( 'Pingado!' );
  } else {
    _print( 'Erro no ping!' );
  }
} catch ( Exception $e ) {
  _print( "[ping] Excessao. Mensagem: {$e->getMessage()} Código: {$e->getCode()}" );
  die();
}


// Obtem informações do usuário
_print( "Mostrando o usuario \"$usuario\"" );
try {
  $ret_usuario = $ipa->getUsuario( $usuario );
  if ( TRUE == $ret_usuario ) {
    _print( 'Usuario encontrado' );
    var_dump( $ret_usuario );
  } else {
    _print( "Usuario $usuario nao foi encontrado");
  }
} catch ( Exception $e ) {
  _print( "Mensagem: {$e->getMessage()} Código: {$e->getCode()}" );
  die();
}


// Procurando um usuário através de um método genérico
_print( "Procurando usuários cujo login/nome contenham \"$procurar\"" );
try {
  $ret_procura_usuarios = $ipa->procurarUsuario( array( $procurar ) );
  if ( $ret_procura_usuarios ) {
    $t = count( $ret_procura_usuarios );
    print "Encontrado $t usuários. Nomes: ";
    for ($i=0; $i<$t; $i++) {
      print $ret_procura_usuarios[$i]->uid[0] . " | " ;
    }
    _print();
  } else {
    _print( 'Nenhum usuário encontrado' );
  }
} catch ( Exception $e ) {
  _print( "[consulta usuario] Excessao. Mensagem: {$e->getMessage()} Código: {$e->getCode()}" );
  die();
}


// Procura um usuario atraves de um campo identificador
// Veja documentação do método procurarUsuarioBy() !
_print( "Procurando usuarios cujo login seja \"$procurar\"" );
try {
	$procura_usuario_por = $ipa->procurarUsuarioBy( 'uid', $procurar ); // login
	//$procura_usuario_por = $ipa->procurarUsuarioBy( 'mail', 'teste@ipateste.com.br' ); // email
	//$procura_usuario_por = $ipa->procurarUsuarioBy( 'givenname', $procurar ); // primeiro nome
	//$procura_usuario_por = $ipa->procurarUsuarioBy( 'cn', 'Administrator' ); // nome completo
    //$procura_usuario_por = $ipa->procurarUsuarioBy( 'in_group', 'admins' ); // usuário está no grupo
    if ( $procura_usuario_por ) {
      _print( 'Usuários encontrados' );
      var_dump($procura_usuario_por);
    } else {
      _print( 'Nenhum usuário encontrado' );
    }
} catch ( \Exception $e ) {
	_print( "[procura usuario por] Excessao. Mensagem: {$e->getMessage()} Código: {$e->getCode()}" );
	_print( "Requisicao json eh {$ipa->getJsonRequest()}" );
	_print( "Resposta json eh {$ipa->getJsonResponse()}" );
	die();
}


// Insere um novo usuario
$dados_novo_usuario =  array (
  'givenname'    => 'Richardi',
  'sn'           => 'Stallman',
  'uid'          => "stallman$random",
  'mail'         => "rms$random@fsf.org",
  'userpassword' => $senha,
);
_print( "Adicionando o usuário {$dados_novo_usuario['uid']} com a senha \"$senha\"" );
try {
  $adicionar_usuario = $ipa->adicionarUsuario( $dados_novo_usuario );
  if ( $adicionar_usuario ) {
    _print( 'Usuario adicionado' );
  } else {
    _print( 'Erro ao adicionar o usuario' );
  }
} catch ( \Exception $e ) {
  _print( "[criando usuario] Mensagem: {$e->getMessage()} Código: {$e->getCode()}" );
  die();
}


// Altera o usuario cadastrado anteriormente
$dados_alterar_usuario = array(
  'givenname' => 'Richard',
);
_print( "Alterando o usuario {$dados_novo_usuario['uid']}" );
try {
  $alterar_usuario = $ipa->modificarUsuario( $dados_novo_usuario['uid'], $dados_alterar_usuario );
  if ( $alterar_usuario ) {
    _print( 'Usuario alterado' );
  } else {
    _print( 'Erro ao alterar o usuario' );
  }
} catch ( \Exception $e ) {
  _print( "[alterando usuario] Mensagem: {$e->getMessage()} Código: {$e->getCode()}" );
  die();
}


// Altera a senha do usuario cadastrado anteriormente
$dados_alterar_usenha_suario = array(
  'userpassword' => 'batatinha123',
);
_print( "Alterando senha do usuario {$dados_novo_usuario['uid']} para {$dados_alterar_usenha_suario['userpassword']}" );
try {
  $alterar_senha_usuario = $ipa->modificarUsuario( $dados_novo_usuario['uid'], $dados_alterar_usenha_suario );
  if ( $alterar_senha_usuario ) {
    _print( 'Senha alterada' );
  } else {
    _print( 'Erro ao alterar a senha do usuario' );
  }
} catch ( \Exception $e ) {
  _print( "[alterando senha usuario] Mensagem: {$e->getMessage()} Código: {$e->getCode()}" );
  die();
}


// Adiciona um grupo
_print( "Adicionando grupo \"grupo$random\"" );
try {
  $adicionar_grupo = $ipa->adicionarGrupo( "grupo$random", "Descrição do grupo$random" );
  if ( $adicionar_grupo ) {
    _print( 'Grupo adicionado' );
  } else {
    _print( 'Erro ao adicionar o grupo' );
  }
} catch ( \Exception $e ) {
  _print( "[adicionando grupo] Mensagem: {$e->getMessage()} Código: {$e->getCode()}" );
  die();
}


// Adicionando um usuario a um grupo
_print( "Adicionando o usuário \"$usuario\" ao grupo \"grupo$random\"" );
try {
  $adicionar_usuario_grupo = $ipa->adicionarMembroGrupo( "grupo$random", $usuario );
  if ( $adicionar_grupo ) {
    _print( 'Usuarios adicionados ao grupo' );
    var_dump( $adicionar_usuario_grupo );
  } else {
    _print( 'Erro ao adicionar usuarios ao grupo' );
  }
} catch ( \Exception $e ) {
  _print( "[adicionando usuarios ao grupo] Mensagem: {$e->getMessage()} Código: {$e->getCode()}" );
  die();
}


_print( 'FIM' );
