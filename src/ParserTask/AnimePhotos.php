<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MalApi\ParserTask;

use MalApi\Entity\Anime as EntityAnimePhotos;
use SpecParser\ParserTask\AbstractTask;

class AnimePhotos extends AbstractTask {

    /**
     *
     * @var EntityAnimePhotos
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
                ['#ad-skin-bg-right', '1']
        ];
    }

    /**
     *
     * {@inheritDoc}
     */
    public function getClassName() {
        return EntityAnimePhotos::class;
    }

    /**
     *
     * {@inheritDoc}
     */
    public function getParseRules() {

        return [
                ['photos', '#content .picSurround > a', 'href_collection'],
        ];
    }

}
