<?php
/**
 *  Component for use MultishipAPI
 *  @author Bugai Maksym <marvelsrp@gmail.com>
 */

class MultishipAPI extends CApplicationComponent
{

    /**
     * Login user multiship
     * @var string
     */
    private $_login;

    /**
     * Password user multiship
     * @var string
     */
    private $_password;

    /**
     * Domain user multiship
     * @var string
     */
    private $_domain;

    /**
     * City of sender
     * @var string
     */
    private $_city;

    /**
     * Config from multiship
     * @var string
     */
    private $_config;

	 /**
     * Response from multiship
     * @var array
     */
    private $_response = false;
	
    /**
     * Error multiship
     * @var string
     */
    private $_error = false;

    /**
     * Format response
     * @var string
     */
    private $_format = 'json';//или php

    /**
     * Curl
     * @var Curl
     */
    private $_ch;

    private $_curlOptions = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_POST           => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => array("Content-Type: application/x-www-form-urlencoded"),
    );


    const INIT_API_URL = 'https://multiship.ru/initApi/';
    const OPEN_API_URL = 'https://multiship.ru/openAPI_v3/';

    public function init()
    {
        $this->_ch = curl_init();
        curl_setopt_array($this->_ch, $this->_curlOptions);
    }

    /**
     * Exec request
     * @param $data
     * @return bool|mixed
     * @throws CException
     */
    private function _execCurl($data)
    {
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($this->_ch);

        $result = array(
            'status' => false,
        );
        if (curl_errno($this->_ch)) {
            $this->Error = curl_error($this->_ch);
        } else {
            $data = CJSON::decode($response);

            if (isset($data['error']))
            {
                $this->Error = $data['error'];
            } else if (empty($data))
            {
                $this->Error = "Нет ответа";
            } else {
                $result = $data;
            }
        }
        return $result;
    }

    /**
     * Query to Multiship API
     * @param $method
     * @param array $params
     * @param string $url
     * @return bool|mixed
     */
    public function apiQuery($method, $params = array(), $url = self::OPEN_API_URL)
    {

       $this->getConfig();

        curl_setopt($this->_ch, CURLOPT_URL, $url.$method);

        $params['format'] = $this->_format;
        $params['client_id'] = (int)$this->_config['config']['clientId'];


        $params['module_install_id'] = (int)$this->_config['moduleInstallId'];
        if (isset($this->_config['config']['methodKeys'][$method]))
            $params['secret_key'] = $this->sign($this->_config['config']['methodKeys'][$method], $params);

        $this->_response = $this->_execCurl($params);

        return $this->_response;
    }

    /**
     * Registration Multiship API
	 * @return bool
     */
    public function initAPI(){
        $params = array(
            "login" => $this->_login,
            "password" => $this->_password,
            'cmsName'     => "%CMS_NAME%",
            'cmsVersion'  => "%CMS_VERSION%",
            "domain" => $this->_domain,
            "callbackUrl" => $this->_domain.'/%CALLBACK_URL%'
        );
        curl_setopt($this->_ch, CURLOPT_URL, self::INIT_API_URL.'init');
        $this->_response = $this->_execCurl($params);

        if ($this->_response['config']){
            $this->_config = $this->_response;
			/*Need save config to db*/
        }
        return !empty($this->_config);
    }


    /**
     * Set domain
     * @param $domain
     */
    public function setDomain($domain){
        $this->_domain = $domain;
    }

    /**
     * Get city
     * @return string
     */
    public function getCity(){
        return $this->_city;
    }

    /**
     * Set password
     * @param $password
     */
    public function setPassword($password){
        $this->_password = $password;
    }

    /**
     * Set login
     * @param $login
     */
    public function setLogin($login){
        $this->_login = $login;
    }

    /**
     * Set Error
     * @param $error
     * @throws Exception
     */
    public function setError($error){
        if (!$this->_error)  $this->_error = $error;
        throw new Exception($this->_error);
    }

    /**
     * Get Error
	 * @return string
     */
    public function getError(){
        return $this->_error;
    }

    /**
     * @return array
     */
    public function getConfig(){

            if (!$this->_config) {
				/*Get config from db*/
				...
				$this->_config = $config_from_db;
            }

        return $this->_config;
    }

    /**
     * Generate secret_key 
	 *
     * @return string
     */
    function sign($methodKey, $data, $debug = false)
    {
        $secretKey = '';

        $keys = array_keys($data);
        sort($keys);

        foreach ($keys as $key)
        {
            if (!is_array($data[$key]))
            {
                $secretKey .= $data[$key];
            }
            else
            {
                $subkeys = array_keys($data[$key]);
                sort($subkeys);
                foreach ($subkeys as $subkey)
                {
                    if (!is_array($data[$key][$subkey]))
                    {
                        $secretKey .= $data[$key][$subkey];
                    }
                    else
                    {
                        $subsubkeys = array_keys($data[$key][$subkey]);
                        sort($subsubkeys);
                        foreach ($subsubkeys as $subsubkey)
                        {
                            if (!is_array($data[$key][$subkey][$subsubkey]))
                            {
                                $secretKey .= $data[$key][$subkey][$subsubkey];
                            }
                        }
                    }
                }
            }
        }

        $preparedData = $secretKey . $methodKey;

        $secretKey = md5($preparedData);

        if (true == $debug)
        {
            echo "-------START sign(...);-------<br>\r\n";
            echo "Request Client Key: \r\n" . $methodKey . "<br>\r\n";
            echo "Data: " . print_r($data, true) . "<br>\n\r";
            echo "Request String Dump: \r\n" . $preparedData . "<br>\r\n";
            echo "Request Secure Key = MD5(Request String Dump): \r\n" . $secretKey . "<br>\r\n";
            echo "-------END sign(...);-------\r\n";
        }

        return $secretKey;
    }

    /**
     * Debug response;
     */
    public function Debug()
    {
        if ($this->_response === false) echo 'Нет ответа';
        if (!empty($this->_response) and $this->_response['status'] == false)
        {
            echo $this->_response['errno'] . ': ' . $this->_response['error'];
        }
        else
        {
            echo '<pre><b>$response: </b><br>';
            $obj = json_decode($this->_response['data']);
            var_dump($obj);
            echo '</pre>';
        }
        exit();
    }

    /**
     * Algorithm for determining the size of the box, which fit the order. 
     * The test array with dimensions of correctnes
     *
     * @param $arr 
     *
     * @return bool
     */
     public static function isValidDimensionArr($arr)
    {
        if (is_array($arr) and 3 == sizeof($arr))
        {
            foreach ($arr as $a)
            {
                if ((!is_int($a) and !is_float($a)) or $a < 0.1)
                {
                    return false;
                }
            }
        }
        else
        {
            return false;
        }

        return true;
    }

    /**
     * Algorithm for determining the size of the box, which fit the order. 
     * The test array with dimensions of correctnes
     *
     * @param array $dimensions
     *
     * @return array
     */
    public static function dimensionAlgorithm(array $dimensions)
    {
        $dim    = [];
        $result = [
            'A' => 0,
            'B' => 0,
            'C' => 0
        ];
        foreach ($dimensions as $d)
        {
            if (self::isValidDimensionArr($d))
            {
                rsort($d);
                $dim[] = $d;
            }
        }

        foreach ($dim as $d)
        {
            ($d[0] > $result['A']) ? $result['A'] = $d[0] : '';
            ($d[1] > $result['B']) ? $result['B'] = $d[1] : '';
            $result['C'] += $d[2];
        }

        return $result;
    }

    /**
     * Call methods
     * @param string $method
     * @param array $args
     * @return mixed|void
     */
    public function __call($method, $args)
    {

        $params = empty($args) ? array() : $args[0];
        return $this->apiQuery($method, $params);
    }

}