<?php

namespace fulicommons\util\math;

/**
 * BCMath高精度数学类
 * 由于精度问题，建议参数都以字符串形式传递。
 */
class Bc
{

    /**
     * 禁止实例化
     */
    private function __construct()
    {
    }

    /**
     * 返回2个任意精度数字的加法计算，返回字符串结果
     * @param mixed $left_operand 第一个数字，可以是字符串，也可以是原值
     * @param mixed $right_operand 第二个数字，可以是字符串，也可以是原值
     * @param int $scale 指定结果小数位，默认是自动
     * @return string
     */
    public static function add($left_operand, $right_operand, $scale = 0)
    {
        if ($scale == 0) {
            return bcadd((string)$left_operand, (string)$right_operand);
        } else {
            return bcadd((string)$left_operand, (string)$right_operand, $scale);
        }
    }

    /**
     * 返回任意精度数字的累积加法计算，返回字符串结果
     * @param array $operands 需要计算的数字组成的数组，元素可以是字符串，也可以是原值
     * @param int $scale 指定结果小数位，默认是自动
     * @return string
     */
    public static function adds(array $operands, $scale = 0)
    {
        $result = '0';
        foreach ($operands as $operand) {
            $result = self::add($result, $operand, $scale);
        }
        return $result;
    }

    /**
     * 把right_operand和left_operand作比较, 并且返回一个整数的结果.
     * @param mixed $left_operand 左边的运算数，可以是字符串，也可以是原值
     * @param mixed $right_operand 右边的运算数，可以是字符串，也可以是原值
     * @param int $scale 设置指示数字， 在使用来作比较的小数点部分. 默认比较全部
     * @return int 如果两个数相等返回0, 左边的数left_operand比较右边的数right_operand大返回1, 否则返回-1.
     */
    public static function comp($left_operand, $right_operand, $scale = 0)
    {
        if ($scale == 0) {
            return bccomp((string)$left_operand, (string)$right_operand);
        } else {
            return bccomp((string)$left_operand, (string)$right_operand, $scale);
        }
    }

    /**
     * 2个任意精度的数字除法计算
     * @param mixed $left_operand 被除数，可以是字符串，也可以是原值
     * @param mixed $right_operand 除数，可以是字符串，也可以是原值
     * @param int $scale 指定结果小数位，默认是自动
     * @return string 返回结果为字符串类型的结果，如果右操作数是0结果为null
     */
    public static function div($left_operand, $right_operand, $scale = 0)
    {
        if ($scale == 0) {
            return bcdiv((string)$left_operand, (string)$right_operand);
        } else {
            return bcdiv((string)$left_operand, (string)$right_operand, $scale);
        }
    }

    /**
     * 对一个任意精度数字取模
     * @param mixed $left_operand 左操作数 ，可以是字符串，也可以是原值
     * @param mixed $modulus 系数 ，可以是字符串，也可以是原值
     * @return string 返回字符串类型取模后结果，如果系数为0则返回null
     */
    public static function mod($left_operand, $modulus)
    {
        return bcmod((string)$left_operand, (string)$modulus);
    }

    /**
     * 2个任意精度数字乘法计算
     * @param mixed $left_operand 左操作数 ，可以是字符串，也可以是原值
     * @param mixed $right_operand 右操作数 ，可以是字符串，也可以是原值
     * @param int $scale 设置结果中小数点后的小数位数
     * @return string 返回计算结果字符串
     */
    public static function mul($left_operand, $right_operand, $scale = 0)
    {
        if ($scale == 0) {
            return bcmul((string)$left_operand, (string)$right_operand);
        } else {
            return bcmul((string)$left_operand, (string)$right_operand, $scale);
        }
    }

    /**
     * 任意精度数字的乘方
     * @param mixed $left_operand 底数，可以是字符串，也可以是原值
     * @param mixed $right_operand 乘方，可以是字符串，也可以是原值
     * @param int $scale 设置结果中小数点后的小数位数
     * @return string
     */
    public static function pow($left_operand, $right_operand, $scale = 0)
    {
        if ($scale == 0) {
            return bcpow((string)$left_operand, (string)$right_operand);
        } else {
            return bcpow((string)$left_operand, (string)$right_operand, $scale);
        }
    }

    /**
     * 对乘方结果进行取模
     * @param mixed $left_operand 底数，可以是字符串，也可以是原值
     * @param mixed $right_operand 乘方，可以是字符串，也可以是原值
     * @param mixed $modulus 模，乘方，可以是字符串，也可以是原值
     * @param int $scale 设置结果中小数点后的小数位数
     * @return string
     */
    public static function powmod($left_operand, $right_operand, $modulus, $scale = 0)
    {
        if ($scale == 0) {
            return bcpowmod((string)$left_operand, (string)$right_operand, (string)$modulus);
        } else {
            return bcpowmod((string)$left_operand, (string)$right_operand, (string)$modulus, $scale);
        }
    }

    /**
     * 设置所有bc数学函数的默认小数点保留位数
     * @param int $scale 小数点保留位数.
     * @return bool 成功时返回 TRUE ， 或者在失败时返回 FALSE
     */
    public static function scale($scale)
    {
        return bcscale($scale);
    }

    /**
     * 任意精度数字的二次方根
     * @param mixed $operand 操作数，可以是字符串，也可以是原值
     * @param int $scale 设置结果中小数点后的小数位数
     * @return string
     */
    public static function sqrt($operand, $scale = 0)
    {
        if ($scale == 0) {
            return bcsqrt((string)$operand);
        } else {
            return bcsqrt((string)$operand, $scale);
        }
    }

    /**
     * 2个任意精度数字的减法
     * @param mixed $left_operand 被减数，可以是字符串，也可以是原值
     * @param mixed $right_operand 减数，可以是字符串，也可以是原值
     * @param int $scale 设置结果中小数点后的小数位数
     * @return string
     */
    public static function sub($left_operand, $right_operand, $scale = 0)
    {
        if ($scale == 0) {
            return bcsub((string)$left_operand, (string)$right_operand);
        } else {
            return bcsub((string)$left_operand, (string)$right_operand, $scale);
        }
    }
}
