<?php

/**
 * This file is part of web3.php package.
 * 
 * (c) Kuan-Cheng,Lai <alk03073135@gmail.com>
 * 
 * @author Peter Lai <alk03073135@gmail.com>
 * @license MIT
 */

namespace Web3\Contracts;

use Web3\Utils;
use Web3\Formatters\Integer as IntegerFormatter;

class SolidityType
{
    /**
     * construct
     * 
     * @return void
     */
    public function  __construct()
    {
        // 
    }

    /**
     * get
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);

        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], []);
        }
    }

    /**
     * set
     * 
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function __set($name, $value)
    {
        $method = 'set' . ucfirst($name);

        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], [$value]);
        }
        return false;
    }

    /**
     * callStatic
     * 
     * @param string $name
     * @param array $arguments
     * @return void
     */
    public static function __callStatic($name, $arguments)
    {
        // 
    }

    /**
     * nestedTypes
     * 
     * @param string $name
     * @return mixed
     */
    public function nestedTypes($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('nestedTypes name must string.');
        }
        $matches = [];

        if (preg_match_all('/(\[[0-9]*\])/', $name, $matches, PREG_PATTERN_ORDER) >= 1) {
            return $matches[0];
        }
        return false;
    }

    /**
     * nestedName
     * 
     * @param string $name
     * @return string
     */
    public function nestedName($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('nestedName name must string.');
        }
        $nestedTypes = $this->nestedTypes($name);

        if ($nestedTypes === false) {
            return $name;
        }
        return mb_substr($name, 0, mb_strlen($name) - mb_strlen($nestedTypes[count($nestedTypes) - 1]));
    }

    /**
     * isDynamicArray
     * 
     * @param string $name
     * @return bool
     */
    public function isDynamicArray($name)
    {
        $nestedTypes = $this->nestedTypes($name);

        return $nestedTypes && preg_match('/[0-9]{1,}/', $nestedTypes[count($nestedTypes) - 1]) !== 1;
    }

    /**
     * isStaticArray
     * 
     * @param string $name
     * @return bool
     */
    public function isStaticArray($name)
    {
        $nestedTypes = $this->nestedTypes($name);

        return $nestedTypes && preg_match('/[0-9]{1,}/', $nestedTypes[count($nestedTypes) - 1]) === 1;
    }

    /**
     * staticArrayLength
     * 
     * @param string $name
     * @return int
     */
    public function staticArrayLength($name)
    {
        $nestedTypes = $this->nestedTypes($name);

        if ($nestedTypes === false) {
            return 1;
        }
        $match = [];

        if (preg_match('/[0-9]{1,}/', $nestedTypes[count($nestedTypes) - 1], $match) === 1) {
            return (int) $match[0];
        }
        return 1;
    }

    /**
     * staticPartLength
     * 
     * @param string $name
     * @return int
     */
    public function staticPartLength($name)
    {
        $nestedTypes = $this->nestedTypes($name);

        if ($nestedTypes === false) {
            $nestedTypes = ['[1]'];
        }
        $count = 32;

        foreach ($nestedTypes as $type) {
            $num = mb_substr($type, 1, 1);

            if (!is_numeric($num)) {
                $num = 1;
            } else {
                $num = intval($num);
            }
            $count *= $num;
        }

        return $count;
    }

    /**
     * encode
     * 
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function encode($value, $name)
    {
        if ($this->isDynamicArray($name)) {
            $length = count($value);
            $nestedName = $this->nestedName($name);
            $result = [];
            $result[] = IntegerFormatter::format($length);

            foreach ($value as $val) {
                $result[] = $this->encode($val, $nestedName);
            }
            return $result;
        } elseif ($this->isStaticArray($name)) {
            $length = $this->staticArrayLength($name);
            $nestedName = $this->nestedName($name);
            $result = [];

            foreach ($value as $val) {
                $result[] = $this->encode($val, $nestedName);
            }
            return $result;
        }
        return $this->inputFormat($value, $name);
    }
}