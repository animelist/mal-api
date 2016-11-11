<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MalApi\Service;

use Pimple\Container;
use phpQueryObject;
use phpQuery;
use LogicException;
use MalApi\ParserTask\AbstractTask;
use MalApi\Exceptions\RuleException;
use MalApi\Exceptions\ConfigureError;
use MalApi\Exceptions\ParseError;

class Parser {

    /**
     *
     * @var phpQueryObject
     */
    protected $doc;

    /**
     *
     * @var Container
     */
    protected $container;

    /**
     * Current task
     * @var AbstractTask
     */
    protected $task;

    /**
     *
     * @param Container|null $container
     */
    public function __construct($container = null) {
        $this->container = $container;
    }

    /**
     * Load a html document
     * If a document has been previously loaded, the new document overwrites the old one.
     * @param string $html
     * @throws RuntimeException
     * @throws RuleException
     */
    protected function loadHtml($html) {
        unset($this->doc);
        $this->doc = phpQuery::newDocumentHTML($html);
    }

    /**
     *
     * @param array $rule
     * @throws RuleException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function useIgnoreRule($rule) {
        $rule_count = $this->getRequiredValue($rule, 'count', 1);
        $css = $this->getRequiredValue($rule, 'css', 0);

        $result = $this->doc->find($css);

        $count = $result->count();

        $can_none = in_array($rule_count, ['0', '?', '*', '++|0', '0|++']);
        $can_one = in_array($rule_count, ['1', '+', '?', '*']);
        $can_many = in_array($rule_count, ['++', '++|0', '0|++', '+', '*']);

        if ($count == 0 && !$can_none) {

            throw new RuleException('item(s) not found');
        }

        if ($count == 1 && !$can_one) {
            throw new RuleException('only one item found');
        }

        if ($count > 1 && !$can_many) {
            throw new RuleException('too many items found');
        }

        $result->remove();
    }

    /**
     *
     * @param array $array
     * @param string $value_name
     * @param int|null $index [null]
     * @param boolean $required [false]
     * @param mixed $default_value [null]
     * @return void
     * @throws ConfigureError
     */
    protected function getValue($array, $value_name, $index = null, $required = false, $default_value = null) {
        if (!is_null($index) && array_key_exists($index, $array)) {
            return $array[$index];
        }

        if (array_key_exists($value_name, $array)) {
            return $array[$value_name];
        }

        if (!$required) {
            return $default_value;
        }

        throw new ConfigureError(sprintf('required parameter "%s" not specified', $value_name));
    }

    protected function getRequiredValue($array, $value_name, $index = null) {
        return $this->getValue($array, $value_name, $index, true);
    }

    protected function getNonRequiredValue($array, $value_name, $index = null, $default_value = null) {
        return $this->getValue($array, $value_name, $index, false, $default_value);
    }

    /**
     *
     * @param string $type
     * @return boolean
     */
    protected function isMulti($type) {
        return preg_match('~\_collection$~', $type) === 1;
    }

    /**
     *
     * @param phpQueryObject $node
     */
    protected function getValueFromDOM($node, $type) {
        switch ($type) {
            case 'img':
                return $node->attr('src');
            case 'html':
                return $node->html();
            case 'string':
                return $node->text();
            case 'val':
                return $node->val();
            case 'link':case 'href':
                return $node->attr('href');
            default:
                throw new ConfigureError('unknown type "' . $type . '"');
        }
    }

    /**
     *
     * @param phpQueryObject $node
     * @param string $field
     * @param string $type
     * @param boolean $is_virtual
     * @return mixed
     */
    protected function processValue($node, $field, $type, $is_virtual) {
        $field_uc = ucfirst($field);

        $transform_method = 'transform' . $field_uc . 'ToModel';

        if (method_exists($this->task, $transform_method)) {

            if ($is_virtual) {
                $this->task->$transform_method($node, $type);
                return null;
            } else {
                $value = $this->task->$transform_method($node, $type);
            }
        } else {
            if ($is_virtual) {
                throw new LogicException('method "' . $transform_method . '" required, because it is a virtual field');
            }
            $value = $this->getValueFromDOM($node, $type);
        }


        $validate_method = 'validate' . $field_uc;

        if (method_exists($this->task, $validate_method)) {

            $value = $this->task->$validate_method($value);
        }
        return $value;
    }

    /**
     *
     * @param object $entity
     * @param string $field
     * @param mixed $value
     * @param boolean $is_virtual
     */
    protected function setEntityField($entity, $field, $value, $is_virtual) {
        if ($is_virtual) {
            return;
        }
        $method = 'set' . lcfirst($field);
        $entity->$method($value);
    }

    /**
     *
     * @param array $rule
     * @param object $entity
     * @throws ParseError
     */
    protected function useParseRule($rule, $entity) {
        $field = $this->getRequiredValue($rule, 'field', 0);

        $type = $this->getRequiredValue($rule, 'type', 2);
        $required = (bool) $this->getNonRequiredValue($rule, 'required', 3, true);

        $multi = $this->isMulti($type);

        $is_virtual = in_array($field, $this->task->getVirtualFields());

        /**
         * @TODO fix: check return value
         */
        $pure_type = preg_replace('~\_[a-z]+$~', '', $type);

        $result = [];

        $css = $this->getRequiredValue($rule, 'css', 1);
        $r = $this->doc->find($css);

        if (!$multi && $r->length != 1) {
            if (!$required && $r->length < 1) {
                return;
            }

            throw new ParseError(sprintf('Parsing error: for selector "%s" was founded %s item(s), %s expected', $css, $r->length, 1));
        }

        foreach ($r as $node) {
            $result[] = $this->processValue(pq($node), $field, $pure_type, $is_virtual);
        }

        $this->setEntityField($entity, $field, $multi ? $result : $result[0], $is_virtual);
    }

    /**
     *
     * @param array $parameters
     * @return string
     * @throws ConfigureError
     */
    protected function obtainHtml($parameters) {
        $url = $this->getNonRequiredValue($parameters, 'url');
        $html = $this->getNonRequiredValue($parameters, 'html');
        if (empty($url) && empty($html)) {
            throw new ConfigureError('html or url must be specified');
        }
        if (empty($html)) {
            $html = $this->container['mal.net']->get($url);
        } elseif (!empty($url)) {
            throw new ConfigureError('both parameters are specified: html and url');
        }
        return $html;
    }

    /**
     * run a task
     * @param AbstractTask $task
     * @param array $parameters
     * <ul>
     * <li>url - url of anime(required if html is not specified)</li>
     * <li>html - html code of anime page(required if url is not specified)</li>
     * </ul>
     * @throws \Exception
     */
    public function execute(AbstractTask $task, $parameters = array()) {

        $this->task = $task;

        $this->loadHtml($this->obtainHtml($parameters));

        $ignore_rules = $task->getIgnoreRules();

        foreach ($ignore_rules as $rule) {
            $this->useIgnoreRule($rule);
        }

        $class = $task->getClassName();

        $entity = new $class;

        $task->setEntity($entity);

        $rules = $task->getParseRules();

        foreach ($rules as $rule) {
            $this->useParseRule($rule, $entity);
        }
        return $entity;
    }

}
