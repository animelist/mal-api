<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MalApi\Service;

class Config {

    protected $config = array('user' => null, 'password' => null);

    /**
     * set user and password for animelist
     * @param string $user
     * @param string $password
     */
    public function setAuthInfo($user, $password) {
        $this->config['user'] = (string) $user;
        $this->config['password'] = (string) $password;
    }

    /**
     * @return string
     */
    public function getUser() {
        return $this->config['user'];
    }

    /**
     * @return string
     */
    public function getPassword() {
        return $this->config['password'];
    }

}
