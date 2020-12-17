<?php

namespace fulicommons\util\math;

use DateInterval;
use DatePeriod;
use DateTime as DT;
use RuntimeException;

/**
 * 日期时间相关计算
 */
class DateTime
{

    const YEAR = 31536000;
    const MONTH = 2592000;
    const WEEK = 604800;
    const DAY = 86400;
    const HOUR = 3600;
    const MINUTE = 60;

    protected static $dateRequirements = ['lastYear', 'lastSeason', 'lastMonth', 'yesterday'];

    /**
     * 根据有效时长返回有效期初始时间
     * @param mixed $validity_duration 有效时长，可以是时间戳或者表达式
     * @param bool  $to_time_stamp     结果是否转为时间戳格式
     * @return mixed
     */
    public static function getValidityDurationStart($validity_duration, $to_time_stamp = false)
    {
        if (is_int($validity_duration)) {  //时间戳形式
            $time_stamp_start = time() - $validity_duration;
        } else {  //表达式形式
            $duration = strtotime($validity_duration) - time();
            $time_stamp_start = time() - $duration;
        }

        if ($to_time_stamp) {
            $result = $time_stamp_start;
        } else {
            $result = date('Y-m-d H:i:s', $time_stamp_start);
        }
        return $result;
    }

    /**
     * 计算两个时区间相差的时长,单位为秒
     *
     * $seconds = self::offset('America/Chicago', 'GMT');
     *
     * [!!] A list of time zones that PHP supports can be found at
     * <http://php.net/timezones>.
     *
     * @param string $remote timezone that to find the offset of
     * @param string $local  timezone used as the baseline
     * @param mixed  $now    UNIX timestamp or date string
     * @return  int
     */
    public static function offset($remote, $local = NULL, $now = NULL)
    {
        if ($local === NULL) {
            // Use the default timezone
            $local = date_default_timezone_get();
        }
        if (is_int($now)) {
            // Convert the timestamp into a string
            $now = date(DT::RFC2822, $now);
        }
        // Create timezone objects
        $zone_remote = new DT($remote);
        $zone_local = new DT($local);
        // Create date objects from timezones
        $time_remote = new DT($now, $zone_remote);
        $time_local = new DT($now, $zone_local);
        // Find the offset
        $offset = $zone_remote->getOffset($time_remote) - $zone_local->getOffset($time_local);
        return $offset;
    }

    /**
     * 计算两个时间戳之间相差的时间
     *
     * $span = self::span(60, 182, 'minutes,seconds'); // array('minutes' => 2, 'seconds' => 2)
     * $span = self::span(60, 182, 'minutes'); // 2
     *
     * @param int    $remote timestamp to find the span of
     * @param int    $local  timestamp to use as the baseline
     * @param string $output formatting string
     * @return  string   when only a single output is requested
     * @return  array    associative list of all outputs requested
     * @from https://github.com/kohana/ohanzee-helpers/blob/master/src/Date.php
     */
    public static function span($remote, $local = NULL, $output = 'years,months,weeks,days,hours,minutes,seconds')
    {
        // Normalize output
        $output = trim(strtolower((string)$output));
        if (!$output) {
            // Invalid output
            return FALSE;
        }
        // Array with the output formats
        $output = preg_split('/[^a-z]+/', $output);
        // Convert the list of outputs to an associative array
        $output = array_combine($output, array_fill(0, count($output), 0));
        // Make the output values into keys
        extract(array_flip($output), EXTR_SKIP);
        if ($local === NULL) {
            // Calculate the span from the current time
            $local = time();
        }
        // Calculate timespan (seconds)
        $timespan = abs($remote - $local);
        if (isset($output['years'])) {
            $timespan -= self::YEAR * ($output['years'] = (int)floor($timespan / self::YEAR));
        }
        if (isset($output['months'])) {
            $timespan -= self::MONTH * ($output['months'] = (int)floor($timespan / self::MONTH));
        }
        if (isset($output['weeks'])) {
            $timespan -= self::WEEK * ($output['weeks'] = (int)floor($timespan / self::WEEK));
        }
        if (isset($output['days'])) {
            $timespan -= self::DAY * ($output['days'] = (int)floor($timespan / self::DAY));
        }
        if (isset($output['hours'])) {
            $timespan -= self::HOUR * ($output['hours'] = (int)floor($timespan / self::HOUR));
        }
        if (isset($output['minutes'])) {
            $timespan -= self::MINUTE * ($output['minutes'] = (int)floor($timespan / self::MINUTE));
        }
        // Seconds ago, 1
        if (isset($output['seconds'])) {
            $output['seconds'] = $timespan;
        }
        if (count($output) === 1) {
            // Only a single output was requested, return it
            return array_pop($output);
        }
        // Return array
        return $output;
    }

    /**
     * 格式化 UNIX 时间戳为人易读的字符串
     *
     * @param int    Unix 时间戳
     * @param mixed $local 本地时间
     *
     * @return    string    格式化的日期字符串
     */
    public static function human($remote, $local = null)
    {
        $timediff = (is_null($local) || $local ? time() : $local) - $remote;
        $chunks = [
            [60 * 60 * 24 * 365, 'year'],
            [60 * 60 * 24 * 30, 'month'],
            [60 * 60 * 24 * 7, 'week'],
            [60 * 60 * 24, 'day'],
            [60 * 60, 'hour'],
            [60, 'minute'],
            [1, 'second']
        ];

        for ($i = 0, $j = count($chunks); $i < $j; $i++) {
            $seconds = $chunks[$i][0];
            $name = $chunks[$i][1];
            if (($count = floor($timediff / $seconds)) != 0) {
                break;
            }
        }
        return __("%d {$name}%s ago", $count, ($count > 1 ? 's' : ''));
    }

    /**
     * 获取一个基于时间偏移的Unix时间戳
     *
     * @param string $type     时间类型，默认为day，可选minute,hour,day,week,month,quarter,year
     * @param int    $offset   时间偏移量 默认为0，正数表示当前type之后，负数表示当前type之前
     * @param string $position 时间的开始或结束，默认为begin，可选前(begin,start,first,front)，end
     * @param int    $year     基准年，默认为null，即以当前年为基准
     * @param int    $month    基准月，默认为null，即以当前月为基准
     * @param int    $day      基准天，默认为null，即以当前天为基准
     * @param int    $hour     基准小时，默认为null，即以当前年小时基准
     * @param int    $minute   基准分钟，默认为null，即以当前分钟为基准
     * @return int 处理后的Unix时间戳
     */
    public static function unixtime($type = 'day', $offset = 0, $position = 'begin', $year = null, $month = null, $day = null, $hour = null, $minute = null)
    {
        $year = is_null($year) ? date('Y') : $year;
        $month = is_null($month) ? date('m') : $month;
        $day = is_null($day) ? date('d') : $day;
        $hour = is_null($hour) ? date('H') : $hour;
        $minute = is_null($minute) ? date('i') : $minute;
        $position = in_array($position, ['begin', 'start', 'first', 'front']);

        switch ($type) {
            case 'minute':
                $time = $position ? mktime($hour, $minute + $offset, 0, $month, $day, $year) : mktime($hour, $minute + $offset, 59, $month, $day, $year);
                break;
            case 'hour':
                $time = $position ? mktime($hour + $offset, 0, 0, $month, $day, $year) : mktime($hour + $offset, 59, 59, $month, $day, $year);
                break;
            case 'day':
                $time = $position ? mktime(0, 0, 0, $month, $day + $offset, $year) : mktime(23, 59, 59, $month, $day + $offset, $year);
                break;
            case 'week':
                $time = $position ?
                    mktime(0, 0, 0, $month, $day - date("w", mktime(0, 0, 0, $month, $day, $year)) + 1 - 7 * (-$offset), $year) :
                    mktime(23, 59, 59, $month, $day - date("w", mktime(0, 0, 0, $month, $day, $year)) + 7 - 7 * (-$offset), $year);
                break;
            case 'month':
                $time = $position ? mktime(0, 0, 0, $month + $offset, 1, $year) : mktime(23, 59, 59, $month + $offset, cal_days_in_month(CAL_GREGORIAN, $month + $offset, $year), $year);
                break;
            case 'quarter':
                $time = $position ?
                    mktime(0, 0, 0, 1 + ((ceil(date('n', mktime(0, 0, 0, $month, $day, $year)) / 3) + $offset) - 1) * 3, 1, $year) :
                    mktime(23, 59, 59, (ceil(date('n', mktime(0, 0, 0, $month, $day, $year)) / 3) + $offset) * 3, cal_days_in_month(CAL_GREGORIAN, (ceil(date('n', mktime(0, 0, 0, $month, $day, $year)) / 3) + $offset) * 3, $year), $year);
                break;
            case 'year':
                $time = $position ? mktime(0, 0, 0, 1, 1, $year + $offset) : mktime(23, 59, 59, 12, 31, $year + $offset);
                break;
            default:
                $time = mktime($hour, $minute, 0, $month, $day, $year);
                break;
        }
        return $time;
    }

    /**
     * 获取两个日期间的所有日期
     * @param string $date_begin 开始日期，(Y-m-d)
     * @param string $date_end   结束日期，(Y-m-d)
     * @return array
     */
    public static function getPeriodDates($date_begin, $date_end)
    {
        $dates = [];
        if ($date_begin > $date_end) {
            return $dates;
        }
        $start = new DT($date_begin);
        $end = new DT($date_end);
        foreach (new DatePeriod($start, new DateInterval('P1D'), $end) as $d) {
            /**
             * @var DT $d
             */
            $dates[] = $d->format('Y-m-d');
        }
        $dates[] = $date_end;
        return $dates;
    }

    /**
     * 获取两个日期间间隔的天数
     * @param string $date_begin 开始日期，(Y-m-d)
     * @param string $date_end   结束日期，(Y-m-d)
     * @return int
     */
    public static function getIntervalDays($date_begin, $date_end)
    {
        $datetime_start = new DT($date_begin);
        $datetime_end = new DT($date_end);
        $days = $datetime_start->diff($datetime_end)->days;
        return (int)$days;
    }

    /**
     * 获取两个日期相隔的天数
     * @param string $start_date 开始日期
     * @param string $end_date   结束日期
     * @param bool   $days360    是否使用360天制
     * @return int
     */
    public static function days($start_date, $end_date, $days360 = false)
    {
        $datetime_start = new DT($start_date);
        $datetime_end = new DT($end_date);
        if ($days360) {
            $year_start = $datetime_start->format('Y');
            $year_end = $datetime_end->format('Y');
            $years = (int)$year_end - (int)$year_start;
            $month_start = $datetime_start->format('m');
            $month_end = $datetime_end->format('m');
            $months = (int)$month_end - (int)$month_start;
            $day_start = $datetime_start->format('d');
            $day_end = $datetime_end->format('d');
            $days = (int)$day_end - (int)$day_start;
            return ($months + $years * 12) * 30 + $days;
        } else {
            $diff = $datetime_start->diff($datetime_end);
            $days = $diff->days;
            return (int)$days;
        }
    }

    /**
     * 对 DateTime 设置指定日期
     * @param DT  $dateTime 日期
     * @param int $day      日期，如果大于当月最后一天，则返回最后一天
     * @return DT
     */
    public static function setday(DT $dateTime, $day)
    {
        $t = (int)$dateTime->format('t');
        if ($day > $t) {
            $day = $t;
        }
        return new DT($dateTime->format("Y-m-{$day}"));
    }

    /**
     * 根据身份证号返回年龄
     * @param string $idcard 身份证号
     * @return int
     */
    public static function getAgeByIdcard($idcard)
    {
        return date('Y') - substr($idcard, 6, 4) + (date('md') >= substr($idcard, 10, 4) ? 1 : 0);
    }

    /**
     * 获取指定表达式日期区间
     * @param string $dateRequirement lastYear lastSeason lastMonth  lastWeek yesterday
     * @return string[] [开始日期, 结束日期]
     */
    public static function getDateReuiqrement($dateRequirement)
    {
        switch ($dateRequirement) {
            case 'lastYear':
                //获取上月日期Y-m
                $startDate = date("Y-01-01", strtotime("-1 year"));
                $endDate = date("Y-12-31", strtotime("-1 year"));
                break;
            case 'lastSeason':
                //上个季度是第几季度
                $season = ceil((date('n')) / 3) - 1;
                $startDate = date('Y-m-d', mktime(0, 0, 0, $season * 3 - 3 + 1, 1, date('Y')));
                $endDate = date('Y-m-d', mktime(0, 0, 0, $season * 3, date('t', mktime(0, 0, 0, $season * 3, 1, date("Y"))), date('Y')));
                break;
            case 'lastMonth':
                $startDate = date("Y-m-01", strtotime("-1 month"));
                $endDate = date('Y-m-d', strtotime(date('Y-m-01') . ' -1 day'));;
                break;
            case 'lastWeek':
                $startDate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1 - 7, date("Y")));
                $endDate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - date("w"), date("Y")));
                break;
            case 'yesterday':
                $startDate = date("Y-m-d", strtotime("-1 day"));
                $endDate = date("Y-m-d", strtotime("-1 day"));
                break;
            default:
                throw new RuntimeException("参数错误：dateRequirement。");
        }
        return [$startDate, $endDate];
    }

    /**
     * 获取指定表达式日期区间开始日期
     * @param string $dateRequirement lastYear lastSeason lastMonth  lastWeek yesterday
     * @return string
     */
    public static function getStartDateRequirement($dateRequirement)
    {
        return self::getDateReuiqrement($dateRequirement)[0];
    }

    /**
     * 获取指定表达式日期区间结束日期
     * @param string $dateRequirement lastYear lastSeason lastMonth  lastWeek yesterday
     * @return string
     */
    public static function getEndDateRequirement($dateRequirement)
    {
        if (!in_array($dateRequirement, self::$dateRequirements)) return $dateRequirement;
        return self::getDateReuiqrement($dateRequirement)[1];
    }
}
