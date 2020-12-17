<?php


namespace fulicommons\util\math;

use DateInterval;
use DatePeriod;
use DateTime;
use fulicommons\util\math\DateTime as DT;


/**
 * 递延收益
 */
class DeferredIncome
{

    /**
     * 获取全部计提金额(使用年利率计算)
     * @param float  $capital_total   总本金
     * @param float  $apr             年利率
     * @param string $start_date      开始日期【yyyy-mm-dd】
     * @param array  $capital_refunds 本金还款【$date => $amount】
     * @return array ['Y-m' => $amount]
     */
    public static function monthAmounts($capital_total, $apr, $start_date, $capital_refunds)
    {
        $datetime_start = new DateTime($start_date);
        $capital_refund_dates = array_keys($capital_refunds);
        $end_date = $capital_refund_dates[count($capital_refund_dates) - 1];
        $datetime_end = new DateTime($end_date);
        $yearmonths = [];
        foreach (new DatePeriod($datetime_start, new DateInterval('P1M'), $datetime_end) as $d) {
            /**
             * @var DateTime $d
             */
            $yearmonths[] = $d->format('Y-m');
        }
        if (!in_array($datetime_end->format('Y-m'), $yearmonths)) {
            $yearmonths[] = $datetime_end->format('Y-m');
        }

        $capital_now = $capital_total;  // 当前剩余本金
        $month_amounts = [];
        $date_gap_start = $start_date;
        foreach ($yearmonths as $yearmonth) {
            $month_amount = 0.00;

            $date_gap_end = (new DateTime("{$yearmonth}-01"))->modify('first day of next month')->format('Y-m-d');
            $temp_date_gap_start = $date_gap_start;

            foreach ($capital_refunds as $capital_refund_day => $capital_refund_amount) {
                if ($capital_refund_day >= $date_gap_start && $capital_refund_day <= $date_gap_end) {
                    $day_count = DT::days($temp_date_gap_start, $capital_refund_day, true);
                    $month_amount += $day_count * $capital_now * $apr / 360;
                    $temp_date_gap_start = $capital_refund_day;
                    $capital_now = Bc::sub($capital_now, $capital_refund_amount, 2);
                }
            }

            $day_count = DT::days($temp_date_gap_start, $date_gap_end, True);
            $month_amount += $day_count * $capital_now * $apr / 360;
            $month_amounts[$yearmonth] = round($month_amount, 2);

            $date_gap_start = $date_gap_end;

        }

        return $month_amounts;
    }

    /**
     * 获取计提金额(使用年利率计算)
     * @param float  $capital_total   总本金
     * @param float  $apr             年利率
     * @param string $start_date      开始日期【yyyy-mm-dd】
     * @param array  $capital_refunds 本金还款【$date => $amount】
     * @param int    $year            计提年
     * @param int    $month           计提月
     * @return float
     */
    public static function monthAmount($capital_total, $apr, $start_date, $capital_refunds, $year, $month)
    {
        $amounts = self::monthAmounts($capital_total, $apr, $start_date, $capital_refunds);
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $key = "{$year}-{$month}";
        if (!isset($amounts[$key])) {
            return 0.00;
        }
        return $amounts[$key];
    }

    /**
     * 获取全部计提比例
     * @param float  $capital_total   总本金
     * @param string $start_date      开始日期【yyyy-mm-dd】
     * @param array  $capital_refunds 本金还款【$date => $amount】
     * @param int    $day             计提日
     * @return array [$date => $scale]
     */
    public static function scales($capital_total, $start_date, $capital_refunds, $day = 1)
    {
        $capital_refund_dates = array_keys($capital_refunds);
        $last_capital_refund_date = $capital_refund_dates[count($capital_refund_dates) - 1];
        $datetime_start = new DateTime($start_date);
        $datetime_last_capital_refund = new DateTime($last_capital_refund_date);

        $datetime_first_accrual = $datetime_start;
        if ((int)$datetime_first_accrual->format('d') > $day) {  // 计提日在放款日之前则在下个月开始计提
            $datetime_first_accrual = $datetime_first_accrual->modify('first day of next month');
        }
        $datetime_first_accrual = DT::setday($datetime_first_accrual, $day);  // 第一个计提日

        $datetime_last_accrual = $datetime_last_capital_refund;
        if ((int)$datetime_last_accrual->format('d') > $day) {  // 计提日在还款日之前则在下个月结束计提
            $datetime_last_accrual = $datetime_last_accrual->modify('first day of next month');
        }
        $datetime_last_accrual = DT::setday($datetime_last_accrual, $day);  // 最后一个计提日

        // 计提日
        $dates = [];
        foreach (new DatePeriod($datetime_first_accrual, new DateInterval('P1M'), $datetime_last_accrual) as $d) {
            /**
             * @var DateTime $d
             */
            $dates[] = $d->format('Y-m-d');
        }
        if (!in_array($datetime_last_accrual->format('Y-m-d'), $dates)) {
            $dates[] = $datetime_last_accrual->format('Y-m-d');
        }

        // 计提金额
        $capital_now = $capital_total;
        $capital_gaps = [];
        $date_gap_start = $start_date;
        foreach ($dates as $date) {
            $date_gap_amount = 0.00;

            $date_gap_end = $date;
            $temp_date_gap_start = $date_gap_start;
            foreach ($capital_refunds as $capital_refund_day => $capital_refund_amount) {
                if ($capital_refund_day >= $date_gap_start && $capital_refund_day <= $date_gap_end) {
                    $day_count = DT::days($temp_date_gap_start, $capital_refund_day, true);
                    $date_gap_amount += $capital_now * $day_count;
                    $temp_date_gap_start = $capital_refund_day;
                    $capital_now = Bc::sub($capital_now, $capital_refund_amount, 2);
                }
            }

            $day_count = DT::days($temp_date_gap_start, $date_gap_end, True);
            $date_gap_amount += $capital_now * $day_count;
            $capital_gaps[$date] = $date_gap_amount;

            $date_gap_start = $date_gap_end;
        }

        $capital_gap_sum = array_sum($capital_gaps);

        // 计提金额
        $scales = [];
        foreach ($capital_gaps as $date => $capital_gap) {
            $scales[$date] = round($capital_gap / $capital_gap_sum, 6);
        }

        // 修正最后一个值
        $date_last_accrual = $datetime_last_accrual->format('Y-m-d');
        $pre_value = Bc::sub(Bc::adds($scales, 6), $scales[$date_last_accrual], 6);
        $last_value = Bc::sub(1, $pre_value, 6);
        $scales[$date_last_accrual] = (float)$last_value;

        return $scales;
    }

    /**
     * 获取计提比例
     * @param float  $capital_total   总本金
     * @param string $start_date      开始日期【yyyy-mm-dd】
     * @param array  $capital_refunds 本金还款【$date => $amount】
     * @param int    $year            计提年
     * @param int    $month           计提月
     * @param int    $day             计提日
     * @return float
     */
    public static function scale($capital_total, $start_date, $capital_refunds, $year, $month, $day = 1)
    {
        $scales = self::scales($capital_total, $start_date, $capital_refunds, $day);
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);
        $key = "{$year}-{$month}-{$day}";
        if (!isset($scales[$key])) {
            return 0.00;
        }
        return $scales[$key];
    }

    /**
     * 获取全部计提金额
     * @param float  $amount          总金额
     * @param float  $capital_total   总本金
     * @param string $start_date      开始日期【yyyy-mm-dd】
     * @param array  $capital_refunds 本金还款【$date => $amount】
     * @param int    $day             计提日
     * @return array [$date => $amount]
     */
    public static function amounts($amount, $capital_total, $start_date, $capital_refunds, $day = 1)
    {
        $scales = self::scales($capital_total, $start_date, $capital_refunds, $day);

        $amounts = [];
        foreach ($scales as $date => $scale) {
            $amounts[$date] = round($scale * $amount, 2);
        }

        // 修正最后一个值
        $dates = array_keys($scales);
        $last_date = $dates[count($dates) - 1];
        $pre_value = Bc::sub(Bc::adds($amounts, 2), $amounts[$last_date], 2);
        $last_value = Bc::sub($amount, $pre_value, 2);
        $amounts[$last_date] = (float)$last_value;

        return $amounts;
    }

    /**
     * 获取计提金额
     * @param float  $amount          总金额
     * @param float  $capital_total   总本金
     * @param string $start_date      开始日期【yyyy-mm-dd】
     * @param array  $capital_refunds 本金还款【$date => $amount】
     * @param int    $year            计提年
     * @param int    $month           计提月
     * @param int    $day             计提日
     * @return float
     */
    public static function amount($amount, $capital_total, $start_date, $capital_refunds, $year, $month, $day = 1)
    {
        $amounts = self::amounts($amount, $capital_total, $start_date, $capital_refunds, $day);
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);
        $key = "{$year}-{$month}-{$day}";
        if (!isset($amounts[$key])) {
            return 0.00;
        }
        return $amounts[$key];
    }
}
