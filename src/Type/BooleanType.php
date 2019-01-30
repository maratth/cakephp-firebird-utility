<?php

namespace CakephpFirebird;

use Cake\Database\Driver;
use Cake\Database\Type\BoolType;
use InvalidArgumentException;

class BooleanType extends BoolType
{

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value, Driver $driver)
    {
        if (is_null($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $this->_cast($value);
        }

        if (in_array($value, [1, 0, '1', '0'], true)) {
            return $this->_cast((bool)$value);
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot convert value of type `%s` to bool',
            getTypeName($value)
        ));
    }

    /**
     * Cast a php boolean value to firebird-php-parameter boolean value.
     *
     * @param bool $value
     * @return string
     */
    private function _cast(bool $value) {
        return $value ? 'true' : 'false';
    }

}