<?php
namespace Kafoso\Questful\Model\Bridge;

use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\InvalidArgumentException;
use Kafoso\Questful\Model\Mapping;

abstract class AbstractBridge implements AbstractBridgeInterface
{
    private $_mapping;
    private $_encoding = "UTF-8";

    public function __construct(Mapping $mapping, $encoding = null)
    {
        $this->_mapping = $mapping;
        if (null === $encoding) {
            $this->_encoding = self::DEFAULT_ENCODING;
        } else {
            $this->setEncoding($encoding);
        }
    }

    public function setEncoding($encoding)
    {
        if (false == is_string($encoding)) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be a string. Found: %s",
                '$encoding',
                FormattingHelper::found($encoding)
            ));
        }
        $this->_encoding = $encoding;
        return $this;
    }

    public function getEncoding()
    {
        return $this->_encoding;
    }

    public function getMapping()
    {
        return $this->_mapping;
    }

    protected function arrayUniqueStrict(array $array)
    {
        $uniqueArray = [];
        foreach ($array as $k => $v) {
            $isUnique = true;
            foreach ($uniqueArray as $b) {
                if ($b === $v) {
                    $isUnique = false;
                }
            }
            if ($isUnique) {
                $uniqueArray[$k] = $v;
            }
        }
        return $uniqueArray;
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
