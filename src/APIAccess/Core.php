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

/**
 * Classes for access to FreeIPA API
 * @since 0.1
 */
namespace FreeIPA\APIAccess;

/**
 * Main class
 *
 * Note que há um problema para fazer, no PHP, o equivalente ao "--negotiate -u :"
 * no utilitário curl cli. Foi feito um workaround que pode ser encontrado procurando
 * pela string (case insensitive) "workaround_for_auth" neste arquivo.
 *
 * @author Tobias Sette <contato@tobias.ws>
 * @copyright Copyright (c) 2015 Tobias Sette <contato@tobias.ws>
 * @license LGPLv3
 * @package FreeIPA
 * @since 0.1 14/06/2015 estrutura da classe e uso básico
 * @since 0.2 16/07/2015 adicionado documentação phpdoc; diversos métodos
 * @since 0.3 21/07/2015 definindo comportamento mais unificado para melhorar os testes; corrigido método getUser
 * @version 0.4
 */
class Core
{

    /**
     * Versão da API que será informado nas requisições.
     * O FreeIPA retorna VersionError se uma versão diferente da versão do servidor for enviada
     * e um aviso caso a versão não seja enviada.
     *
     * @var string|null api_version API version that will be sent in each requisition
     * @access private
     * @since 0.1
     */
    private $api_version = null;

    /**
     * @var mixed cURL handler
     * @access public
     * @since 0.1
     */
    public $curl_handler = null;

    /**
     * @var bool curl_initiated if cURL was initiated or not
     * @access protected
     * @since 0.1
     */
    protected $curl_initiated = false;

    /**
     * @var bool curl_debug if cURL will be initiated with debug or not
     * @access private
     * @since 0.1
     */
    private $curl_debug = false;

    /**
     * @var string|null curl_response Stores response/return of cURL
     * @access protected
     * @since 0.1
     */
        protected $curl_response = null;

    /**
     * @var int curl_timeout Timeout for cURL connection
     * @access public
     * @since 0.1
     */
    public $curl_timeout = 10;

    /**
     * @var string|null cookie_file Full path for file that will stores cookie
     * @access private
     * @since 0.1
     */
    private $cookie_file = null;

    /**
     * @var string|null cookie_string String that contains cookie for use in cURL. workaround_for_auth
     * @access private
     * @since 0.1
     */
    private $cookie_string = null;

    /**
     * @var string|null certificate_file Full path of certificate file for use in connections with the server
     * @access public
     * @since 0.1
     */
    public $certificate_file  = null;

    /**
     * @var array curl_http_header HTTP header that will be used with cURL
     * @access public
     * @since 0.1
     */
    public $curl_http_header = array();

    /**
     * @var string|null ipa_server IP address or hostname of freeIPA server
     * @access protected
     * @since 0.1
     */
    protected $ipa_server = null;

    /**
     * @var string|null jsonrpc_url URL where the server accept json RPC connections
     * @access protected
     * @since 0.1
     */
    protected $jsonrpc_url = null;

    /**
     * @var string|null jsonrpc_login_url URL where the server accept loggin connections
     * @access protected
     * @since 0.1
     */
    protected $jsonrpc_login_url = null;

    /**
     * @var bool user_logged If user made login or not
     * @access protected
     * @since 0.1
     */
    private $user_logged  = false;

    /**
     * @var string|null $json_request String that contains the last json request what will be (or was) sent to the server
     * @access protected
     * @since 0.2
     */
    protected $json_request = null;

    /**
     * @var string|null $json_response String that contains the last json response from server
     * @access protected
     * @since 0.2
     */
    protected $json_response = null;


    /**
     * Executa ações necessárias ao início do uso de uma instância desta classe.
     * Por favor, note que o servidor e certificado não são obrigatórios ao instanciar a classe,
     * mas são obrigatórios em diversos métodos.
     *
     * <code>
     * $ipa = new \FreeIPA\AcessoAPI( '192.168.0.5', '/tmp/certificado.crt' );
     * $ipa2 = new \FreeIPA\AcessoAPI();
     * $ipa2->setIPAServer( '192.168.0.5' );
     * $ipa2->setCertificateFile( '/tmp/certificado.crt' );
     * </code>
     *
     * @param string|null $server address (IP or hostname) of server
     * @param string|null $certificate full path of server certificate
     * @return void
     * @since 0.1
     * @see setIPAServer()
     * @see setCertificateFile()
     * @throws \Exception caso o módulo não esteja instalado curl
     * @throws \Exception caso o método setIPAServer() retorne false
     * @throws \Exception caso o método setCertificateFile() retorne false
     */
    public function __construct( $server = null, $certificate = null )
    {
        if (! function_exists('curl_init')) {
            throw new \Exception('Unable to find cURL');
        }

        if ( ! empty($server) && ! $this->setIPAServer($server) ) {
            throw new \Exception("Error while validating the server");
        }
        else if ( ! empty($certificate) && ! $this->setCertificateFile($certificate) ) {
            throw new \Exception("Error while validating the certificate");
        }

        $this->cookie_file = tempnam(sys_get_temp_dir(), 'php_freeipa_api');
    }


    /**
     * To finalize a instance of this class
     *
     * @param void
     * @return void
     * @since 0.1
     */
    public function __destruct()
    {
        $this->endCurl();
        unlink($this->cookie_file);
    }

    /**
     * Define a version that will be used in json sent to the server. The server will refuse
     * requests from API that are greater than him
     *
     * @param string
     * @return void
     * @since 0.1
     * @see getVersaoAPI()
     */
    private function setAPIVersion($version)
    {
        $this->api_version = $version;
    }

    /**
     * Obtém a versão da API que está sendo utilizada nesta classe
     *
     * @param void
     * @return string
     * @since 0.1
     * @see setAPIVersion()
     */
    private function getVersaoAPI()
    {
        return $this->api_version;
    }

    /**
     * Define o endereço (IP ou hostname) do servidor
     *
     * @param string $host endereço (IP ou hostname) do servidor
     * @return bool
     * @since 0.1
     * @see __construct()
     * @see getIPAServer()
     */
    public function setIPAServer($host = null)
    {
        if (empty($host) || is_null($host) || !is_string($host)) {
            return false;
        }
        $this->ipa_server = $host;
        $this->jsonrpc_url = 'https://' . $host . '/ipa/session/json';
        $this->jsonrpc_login_url = 'https://' . $host . '/ipa/session/login_password';
        return true;
    }

    /**
     * Obtém o endereço (IP ou hostname) do servidor
     *
     * @param void
     * @return string|bool
     * @since 0.1
     * @see setIPAServer()
     */
    public function getIPAServer()
    {
        return $this->ipa_server;
    }

    /**
     * Define caminho completo (path) do certificado
     *
     * @param string $arquivo caminho completo (path) do certificado
     * @return bool false caso o arquivo não seja especificado ou não seja string. True executar corretamente
     * @since 0.1
     * @see __construct()
     * @see getCertificateFile()
     * @throws \Exception caso o arquivo não exista ou não possa ser lido
     */
    public function setCertificateFile($arquivo)
    {
        if (empty($arquivo) || is_null($arquivo) || !is_string($arquivo)) {
            return false;
        } else if (!file_exists($arquivo)) {
            throw new \Exception('Arquivo do certificado não existe');
        } else if (!is_readable($arquivo)) {
            throw new \Exception('Arquivo do certificado não pôde ser lido');
        }
        $this->certificate_file = $arquivo;
        return true;
    }

    /**
     * Retorna caminho completo (path) do certificado
     *
     * @return string|bool
     * @since 0.1
     * @see setCertificateFile()
     */
    public function getCertificateFile()
    {
        return $this->certificate_file;
    }

    /**
     * Define uma string de retorno do cURL
     *
     * @param string $string a string obtida através de uma execução do cURL
     * @return void
     * @since 0.1
     * @see getCurlResponse()
     */
    public function setCurlResponse($string = null)
    {
        $this->curl_response = $string;
    }

    /**
     * Obtem a string de retorno do cURL definida anteriormente com setCurlResponse()
     *
     * @param void
     * @return string|bool
     * @since 0.1
     * @see setCurlResponse()
     */
    public function getCurlResponse()
    {
        return $this->curl_response;
    }

    /**
     * Obtem a string da última requisição json feita (ou que será feita) ao servidor
     *
     * @param void
     * @return string|null
     * @see buildJsonRequest()
     * @since 0.2
     */
    public function getJsonRequest()
    {
        return $this->json_request;
    }

    /**
     * Obtem a string do último retorno json do servidor FreeIPA
     *
     * @param void
     * @return string|null
     * @since 0.2
     */
    public function getJsonResponse()
    {
        return $this->json_response;
    }

    /**
     * Obtem uma manipulador (handler) cURL já com algumas opções definidas
     *
     * @param bool $forcar inicia o cURL mesmo que ele já esteja
     * @return mixed Manipulador do cURL
     * @since 0.1
     * @see endCurl()
     */
    public function startCurl($forcar = false)
    {
        if (false === $this->curl_initiated || true === $forcar) {
            // Para garantir que a sessão é finalizada
            $this->endCurl();
            $this->curl_handler = curl_init();

            $opcoes_curl = array(
                // nome do arquivo do cookie
                CURLOPT_COOKIEFILE => $this->cookie_file,
                // The name of a file to save all internal cookies to when the handle is closed, e.g. after a call to curl_close.
                //CURLOPT_COOKIEJAR => $cookie_file,
                // Verificar o certificado
                CURLOPT_SSL_VERIFYPEER => true,
                // http://php.net/manual/pt_BR/function.curl-setopt.php
                CURLOPT_SSL_VERIFYHOST => 2,
                //
                CURLOPT_CAINFO => $this->certificate_file,
                //
                CURLOPT_POST => true,
                //
                CURLOPT_FOLLOWLOCATION => true,
                /* Retorna o valor de curl_exec() como string ao inves de jogar para a tela.
                  IMPORTANTE: o valor retornado pela função curl_exec() muda de acordo com este parametro e
                  o PHP não disponibiliza um método para obter o valor deste parâmetro, então compreenda que
                  o código assumirá que esta opção é sempre true, exceto onde explicitamente definido o
                  contrário */
                CURLOPT_RETURNTRANSFER => true,
                // The maximum number of seconds to allow cURL functions to execute.
                CURLOPT_TIMEOUT => $this->curl_timeout,
            );

            // workaround_for_auth
            if ($this->cookie_string) {
                $opcoes_curl = array_merge($opcoes_curl, array(CURLOPT_COOKIE => $this->cookie_string));
            }

            return $this->curl_initiated = curl_setopt_array($this->curl_handler, $opcoes_curl);
        }

        return $this->curl_initiated;
    }

    /**
     * Fecha o manipulador (handler) cURL
     *
     * @param void
     * @return void
     * @since 0.1
     * @see startCurl()
     */
    public function endCurl()
    {
        // @ suprime erros, já que no inicio o parâmetro é nulo
        @curl_close($this->curl_handler);
    }

    /**
     * Auxilia no debug do cURL. Deve ser utilizada no lugar de startCurl()
     *
     * @param void
     * @return Manipulador (handler) para o cURL
     * @since 0.1
     * @see startCurl()
     * @todo need improvements
     */
    public function debugCurl()
    {
        $this->startCurl();
        print "\n<br/>Debug do curl ativado<br/>\n";
        $opcoes_curl = array(
            // Verboso
            CURLOPT_VERBOSE => true,
            // Incluir o cabeçalho na resposta
            CURLOPT_HEADER => true,
            // true to output SSL certification information to STDERR on secure transfers.
            CURLOPT_CERTINFO => true,
            // Para rastrear a sequencia de requisição
            CURLINFO_HEADER_OUT => true,
        );
        $this->curl_debug = true;
        return curl_setopt_array($this->curl_handler, $opcoes_curl);
    }

    /**
     * Retorna bool dizendo se há erro no cURL
     *
     * @param void
     * @return bool
     * @since 0.1
     */
    public function curlHaveError()
    {
        return ( curl_errno($this->curl_handler) ) ? true : false;
    }

    /**
     * Retorna array contendo mensagem e número do erro ocorrido no cURL
     *
     * @param void
     * @return array
     * @since 0.1
     * @link http://curl.haxx.se/libcurl/c/libcurl-errors.html
     */
    public function getCurlError()
    {
        return array(
            curl_error($this->curl_handler),
            curl_errno($this->curl_handler),
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
    public function getCurlInfo()
    {
        return curl_getinfo($this->curl_handler);
    }

    /**
     * Executa uma instrução com o manipulador (handler) do cURL.
     * Em caso de erro, é possível obter mais informações com o método getCurlError() e
     * o retorno do cURL com o método getCurlResponse()
     *
     * @param void
     * @return string|bool retorna false em caso de erro ou o código da resposta HTTP em caso de sucesso
     * @since 0.1
     * @see getCurlError()
     * @see getCurlResponse()
     * @link http://php.net/manual/pt_BR/function.curl-exec.php
     * @todo sem o certificado o $codigo_http é 0 e nenhum output é gerado
     */
    public function curlExec()
    {
        $this->setCurlResponse(curl_exec($this->curl_handler));
        $codigo_http = curl_getinfo($this->curl_handler, CURLINFO_HTTP_CODE);
        // Pelo que entendi da documentação http://php.net/manual/pt_BR/function.curl-exec.php
        // e da prática o curl_exec retornará false somente se der algo errado na conexao. O webservice
        // do IPA retorna um html com o erro, então não vem o true, vem string.
        //
      // para quando CURLOPT_RETURNTRANSFER é false
        // if ( '1' == $curl_response_exec ) {
        // return true;
        // }
        //  removido no lugar de usar o metodo curlHaveError()
        // $retorno = $this->getCurlResponse() === false ? false : true;
        return ( $this->curlHaveError() ) ? false : $codigo_http;
    }

    /**
     * Tenta authenticate o usuário e senha no servidor, através da url defina em $jsonrpc_login_url
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
     * @throws \Exception se o cookie da sessão não for identificado. workaround_for_auth
     * @see docs/return_samples/authentication.txt
     * @todo este método é workaround_for_auth
     */
    public function authenticate($usuario = null, $senha = null)
    {
        if (!$usuario || !$senha) {
            return false;
        }

        $retorno = array(
            'autenticado' => false,
            'motivo' => '',
            'mensagem' => '',
            'codigo_http' => null,
        );
        $this->startCurl();
        $this->curl_http_header = array(
            'Content-type: application/x-www-form-urlencoded',
            'Accept: */*',
        );

        if (empty($usuario) || empty($senha)) {
            $retorno['autenticado'] = false;
            $retorno['mensagem'] = 'Usuário e/ou senha não foram informados';
            return $retorno;
        }

        //$usuario = urlencode($usuario);
        //$senha = urlencode($senha);

        curl_setopt($this->curl_handler, CURLOPT_HTTPHEADER, $this->curl_http_header);
        curl_setopt($this->curl_handler, CURLOPT_URL, $this->jsonrpc_login_url);
        curl_setopt($this->curl_handler, CURLOPT_POSTFIELDS, "user=$usuario&password=$senha");
        // Preciso do header para pegar o valor do campo X-IPA-Rejection-Reason
        // e como workaround_for_auth
        if (!$this->curl_debug) {
            curl_setopt($this->curl_handler, CURLOPT_HEADER, true);
        }

        $retorno['codigo_http'] = $this->curlExec();

        if ($this->curlHaveError()) {
            throw new \Exception($this->getCurlError()[0], $this->getCurlError()[1]);
        }

        // #TODO Talvez esse campo seja retornado apenas em erros 401
        // Exemplo do campo no header: X-IPA-Rejection-Reason: invalid-password
        preg_match('/X-IPA-Rejection-Reason: ([^\n]*)/i', $this->getCurlResponse(), $busca_reject_reason);
        $retorno['motivo'] = (empty($busca_reject_reason[1])) ? false : trim($busca_reject_reason[1]);

        // Preciso do header para pegar o valor do campo X-IPA-Rejection-Reason
        // e como workaround_for_auth
        if (!$this->curl_debug) {
            curl_setopt($this->curl_handler, CURLOPT_HEADER, false);
        }

        // #TODO Talvez esse campo seja retornado apenas em erros 401
        // Vi que nos erros 401 o ipa retorna um html que exibe uma mensagem de erro
        $retorno_uma_linha = str_replace(array("\n", "\r", "\r\n"), ' ', $this->curl_response);
        preg_match('#<p>(.*?)</p>#', $retorno_uma_linha, $busca_descricao_ipa);
        if (empty($busca_descricao_ipa[1])) {
            $descricao_erro_ipa = null;
        } else {
            $descricao_erro_ipa = str_replace(array('<strong>', '</strong>'), '', $busca_descricao_ipa[1]);
            $descricao_erro_ipa = trim($descricao_erro_ipa);
        }

        if ('401' == $retorno['codigo_http']) {
            $retorno['autenticado'] = false;
            // É melhor não exibir todas as mensagens diretamente ao usuário, para ficar mais amigável.
            // O $retorno['motivo'] invalid-password vem em mais de um caso (quando o usuario é bloqueado e ou usuario/senha está incorreto)
            if ('kinit: Preauthentication failed while getting initial credentials' == $descricao_erro_ipa) {
                $retorno['mensagem'] .= 'Usuário e/ou senha incorretos. ';
            } else if (preg_match("/Client (.*?) not found in Kerberos database while getting initial credentials/i", $descricao_erro_ipa)) {
                $retorno['mensagem'] .= 'Usuário não encontrado no servidor. ';
            } else {
                $retorno['mensagem'] .= 'Erro na autenticação. ';
                if (!empty($descricao_erro_ipa)) {
                    $retorno['mensagem'] .= "O servidor retornou \"" . $descricao_erro_ipa . "\". ";
                }
            }
        } else if ('200' != $retorno['codigo_http']) {
            $retorno['autenticado'] = false;
            $retorno['mensagem'] = "A resposta retornou o código HTTP \"" . $retorno['codigo_http'] . "\" que não é aceitável. ";
            if (!empty($descricao_erro_ipa)) {
                $retorno['mensagem'] .= "O servidor retornou \"" . $descricao_erro_ipa . "\". ";
            }
        } else {
            $retorno['autenticado'] = true;
            $retorno['mensagem'] = 'Usuário autenticado com sucesso. ';
            // workaround_for_auth. Obtenho a string do cookie manualmente.
            preg_match("/Set-Cookie: ([^\n]*)/", $this->getCurlResponse(), $encontrados);
            if (empty($encontrados[1])) {
                throw new \Exception('Erro ao identificar cookie de sessao');
            }
            // exemplo do $encontrados[1]:
            //ipa_session=2dd6a6e7ae5c0c388be3de7e50b454e9; Domain=fedora.ipateste.com.br; Path=/ipa; Expires=Sat, 06 Jun 2015 20:14:50 GMT; Secure; HttpOnly
            $this->cookie_string = trim($encontrados[1]);
            curl_setopt($this->curl_handler, CURLOPT_COOKIE, $this->cookie_string);
        }

        $this->user_logged = $retorno['autenticado'];
        return $retorno;
    }

    /**
     * Retorna bool que diz se o usuário está logado ou não
     *
     * @param void
     * @return bool
     * @since 0.1
     */
    public function userLogged()
    {
        return $this->user_logged;
    }

    /**
     * Verifica se a variavel eh um array associativo
     * 
     * @param array $var
     * @param bool $force se true, array tem que ser associativo. Se false, tem que ser associativo somente se nao for vazio
     * @return bool
     * @link http://php.net/manual/pt_BR/function.is-array.php#89332
     */
    public function isAssociativeArray($var, $force = true)
    {
        if (!is_array($var)) {
            return false;
        }

        if (!empty($var) || $force) {
            return array_diff_key($var, array_keys(array_keys($var)));
        }

        return true;
    }

    /**
     * Retorna uma string json no formato exigido pelo FreeIPA
     *
     * @param string $metodo parâmetro obrigatório que define o método a ser executado pelo servidor
     * @param array $argumentos argumentos para o método
     * @param array $opcoes parâmetros para o método
     * @return string|bool retorna false caso hava erro nos parâmetros passados
     * @since 0.1
     * @link http://php.net/manual/pt_BR/function.json-encode.php
     */
    public function buildJsonRequest($metodo = null, $argumentos = array(), $opcoes = array())
    {
        if (!$metodo || !is_array($argumentos) || !$this->isAssociativeArray($opcoes, false)) {
            return false;
        }

        $argumentos_padrao = array();
        $argumentos_final = array_merge($argumentos_padrao, $argumentos);

        $opcoes_padrao = array();
        // O FreeIPA retorna VersionError se uma versão diferente da do servidor for enviada
        if ($this->api_version) {
            $opcoes_padrao['version'] = $this->api_version;
        }
        $opcoes_final = array_merge($opcoes_padrao, $opcoes);

        // no ping as opções vao como {} mesmo que sejam vazias. O PHP manda array vazio como []
        // Uma possível solução seria usar o parâmetro JSON_FORCE_OBJECT apenas com a conversão
        // das opções e encaixar o resultado no retorno, mas ao fazer isso o PHP delimita o {}
        if ('ping' == strtolower($metodo) && empty($opcoes_final)) {
            return $this->json_request = '{ "id": 0, "method": "ping", "params": [ [],{} ] }';
        }

        $retorno = array(
            'id' => 0,
            'method' => $metodo,
            'params' => array($argumentos_final, $opcoes_final),
        );

        $this->json_request = json_encode($retorno, JSON_PRETTY_PRINT);
        return $this->json_request;
    }

    /**
     * Envia requisições para o servidor do FreeIPA utilizando a sessão previamente estabelecida e
     * armazena o retorno em $this->json_response.
     * Com este método é possível utilizar qualquer método da API json RPC do FreeIPA.
     *
     * @param string $metodo parâmetro obrigatório que define o método a ser executado pelo servidor
     * @param array $parametros argumentos para o método
     * @param array $opcoes parâmetros para o método
     * @param bool $exceptionInError se true, irá lançar uma \Exception caso o campo error da resposta venha preenchido
     * @return array com objeto (vindo de json_decode()) e código http da resposta
     * @since 0.1
     * @since 0.3 $exceptionInError
     * @throws \Exception se o usuário não esteja logado
     * @throws \Exception se há erro ao criar a requisição
     * @throws \Exception se há erro ao definir opções no cURL ou ao realizar a requisição
     * @throws \Exception se o código http de resposta é vazio ou diferente de 200
     * @throws \Exception se o retorno json está vazio
     * @throws \Exception (se $exceptionInError é true) com descrição e número do erro caso o retorno json retorne erro
     * @see userLogged()
     * @see buildJsonRequest()
     * @see $json_response
     * @see ../docs/return_samples/invalid_json_request.txt
     * @link http://php.net/manual/pt_BR/function.json-decode.php
     */
    public function buildRequest($metodo = null, $parametros = array(), $opcoes = array(), $exceptionInError = true)
    {
        if (!$this->userLogged()) {
            throw new \Exception('Usuario não está logado');
        }

        $json = $this->buildJsonRequest($metodo, $parametros, $opcoes);
        if (false === $json) {
            throw new \Exception('Erro ao criar requisição json');
        }

        $opcoes_curl = array(
            CURLOPT_URL => $this->jsonrpc_url,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array(
                'referer:https://' . $this->ipa_server . '/ipa/ui/index.html',
                'Content-Type:application/json',
                'Accept:applicaton/json',
                'Content-Length: ' . strlen($json),
            ),
        );

        $define_opcoes = curl_setopt_array($this->curl_handler, $opcoes_curl);
        if (false === $define_opcoes) {
            throw new \Exception('Erro ao definir opções no curl');
        }

        $codigo_http_resposta = $this->curlExec();
        $json_retorno = $this->json_response = $this->getCurlResponse();
        $objeto_json_retorno = json_decode($json_retorno);
        if ($this->curlHaveError()) {
            throw new \Exception('Erro na requisição curl');
        }
        if (!$codigo_http_resposta || '200' != $codigo_http_resposta) {
            throw new \Exception("O valor \"$codigo_http_resposta\" não é um código de resposta válido");
        }
        if (empty($json_retorno) || empty($objeto_json_retorno)) {
            #TODO criar exceção para passar os dados do erro ao inves do json puro. Vide arquivo exemplos_retornos.txt
            throw new \Exception("Erro no retorno json. Valor é ${json_retorno}");
        }
        if ($exceptionInError && !empty($objeto_json_retorno->error)) {
            throw new \Exception("Erro na requisição. Detalhes: " . $objeto_json_retorno->error->message, $objeto_json_retorno->error->code);
        }

        return array($objeto_json_retorno, $codigo_http_resposta);
    }

    /**
     * Realiza um ping no servidor do FreeIPA através da API.
     *
     * @param bool $retornar_string se true, irá retornar o campo de resumo da resposta json
     * @return string|bool boleano indicado se o processo foi bem sucedido ou a string do resumo do retorno caso o parâmetro $retornar_string seja true
     * @since 0.1
     * @see ../docs/return_samples/ping.txt
     */
    public function pingToServer($retornar_string = false)
    {
        $ret = $this->buildRequest('ping'); // retorna json e codigo http da resposta
        $json = $ret[0];
        if (!empty($json->error) || empty($json->result) || empty($json->result->summary) || !is_string($json->result->summary)) {
            return false;
        }
        if ($retornar_string) {
            return $json->result->summary;
        } else {
            return true;
        }
    }

}
