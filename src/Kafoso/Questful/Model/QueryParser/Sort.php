<?php
namespace Kafoso\Questful\Model\QueryParser;

use Kafoso\Questful\Exception\InvalidArgumentException;
use Kafoso\Questful\Exception\FormattingHelper;

class Sort implements SortInterface
{
    protected $key;
    protected $isAscending;

    /**
     * @param $key string
     * @param $isAscending boolean
     * @throws \Kafoso\Questful\Exception\InvalidArgumentException
     */
    public function __construct($key, $isAscending)
    {
        if (false == is_string($key)) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be a string. Found: %s",
                '$key',
                FormattingHelper::found($key)
            ));
        }
        if (false == is_bool($isAscending)) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be a boolean. Found: %s",
                '$isAscending',
                FormattingHelper::found($isAscending)
            ));
        }
        $this->key = $key;
        $this->isAscending = $isAscending;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'key' => $this->getKey(),
            'isAscending' => $this->isAscending(),
        ];
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return boolean
     */
    public function isAscending()
    {
        return $this->isAscending;
    }
}
