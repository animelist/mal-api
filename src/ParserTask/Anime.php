<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MalApi\ParserTask;

use MalApi\Entity\Anime as EntityAnime;
use phpQueryObject;
use SpecParser\Exceptions\ParseError;
use DateTime;
use SpecParser\ParserTask\AbstractTask;

class Anime extends AbstractTask {

    /**
     *
     * @var EntityAnime
     */
    protected $entity;

    /**
     *
     * {@inheritDoc}
     */
    public function getIgnoreRules() {
        return [
                ['script', '*'],
                ['#myanimelist ._unit', '1'],
                ['#myanimelist #ad-skin-bg-left', '1'],
            /**
             * @TODO why "#myanimelist #ad-skin-bg-right" not works?
             */
                ['#ad-skin-bg-right', '1']
        ];
    }

    /**
     *
     * {@inheritDoc}
     */
    public function getClassName() {
        return EntityAnime::class;
    }

    /**
     *
     * @param phpQueryObject $node
     */
    public function transformTypeToModel($node) {
        $text = $this->removeThisAndGetParentText($node);

        switch ($text) {
            case 'TV':
                return EntityAnime::TYPE_TV;
            case 'OVA':
                return EntityAnime::TYPE_OVA;
            case 'ONA':
                return EntityAnime::TYPE_ONA;
            case 'Movie':
                return EntityAnime::TYPE_MOVIE;
            case 'Special':
                return EntityAnime::TYPE_SPECIAL;
            case 'Unknown':
                return EntityAnime::TYPE_UNKNOWN;
        }

        throw new ParseError('wrong anime type');
    }

    /**
     *
     * @param phpQueryObject $node
     */
    public function transformStatusToModel($node) {
        $text = $this->removeThisAndGetParentText($node);

        switch ($text) {
            case 'Finished Airing':
                return EntityAnime::STATUS_FINISHED_AIRING;
            case 'Currently Airing':
                return EntityAnime::STATUS_AIRING;
            case 'Not yet aired':
                return EntityAnime::STATUS_NOT_YET_AIRED;
        }

        throw new ParseError('wrong anime status');
    }

    /**
     *
     * @param phpQueryObject $node
     * @param boolean $asHTML [false]
     */
    protected function removeThisAndGetParentText($node, $asHTML = false) {
        $parent = $node->parent();
        $node->remove();
        $r = $asHTML ? $parent->html() : $parent->text();
        return trim($r);
    }

    /**
     *
     * @param phpQueryObject $node
     */
    public function transformEpisodesToModel($node) {
        return $this->removeThisAndGetParentText($node);
    }

    /**
     *
     * @param phpQueryObject $node
     */
    public function transformEnglishToModel($node) {

        return $this->removeThisAndGetParentText($node);
    }

    /**
     *
     * @param string $str
     * @return DateTime|null
     */
    protected function strToDate($str) {
        if ($str == '?') {
            return null;
        }
        $m = array();

        $this->checkRegExp($str, '~^(([a-zA-Z]+)([ ]{1,}([0-9]+)){0,1}\,[ ]{1,}){0,1}([0-9]{4,4})$~', $m);

        $month = $m[2];
        $day = $m[4];
        if (empty($day)) {
            $day = '1';
        }
        if (empty($month)) {
            $month = 'Jan';
        }
        $year = $m[5];

        $f = DateTime::createFromFormat('M j, Y', $month . ' ' . $day . ', ' . $year);

        if ($f === false) {
            throw new ParseError('failed to create "DateTime" object');
        }
        return $f;
    }

    /**
     *
     * @param phpQueryObject $node
     */
    public function transformDateToModel($node) {

        $text = $this->removeThisAndGetParentText($node);
        if (empty($text) || $text == '?' || $text == 'Not available') {
            $values = ['?', '?'];
        } else {
            $values = explode(' to ', $text);
            if (count($values) == 1) {
                $values[1] = '?';
            } elseif (count($values) != 2) {

                throw new ParseError('wrong date format');
            }
        }
        $this->entity->setStartDate($this->strToDate(trim($values[0])));
        $this->entity->setEndDate($this->strToDate(trim($values[1])));
    }

    /**
     *
     * @param phpQueryObject $node
     */
    public function transformSynonymsToModel($node) {

        return $this->removeThisAndGetParentText($node, true);
    }

    public function validateEpisodes($value) {
        if ($value == 'Unknown') {
            return 0;
        }
        $this->validateExternalId($value);
        $int_value = intval($value);
        if ($int_value < 1 || $int_value > 999) {
            throw new ParseError('Episodes count is wrong: ' . $value);
        }
        return $int_value;
    }

    public function validateExternalId($value) {
        $this->checkRegExp($value, '~[0-9]{1}[0-9]{0,}~');
        return $value;
    }

    /**
     *  {@inheritDoc}
     */
    public function getVirtualFields() {
        return ['date'];
    }

    /**
     *
     * {@inheritDoc}
     */
    public function getParseRules() {

        return [
                ['title', '#contentWrapper div h1', 'string', true/* true = required this is default value */],
                ['synopsis', 'span[itemprop="description"]', 'html', true],
                ['imgSrc', '#content > table tr:first-of-type > td:first-of-type img.ac', 'img', true],
                ['externalId', '#addtolist #myinfo_anime_id', 'val', true],
                ['picturesLink', '#horiznav_nav ul li a:contains("Pictures")', 'link', true],
                ['type', '#content > table tr:first-of-type > td:first-of-type h2:contains("Information") ~ div span.dark_text:contains("Type:")', 'custom', true],
                ['episodes', '#content > table tr:first-of-type > td:first-of-type h2:contains("Information") ~ div > span.dark_text:contains("Episodes:")', 'custom', true],
                ['english', '#content h2:contains("Alternative Titles") ~ .spaceit_pad > .dark_text:contains("English:")', 'custom', false],
                ['synonyms', '#content h2:contains("Alternative Titles") ~ .spaceit_pad > .dark_text:contains("Synonyms:")', 'custom', false],
                ['status', '#content > table tr:first-of-type > td:first-of-type h2:contains("Information") ~ div > span.dark_text:contains("Status:")', 'custom', true],
                ['date', '#content > table tr:first-of-type > td:first-of-type h2:contains("Information") ~ div > span.dark_text:contains("Aired:")', 'custom', true],
        ];
    }

}
