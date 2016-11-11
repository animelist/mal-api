<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MalApi\Entity;

use DateTime;
use InvalidArgumentException;

class Anime {

    /**
     *
     * @var string title
     */
    protected $title;

    /**
     *
     * @var string in html format
     */
    protected $synopsis;

    /**
     *
     * @var string english title
     */
    protected $english;

    /**
     *
     * @var string in html format
     */
    protected $synonyms;

    /**
     *
     * @var string
     */
    protected $imgSrc;

    /**
     *
     * @var int
     */
    protected $externalId;

    /**
     *
     * @var string
     */
    protected $picturesLink;

    /**
     *
     * @var int one of TYPE_XXX constant
     */
    protected $type;

    /**
     *
     * @var int
     */
    protected $episodes;

    /**
     *
     * @var int one of STATUS_XXX constant
     */
    protected $status;

    /**
     *
     * @var array array of string - img uri/url
     */
    protected $photos = [];

    /**
     *
     * @var DateTime
     */
    protected $startDate;

    /**
     *
     * @var DateTime
     */
    protected $endDate;

    const TYPE_UNKNOWN = 0;
    const TYPE_TV = 1;
    const TYPE_OVA = 2;
    const TYPE_ONA = 3;
    const TYPE_MOVIE = 4;
    const TYPE_SPECIAL = 5;
    const STATUS_FINISHED_AIRING = 1;
    const STATUS_AIRING = 2;
    const STATUS_NOT_YET_AIRED = 3;

    /**
     * Get synopsis
     * @return string synopsis, contains html tags
     */
    public function getSynopsis() {
        return $this->synopsis;
    }

    /**
     * Set synopsis
     * @param string $synopsis
     * @return \static
     */
    public function setSynopsis($synopsis) {
        $this->synopsis = $synopsis;
        return $this;
    }

    /**
     * Get title
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set title
     * @param string $title
     * @return \static
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * Get img src
     * @return string
     */
    public function getImgSrc() {
        return $this->imgSrc;
    }

    /**
     * Set img src
     * @param string $img_src
     * @return \static
     */
    public function setImgSrc($img_src) {
        $this->imgSrc = $img_src;
        return $this;
    }

    /**
     * Get external id
     * @return string
     */
    public function getExternalId() {
        return $this->externalId;
    }

    /**
     * Set external id
     * @param string $external_id
     * @return \static
     */
    public function setExternalId($external_id) {
        $this->externalId = $external_id;
        return $this;
    }

    /**
     * Get pictures link
     * @return string
     */
    public function getPicturesLink() {
        return $this->picturesLink;
    }

    /**
     * Set pictures link
     * @param string $pictures_link
     * @return \static
     */
    public function setPicturesLink($pictures_link) {
        $this->picturesLink = $pictures_link;
        return $this;
    }

    /**
     * Get type
     * @return int
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set type
     * @param int $type
     * @return \static
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * Get episodes
     * @return int 0 = unknown
     */
    public function getEpisodes() {
        return $this->episodes;
    }

    /**
     * Set episodes
     * @param int $episodes
     * @return \static
     */
    public function setEpisodes($episodes) {
        $this->episodes = $episodes;
        return $this;
    }

    /**
     * Get photos
     * @return array
     */
    public function getPhotos() {
        return $this->photos;
    }

    /**
     * Set photos
     * @param array $photos
     * @return \static
     */
    public function setPhotos($photos) {
        $this->photos = $photos;

        return $this;
    }

    /**
     * Get english title
     * @return string
     */
    public function getEnglish() {
        return $this->english;
    }

    /**
     * Set english title
     * @param string $english
     * @return \static
     */
    public function setEnglish($english) {
        $this->english = $english;
        return $this;
    }

    /**
     * Get synonyms
     * @return string
     */
    public function getSynonyms() {
        return $this->synonyms;
    }

    /**
     * Set synonyms
     * @param string $synonyms
     * @return \static
     */
    public function setSynonyms($synonyms) {
        $this->synonyms = $synonyms;
        return $this;
    }

    /**
     * Get status
     * @return int
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Set status
     * @param int $status
     * @return \static
     */
    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }

    /**
     * Get start date
     * @return type
     */
    public function getStartDate() {
        return $this->startDate;
    }

    /**
     * Set start date
     * @param DateTime $start_date
     * @throws InvalidArgumentException
     */
    public function setStartDate($start_date) {
        if ($start_date !== null && !$start_date instanceof DateTime) {
            throw new InvalidArgumentException('wrong date');
        }
        $this->startDate = $start_date;
    }

    /**
     * Get end date
     * @return type
     */
    public function getEndDate() {
        return $this->endDate;
    }

    /**
     * Set end date
     * @param DateTime $end_date
     * @throws InvalidArgumentException
     */
    public function setEndDate($end_date) {
        if ($end_date !== null && !$end_date instanceof DateTime) {
            throw new InvalidArgumentException('wrong date');
        }
        $this->endDate = $end_date;
    }

}
