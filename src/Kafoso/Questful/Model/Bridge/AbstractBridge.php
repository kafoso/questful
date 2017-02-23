<?php
namespace Kafoso\Questful\Model\Bridge;

use Kafoso\Questful\Model\Mapping;

abstract class AbstractBridge implements AbstractBridgeInterface
{
    protected $mapping;

    public function __construct(Mapping $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getMapping()
    {
        return $this->mapping;
    }

    protected function trimAndWrapInParentheses($str)
    {
        $str = trim($str);
        while ($str && preg_match('/^\((.*)\)$/', $str, $match)) {
            $str = trim($match[1]);
        }
        return "({$str})";
    }
}
