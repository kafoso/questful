<?php
namespace Kafoso\Questful\Model\Mapping\Allowable;

use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\InvalidArgumentException;
use Kafoso\Questful\Exception\UnexpectedValueException;
use Kafoso\Questful\Model\Mapping\AllowableInterface;

class AllowedSort implements AllowableInterface
{
    private $key;

    /**
     * @param $key
     * @throws \Kafoso\Questful\Exception\InvalidArgumentException
     * @throws \Kafoso\Questful\Exception\UnexpectedValueException
     */
    public function __construct($key)
    {
        if (false == is_string($key)) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be a string. Found: %s",
                '$key',
                FormattingHelper::found($key)
            ));
        }
        if ("" === $key) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to not be an empty string. Found: %s",
                '$key',
                FormattingHelper::found($key)
            ));
        }
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
}
