<?php

/**
 * This file is part of web3.php package.
 * 
 * (c) Kuan-Cheng,Lai <alk03073135@gmail.com>
 * 
 * @author Peter Lai <alk03073135@gmail.com>
 * @license MIT
 */

namespace Web3;

use InvalidArgumentException;
use stdClass;
use kornrunner\Keccak;
use phpseclib\Math\BigInteger as BigNumber;

class Utils
{
    /**
     * SHA3_NULL_HASH
     * 
     * @const string
     */
    const SHA3_NULL_HASH = 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470';

    /**
     * UNITS
     * from ethjs-unit
     * 
     * @const array
     */
    const UNITS = [
        'noether' => '0',
        'wei' => '1',
        'kwei' => '1000',
        'Kwei' => '1000',
        'babbage' => '1000',
        'femtoether' => '1000',
        'mwei' => '1000000',
        'Mwei' => '1000000',
        'lovelace' => '1000000',
        'picoether' => '1000000',
        'gwei' => '1000000000',
        'Gwei' => '1000000000',
        'shannon' => '1000000000',
        'nanoether' => '1000000000',
        'nano' => '1000000000',
        'szabo' => '1000000000000',
        'microether' => '1000000000000',
        'micro' => '1000000000000',
        'finney' => '1000000000000000',
        'milliether' => '1000000000000000',
        'milli' => '1000000000000000',
        'ether' => '1000000000000000000',
        'kether' => '1000000000000000000000',
        'grand' => '1000000000000000000000',
        'mether' => '1000000000000000000000000',
        'gether' => '1000000000000000000000000000',
        'tether' => '1000000000000000000000000000000'
    ];

    /**
     * construct
     *
     * @return void
     */
    public function __construct()
    {
        // 
    }

    /**
     * toHex
     * 
     * @param string $value
     * @param bool $isPrefix
     * @return string
     */
    public static function toHex($value, $isPrefix=false)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to toHex function must be string.');
        }
        if ($isPrefix) {
            return '0x' . implode('', unpack('H*', $value));
        }
        return implode('', unpack('H*', $value));
    }

    /**
     * hexToBin
     * 
     * @param string
     * @return string
     */
    public static function hexToBin($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to toHex function must be string.');
        }
        if (strpos($value, '0x') === 0) {
            $count = 1;
            $value = str_replace('0x', '', $value, $count);
        }
        return pack('H*', $value);
    }

    /**
     * isZeroPrefixed
     * 
     * @param string
     * @return bool
     */
    public static function isZeroPrefixed($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to zeroPrefixed function must be string.');
        }
        return (strpos($value, '0x') === 0);
    }

    /**
     * stripZero
     * 
     * @param string $value
     * @return string
     */
    public static function stripZero($value)
    {
        if (self::isZeroPrefixed($value)) {
            $count = 1;
            return str_replace('0x', '', $value, $count);
        }
        return $value;
    }

    /**
     * isAddress
     * 
     * @param string
     * @return bool
     */
    public static function isAddress($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to isAddress function must be string.');
        }
        if (preg_match('/^(0x|0X)?[a-f0-9A-F]{40}$/', $value) !== 1) {
            return false;
        } elseif (preg_match('/^(0x|0X)?[a-f0-9]{40}$/', $value) === 1 || preg_match('/^(0x|0X)?[A-F0-9]{40}$/', $value) === 1) {
            return true;
        }
        return self::isAddressChecksum($value);
    }

    /**
     * isAddressChecksum
     * 
     * @param string
     * @return bool
     */
    public static function isAddressChecksum($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to isAddressChecksum function must be string.');
        }
        $value = self::stripZero($value);
        $hash = self::stripZero(self::sha3(mb_strtolower($value)));

        for ($i = 0; $i < 40; $i++) {
            if (
                (intval($hash[$i], 16) > 7 && mb_strtoupper($value[$i]) !== $value[$i]) ||
                (intval($hash[$i], 16) <= 7 && mb_strtolower($value[$i]) !== $value[$i])
            ) {
            return false;
        }
        }
        return true;
    }

    /**
     * isHex
     * 
     * @param string
     * @return bool
     */
    public static function isHex($value)
    {
        return (is_string($value) && preg_match('/^(0x)?[a-f0-9]*$/', $value) === 1);
    }

    /**
     * sha3
     * keccak256
     * 
     * @param string $value
     * @return string
     */
    public static function sha3($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to sha3 function must be string.');
        }
        if (strpos($value, '0x') === 0) {
            $value = self::hexToBin($value);
        }
        $hash = Keccak::hash($value, 256);

        if ($hash === self::SHA3_NULL_HASH) {
            return null;
        }
        return '0x' . $hash;
    }

    /**
     * toWei
     * Change number from unit to wei.
     * For example:
     * $wei = Utils::toWei('1', 'kwei'); 
     * $wei->toString(); // 1000
     * 
     * @param BigNumber|string|int $number
     * @param string $unit
     * @return \phpseclib\Math\BigInteger
     */
    public static function toWei($number, $unit)
    {
        $bn = self::toBn($number);

        if (!is_string($unit)) {
            throw new InvalidArgumentException('toWei unit must be string.');
        }
        if (!isset(self::UNITS[$unit])) {
            throw new InvalidArgumentException('toWei doesn\'t support ' . $unit . ' unit.');
        }
        $bnt = new BigNumber(self::UNITS[$unit]);

        return $bn->multiply($bnt);
    }

    /**
     * toEther
     * Change number from unit to ether.
     * For example:
     * list($bnq, $bnr) = Utils::toEther('1', 'kether'); 
     * $bnq->toString(); // 1000
     * 
     * @param BigNumber|string|int $number
     * @param string $unit
     * @return array
     */
    public static function toEther($number, $unit)
    {
        if ($unit === 'ether') {
            throw new InvalidArgumentException('Please use another unit.');
        }
        $wei = self::toWei($number, $unit);
        $bnt = new BigNumber(self::UNITS['ether']);

        return $wei->divide($bnt);
    }

    /**
     * fromWei
     * Change number from wei to unit.
     * For example:
     * list($bnq, $bnr) = Utils::fromWei('1000', 'kwei'); 
     * $bnq->toString(); // 1
     * 
     * @param BigNumber|string|int $number
     * @param string $unit
     * @return \phpseclib\Math\BigInteger
     */
    public static function fromWei($number, $unit)
    {
        $bn = self::toBn($number);

        if (!is_string($unit)) {
            throw new InvalidArgumentException('fromWei unit must be string.');
        }
        if (!isset(self::UNITS[$unit])) {
            throw new InvalidArgumentException('fromWei doesn\'t support ' . $unit . ' unit.');
        }
        $bnt = new BigNumber(self::UNITS[$unit]);

        return $bn->divide($bnt);
    }

    /**
     * jsonMethodToString
     * 
     * @param stdClass|array $json
     * @return string
     */
    public static function jsonMethodToString($json)
    {
        if ($json instanceof stdClass) {
            // one way to change whole json stdClass to array type
            // $jsonString = json_encode($json);

            // if (JSON_ERROR_NONE !== json_last_error()) {
            //     throw new InvalidArgumentException('json_decode error: ' . json_last_error_msg());
            // }
            // $json = json_decode($jsonString, true);

            // another way to change whole json to array type but need the depth
            // $json = self::jsonToArray($json, $depth)

            // another way to change json to array type but not whole json stdClass
            $json = (array) $json;
            $typeName = [];

            foreach ($json['inputs'] as $param) {
                if (isset($param->type)) {
                    $typeName[] = $param->type;
                }
            }
            return $json['name'] . '(' . implode(',', $typeName) . ')';
        } elseif (!is_array($json)) {
            throw new InvalidArgumentException('jsonMethodToString json must be array or stdClass.');
        }
        if (isset($json['name']) && (bool) strpos('(', $json['name']) === true) {
            return $json['name'];
        }
        $typeName = [];

        foreach ($json['inputs'] as $param) {
            if (isset($param['type'])) {
                $typeName[] = $param['type'];
            }
        }
        return $json['name'] . '(' . implode(',', $typeName) . ')';
    }

    /**
     * jsonToArray
     * 
     * @param stdClass|array|string $json
     * @param int $depth
     * @return array
     */
    public static function jsonToArray($json, $depth=1)
    {
        if (!is_int($depth) || $depth <= 0) {
            throw new InvalidArgumentException('jsonToArray depth must be int and depth must bigger than 0.');
        }
        if ($json instanceof stdClass) {
            $json = (array) $json;
            $typeName = [];

            if ($depth > 1) {
                foreach ($json as $key => $param) {
                    if (is_array($param)) {
                        foreach ($param as $subKey => $subParam) {
                            $json[$key][$subKey] = self::jsonToArray($subParam, $depth-1);
                        }
                    } elseif ($param instanceof stdClass) {
                        $json[$key] = self::jsonToArray($param, $depth-1);
                    }
                }
            }
            return $json;
        } elseif (is_array($json)) {
            if ($depth > 1) {
                foreach ($json as $key => $param) {
                    if (is_array($param)) {
                        foreach ($param as $subKey => $subParam) {
                            $json[$key][$subKey] = self::jsonToArray($subParam, $depth-1);
                        }
                    } elseif ($param instanceof stdClass) {
                        $json[$key] = self::jsonToArray($param, $depth-1);
                    }
                }
            }
        } elseif (is_string($json)) {
            $json = json_decode($json, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new InvalidArgumentException('json_decode error: ' . json_last_error_msg());
            }
            return $json;
        } else {
            throw new InvalidArgumentException('The json param to jsonToArray must be array or stdClass or string.');
        }
        return $json;
    }

    /**
     * toBn
     * Change number or number string to bignumber.
     * 
     * @param BigNumber|string|int $number
     * @return \phpseclib\Math\BigInteger
     */
    public static function toBn($number)
    {
        if (is_numeric($number)) {
            $bn = new BigNumber($number);
        } elseif (is_string($number)) {
            $number = mb_strtolower($number);

            if (self::isZeroPrefixed($number) || preg_match('/[a-f]+/', $number) === 1) {
                $number = self::stripZero($number);
                $bn = new BigNumber($number, 16);
            } else {
                $bn = new BigNumber($number);
            }
        } elseif (!$number instanceof BigNumber){
            throw new InvalidArgumentException('toBn number must be BigNumber, string or int.');
        }
        return $bn;
    }
}