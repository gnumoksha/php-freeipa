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
 * @sice GIT 0.1.0
 */
namespace FreeIPA\APIAccess;

/**
 * Singleton class for connection with the freeIPA server
 *
 * Note there is a problem doing in PHP similar to "--negotiate -u :" in
 * cURL cli. It was made a workaround that can be found using the case sensitive
 * string "workaround_for_auth" in this file
 *
 * @author Tobias Sette <contato@tobias.ws>
 * @copyright Copyright (c) 2015 Tobias Sette <contato@tobias.ws>
 * @license LGPLv3
 * @package php-freeipa
 * @since GIT: 0.1.0
 * @version GIT 0.2.0
 */
class Connection
{
    /**
     * Sotre only one instance of this class
     * @var instance
     * @access private
     * @since GIT: 0.1.0
     */
    private static $_instance;

    /**
     * API version that will be defined in the request
     * The freeIPA returns VersionError if diferent version of server version is sent
     * and a warning if the version is not sent
     *
     * @var string|null api_version API version that will be sent in each requisition
     * @access private
     * @since GIT: 0.1.0
     */
    protected $api_version = null;

    /**
     * @var mixed cURL handler
     * @access public
     * @since GIT: 0.1.0
     */
    protected $curl_handler = null;

    /**
     * @var bool curl_initiated if cURL was initiated or not
     * @access protected
     * @since GIT: 0.1.0
     */
    protected $curl_initiated = false;

    /**
     * @var bool curl_debug if cURL will be initiated with debug or not
     * @access private
     * @since GIT: 0.1.0
     */
    protected $curl_debug = false;

    /**
     * @var string|null curl_response Stores response/return of cURL
     * @access protected
     * @since GIT: 0.1.0
     */
    protected $curl_response = null;

    /**
     * @var int curl_timeout Timeout for cURL connection
     * @access public
     * @sice GIT 0.1.0
     */
    protected $curl_timeout = 10;

    /**
     * @var string|null cookie_file Full path for file that will stores cookie
     * @access private
     * @sice GIT 0.1.0
     */
    protected $cookie_file = null;

    /**
     * @var string|null cookie_string String that contains cookie for use in cURL. workaround_for_auth
     * @access private
     * @sice GIT 0.1.0
     */
    protected $cookie_string = null;

    /**
     * @var string|null certificate_file Full path of certificate file for use in connections with the server
     * @access public
     * @sice GIT 0.1.0
     */
    protected $certificate_file  = null;

    /**
     * @var array curl_http_header HTTP header that will be used with cURL
     * @access public
     * @sice GIT 0.1.0
     */
    protected $curl_http_header = array();

    /**
     * @var string|null ipa_server IP address or hostname of freeIPA server
     * @access protected
     * @sice GIT 0.1.0
     */
    protected $ipa_server = null;

    /**
     * @var string|null jsonrpc_url URL where the server accept json RPC connections
     * @access protected
     * @sice GIT 0.1.0
     */
    protected $jsonrpc_url = null;

    /**
     * @var string|null jsonrpc_login_url URL where the server accept loggin connections
     * @access protected
     * @sice GIT 0.1.0
     */
    protected $jsonrpc_login_url = null;

    /**
     * @var bool user_logged If user made login or not
     * @access protected
     * @sice GIT 0.1.0
     */
    protected $user_logged  = false;
    
    /**
     * @var string|null $json_request String that contains the last json request what will be (or was) sent to the server
     * @access protected
     * @since GIT: 0.1.0
     */
    protected $json_request = null;

    /**
     * @var string|null $json_response String that contains the last json response from server
     * @access protected
     * @since GIT: 0.1.0
     */
    protected $json_response = null;
    
    /**
     * @var array Stores information about a previous authentication
     * @see authenticate()
     * @see getAuthenticationInfo()
     * @since GIT: 0.2.0
     */
    protected $authentication_info = array();


    /**
     * Executa ações necessárias ao início do uso de uma instância desta classe.
     * Por favor, note que o servidor e certificado não são obrigatórios ao instanciar a classe,
     * mas são obrigatórios em diversos métodos.
     *
     * <code>
     * $ipa = FreeIPA\APIAccess\Connection::getInstance('192.168.0.5', '/tmp/certificate.crt');
     * $ipa2 = FreeIPA\APIAccess\Connection::getInstance();
     * $ipa2->setIPAServer('192.168.0.5');
     * $ipa2->setCertificateFile('/tmp/certificado.crt');
     * </code>
     *
     * @param string|null $server address (IP or hostname) of server
     * @param string|null $certificate full path of server certificate
     * @return void
     * @sice GIT 0.1.0
     * @see getInstance()
     * @see setIPAServer()
     * @see setCertificateFile()
     * @throws \Exception caso o módulo não esteja instalado curl
     * @throws \Exception caso o método setIPAServer() retorne false
     * @throws \Exception caso o método setCertificateFile() retorne false
     */
    private function __construct($server = null, $certificate = null) {
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
     * This a Singleton class
     * 
     * @since GIT: 0.1.0
     */
    private function __clone()
    {
        // nothing
    }
    
    /**
     * This is a Singlton class
     * 
     * @since GIT: 0.1.0
     */
    private function __wakeup()
    {
        // nothing
    }

    /**
     * To finalize a instance of this class
     *
     * @param void
     * @return void
     * @sice GIT 0.1.0
     */
    public function __destruct()
    {
        $this->endCurl();
        unlink($this->cookie_file);
    }
    
    /**
     * 
     * @param type $server address (IP or hostname) of server
     * @param type $certificate full path of server certificate
     * @param type $force_new if true, a new instance is returned (breaking the Singleton)
     * @return type
     * @return instance of this class
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public static function getInstance($server = null, $certificate = null, $force_new = false)
    {
        switch ($force_new) {
            case false:
                if (! isset(self::$_instance)) {
                    self::$_instance = new self($server, $certificate);
                }
                $r = self::$_instance;
                break;
            case true:
                $r = new self($server, $certificate);
                break;
        }
        return($r);
	}

    /**
     * Define a version that will be used in json sent to the server. The server will refuse
     * requests from API that are greater than him
     *
     * @param string
     * @return void
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     * @see getAPIVersion()
     */
    private function setAPIVersion($version)
    {
        $this->api_version = $version;
    }

    /**
     * Get the API version that is being used in this class
     *
     * @param void
     * @return string
     * @sice GIT 0.1.0
     * @version GIT: 0.2.0
     * @see setAPIVersion()
     */
    private function getAPIVersion()
    {
        return $this->api_version;
    }

    /**
     * Define the server address (IP or hostname)
     *
     * @param string $host endereço (IP ou hostname) do servidor
     * @return bool
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
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
     * Get the server address (IP or hostname)
     *
     * @param void
     * @return string|bool
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     * @see setIPAServer()
     */
    public function getIPAServer()
    {
        return $this->ipa_server;
    }

    /**
     * Define the full path of certificate file
     *
     * @param string $file full path of certificate file
     * @return bool false if the file is not stated nor string. True in success
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     * @see __construct()
     * @see getCertificateFile()
     * @throws \Exception if the file does not exist or can't be read
     */
    public function setCertificateFile($file)
    {
        if (empty($file) || is_null($file) || !is_string($file)) {
            return false;
        } else if (!file_exists($file)) {
            throw new \Exception("Certificate file doesn't exists");
        } else if (!is_readable($file)) {
            throw new \Exception("Certificate file can't be read");
        }
        $this->certificate_file = $file;
        return true;
    }

    /**
     * Get the full path of certificate file
     *
     * @return string|bool
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     * @see setCertificateFile()
     */
    public function getCertificateFile()
    {
        return($this->certificate_file);
    }

    /**
     * Define the string returned by cURL
     *
     * @param string $string string returned by cURL
     * @return void
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     * @see getCurlReturn()
     */
    public function setCurlReturn($string = null)
    {
        $this->curl_response = $string;
    }

    /**
     * Get the string returned by cURL
     *
     * @param void
     * @return string|bool
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     * @see setCurlReturn()
     */
    public function getCurlReturn()
    {
        return $this->curl_response;
    }

    /**
     * Get the string of last json request (or the one that will be made) to the server
     *
     * @param void
     * @return string|null
     * @see buildJsonRequest()
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function getJsonRequest()
    {
        return $this->json_request;
    }

    /**
     * Get the string of last json return of freeIPA server
     *
     * @param void
     * @return string|null
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
     */
    public function getJsonResponse()
    {
        return $this->json_response;
    }

    /**
     * Get the cURL handler with options already defined
     *
     * @param bool $force force cURL to be initiated again
     * @return mixed cURL handler
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     * @see endCurl()
     */
    public function startCurl($force = false)
    {
        if (false === $this->curl_initiated || true === $force) {
            $this->endCurl(); // for precaution
            $this->curl_handler = curl_init();

            $curl_options = array(
                // nome do arquivo do cookie
                CURLOPT_COOKIEFILE => $this->cookie_file,
                // The name of a file to save all internal cookies to when the handle is closed, e.g. after a call to curl_close.
                //CURLOPT_COOKIEJAR => $cookie_file,
                // Verify the certificate
                CURLOPT_SSL_VERIFYPEER => true,
                // http://php.net/manual/en/function.curl-setopt.php
                CURLOPT_SSL_VERIFYHOST => 2,
                //
                CURLOPT_CAINFO => $this->certificate_file,
                //
                CURLOPT_POST => true,
                //
                CURLOPT_FOLLOWLOCATION => true,
                /*
                 * Return the value of curl_exec() as string insted of print to screen
                 * IMPORTANT: the returned value by the function curl_exec() changes according to
                 * this parameter and PHP does not give an method to obtain this parameter's value,
                 * so understand that the code will assume that this option is always true, except
                 * where explicitly defined the opossite.
                 */
                CURLOPT_RETURNTRANSFER => true,
                // The maximum number of seconds to allow cURL functions to execute.
                CURLOPT_TIMEOUT => $this->curl_timeout,
            );

            // workaround_for_auth
            if ($this->cookie_string) {
                $curl_options = array_merge($curl_options, array(CURLOPT_COOKIE => $this->cookie_string));
            }

            return $this->curl_initiated = curl_setopt_array($this->curl_handler, $curl_options);
        }

        return $this->curl_initiated;
    }

    /**
     * Close the cURL handler
     *
     * @param void
     * @return void
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     * @see startCurl()
     */
    public function endCurl()
    {
        // @ suppress error. In the beginning the curl_handler is null
        @curl_close($this->curl_handler);
    }

    /**
     * Aid in cURL debug. Must be used in the place of startCurl()
     *
     * @param void
     * @return Manipulador (handler) para o cURL
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     * @see startCurl()
     * @todo need improvements
     */
//    public function debugCurl()
//    {
//        $this->startCurl();
//        print PHP_EOL . '<br/>Debug do curl ativado<br/>' . PHP_EOL;
//        $curl_options = array(
//            // Verbosity
//            CURLOPT_VERBOSE => true,
//            // Include header in the response
//            CURLOPT_HEADER => true,
//            // true to output SSL certification information to STDERR on secure transfers.
//            CURLOPT_CERTINFO => true,
//            // 
//            CURLINFO_HEADER_OUT => true,
//        );
//        $this->curl_debug = true;
//        return curl_setopt_array($this->curl_handler, $curl_options);
//    }

    /**
     * If a previous use of cURL has generated an error
     *
     * @param void
     * @return bool
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     */
    public function curlHaveError()
    {
        return ( curl_errno($this->curl_handler) ) ? true : false;
    }

    /**
     * Return an array that contains the message and error number of last cURL error
     *
     * @param void
     * @return array
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
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
     * Return an array that contains cURL information
     *
     * @param void
     * @return array
     * @sice GIT 0.1.0
     * @link http://php.net/manual/en/function.curl-getinfo.php
     */
    public function getCurlInfo()
    {
        return curl_getinfo($this->curl_handler);
    }

    /**
     * Execute a statement with cURL handler.
     * In error, use the method getCurlError to obtain more information e
     * getCurlReturn to obtain the cURL response
     *
     * @param void
     * @return string|bool return false in error or HTTP response code
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     * @see getCurlError()
     * @see getCurlReturn()
     * @link http://php.net/manual/en/function.curl-exec.php
     * @TODO without certificate the $http_code é 0 e nenhum output é gerado
     */
    public function curlExec()
    {
        $this->setCurlReturn(curl_exec($this->curl_handler));
        $http_code = curl_getinfo($this->curl_handler, CURLINFO_HTTP_CODE);
        // Pelo que entendi da documentação http://php.net/manual/en/function.curl-exec.php
        // e da prática o curl_exec retornará false somente se der algo errado na conexao. O webservice
        // do IPA retorna um html com o erro, então não vem o true, vem string.
        //
        // when CURLOPT_RETURNTRANSFER is false
        // if ( '1' == $curl_response_exec ) {
        //     return true;
        // }
        return (($this->curlHaveError()) ? false : $http_code);
    }
    
    /**
     * Try to authenticate the user and password in the server through URL
     * defined in $jsonrpc_login_url
     *
     * @param string $user
     * @param string $password
     * @return bool
     * @sice GIT 0.1.0
     * @version GIT: 0.2.0
     * @throws \Exception if cURL has error
     * @throws \Exception if $this->ipa_server is invalid
     * @throws \Exception if $this->certificate_file is invalid
     * @throws \Exception if unable to find the session cookie. workaround_for_auth
     * @see docs/return_samples/authentication.txt
     * @TODO this method contains a workaround_for_auth
     */
    public function authenticate($user = null, $password = null)
    {
        if ($this->userLogged()) {
            return true;
        }
        
        if (!$user || !$password) {
            return false;
        }
        
        if (! $this->getIPAServer()) {
            throw new \Exception("Error while validating the server");
        }
        if (! $this->getCertificateFile()) {
            throw new \Exception("Error while validating the certificate");
        }

        $auth_info = array(
            'authenticate' => false,
            'reason' => '',
            'message' => '',
            'http_code' => null,
        );
        $this->startCurl();
        $this->curl_http_header = array(
            'Content-type: application/x-www-form-urlencoded',
            'Accept: */*',
        );

        if (empty($user) || empty($password)) {
            $auth_info['authenticate'] = false;
            $auth_info['message'] = 'User/password is empty';
            return $auth_info;
        }

        //$user = urlencode($user);
        //$password = urlencode($password);

        curl_setopt($this->curl_handler, CURLOPT_HTTPHEADER, $this->curl_http_header);
        curl_setopt($this->curl_handler, CURLOPT_URL,        $this->jsonrpc_login_url);
        curl_setopt($this->curl_handler, CURLOPT_POSTFIELDS, "user=$user&password=$password");
        // I need header for get the value for X-IPA-Rejection-Reason field
        // and as workaround_for_auth
        if (! $this->curl_debug) {
            curl_setopt($this->curl_handler, CURLOPT_HEADER, true);
        }

        $auth_info['http_code'] = $this->curlExec();

        if ($this->curlHaveError()) {
            $e = $this->getCurlError();
            throw new \Exception($e[0], $e[1]);
        }

        // #TODO Maybe this field be returned only in 401 errors
        // Example of this field on header: X-IPA-Rejection-Reason: invalid-password
        preg_match('/X-IPA-Rejection-Reason: ([^\n]*)/i', $this->getCurlReturn(), $search_reject_reason);
        $auth_info['reason'] = (empty($search_reject_reason[1])) ? false : trim($search_reject_reason[1]);

        // I need header for get the value for X-IPA-Rejection-Reason field
        // and as workaround_for_auth
        if (!$this->curl_debug) {
            curl_setopt($this->curl_handler, CURLOPT_HEADER, false);
        }

        // #TODO Maybe this field be returned only in 401 errors
        // I see that in 401 error freeIPA server returns a html that contains  a error message
        $online_return = str_replace(array("\n", "\r", "\r\n"), ' ', $this->curl_response);
        preg_match('#<p>(.*?)</p>#', $online_return, $search_description_in_ipa);
        if (empty($search_description_in_ipa[1])) {
            $ipa_error_description = null;
        } else {
            $ipa_error_description = str_replace(array('<strong>', '</strong>'), '', $search_description_in_ipa[1]);
            $ipa_error_description = trim($ipa_error_description);
        }

        if ('401' == $auth_info['http_code']) {
            $auth_info['authenticate'] = false;
            // É melhor não exibir todas as mensagens diretamente ao usuário, para ficar mais amigável.
            // O $auth_info['reason'] invalid-password vem em mais de um caso (quando o usuario é bloqueado e ou usuario/senha está incorreto)
            if ('kinit: Preauthentication failed while getting initial credentials' == $ipa_error_description) {
                $auth_info['message'] .= 'User or password are wrong. ';
            } else if (preg_match("/Client (.*?) not found in Kerberos database while getting initial credentials/i", $ipa_error_description)) {
                $auth_info['message'] .= 'Unable to find user in the server. ';
            } else {
                $auth_info['message'] .= 'Generic error in authentication. ';
                if (! empty($ipa_error_description)) {
                    $auth_info['message'] .= "The server returned \"" . $ipa_error_description . "\". ";
                }
            }
        } else if ('200' != $auth_info['http_code']) {
            $auth_info['authenticate'] = false;
            $auth_info['message'] = "The response returned the HTTP code \"" . $auth_info['http_code'] . "\" that is not acceptable. ";
            if (!empty($ipa_error_description)) {
                $auth_info['message'] .= "The server returned \"" . $ipa_error_description . "\". ";
            }
        } else {
            $auth_info['authenticate'] = true;
            $auth_info['message'] = 'User has successfully authenticated. ';
            // workaround_for_auth. Obtenho a string do cookie manualmente.
            preg_match("/Set-Cookie: ([^\n]*)/", $this->getCurlReturn(), $found);
            if (empty($found[1])) {
                throw new \Exception('Erro for locate the session cookie');
            }
            // example of $found[1]:
            //ipa_session=2dd6a6e7ae5c0c388be3de7e50b454e9; Domain=fedora.ipatest.com; Path=/ipa; Expires=Sat, 06 Jun 2015 20:14:50 GMT; Secure; HttpOnly
            $this->cookie_string = trim($found[1]);
            curl_setopt($this->curl_handler, CURLOPT_COOKIE, $this->cookie_string);
        }

        $this->user_logged = $auth_info['authenticate'];
        $this->authentication_info = $auth_info;
        return($this->user_logged);
    }
    
    /**
     * Get information about a previous authentication through
     * authenticate() method
     * 
     * $return
     *  ['authenticate'] bool if user is authenticated
     *  ['reason'] string the reason of the last action
     *  ['message'] string with the message generated for the last action
     *  ['http_code'] HTTP code for the response
     * 
     * @return array $return see description above
     * @since GIT: 0.2.0
     * @version GIT: 0.2.0
     */
    public function getAuthenticationInfo()
    {
        return($this->authentication_info);
    }

    /**
     * Retorna bool que diz se o usuário está logado ou não
     *
     * @param void
     * @return bool
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     */
    public function userLogged()
    {
        return $this->user_logged;
    }

    /**
     * Checks if a variable is a associative array
     * 
     * @param array $var
     * @param bool $force if true array must be associative. If false, must be associative only if not empty
     * @return bool
     * @link http://php.net/manual/en/function.is-array.php#89332
     * @since GIT: 0.1.0
     * @version GIT: 0.1.0
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
     * Returns a json string in the format required by FreeIPA
     *
     * @param string $method required parameter that defines the method that will be executed in the server
     * @param array $args arguments for the method
     * @param array $options options for the method
     * @return string|bool returns false if there is error in passed parameters
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     * @link http://php.net/manual/en/function.json-encode.php
     */
    public function buildJsonRequest($method = null, $args = array(), $options = array())
    {
        if (!$method || !is_array($args) || !$this->isAssociativeArray($options, false)) {
            return false;
        }

        $default_args = array();
        $final_args = array_merge($default_args, $args);

        $default_options = array();
        // freeIPA returns VersionError if a different version of the server is sent
        if ($this->api_version) {
            $default_options['version'] = $this->api_version;
        }
        $final_options = array_merge($default_options, $options);

        // in ping the options are {} even if empty. The PHP send empty array as []
        // One possible solution is to use the JSON_FORCE_OBJECT parameter only with
        // the convert options and merge the result in the return, but doing that
        // the PHP delimits the {}
        if ('ping' == strtolower($method) && empty($final_options)) {
            return $this->json_request = '{ "id": 0, "method": "ping", "params": [ [],{} ] }';
        }

        $return = array(
            'id' => 0,
            'method' => $method,
            'params' => array($final_args, $final_options),
        );

        $this->json_request = json_encode($return, JSON_PRETTY_PRINT);
        return $this->json_request;
    }

    /**
     * Sends requests for the freeIPA server using the previous established session
     * and stores the return in $this->json_response
     * With this method is possible to make requests for any freeIPA API method
     *
     * @param string $method required parameter that defines the method that will be executed in the server
     * @param array $params arguments for the method
     * @param array $options options for the method
     * @param bool $exceptionInError if true, will lauch \Exception if error field in response comes filled
     * @return array with response object (comes of json_decode()) and http code of response
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     * @throws \Exception if user is not logged in
     * @throws \Exception if has error while create request
     * @throws \Exception if has error while define cURL options or make a request
     * @throws \Exception if a http code of response is not 200
     * @throws \Exception if json response is empty
     * @throws \Exception (if $exceptionInError is true) with description and number of error if json returns error
     * @see userLogged()
     * @see buildJsonRequest()
     * @see $json_response
     * @see ../../docs/return_samples/invalid_json_request.txt
     * @link http://php.net/manual/en/function.json-decode.php
     */
    public function buildRequest($method = null, $params = array(), $options = array(), $exceptionInError = true)
    {
        if (!$this->userLogged()) {
            throw new \Exception('User is not logged in');
        }

        $json = $this->buildJsonRequest($method, $params, $options);
        if (false === $json) {
            throw new \Exception('Error while create json request');
        }

        $curl_options = array(
            CURLOPT_URL => $this->jsonrpc_url,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array(
                'referer:https://' . $this->ipa_server . '/ipa/ui/index.html',
                'Content-Type:application/json',
                'Accept:applicaton/json',
                'Content-Length: ' . strlen($json),
            ),
        );

        $define_options = curl_setopt_array($this->curl_handler, $curl_options);
        if (false === $define_options) {
            throw new \Exception('Error while define cURL options');
        }

        $response_http_code = $this->curlExec();
        $json_retorno = $this->json_response = $this->getCurlReturn();
        $object_json_returned = json_decode($json_retorno);
        if ($this->curlHaveError()) {
            throw new \Exception('Error in cURL request');
        }
        if (!$response_http_code || '200' != $response_http_code) {
            throw new \Exception("The value \"$response_http_code\" is not a valid response code");
        }
        if (empty($json_retorno) || empty($object_json_returned)) {
            #TODO criar exceção para passar os dados do erro ao inves do json puro. Vide arquivo exemplos_retornos.txt
            throw new \Exception("Erro in json return. Value is ${json_retorno}");
        }
        if ($exceptionInError && !empty($object_json_returned->error)) {
            throw new \Exception("Error in request. Details: " . $object_json_returned->error->message, $object_json_returned->error->code);
        }

        return array($object_json_returned, $response_http_code);
    }
    
    /**
     * Makes a ping in FreeIPA server through of api
     *
     * @param bool if $return_string is true, will return the summary field of json response
     * @return string|bool true if success or string if $return_string is true
     * @sice GIT 0.1.0
     * @version GIT: 0.1.0
     * @see ../../docs/return_samples/ping.txt
     */
    public function ping($return_string = false)
    {
        $ret = $this->buildRequest('ping'); // receives json and http response code
        $json = $ret[0];
        if (!empty($json->error) || empty($json->result) || empty($json->result->summary) || !is_string($json->result->summary)) {
            return false;
        }
        if ($return_string) {
            return $json->result->summary;
        } else {
            return true;
        }
    }

}
