<?php

namespace Scriptotek\Marc\Fields;

use Scriptotek\Marc\Record;

abstract class Field implements \JsonSerializable
{
    /**
     * @var array List of properties to be included when serializing the record using the `toArray()` method.
     */
    public $properties = [];

    protected $field;

    public function __construct(\File_MARC_Field $field)
    {
        $this->field = $field;
    }

    public function getField()
    {
        return $this->field;
    }

    public function jsonSerialize()
    {
        if (count($this->properties)) {
            $o = [];
            foreach ($this->properties as $prop) {
                $value = $this->$prop;
                if (is_object($value)) {
                    $o[$prop] = $value->jsonSerialize();
                } elseif ($value) {
                    $o[$prop] = $value;
                }
            }
            return $o;
        }
        return (string) $this;
    }

    public function __call($name, $args)
    {
        return call_user_func_array([$this->field, $name], $args);
    }

    public function __get($key)
    {
        $method = 'get' . ucfirst($key);
        if (method_exists($this, $method)) {
            return call_user_func([$this, $method]);
        }
    }

    /**
     * @param string|string[] $codes
     * @return array
     */
    protected function getSubfieldValues($codes)
    {
        if (!is_array($codes)) {
            $codes = [$codes];
        }
        $parts = [];
        foreach ($this->field->getSubfields() as $sf) {
            if (in_array($sf->getCode(), $codes)) {
                $parts[] = trim($sf->getData());
            }
        }

        return $parts;
    }

    /**
     * Return concatenated string of the given subfields.
     *
     * @param string[] $codes
     * @param string   $glue
     * @return string
     */
    protected function toString($codes, $glue = ' ')
    {
        return trim(implode($glue, $this->getSubfieldValues($codes)));
    }

    /**
     * Return the data value of the *first* subfield with a given code.
     *
     * @param string $code
     * @param mixed $default
     * @return mixed
     */
    public function sf($code, $default = '')
    {
        $subfield = $this->getSubfield($code);
        if (!$subfield) {
            return $default;
        }

        return trim($subfield->getData());
    }

    public static function makeFieldObject(Record $record, $tag, $pcre=false)
    {
        $field = $record->getField($tag, $pcre);

        // Note: `new static()` is a way of creating a new instance of the
        // called class using late static binding.
        return $field ? new static($field) : null;
    }

    public static function makeFieldObjects(Record $record, $tag, $pcre=false)
    {
        return array_map(function ($field) {

            // Note: `new static()` is a way of creating a new instance of the
            // called class using late static binding.
            return new static($field);
        }, $record->getFields($tag, $pcre));
    }
}
