<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MalApi\Service;

use RuntimeException as Exception;

class Net {

    /**
     * @var Resource $ch
     */
    protected $ch;

    protected function setCurlDefaults() {
        $options = array(
            /**
             * @TODO show html body for 4xx HTTP codes
             */
            CURLOPT_FAILONERROR => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_SAFE_UPLOAD => true,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'MAL-api/0.0.1'
        );
        $result = curl_setopt_array($this->ch, $options);
        if (!$result) {
            throw new Exception('curl_setopt_array() failed');
        }
    }

    protected function setCurlOption($option, $value) {
        if (!curl_setopt($this->ch, $option, $value)) {
            throw new Exception('curl_setopt() failed');
        }
    }

    protected function setUrl($url) {
        if (empty($url)) {
            throw new Exception('empty url is not allowed');
        }
        $this->setCurlOption(CURLOPT_URL, $url);
    }

    /**
     *
     * @throws Exception
     */
    public function __construct() {
        $this->ch = curl_init();
        if ($this->ch === false) {
            throw new Exception('curl_init() failed');
        }
        $this->setCurlDefaults();
    }

    /**
     *
     * @return string
     * @throws Exception
     */
    protected function curlExec() {
        $data = curl_exec($this->ch);
        if ($data === false) {

            $http_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
            throw new Exception('curl_exec() failed, HTTP code: ' . $http_code);
        }
        return $data;
    }

    protected function setNoAuth() {
        $this->setCurlOption(CURLOPT_HTTPAUTH, CURLAUTH_NONE);
        $this->setCurlOption(CURLOPT_USERPWD, null);
    }

    protected function requireAuth($user, $password) {

        $this->setCurlOption(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $this->setCurlOption(CURLOPT_USERPWD, $user . ':' . $password);
    }

    /**
     * HTTP GET
     * @param string $url
     * @return string
     */
    public function get($url) {


        $this->setUrl($url);
        $this->setCurlOption(CURLOPT_HTTPGET, true);
        $this->setNoAuth();

        $data = $this->curlExec();

        return $data;
    }

    /**
     * HTTP POST
     * @param string $url
     * @param array $data
     * @param array|null $auth [null] array with indexes user and password
     * @return string
     */
    public function post($url, $data = null, $auth = null) {
        $this->setUrl($url);
        $this->setCurlOption(CURLOPT_POST, true);
        if (!is_null($data)) {
            $this->setCurlOption(CURLOPT_POSTFIELDS, http_build_query($data));
        }

        if (!is_null($auth)) {
            $this->requireAuth($auth['user'], $auth['password']);
        } else {
            $this->setNoAuth();
        }

        return $this->curlExec();
    }

}
