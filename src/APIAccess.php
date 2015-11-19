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
 * Classes para acesso à API do FreeIPA.
 *
 */

/**
 * Namespace para o projeto
 * @since 0.1
 */
namespace FreeIPA;


//class Excecoes extends \Exception {} ;


/**
 * Classe principal para acesso à API do FreeIPA.
 *
 * Note que há um problema para fazer, no PHP, o equivalente ao "--negotiate -u :"
 * no utilitário curl cli. Foi feito um workaround que pode ser encontrado procurando
 * pela string (case insensitive) "Parte do workaround para atenticação" neste arquivo.
 *
 * @author Tobias Sette <contato@tobias.ws>
 * @copyright Copyright (c) 2015 Tobias Sette <contato@tobias.ws>
 * @license GPLv3
 * @package FreeIPA
 * @since 0.1 14/06/2015 estrutura da classe e uso básico
 * @since 0.2 16/07/2015 adicionado documentação phpdoc; diversos métodos
 * @since 0.3 21/07/2015 definindo comportamento mais unificado para melhorar os testes; corrigido método getUsuario
 * @version 0.3
 */
class APIAccess {

 /**
  * Versão da API que será informado nas requisições.
  * O FreeIPA retorna VersionError se uma versão diferente da versão do servidor for enviada
  * e um aviso caso a versão não seja enviada.
  *
  * @var string|null versao_api Versão da API que será informado nas requisições.
  * @access private
  * @since 0.1
  */
  private $versao_api = NULL; // API 2.114 no Fedora 22

  /**
   * @var mixed Manipulador (handler) do cURL
   * @access public
   * @since 0.1
   */
  public $manipulador_curl = NULL;

  /**
   * @var bool curl_iniciado Diz se o cURL foi iniciado
   * @access protected
   * @since 0.1
   */
  protected $curl_iniciado = FALSE;

  /**
   * @var bool debugar_curl Diz se o cURL foi iniciado com debug
   * @access private
   * @since 0.1
   */
  private $debugar_curl = FALSE;

  /**
   * @var string|null retorno_curl Armazena o retorno do cURL
   * @access protected
   * @since 0.1
   */
  protected $retorno_curl = NULL;

  /**
   * @var int timeout_conexao Timeout para o cURL
   * @access public
   * @since 0.1
   */
  public $timeout_conexao = 10;

  /**
   * @var string|null arquivo_cookie Caminho completo (path) para o arquivo de cookie
   * @access private
   * @since 0.1
   */
  private $arquivo_cookie = NULL;

  /**
   * @var string|null string_cookie Cookie, em formato de string, que o cURL irá utilizar. Parte do workaround para atenticação
   * @access private
   * @since 0.1
   */
  private $string_cookie = NULL;

  /**
   * @var string|null arquivo_certificado Caminho completo (path) para o arquivo do certificado a ser utilizado nas conexões ao servidor
   * @access public
   * @since 0.1
   */
  public $arquivo_certificado  = NULL;

  /**
   * @var array cabecalho_http Cabeçalho HTTP que será utilizado pelo cURL
   * @access public
   * @since 0.1
   */
  public $cabecalho_http = array();

  /**
   * @var string|null servidor_ipa String contendo o endereço (IP ou hostname) do servidor
   * @access protected
   * @since 0.1
   */
  protected $servidor_ipa = NULL;

  /**
   * @var string|null jsonrpc_url String contendo a URL onde o servidor aceita conexões json RPC
   * @access protected
   * @since 0.1
   */
  protected $jsonrpc_url = NULL;

  /**
   * @var string|null jsonrpc_login_url String contendo a URL onde o servidor aceita a conexão de login que retorna permissão para acessar $jsonrpc_url
   * @access protected
   * @since 0.1
   */
  protected $jsonrpc_login_url = NULL;

  /**
   * @var bool usuario_esta_logado Diz se o usuário logou no servidor através da instância desta classe
   * @access protected
   * @since 0.1
   */
  private $usuario_esta_logado  = FALSE;

  /**
   * Define uma relação entre nomes de campos utilizados nesta classe e os
   * nomes (geralmente parâmetros de métodos) utilizados pelo FreeIPA. Isso
   * foi feito para tornar o nome dos parâmetros mais intuitivos.
   * Ao alterar essa variável, lembre-se de alterar a descrição do(s) método(s)
   * que utiliza(m) os antigos e novos valores.
   * @var array associacao_campos
   * @access protected
   * @since 0.2
   */
//  protected $associacao_campos = array(
//    'first_name'     => 'givenname', // primeiro nome
//    'last_name'      => 'sn', // sobrenome
//    'full_name'      => 'cn', // nome completo
//    'in_group'       => 'in_group', // usuário está no grupo
//    'not_in_group'   => 'not_in_group', // usuário não está no grupo
//    'email'          => 'mail', // endereço de e-mail
//    'login'          => 'uid', // nome de usuário
//    'uid'            => 'uidnumber', // identificador único do usuário
//  );


  /**
   * @var string|null $json_request String contendo a última requisição json que será (ou foi) enviada ao servidor
   * @access protected
   * @since 0.2
   */
  protected $json_request = NULL;


  /**
   * @var string|null $json_response String contendo a última resposta json do servidor
   * @access protected
   * @since 0.2
   */
  protected $json_response = NULL;


  /**
   * Executa ações necessárias ao início do uso de uma instância desta classe.
   * Por favor, note que o servidor e certificado não são obrigatórios ao instanciar a classe,
   * mas são obrigatórios em diversos métodos.
   *
   * <code>
   * $ipa = new \FreeIPA\AcessoAPI( '192.168.0.5', '/tmp/certificado.crt' );
   * $ipa2 = new \FreeIPA\AcessoAPI();
   * $ipa2->setServidorIPA( '192.168.0.5' );
   * $ipa2->setArquivoCertificado( '/tmp/certificado.crt' );
   * </code>
   *
   * @param string|null $servidor endereço (IP ou hostname) do servidor
   * @param string|null $certificado caminho completo (path) do certificado
   * @return void
   * @since 0.1
   * @see setServidorIPA()
   * @see setArquivoCertificado()
   * @throws \Exception caso o módulo não esteja instalado curl
   * @throws \Exception caso o método setServidorIPA() retorne false
   * @throws \Exception caso o método setArquivoCertificado() retorne false
   */
  public function __construct( $servidor = NULL, $certificado = NULL ) {
    if ( ! function_exists('curl_init') ) {
      throw new \Exception('PHP curl não está instalado');
    }

    if ( ! empty($servidor) && ! $this->setServidorIPA($servidor) ) {
      throw new \Exception("Erro ao validar o servidor");
    }
    else if ( ! empty($certificado) && ! $this->setArquivoCertificado($certificado) ) {
      throw new \Exception("Erro ao validar o certificado");
    }

    $this->arquivo_cookie = tempnam(sys_get_temp_dir(), 'php_freeipa_api');
  }


  /**
   * Executa ações necessárias ao término do uso de uma instância desta classe.
   *
   * @param void
   * @return void
   * @since 0.1
   */
  public function __destruct(){
    $this->finalizarCurl();
    unlink($this->arquivo_cookie);
  }


  /**
   * Define uma versão que será utilizada no json enviado ao servidor. O comportamento
   * do servidor é negar requisições cuja versão da API são maiores que a dele.
   *
   * @param string
   * @return void
   * @since 0.1
   * @see getVersaoAPI()
   */
  private function setVersaoAPI( $versao ) {
    $this->versao_api = $versao;
  }


  /**
   * Obtém a versão da API que está sendo utilizada nesta classe
   *
   * @param void
   * @return string
   * @since 0.1
   * @see setVersaoAPI()
   */
  private function getVersaoAPI() {
    return $this->versao_api;
  }


  /**
   * Define o endereço (IP ou hostname) do servidor
   *
   * @param string $host endereço (IP ou hostname) do servidor
   * @return bool
   * @since 0.1
   * @see __construct()
   * @see getServidorIPA()
   */
  public function setServidorIPA( $host = NULL ) {
    if ( empty($host) || is_null($host) || ! is_string($host) ) {
      return FALSE;
    }
    $this->servidor_ipa      = $host;
    $this->jsonrpc_url       = 'https://' . $host . '/ipa/session/json';
    $this->jsonrpc_login_url = 'https://' . $host . '/ipa/session/login_password';
    return TRUE;
  }


  /**
   * Obtém o endereço (IP ou hostname) do servidor
   *
   * @param void
   * @return string|bool
   * @since 0.1
   * @see setServidorIPA()
   */
  public function getServidorIPA() {
    return $this->servidor_ipa;
  }

  /**
   * Define caminho completo (path) do certificado
   *
   * @param string $arquivo caminho completo (path) do certificado
   * @return bool false caso o arquivo não seja especificado ou não seja string. True executar corretamente
   * @since 0.1
   * @see __construct()
   * @see getArquivoCertificado()
   * @throws \Exception caso o arquivo não exista ou não possa ser lido
   */
  public function setArquivoCertificado( $arquivo ) {
    if ( empty( $arquivo ) || is_null( $arquivo ) || ! is_string( $arquivo ) ) {
      return FALSE;
    } else if ( ! file_exists($arquivo) ) {
      throw new \Exception( 'Arquivo do certificado não existe' );
    } else if ( ! is_readable($arquivo) ) {
      throw new \Exception( 'Arquivo do certificado não pôde ser lido' );
    }
    $this->arquivo_certificado = $arquivo;
    return TRUE;
  }

  /**
   * Retorna caminho completo (path) do certificado
   *
   * @return string|bool
   * @since 0.1
   * @see setArquivoCertificado()
   */
  public function getArquivoCertificado() {
    return $this->arquivo_certificado;
  }

  /**
   * Define uma string de retorno do cURL
   *
   * @param string $string a string obtida através de uma execução do cURL
   * @return void
   * @since 0.1
   * @see getRetornoCurl()
   */
  public function setRetornoCurl( $string = NULL ) {
    $this->retorno_curl = $string;
  }

  /**
   * Obtem a string de retorno do cURL definida anteriormente com setRetornoCurl()
   *
   * @param void
   * @return string|bool
   * @since 0.1
   * @see setRetornoCurl()
   */
  public function getRetornoCurl() {
    return $this->retorno_curl;
  }


  /**
   * Obtem a string da última requisição json feita (ou que será feita) ao servidor
   *
   * @param void
   * @return string|null
   * @see criarRequisicaoJson()
   * @since 0.2
   */
  public function getJsonRequest() {
    return $this->json_request;
  }


  /**
   * Obtem a string do último retorno json do servidor FreeIPA
   *
   * @param void
   * @return string|null
   * @since 0.2
   */
  public function getJsonResponse() {
    return $this->json_response;
  }


  /**
   * Obtem uma manipulador (handler) cURL já com algumas opções definidas
   *
   * @param bool $forcar inicia o cURL mesmo que ele já esteja
   * @return mixed Manipulador do cURL
   * @since 0.1
   * @see finalizarCurl()
   */
  public function iniciarCurl($forcar = FALSE) {
    if ( FALSE === $this->curl_iniciado || TRUE === $forcar ) {
      // Para garantir que a sessão é finalizada
      $this->finalizarCurl();
      $this->manipulador_curl = curl_init();

      $opcoes_curl = array(
        // nome do arquivo do cookie
        CURLOPT_COOKIEFILE => $this->arquivo_cookie,
        // The name of a file to save all internal cookies to when the handle is closed, e.g. after a call to curl_close.
        //CURLOPT_COOKIEJAR => $arquivo_cookie,
        // Verificar o certificado
        CURLOPT_SSL_VERIFYPEER => TRUE,
        // http://php.net/manual/pt_BR/function.curl-setopt.php
        CURLOPT_SSL_VERIFYHOST => 2,
        //
        CURLOPT_CAINFO => $this->arquivo_certificado,
        //
        CURLOPT_POST => TRUE,
        //
        CURLOPT_FOLLOWLOCATION => TRUE,
        /* Retorna o valor de curl_exec() como string ao inves de jogar para a tela.
        IMPORTANTE: o valor retornado pela função curl_exec() muda de acordo com este parametro e
        o PHP não disponibiliza um método para obter o valor deste parâmetro, então compreenda que
        o código assumirá que esta opção é sempre TRUE, exceto onde explicitamente definido o
        contrário */
        CURLOPT_RETURNTRANSFER => TRUE,
        // The maximum number of seconds to allow cURL functions to execute.
        CURLOPT_TIMEOUT => $this->timeout_conexao,
      );

      // Parte do workaround para atenticação
      if ($this->string_cookie) {
        $opcoes_curl = array_merge($opcoes_curl, array(CURLOPT_COOKIE => $this->string_cookie));
      }

      return $this->curl_iniciado = curl_setopt_array($this->manipulador_curl, $opcoes_curl);
    }

    return $this->curl_iniciado;
  }


  /**
   * Fecha o manipulador (handler) cURL
   *
   * @param void
   * @return void
   * @since 0.1
   * @see iniciarCurl()
   */
  public function finalizarCurl() {
    // @ suprime erros, já que no inicio o parâmetro é nulo
    @curl_close( $this->manipulador_curl );
  }


  /**
   * Auxilia no debug do cURL. Deve ser utilizada no lugar de iniciarCurl()
   *
   * @param void
   * @return Manipulador (handler) para o cURL
   * @since 0.1
   * @see iniciarCurl()
   */
  public function debugarCurl() {
    $this->iniciarCurl();
    print "\n<br/>Debug do curl ativado<br/>\n";
    $opcoes_curl = array(
      // Verboso
      CURLOPT_VERBOSE => TRUE,
      // Incluir o cabeçalho na resposta
      CURLOPT_HEADER => TRUE,
      // TRUE to output SSL certification information to STDERR on secure transfers.
      CURLOPT_CERTINFO => TRUE,
      // Para rastrear a sequencia de requisição
      CURLINFO_HEADER_OUT => TRUE,
    );
    $this->debugar_curl = TRUE;
    return curl_setopt_array($this->manipulador_curl, $opcoes_curl);
  }


  /**
   * Retorna bool dizendo se há erro no cURL
   *
   * @param void
   * @return bool
   * @since 0.1
   */
  public function haErroCurl() {
    return ( curl_errno( $this->manipulador_curl ) ) ? TRUE : FALSE;
  }


  /**
   * Retorna array contendo mensagem e número do erro ocorrido no cURL
   *
   * @param void
   * @return array
   * @since 0.1
   * @link http://curl.haxx.se/libcurl/c/libcurl-errors.html
   */
  public function getErroCurl() {
    return array(
      curl_error( $this->manipulador_curl ),
      curl_errno( $this->manipulador_curl ),
    );
  }


  /**
   * Retorna array contendo informações do cURL
   *
   * @param void
   * @return array
   * @since 0.1
   * @link http://php.net/manual/pt_BR/function.curl-getinfo.php
   */
  public function getInfoCurl() {
    return curl_getinfo( $this->manipulador_curl );
  }


  /**
   * Executa uma instrução com o manipulador (handler) do cURL.
   * Em caso de erro, é possível obter mais informações com o método getErroCurl() e
   * o retorno do cURL com o método getRetornoCurl()
   *
   * @param void
   * @return string|bool retorna FALSE em caso de erro ou o código da resposta HTTP em caso de sucesso
   * @since 0.1
   * @see getErroCurl()
   * @see getRetornoCurl()
   * @link http://php.net/manual/pt_BR/function.curl-exec.php
   * @todo sem o certificado o $codigo_http é 0 e nenhum output é gerado
   */
  public function executarCurl() {
    $this->setRetornoCurl( curl_exec( $this->manipulador_curl ) );
    $codigo_http = curl_getinfo( $this->manipulador_curl, CURLINFO_HTTP_CODE );
    // Pelo que entendi da documentação http://php.net/manual/pt_BR/function.curl-exec.php
    // e da prática o curl_exec retornará FALSE somente se der algo errado na conexao. O webservice
    // do IPA retorna um html com o erro, então não vem o TRUE, vem string.
    //
    // para quando CURLOPT_RETURNTRANSFER é FALSE
    // if ( '1' == $retorno_curl_exec ) {
    // return TRUE;
    // }
    //  removido no lugar de usar o metodo haErroCurl()
    // $retorno = $this->getRetornoCurl() === FALSE ? FALSE : TRUE;
    return ( $this->haErroCurl() ) ? FALSE : $codigo_http;
  }


  /**
   * Tenta autenticar o usuário e senha no servidor, através da url defina em $jsonrpc_login_url
   *
   * $ret
   *  ['autenticado'] bool que define se o usuário está autenticado ou não
   *  ['motivo'] string com o motivo da ação que ocorreu
   *  ['mensagem'] string com a mensagem da ação que ocorreu
   *  ['codigo_http'] código HTTP da resposta
   *
   * @param string $usuario
   * @param string $senha
   * @return array $ret veja descrição acima
   * @since 0.1
   * @throws \Exception caso de erro no cURL
   * @throws \Exception se o cookie da sessão não for identificado. Parte do workaround para atenticação
   * @see docs/return_samples/authentication.txt
   * @todo este método é parte do workaround para atenticação
   */
  public function autenticar( $usuario = NULL, $senha = NULL ) {
    if ( ! $usuario || ! $senha ) {
      return FALSE;
    }
    
    $retorno = array(
      'autenticado' => FALSE,
      'motivo' => '',
      'mensagem' => '',
      'codigo_http' => NULL,
    );
    $this->iniciarCurl();
    $this->cabecalho_http = array(
      'Content-type: application/x-www-form-urlencoded',
      'Accept: */*',
    );

    if ( empty($usuario) || empty($senha) ) {
      $retorno['autenticado'] = FALSE;
      $retorno['mensagem'] = 'Usuário e/ou senha não foram informados';
      return $retorno;
    }

    //$usuario = urlencode($usuario);
    //$senha = urlencode($senha);

    curl_setopt($this->manipulador_curl, CURLOPT_HTTPHEADER, $this->cabecalho_http);
    curl_setopt($this->manipulador_curl, CURLOPT_URL,        $this->jsonrpc_login_url);
    curl_setopt($this->manipulador_curl, CURLOPT_POSTFIELDS, "user=$usuario&password=$senha");
    // Preciso do header para pegar o valor do campo X-IPA-Rejection-Reason
    // e como Parte do workaround para atenticação
    if ( ! $this->debugar_curl ) {
      curl_setopt($this->manipulador_curl, CURLOPT_HEADER, TRUE);
    }

    $retorno['codigo_http'] = $this->executarCurl();

    if ( $this->haErroCurl() ) {
      throw new \Exception($this->getErroCurl()[0], $this->getErroCurl()[1]);
    }

    // #TODO Talvez esse campo seja retornado apenas em erros 401
    // Exemplo do campo no header: X-IPA-Rejection-Reason: invalid-password
    preg_match('/X-IPA-Rejection-Reason: ([^\n]*)/i', $this->getRetornoCurl(), $busca_reject_reason);
    $retorno['motivo'] = (empty($busca_reject_reason[1])) ? FALSE : trim($busca_reject_reason[1]);

    // Preciso do header para pegar o valor do campo X-IPA-Rejection-Reason
    // e como Parte do workaround para atenticação
    if ( ! $this->debugar_curl ) {
      curl_setopt($this->manipulador_curl, CURLOPT_HEADER, FALSE);
    }

    // #TODO Talvez esse campo seja retornado apenas em erros 401
    // Vi que nos erros 401 o ipa retorna um html que exibe uma mensagem de erro
    $retorno_uma_linha = str_replace( array("\n", "\r", "\r\n"), ' ', $this->retorno_curl );
    preg_match('#<p>(.*?)</p>#', $retorno_uma_linha, $busca_descricao_ipa);
    if ( empty($busca_descricao_ipa[1]) ) {
      $descricao_erro_ipa = NULL;
    }
    else {
      $descricao_erro_ipa = str_replace(array('<strong>', '</strong>'), '', $busca_descricao_ipa[1]);
      $descricao_erro_ipa = trim($descricao_erro_ipa);
    }

    if ( '401' == $retorno['codigo_http'] ) {
      $retorno['autenticado'] = FALSE;
      // É melhor não exibir todas as mensagens diretamente ao usuário, para ficar mais amigável.
      // O $retorno['motivo'] invalid-password vem em mais de um caso (quando o usuario é bloqueado e ou usuario/senha está incorreto)
      if ( 'kinit: Preauthentication failed while getting initial credentials' == $descricao_erro_ipa ) {
        $retorno['mensagem'] .= 'Usuário e/ou senha incorretos. ';
      } else if ( preg_match("/Client (.*?) not found in Kerberos database while getting initial credentials/i", $descricao_erro_ipa) ) {
        $retorno['mensagem'] .= 'Usuário não encontrado no servidor. ';
      } else {
        $retorno['mensagem'] .= 'Erro na autenticação. ';
        if ( ! empty($descricao_erro_ipa) ) {
          $retorno['mensagem'] .= "O servidor retornou \"" . $descricao_erro_ipa . "\". ";
        }
      }
    }
    else if ( '200' != $retorno['codigo_http'] ) {
      $retorno['autenticado'] = FALSE;
      $retorno['mensagem'] = "A resposta retornou o código HTTP \"" . $retorno['codigo_http'] . "\" que não é aceitável. ";
      if ( ! empty($descricao_erro_ipa) ) {
        $retorno['mensagem'] .= "O servidor retornou \"" . $descricao_erro_ipa . "\". ";
      }
    }
    else {
      $retorno['autenticado'] = TRUE;
      $retorno['mensagem']   = 'Usuário autenticado com sucesso. ';
      // Parte do workaround para atenticação. Obtenho a string do cookie manualmente.
      preg_match("/Set-Cookie: ([^\n]*)/", $this->getRetornoCurl(), $encontrados);
      if ( empty($encontrados[1]) ) {
        throw new \Exception('Erro ao identificar cookie de sessao');
      }
      // exemplo do $encontrados[1]:
      //ipa_session=2dd6a6e7ae5c0c388be3de7e50b454e9; Domain=fedora.ipateste.com.br; Path=/ipa; Expires=Sat, 06 Jun 2015 20:14:50 GMT; Secure; HttpOnly
      $this->string_cookie = trim($encontrados[1]);
      curl_setopt($this->manipulador_curl, CURLOPT_COOKIE, $this->string_cookie);
    }

    $this->usuario_esta_logado = $retorno['autenticado'];
    return $retorno;
  }


  /**
   * Retorna bool que diz se o usuário está logado ou não
   *
   * @param void
   * @return bool
   * @since 0.1
   */
  public function usuarioEstaLogado() {
    return $this->usuario_esta_logado;
  }

  
  /**
   * Verifica se a variavel eh um array associativo
   * 
   * @param array $var
   * @param bool $force se true, array tem que ser associativo. Se false, tem que ser associativo somente se nao for vazio
   * @return bool
   * @link http://php.net/manual/pt_BR/function.is-array.php#89332
   */
  public function isAssociateArray( $var, $force=TRUE ) {
    if ( ! is_array( $var ) ) {
      return FALSE;
    }
    
    if ( ! empty( $var ) || $force ) {
      return array_diff_key($var,array_keys(array_keys($var)));
    }
    
    return TRUE;
  }
  

  /**
   * Retorna uma string json no formato exigido pelo FreeIPA
   *
   * @param string $metodo parâmetro obrigatório que define o método a ser executado pelo servidor
   * @param array $argumentos argumentos para o método
   * @param array $opcoes parâmetros para o método
   * @return string|bool retorna FALSE caso hava erro nos parâmetros passados
   * @since 0.1
   * @link http://php.net/manual/pt_BR/function.json-encode.php
   */
  public function criarRequisicaoJson( $metodo = NULL, $argumentos = array(), $opcoes = array() ) {
    if ( ! $metodo || ! is_array( $argumentos ) || ! $this->isAssociateArray( $opcoes, FALSE ) ) {
      return FALSE;
    }

    $argumentos_padrao = array();
    $argumentos_final = array_merge( $argumentos_padrao, $argumentos );

    $opcoes_padrao = array();
    // O FreeIPA retorna VersionError se uma versão diferente da do servidor for enviada
    if ( $this->versao_api ) {
      $opcoes_padrao['version'] = $this->versao_api;
    }
    $opcoes_final = array_merge( $opcoes_padrao, $opcoes );

    // no ping as opções vao como {} mesmo que sejam vazias. O PHP manda array vazio como []
    // Uma possível solução seria usar o parâmetro JSON_FORCE_OBJECT apenas com a conversão
    // das opções e encaixar o resultado no retorno, mas ao fazer isso o PHP delimita o {}
    if ( 'ping' == strtolower( $metodo ) && empty( $opcoes_final ) ) {
      return $this->json_request = '{ "id": 0, "method": "ping", "params": [ [],{} ] }';
    }

    $retorno = array(
      'id' => 0,
      'method' => $metodo,
      'params' => array( $argumentos_final, $opcoes_final ),
    );

    $this->json_request = json_encode( $retorno, JSON_PRETTY_PRINT );
    return $this->json_request;
  }

  
  /**
   * Recebe um retorno do FreeIPA e altera as variáveis descritas nos valores da propriedade $associacao_campos
   * para as suas respectivas chaves.
   * 
   * @param array $retorno_freeipa objeto json do retorno obtido do FreeIPA
   * @return array o array $retorno_freeipa com os indices alterados
   * @since 0.2
   * @see $associacao_campos
   */
//  private function associacaoCamposFreeIPA( $retorno_freeipa = array() ) {
//    $associacao_invertida_campos = array_flip( $this->associacao_campos );
//    foreach ( $retorno_freeipa as $chave => $valor ) {
//      
//    }
//    
//  }

  
  /**
   * Envia requisições para o servidor do FreeIPA utilizando a sessão previamente estabelecida e
   * armazena o retorno em $this->json_response.
   * Com este método é possível utilizar qualquer método da API json RPC do FreeIPA.
   *
   * @param string $metodo parâmetro obrigatório que define o método a ser executado pelo servidor
   * @param array $parametros argumentos para o método
   * @param array $opcoes parâmetros para o método
   * @param bool $exceptionInError se TRUE, irá lançar uma \Exception caso o campo error da resposta venha preenchido
   * @return array com objeto (vindo de json_decode()) e código http da resposta
   * @since 0.1
   * @since 0.3 $exceptionInError
   * @throws \Exception se o usuário não esteja logado
   * @throws \Exception se há erro ao criar a requisição
   * @throws \Exception se há erro ao definir opções no cURL ou ao realizar a requisição
   * @throws \Exception se o código http de resposta é vazio ou diferente de 200
   * @throws \Exception se o retorno json está vazio
   * @throws \Exception (se $exceptionInError é true) com descrição e número do erro caso o retorno json retorne erro
   * @see usuarioEstaLogado()
   * @see criarRequisicaoJson()
   * @see $json_response
   * @see ../docs/return_samples/invalid_json_request.txt
   * @link http://php.net/manual/pt_BR/function.json-decode.php
   */
  public function requisicao( $metodo = NULL, $parametros = array(), $opcoes = array(), $exceptionInError = TRUE ) {
    if ( ! $this->usuarioEstaLogado() ) {
      throw new \Exception( 'Usuario não está logado' );
    }

    $json = $this->criarRequisicaoJson( $metodo, $parametros, $opcoes );
    if ( FALSE === $json) {
      throw new \Exception( 'Erro ao criar requisição json' );
    }

    $opcoes_curl = array(
      CURLOPT_URL        => $this->jsonrpc_url,
      CURLOPT_POSTFIELDS => $json,
      CURLOPT_HTTPHEADER => array(
        'referer:https://' . $this->servidor_ipa . '/ipa/ui/index.html',
        'Content-Type:application/json',
        'Accept:applicaton/json',
        'Content-Length: ' . strlen( $json ),
      ),
    );

    $define_opcoes = curl_setopt_array( $this->manipulador_curl, $opcoes_curl );
    if ( FALSE === $define_opcoes ) {
      throw new \Exception( 'Erro ao definir opções no curl' );
    }

    $codigo_http_resposta = $this->executarCurl();
    $json_retorno         = $this->json_response = $this->getRetornoCurl();
    $objeto_json_retorno  = json_decode( $json_retorno );
    if ( $this->haErroCurl() ) {
      throw new \Exception( 'Erro na requisição curl' );
    }
    if ( ! $codigo_http_resposta || '200' != $codigo_http_resposta ) {
      throw new \Exception( "O valor \"$codigo_http_resposta\" não é um código de resposta válido" );
    }
    if ( empty( $json_retorno ) || empty( $objeto_json_retorno ) ) {
      #TODO criar exceção para passar os dados do erro ao inves do json puro. Vide arquivo exemplos_retornos.txt
      throw new \Exception( "Erro no retorno json. Valor é ${json_retorno}" );
    }
    if ( $exceptionInError && ! empty( $objeto_json_retorno->error ) ) {
      throw new \Exception( "Erro na requisição. Detalhes: " . $objeto_json_retorno->error->message, $objeto_json_retorno->error->code );
    }

    return array( $objeto_json_retorno, $codigo_http_resposta );
  }


  /**
   * Realiza um ping no servidor do FreeIPA através da API.
   *
   * @param bool $retornar_string se TRUE, irá retornar o campo de resumo da resposta json
   * @return string|bool boleano indicado se o processo foi bem sucedido ou a string do resumo do retorno caso o parâmetro $retornar_string seja TRUE
   * @since 0.1
   * @see ../docs/return_samples/ping.txt
   */
  public function pingar( $retornar_string = FALSE ) {
    $ret = $this->requisicao( 'ping' ); // retorna json e codigo http da resposta
    $json = $ret[0];
    if ( ! empty($json->error) || empty($json->result) || empty($json->result->summary) || ! is_string($json->result->summary) )
      return FALSE;

    if ( $retornar_string ) {
      return $json->result->summary;
    } else {
      return TRUE;
    }
  }


  /**
   * Procura usuários através do método user_find e retorna suas informações
   * Se uma string for especificada em $argumentos, o servidor irá fazer uma busca genérica
   * procurando a string nos campos login, first_name e last_name.
   *
   * @param array $argumentos argumentos para o método user_find.
   * @param array $opcoes parâmetros para o método user_find
   * @return array|bool um indice para cada resultado, em cada resultado um objeto. OU FALSE se não encontrar o usuário
   * @since 0.1
   * @throws \Exception se houver erro no retorno json
   * @see ../docs/return_samples/user_find.txt
   * @see requisicao()
   */
  public function procurarUsuario( $argumentos = array(), $opcoes = array() ) {
    if ( ! is_array( $argumentos ) || ! is_array( $opcoes ) ) {
      return FALSE;
    }

    // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv user-find --all
    $opcoes_padrao = array(
      'all'         => true,
      'no_members'  => false,
      'pkey_only'   => false,
      'raw'         => false,
      'whoami'      => false,
    );
    $opcoes_final = array_merge( $opcoes_padrao, $opcoes );

    $retorno_requisicao = $this->requisicao( 'user_find', $argumentos, $opcoes_final ); // retorna json e codigo http da resposta
    $json = $retorno_requisicao[0];
    $json_string = json_encode( $json );

    if ( empty( $json->result ) || ! isset( $json->result->count ) ) {
      throw new \Exception("Um ou mais elementos necessários não foram encontrados na resposta json. Resposta foi \"$json_string\".");
    }

    if ( $json->result->count < 1 ) {
      return FALSE;
    }

    return $json->result->result;
  }


  /**
   * Procura usuário através de um campo especificado e retorna suas informações
   * Principais campos são:
   *  'givenname' => primeiro nome
   *  'sn' => sobrenome
   *  'cn' => nome completo
   *  'in_group' => usuário está no grupo
   *  'not_in_group' => usuário não está no grupo
   *  'mail' => endereço de e-mail
   *  'uid' => nome de usuário
   *  'uidnumber' => identificador único do usuário
   *
   * @param array $campo nome do campo. Ver exemplos acima
   * @param string $valor valor para $campo
   * @return array|bool um indice para cada resultado, em cada resultado um objeto. OU FALSE se não encontrar o usuário
   * @since 0.2
   * @see procurarUsuario()
   */
  public function procurarUsuarioBy( $campo = NULL, $valor = NULL ) {
    if ( ! $campo || ! $valor ) {
      return FALSE;
    }

//    if ( ! isset( $this->associacao_campos[ $campo ] ) ) {
//      throw new \Exception( "Campo $campo nao está mapeado" );
//    } else {
//      $campo_ipa = $this->associacao_campos[ $campo ];
//    }
//
//    $opcoes = array( $campo_ipa => $valor );
    $opcoes = array( $campo => $valor );
    return $this->procurarUsuario( array(), $opcoes );
  }


  /**
   * Obtém os dados de um usuário identificado pelo seu login através
   * do método user_show da API.
   *
   * @param string|array $parametros login do usuário ou array com parâmetros para o método user_show
   * @param array $opcoes opções para o método user_show
   * @return array|bool um indice para cada resultado, em cada resultado um objeto. OU FALSE se não encontrar o usuário
   * @since 0.1
   * @since 0.2 $parametros pode ser uma string
   * @throws \Exception se houver erro no retorno json
   * @see ../docs/return_samples/user_show.txt
   * @see requisicao()
   */
  public function getUsuario( $parametros = NULL, $opcoes = array() ) {
    if ( ! is_array( $opcoes ) ) {
      return FALSE;
    }

    if ( is_string( $parametros ) ) {
      $parametros_final = array( $parametros );
    } else if ( is_array( $parametros ) ) {
      $parametros_final = $parametros;
    } else {
      return FALSE;
    }

    // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando [root@fedora ~]# ipa -vv user-show admin
    $opcoes_padrao = array(
      'all'         => true,
      'no_members'  => false,
      'raw'         => false,
      'rights'      => false,
    );
    $opcoes_final = array_merge( $opcoes, $opcoes_padrao );

    $retorno_requisicao = $this->requisicao( 'user_show', $parametros_final, $opcoes_final, FALSE ); // retorna json e codigo http da resposta
    $json = $retorno_requisicao[0];
    $json_string = json_encode($json);
    
    if ( ! empty( $json->error ) && strtolower( $json->error->name ) == 'notfound' ) {
      // usuário não encontrado
      return FALSE;
    }

    if ( empty( $json->result ) ) {
      throw new \Exception("Um ou mais elementos necessários não foram encontrados na resposta json. Resposta foi \"$json_string\".");
    }

    // #TODO remover este trecho?
    if ( ! isset( $json->result->result ) ) {
      return FALSE;
    }

    return $json->result->result;
  }


  /**
   * Adiciona usuário no FreeIPA
   * Principais campos em $dados:
   *  'givenname' => primeiro nome
   *  'sn' => sobrenome
   *  'cn' => nome completo
   *  'mail' => endereço de e-mail
   *  'uid' => nome de usuário (login). Campo obrigatório
   *  'userpassword' => senha do usuario
   * 
   * @param array $dados contém as informações do usuário. Ver exemplo acima
   * @return object|bool Objeto contendo os dados do usuário criado ou FALSE em caso de erro
   * @since 0.2
   * @see requisicao()
   */
  public function adicionarUsuario ( $dados ) {
    if ( ! $dados || ! isset( $dados['uid'] ) || empty( $dados['uid'] ) ) {
      return FALSE;
    }

    // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv user_add tobias --first="Tobias" --last="Sette" --email="contato@tobias.ws" --password
    $argumentos    = array( $dados['uid'] );
    $opcoes_padrao = array(
      'all'         => false,
      'no_members'  => false,
      'noprivate'   => false,
      'random'      => false,
      'raw'         => false,
    );
    unset( $dados['uid'] );
    $opcoes_final = array_merge( $opcoes_padrao, $dados );

    // O método requisicao() já verifica o campo 'error', que é o único relevante para este método da API
    $retorno_requisicao = $this->requisicao( 'user_add', $argumentos, $opcoes_final ); // retorna json e codigo http da resposta
    if ( ! $retorno_requisicao ) {
      return FALSE;
    }

    return $retorno_requisicao[0]->result->result;    
  }
  
  
  /**
   * Altera os dados de um usuário no FreeIPA.
   * Principais campos em $dados:
   *  'givenname' => primeiro nome
   *  'sn' => sobrenome
   *  'cn' => nome completo
   *  'mail' => endereço de e-mail
   *  'userpassword' => senha do usuario
   *  'krbprincipalexpiration' '__datetime__' => Data da expiração da senha. Exemplo: 20150816010101Z
   * 
   * Caso o usuário não exista, o método requisicao() irá retornar uma \Exception.
   * Note que ao alterar a senha o usuário estará sujeito as políticas do servidor, tais como
   * tamanho e data de expiração da senha, alem da politica do FreeIPA de invalidar a primeira senha.
   * Se a senha for invalidada o usuário não conseguirá fazer login através do método autenticar()
   *
   * @param string $login login (uid) do usuário que será alterado.
   * @param array $dados contém as informações que serão alteradas. Ver exemplo acima
   * @return object|bool Objeto contendo os dados do usuário criado ou FALSE em caso de erro
   * @since 0.2
   * @see ../docs/return_samples/user_mod.txt
   * @see requisicao()
   * @link https://www.freeipa.org/page/New_Passwords_Expired
   * @link https://access.redhat.com/documentation/en-US/Red_Hat_Enterprise_Linux/6/html/Identity_Management_Guide/changing-pwds.html
   * @link http://docs.fedoraproject.org/en-US/Fedora/17/html/FreeIPA_Guide/pwd-expiration.html
   */
  public function modificarUsuario( $login = NULL, $dados = array() ) {
    if ( ! $login || ! $dados ) {
      return FALSE;
    }

    // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv user_mod tobias --first="testeaaaaaa"
    $argumentos    = array( $login );
    $opcoes_padrao = array(
      'all'         => false,
      'no_members'  => false,
      'random'      => false,
      'raw'         => false,
      'rights'      => false,
    );
    $opcoes_final = array_merge( $opcoes_padrao, $dados );

    // O método requisicao() já verifica o campo 'error', que é o único relevante para este método da API
    $retorno_requisicao = $this->requisicao( 'user_mod', $argumentos, $opcoes_final ); // retorna json e codigo http da resposta
    if ( ! $retorno_requisicao ) {
      return FALSE;
    }

    return $retorno_requisicao[0]->result->result;
  }
  
  
  /**
   * Adiciona um grupo no FreeIPA
   * Principais parâmetros de $dados:
   *  'description' => descrição do grupo
   * Se $dados for uma string, será encarada como sendo a descrição do grupo
   * 
   * @param string $nome nome do grupo
   * @param array|string $dados contém as informações que serão adicionadas. Ver exemplo acima
   * @return object|bool Objeto contendo os dados do grupo criado ou FALSE em caso de erro
   * @since 0.2
   * @see ../docs/return_samples/group_add.txt
   * @see requisicao()
   */
  public function adicionarGrupo ( $nome = NULL, $dados = array() ) {
    if ( ! $nome || ! $dados ) {
      return FALSE;
    }
    
    // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv group-add blube_bolinha --desc="Grupo tal" --all 
    $argumentos    = array( $nome );
    $opcoes_padrao = array(
      'all'         => false,
      'external'    => false,
      'no_members'  => false,
      'nonposix'    => false,
      'raw'         => false,
    );
    if ( is_array( $dados ) ) {
      $opcoes_final = array_merge( $opcoes_padrao, $dados );
    } else if ( is_string( $dados ) ) {
      $opcoes_final = array_merge( $opcoes_padrao, array( 'description' => $dados ) );
    } else {
      return FALSE;
    }

    // O método requisicao() já verifica o campo 'error', que é o único relevante para este método da API
    $retorno_requisicao = $this->requisicao( 'group_add', $argumentos, $opcoes_final ); // retorna json e codigo http da resposta
    if ( ! $retorno_requisicao ) {
      return FALSE;
    }

    return $retorno_requisicao[0]->result->result;
  }
  
  
  /**
   * Adiciona membros (usuários ou outros grupos) a um grupo
   * Parâmetros principais de $dados:
   *  'user' => array contendo os usuários a serem adicionados
   *  'group' => array contendo os grupos a serem adicionados
   * Se $dados for uma string, será encarado como sendo o uid de um usuário
   * 
   * @param string $nome_grupo Nome do grupo no qual os membros serão adicionados
   * @param array|string $dados contém as informações que serão adicionadas. Ver exemplo acima
   * @return mixed Array contendo informações sobre o processamento e os dados do grupo em questão. Ou FALSE em caso de erro
   * @since 0.2
   * @see ../docs/return_samples/group_add_member.txt
   * @see requisicao()
   * @throws \Exception se a requisição não foi completada com sucesso
   */
  public function adicionarMembroGrupo( $nome_grupo = NULL, $dados = array() ) {
    if ( ! $nome_grupo || ! $dados ) {
      return FALSE;
    }
    
    // Estas opcoes foram obtidos com base nos parâmetros definidos pelo comando ipa -vv group_add_member clube_bolinha --users="stallman" 
    $argumentos    = array( $nome_grupo );
    $opcoes_padrao = array(
      'all'         => true,
      'no_members'  => false,
      'raw'         => false,
    );
    if ( is_array( $dados ) ) {
      $opcoes_final = array_merge( $opcoes_padrao, $dados );
    } else if ( is_string( $dados ) ) {
      $opcoes_final = array_merge( $opcoes_padrao, array( 'user' => array( $dados ) ) );
    } else {
      return FALSE;
    }

    $retorno_requisicao = $this->requisicao( 'group_add_member', $argumentos, $opcoes_final ); // retorna json e codigo http da resposta
    if ( ! $retorno_requisicao ) {
      return FALSE;
    }
    $json_retorno = $retorno_requisicao[0];
    if ( ! $json_retorno->result->completed ) {
      $mensagem = "Erro ao inserir membros no grupo \"$nome_grupo\".";
      if ( ! empty( $json_retorno->result->failed->member->group ) || ! empty( $json_retorno->result->failed->member->user ) ) {
        $mensagem .= 'Detalhes: ';
      }
      
      if ( ! empty( $json_retorno->result->failed->member->group ) ) {
        $mensagem .= implode( ' ', $json_retorno->result->failed->member->group[0] );
      }
      
      if ( ! empty( $json_retorno->result->failed->member->user ) ) {
        $mensagem .= implode( ' ', $json_retorno->result->failed->member->user[0] );
      }
      
      throw new \Exception( $mensagem );
    }

    // ao contrário os outros métodos, onde é retornado $json_retorno->result->result, o $json_retorno->result deste contém informações que podem ser úteis
    return $json_retorno->result;
  }


} // Fim da classe
