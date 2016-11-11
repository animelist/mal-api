<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MalApi\ParserTask;

use Exception;
use MalApi\Exceptions\ParseError;

abstract class AbstractTask {

    /**
     *
     * @var object
     */
    protected $entity;

    /**
     * Returns a list of ignore rules
     * @return array an array of arrays. Each array consists of:
     * <ul>
     * <li>css(or index 0) - css selector for search</li>
     * <li>count(or index 1) - the expected number. Can be one of:
     * <ul>
     * <li>0 - no items</li>
     * <li>1 - 1 item</li>
     * <li>++ - at least 2 items</li>
     * <li>++|0 - at least 2 items or 0</li>
     * <li>+ - at least 1 item</li>
     * <li>? - Maximum 1 item</li>
     * <li>* - any number of elements, including their lack of</li>
     * </ul>
     * </li>
     * </ul>
     */
    public function getIgnoreRules() {
        return [];
    }

    /**
     * @access private
     */
    public function setEntity($entity) {
        $this->entity = $entity;
    }

    /**
     *
     * @param string $value
     * @param string $pattern
     * @param array $m out matches
     * @throws Exception
     * @throws ParseError
     */
    protected function checkRegExp($value, $pattern, &$m = null) {
        $r = preg_match($pattern, $value, $m);
        if ($r === false) {
            throw new Exception('preg_match() failed');
        }
        if (!$r) {
            throw new ParseError(sprintf('The value must match the pattern "%s"', $pattern));
        }
    }

    /**
     * Gets a list of "virtual" fields.
     * Virtual fields is a non-exists field of an entity
     * @return array an array of strings
     */
    public function getVirtualFields() {
        return [];
    }

    /**
     * Get parsing rules
     * @return array an array of array:
     * <ul>
     * <li>field(or index 0) - entity(or "virtual") field</li>
     * <li>css(or index 1) css selector</li>
     * <li>type(or index 2) value type:
     * <ul>
     * <li>string - strip html tags</li>
     * <li>html - html content</li>
     * <li>img - "src" attribute</li>
     * <li>val - "value", for example used for &lt;input type="hidden"&gt;
     * <li>img_collection - collection of "src" attributes</li>
     * </ul>
     * </li>
     * </ul>
     */
    abstract function getParseRules();

    /**
     * get object class name
     * @return string class name
     */
    abstract public function getClassName();
}
