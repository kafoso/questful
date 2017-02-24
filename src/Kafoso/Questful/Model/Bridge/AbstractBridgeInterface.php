<?php
namespace Kafoso\Questful\Model\Bridge;

use Kafoso\Questful\Model\Mapping;

interface AbstractBridgeInterface
{
    const DEFAULT_ENCODING = "UTF-8";
    const EXCEPTION_CODE = 5;

    /**
     * @param $mapping object \Kafoso\Questful\Model\Mapping
     */
    public function __construct(Mapping $mapping, $encoding = null);

    /**
     * @throws \Kafoso\Questful\Exception\RuntimeException
     * @return object $this
     */
    public function generate();

    /**
     * Allows overriding of encoding. Default encoding is "UTF-8".
     * @param $encoding string
     * @throws Kafoso\Questful\Exception\InvalidArgumentException
     * @return object $this
     */
    public function setEncoding($encoding);

    /**
     * @return array
     */
    public function toArray();

    /**
     * @return string
     */
    public function getEncoding();

    /**
     * @return object \Kafoso\Questful\Model\Mapping
     */
    public function getMapping();
}
