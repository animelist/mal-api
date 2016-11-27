<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MalApi;

use InvalidArgumentException;
use RuntimeException;
use DateTime;
use Pimple\Container;
use MalApi\Entity\Anime;
use SpecParser\Exceptions\ConfigureError;
use MalApi\ParserTask\Anime as AnimeTask;
use MalApi\ParserTask\AnimePhotos as AnimePhotosTask;
use MalApi\Service\Config;
use MalApi\Service\MalRouter;
use NetHelper\Service\Net;
use SpecParser\Service\Parser;
use MalApi\Service\Xml;

class Api {

    const STATUS_WATCHING = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_ON_HOLD = 3;
    const STATUS_DROPPED = 4;
    const STATUS_PLAN_TO_WATCH = 6;

    /**
     *
     * @var Container
     */
    protected $container;

    protected function initTasks() {
        $this->container['mal.task.anime'] = function() {
            return new AnimeTask;
        };

        $this->container['mal.task.anime_photos'] = function() {
            return new AnimePhotosTask;
        };
    }

    public function __construct() {
        $this->container = new Container;
        $this->container['mal.config'] = function() {
            return new Config;
        };

        $this->container['nxnx.net-helper'] = function () {
            $net = new Net();
            $net->setUserAgent('MAL-api/1');
            return $net;
        };

        $this->container['mal.xml'] = function() {
            return new Xml;
        };

        $this->container['nxnx.spec-parser'] = function($c) {
            return new Parser($c);
        };

        $this->container['mal.api'] = function($c) {
            return new Api($c);
        };

        $this->container['mal.router'] = function() {
            return new MalRouter();
        };

        $this->initTasks();
    }

    /**
     * Set login and password for access to official api functions
     * @param type $login
     * @param type $password
     */
    public function setAuth($login, $password) {
        $this->container['mal.config']->setAuthInfo($login, $password);
    }

    /**
     * Anime parsing.
     * Side effect - open url with photos and parse it.
     * @param string $url
     * @return Anime
     */
    public function getAnime($url) {

        $anime = $this->container['nxnx.spec-parser']->execute($this->container['mal.task.anime'], ['url' => $url]);
        $anime_photos = $this->container['nxnx.spec-parser']->execute($this->container['mal.task.anime_photos'], ['url' => $anime->getPicturesLink()]);
        $anime->setPhotos($anime_photos->getPhotos());
        return $anime;
    }

    /**
     * Post request.
     * @param string $url url
     * @param array|null $data request data
     * @param string|null $xml_field <p>Field name, which will be transformed to xml format</p>
     * <p>$data[$xml_field] will be transformed to xml</p>
     * @return string
     * @throws ConfigureError
     */
    protected function postRequest($url, $data = null, $xml_field = null) {

        $user = $this->container['mal.config']->getUser();

        if (empty($user)) {
            throw new ConfigureError('login required');
        }

        if (!is_null($data) && !is_null($xml_field) && isset($data[$xml_field])) {
            $data[$xml_field] = $this->container['mal.xml']->convertToXml($data[$xml_field]);
        }

        $auth = [
            'user' => $this->container['mal.config']->getUser(),
            'password' => $this->container['mal.config']->getPassword()
        ];

        return $this->container['nxnx.net-helper']->post($url, $data, $auth);
    }

    /**
     * Search anime
     * @param string $q search query
     * @TODO fix return type
     * @TODO add support unofficial method
     * @TODO write function
     */
    public function search($q) {
        throw new Exception('not released');
        $url = $this->container['mal.router']->getApiAnimeSearchUrl();
        $r = $this->postRequest($url, ['q' => $q]);
        $assoc = $this->container['mal.xml']->convertFromXml($r);
        return $assoc;
    }

    /**
     *
     * @param DateTime $object
     * @return string
     * @throws RuntimeException
     */
    protected function getDateString(DateTime $object) {
        $str = $object->format('mdY');
        if ($str === false) {
            throw new RuntimeException('DateTime::format() failed');
        }
        return $str;
    }

    /**
     *
     * @param string $string
     * @return DateTime
     */
    protected function createDTFromString($string) {
        $object = DateTime::createFromFormat('mdY', $string);
        if ($object === false) {
            throw new RuntimeException('DateTime::createFromFormat() failed');
        }
        return $object;
    }

    protected function checkStatus($status) {
        if (!in_array($status, [
                    static::STATUS_WATCHING,
                    static::STATUS_COMPLETED,
                    static::STATUS_ON_HOLD,
                    static::STATUS_DROPPED,
                    static::STATUS_PLAN_TO_WATCH
                ])) {
            throw new RuntimeException('wrong status');
        }
    }

    /**
     *
     * @param DateTime|string $date
     * @return string
     * @throws RuntimeException
     */
    protected function getDateForApi($date) {
        if ($date instanceof DateTime) {
            return $this->getDateString($date);
        } elseif (is_string($date)) {
            return $this->getDateString(
                            $this->createDTFromString($date)
            ); //for validate and saintize
        } else {
            throw new RuntimeException('unknown date format');
        }
    }

    /**
     *
     * @param array $from
     * @param array $to
     * @param string $field
     * @param int $min
     * @param int|null $max [null]
     * @return void
     * @throws RuntimeException
     */
    protected function validateInt($from, &$to, $field, $min = 0, $max = null) {
        if (!isset($from[$field])) {
            return;
        }
        $value = intval($from[$field]);

        if ($value < $min) {
            throw new RuntimeException(sprintf('value of "%s" less than %d', $field, $min));
        }

        if (!is_null($max) && $value > $max) {
            throw new RuntimeException(sprintf('value of "%s" greater than %d', $field, $max));
        }
        $to[$field] = $value;
    }

    /**
     * Validate and saintize data
     * @param array $info
     * @param string $type add/update/delete
     * @throws RuntimeException
     */
    protected function validateInfo(array &$info, $type) {

        if ($type == 'delete') {
            return;
        }

        $result = [];
        if (isset($info['status'])) {
            $result['status'] = intval($info['status']);
            $this->checkStatus($result['status']);
        }

        $this->validateInt($info, $result, 'episode', 0);
        $this->validateInt($info, $result, 'score', 1, 10);

        foreach (['date_start', 'date_finish'] as $m) {
            if (isset($info[$m])) {
                $result[$m] = $this->getDateForApi($info[$m]);
            }
        }

        unset($info['status'], $info['episode'], $info['score'], $info['date_start'], $info['date_finish']);
        if (count($info)) {
            throw new RuntimeException('Parameter(s): "' . implode('", "', array_keys($info)) . '" is undefined');
        }
        $info = $result;
    }

    /**
     * @see update
     * @param array $info
     * @param string $type add/update/delete
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function edit($info, $type = 'update') {
        $anime_id = $info['id'];
        unset($info['id']);

        if (!is_numeric($anime_id) || empty($anime_id)) {
            throw new InvalidArgumentException('wrong id');
        }

        $actions = ['add' => 'Created', 'update' => 'Updated', 'delete' => 'Deleted'];

        if (!in_array($type, array_keys($actions), true)) {
            throw new InvalidArgumentException('wrong type');
        }

        $this->validateInfo($info, $type);

        $url = $this->container['mal.router']->getApiAnimeUrl($type, $anime_id);

        if ($type == 'delete') {
            $result = $this->postRequest($url);
        } else {
            $result = $this->postRequest($url, ['data' => ['entry' => $info]], 'data');
        }

        if ($result != $actions[$type]) {
            throw new RuntimeException('Action "' . $type . '" failed: ' . $result);
        }
    }

    /**
     * Anime update
     * @param array $info
     * <ul>
     * <li>id - anime id</li>
     * <li>episode - int</li>
     * <li>status - int one of STATUS_XXX constant</li>
     * <li>score - int 1-10</li>
     * <li>date_start - DateTime object</li>
     * <li>date_finish - DateTime object</li>
     * </ul>
     */
    public function update($info) {

        $this->edit($info, 'update');
    }

    /**
     * Adding anime
     * @param array $info
     * <ul>
     * <li>id - anime id</li>
     * <li>episode - int</li>
     * <li>status - int one of STATUS_XXX constant</li>
     * <li>score - int 1-10</li>
     * <li>date_start - DateTime object</li>
     * <li>date_finish - DateTime object</li>
     * </ul>
     */
    public function add($info) {

        $this->edit($info, 'add');
    }

    /**
     * Removing anime
     * @param int $id anime id
     */
    public function delete($id) {
        $this->edit(['id' => $id], 'delete');
    }

}
