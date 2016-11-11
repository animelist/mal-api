<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MalApi\Service;

use Exception;

class MalRouter {

    /**
     *
     * @return string
     */
    public function getSite() {
        return 'https://myanimelist.net';
    }

    /**
     * @return string
     */
    public function getApiAnimeSearchUrl() {
        return $this->getSite() . '/api/anime/search.xml';
    }

    /**
     *
     * @param string $action add/update/delete
     * @param int $anime_id
     * @return string
     * @throws Exception
     */
    public function getApiAnimeUrl($action, $anime_id) {
        if (!in_array($action, ['add', 'update', 'delete'], true)) {
            throw new Exception('unknown action');
        }
        if (!is_int($anime_id) || $anime_id < 1) {
            throw new Exception('wrong anime id');
        }
        return $this->getSite() . '/api/animelist/' . $action . '/' . $anime_id . '.xml';
    }

}
