<?php
namespace Kafoso\Questful\Model\Bridge;

use Kafoso\Questful\Model\Mapping;

interface AbstractBridgeInterface
{
    const EXCEPTION_CODE = 5;

    /**
     * @param $mapping object \Kafoso\Questful\Model\Mapping
     */
    public function __construct(Mapping $mapping);

    /**
     * @throws \Kafoso\Questful\Exception\RuntimeException
     * @return object $this
     */
    public function generate();

    /**
     * @return array
     */
    public function toArray();

    /**
     * @return object \Kafoso\Questful\Model\Mapping
     */
    public function getMapping();
}
