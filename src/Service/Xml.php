<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MalApi\Service;

use XMLWriter;
//use simplexml
use RuntimeException;

class Xml {

    /**
     *
     * @var XMLWriter
     */
    protected $xw;

    protected function init() {
        unset($this->xw);
        $this->xw = new XMLWriter;
        if (!$this->xw->openMemory()) {
            throw new RuntimeException('openMemory() failed');
        }

        if (!$this->xw->startDocument('1.0', 'utf-8')) {
            throw new RuntimeException('startDocument() failed');
        }
    }

    protected function wr($k, $v) {
        if (!$this->xw->writeElement($k, $v)) {
            throw new RuntimeException('writeElement() failed');
        }
    }

    protected function st($k) {
        if (!$this->xw->startElement($k)) {
            throw new RuntimeException('startElement() failed');
        }
    }

    protected function sp() {
        if (!$this->xw->endElement()) {
            throw new RuntimeException('endElement() failed');
        }
    }

    /**
     *
     * @param array $data
     */
    protected function writer($data) {
        foreach ($data as $k => $v) {
            if (is_scalar($v)) {
                $this->wr($k, $v);
            } else {
                $this->st($k);
                $this->writer($v);
                $this->sp();
            }
        }
    }

    /**
     * Transform xml to an assoc. array
     * @param string $xml_string
     * @return array
     * @throws RuntimeException
     */
    public function convertFromXml($xml_string) {
        if (empty($xml_string)) {
            throw new RuntimeException('$xml_string is empty');
        }
        $xml = simplexml_load_string($xml_string);

        return json_decode(json_encode($xml), true);
    }

    /**
     * Transform assoc. array to a xml
     * @param array $data source data
     * @return string
     * @throws RuntimeException
     */
    public function convertToXml($data) {
        $this->init();
        $this->writer($data);
        if (!$this->xw->endDocument()) {
            throw new RuntimeException('endDocument() failed');
        }
        return $this->xw->outputMemory();
    }

}
