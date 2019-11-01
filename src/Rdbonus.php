<?php

namespace Aitasty;

class Rdbonus extends Core
{
        /**
         * upper limit (percentage)
         * first maximum random value by upper limit percentage(not null and not 0)
         * 
         * @var integer
         */
        protected $upper = 30;
        /**
         * lower limit (percentage)
         * first minimum random value by lower limit percentage(not null and not 0)
         * 
         * @var integer
         */
        protected $lower = 20;
        /**
         * baseline fluctuate (it has to be greater than 1,suggest more than 2)
         *
         * @var integer
         */
        protected $fluctuate = 3;

        protected $tail_total = 0;
        protected $pre_total  = 0;
        protected $num = 0;

        public function __construct()
        {
                parent::__construct();
        }

        protected function print($bonus, $divide = 100)
        {
                $count = 0;
                foreach ($bonus as $k => $v) {
                        $count += $v;
                        echo ($v / $divide) . "<br />";
                }
                echo "total:" . ($count / $divide) . "<br />";
        }
        /**
         * test ippm by rule rate
         *
         * @return void
         */
        protected function testIppm()
        {
                $arr = ['a' => '30', 'b' => '0.08', 'c' => '0.055', 'd' => '0.02', 'e' => '0.01'];
                for ($j = 1; $j <= 10; $j++) {
                        $h = ['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 0];
                        for ($i = 1; $i <= 100; $i++) {
                                $k = $this->rdIppm($arr);
                                if ($k === false)
                                        continue;
                                $h[$k] += 1;
                        }
                        var_dump($h);
                        echo "<br />";
                }
        }

        function test()
        {

                //init
                $total  = 3000;
                $num    = 7;
                //$boom   = 3; $a =  $this->actionSingleBoom($total, $num, $boom,2);$this->print($a);die; //
                //$boom = 3; $result = $this->actionSingleBoom($total, $num, $boom, 2); $s = $this->rdBan($result, []); $this->print($s);die;
                //$a = $this->actionMultipleBoom($total, $num, [1,2,3,6,8], 3);$this->print($a);die; //
                //$b = $this->tailCompare(777, 5, -10);var_dump($b);die; //得尾数比大小
                //$b = $this->adjustAvoid(21, 3);var_dump($b);die; //保底两组数,尾号成功避雷  
                //$this->testIppm();die;//万分机率配置测试
                //$h = $this->singleBoomHit(); var_dump( $h );die;    
                /* $verify = $this->initBonus($total, $num);
                if (!$verify) {
                        die('illegal total or num！');
                }
                $preBonus = $this->preBonus();
                $this->print($preBonus, 10);
                $tailBonus = $this->tailBonus();
                $this->print($tailBonus);
                die; */
        }
        
        /**
         * Two reservations required
         *
         * @param [int] $total
         * @param [int] $num  (must be more than 1) 
         * @param [int] $boom
         * @param [int] $hit 
         * @return void
         */
        protected function actionSingleBoom($total, $num, $boom, $hit = 0)
        {
                if ($num < 2) {
                        return false;
                }
                $verify = $this->initBonus($total, $num);
                if (!$verify) {
                        return false;
                }
                $preBonus  = $this->preBonus();
                $bonus     = [];
                $bonusTotal = 0;
                foreach ($preBonus as $k => $v) {
                        $rdValue = 0;
                        if ($hit > 0) {
                                $rdValue = $boom;
                                $hit--;
                        } else {
                                $rdValue = $this->rdAvoid($boom);
                        }
                        //$diff += (9 - $rdValue);
                        $value = ($v * 10 + $rdValue);
                        $bonus[] = $value;
                        $bonusTotal += intval($value);
                }
                $diff = intval($total - $bonusTotal);
                //two element adjust avoid
                $bonus1 = array_pop($bonus);
                $bonus2 = array_pop($bonus);
                $twobonusTotal = $bonus1 + $bonus2 + $diff;
                $adjustAvoid = $this->adjustAvoid($twobonusTotal, $boom);
                $endBonusIdx = array_rand($adjustAvoid);
                $endBonus    = $adjustAvoid[$endBonusIdx];
                $bonus = array_merge($bonus, $endBonus);
                return $bonus;
        }
        /**
         * 
         * @param [int] $total
         * @param [int] $num
         * @param [array] $booms
         * @param integer $hit
         * @return void
         */
        protected function actionMultipleBoom($total, $num, $booms, $hit = 0)
        {
                if ($num < 2) {
                        return false;
                }
                if (!is_array($booms)) {
                        return false;
                }
                $verify = $this->initBonus($total, $num);
                if (!$verify) {
                        return false;
                }
                $boomsNum = count( $booms );
                $preBonus   = $this->preBonus();
                $bonus      = [];
                $bonusTotal = 0;
                $fixBoom    = [];
                foreach ($preBonus as $k => $v) {
                        $rdValue   = 0;
                        $boomValue = array_pop($booms);
                        if ($hit > 0 && !is_null($boomValue)) {
                                $rdValue = $boomValue;
                                $hit--;
                        } else {
                                if (empty($fixBoom) && !is_null($boomValue)) { 
                                        $fixBoom = array_merge($booms,[$boomValue]);
                                 }
                                $rdValue = $this->rdMultiAvoid($fixBoom);
                        }
                        $value = ($v * 10 + $rdValue);
                        $bonus[] = $value;
                        $bonusTotal += intval($value);
                }
                $diff = intval($total - $bonusTotal);
                $fixBoomNum = count( $fixBoom );
                $hitNum = $boomsNum - $fixBoomNum;
                if( ($hitNum + 2 > $num) && $hitNum == $boomsNum ){
                        $bonus1 = array_pop( $bonus );
                        $endBonus = [$bonus1 + $diff];
                }else{
                        $bonus1 = array_pop($bonus);
                        $bonus2 = array_pop($bonus);
                        $twobonusTotal = $bonus1 + $bonus2 + $diff;
                        $adjustAvoid = $this->adjustAvoid($twobonusTotal, $fixBoom);
                        $endBonusIdx = array_rand($adjustAvoid);
                        $endBonus    = $adjustAvoid[$endBonusIdx];
                }
                $bonus = array_merge($bonus, $endBonus);
                return $bonus;
        }

        /**
         * get hit num according to the config rate and hit
         * @param [array][key=>value] $config
         * @return void
         */
        protected function singleBoomHit($config)
        {
                if (empty($config) || !is_array($config))
                        return 0;
                //config
                $rate = [];
                $hits = [];
                foreach ($config as $k => $v) {
                        $rate[$k] = isset($v['rate']) ? $v['rate'] : 0;
                        $hit[$k]  = isset($v['hit']) ? $v['hit'] : 0;
                }
                if (empty($rate) || empty($hit))
                        return 0;
                $key  = $this->rdIppm($rate);
                $hit = !empty($key) ? $hits[$key] : 0;
                return $hit;
        }
        /**
         * verify random result must be without ban array
         * @param [array] $result
         * @param [array] $banArr
         * @return void
         */
        function rdBan($result, array $banArr)
        {
                $safety    = [];
                $wheel     = 0;
                $diff      = 0;
                foreach ($result as $k => $v) {
                        if (in_array($v, $banArr)) {
                                if ($wheel == 0) {
                                        $wheel = 1;
                                        $v -= 10;
                                        $diff += 10;
                                } else {
                                        $wheel = 0;
                                        $v += 10;
                                        $diff -= 10;
                                }
                        }
                        $safety[] = $v;
                }
                if ($diff != 0) {
                        $popValue = array_pop($safety);
                        $popValue += 10;
                        $safety   = array_merge($safety, [$popValue]);
                }
                shuffle($safety);
                return $safety;
        }

        /**
         * two numbers bits do add up to total but each tail without boom
         * @param [int] $total
         * @param [int] $boom (must be 0 to 9)
         * @return array
         */
        protected function adjustAvoid($total, $boom)
        {
                $boom = (int) $boom;
                $p1 = range(0, 9);
                $p2 = range(0, 9);
                if(is_array($boom)){
                        foreach( $boom as $v ){
                                $key = (int) $v;
                                unset( $p1[$key] );
                                unset( $p2[$key] );
                        }
                }else{
                        unset($p1[$boom]);
                        unset($p2[$boom]);
                }
                $arr = [];
                $totalBit  = $this->intUnit($total);
                $totalTens = $total - $totalBit;
                foreach ($p1 as $v1) {
                        foreach ($p2 as $v2) {
                                $sum = $v1 + $v2;
                                $sumBit   = $this->intUnit($sum);
                                if ($sumBit == $totalBit) {
                                        $sumTens  = $sum - $sumBit;
                                        $diffTens = $totalTens - $sumTens;
                                        $tens = intval($diffTens / 10);
                                        if ($tens > 0) {
                                                $v1Tens  = rand(0, $tens);
                                                $v2Tens  = $tens - $v1Tens;
                                                $v1 = $v1 + $v1Tens * 10;
                                                $v2 = $v2 + $v2Tens * 10;
                                        }
                                        $arr[] = [$v1, $v2];
                                }
                        }
                }
                return $arr;
        }
        /**
         * create a value sum add up to tail bits compare boom
         * @param [integer] $value
         * @param [integer] $boom
         * @param integer $compare (-1:less than, 0: equal, 1: greater than, -10:less than or equal, 10: greater than or equal)
         * @return array
         */
        protected function tailCompare($value, $boom, $compare = 0)
        {
                $value  = strval(intval($value));
                $len    = strlen($value);
                $bitSum = 0;
                for ($i = 0; $i <= $len - 1; $i++) {
                        $bitSum += $value[$i];
                }
                $numbers = range(0, 9);
                $arr = [];
                foreach ($numbers as $v) {
                        $sum  = $v + $bitSum;
                        $bits = $this->intUnit($sum);
                        switch ($compare) {
                                case -1:
                                        if ($bits < $boom) $arr[] = $v;
                                        break;
                                case -10;
                                        if ($bits <= $boom) $arr[] = $v;
                                        break;
                                case 0:
                                        if ($bits == $boom) $arr[] = $v;
                                        break;
                                case 1:
                                        if ($bits > $boom) $arr[] = $v;
                                        break;
                                case 10:
                                        if ($bits >= $boom) $arr[] = $v;
                                        break;
                                default:
                                        $arr[] = $v;
                                        break;
                        }
                }
                return $arr;
        }

        /**
         * exclude a value
         * @param [int] $value [0-9]
         * @return void
         */
        function rdAvoid($value)
        {
                $value   = (int) $value;
                $numbers = range(0, 9);
                unset($numbers[$value]);
                return array_rand($numbers, 1);
        }
        /**
         * exclude multiple values
         *
         * @param [type] $values
         * @return void
         */
        function rdMultiAvoid($values)
        {
                $numbers = range(0, 9);
                foreach ($values as $k => $v) {
                        $key = (int) $v;
                        unset($numbers[$key]);
                }
                if (empty($numbers))
                        return 0;
                return array_rand($numbers, 1);
        }

        /**
         * ippm odds
         * multiple rate config reserve ippm range than random odds score a hit
         * @param [array] $hit ['key1'=>'percentage%','key2'=>'percentage%']]
         * @return false || $key
         */
        function rdIppm($hit)
        {
                if (!is_array($hit)) {
                        return false;
                }
                $totalRate = 0;
                $tmp = 1;
                $hitAlloc = [];
                foreach ($hit as $k => $v) {
                        $v = intval($v * 100);
                        $len = 0;
                        if ($v <= 0) {
                                $min = 0;
                                $max = 0;
                        } else {
                                $totalRate += $v;
                                $min = $tmp;
                                $max = $tmp + $v - 1;
                                $tmp = $max + 1;
                                $len = $max - $min + 1;
                        }
                        $hitAlloc[$k] = [
                                'min' => $min,
                                'max' => $max,
                                'len' => $len,
                        ];
                }
                if ($totalRate > 10000) {
                        return false;
                }
                $rand = rand(1, 10000);
                foreach ($hitAlloc as $k => $v) {
                        if ($rand >= $v['min'] && $rand <= $v['max']) {
                                return $k;
                        }
                }
                return false;
        }

        /**
         * init total and num baseline
         *
         * @param [int] $total(unit:cent,that is keep two decimal place * 100 to integer. must be a multiple of 100)
         * @param [int] $num
         * @return void boolen
         */
        protected function initBonus($total, $num)
        {
                $maxTailTotal   = $this->maxTailTotal($num);
                $totalUnit      = $this->intUnit($total);
                $this->tail_total= $tail_total = $maxTailTotal + (int)$totalUnit;
                $this->pre_total = $preTotal = (int) $total - $tail_total;
                $this->num       = (int) $num;
                return $preTotal >= 0 ? true : false;
        }
        /**
         * random preposition bonus
         *
         * @return void
         */
        protected function preBonus()
        {
                $pre_total = intval($this->pre_total / 10);
                return $this->baseBonus($pre_total, $this->num, true);
        }
        /**
         * random tail bonus
         *
         * @return void
         */
        protected function tailBonus()
        {
                $total = $this->tail_total;
                return $this->baseBonus($total, $this->num);
        }
        /**
         * must be a multiple of 10
         *
         * @param [type] $num
         * @return void
         */
        protected function maxTailTotal($num)
        {
                return ceil((int) $num * 9 / 10) * 10;
        }

        /**
         * base random bonus
         * @param [int] $total
         * @param [int] $num
         * @param [boolen] $isnull //allow or not 0
         * @return void
         */
        protected function baseBonus($total, $num, $isnull = false)
        {
                $bonus  = [];
                $balance = $total;
                $rdLimit = $this->rdLimit($num);
                if ($balance <= $rdLimit) {
                        return $bonus;
                }

                for ($i = $num; $i > 0; $i--) {
                        $rand = $this->rand($balance, $i, $isnull);
                        $bonus[] = $rand;
                        $balance = $balance - $rand;
                }
                return $bonus;
        }

        /**
         * random value by total and num, that must be integer.
         * Total arithmetic rule by rdFluctuate() when total verge to baseline (just in case balance total cannot allocate)
         * @param [int] $total
         * @param [int] $num
         * @param [boolen] $isnull //allow or not 0
         * arithmetic rule:
         *      maximum limit: 
         *              if ( upper * num >= 100 ):
         *                      max = total * upper_rate  [upper_rate: upper/100]
         *              else
         *                      max = [total/num] * upper_rate + [total/num]
         *     [notice: verify baseline,must be greater than baseline]
         *      minimum limit:
         *              min  =  totla * lower_rate [lower_rate: lower/100]
         *     [notice: min must be less than or equal to max]
         * @return void
         */
        protected function rand($total, $num, $isnull = false)
        {
                $total = (int) $total;
                if ($num <= 1)
                        return $total;
                $rdLimit = $this->rdLimit($num);
                if ($total <= $rdLimit) {
                        //fluctuate by baseline
                        return $this->rdFluctuate($total, $num);
                }
                //unit avg total
                $unitAvg = intval($total / $num);
                //lower and upper setting
                $lower = (int) $this->lower;
                $upper = (int) $this->upper;
                $lower = $lower > 100 ? 100 : ($lower < 1 ? 1 : $lower);
                $upper = $upper > 100 ? 100 : ($upper < 1 ? 1 : $upper);
                // min value
                $lower_rate = (float) ($lower / 100);
                $min = (int) ($total * $lower_rate);
                //max value
                $upper_rate = (float) ($upper / 100);
                if ($upper * $num >= 100) {
                        $max = (int) ($total * $upper_rate);
                } else {
                        $max = (int) ($unitAvg * $upper_rate + $unitAvg);
                }
                //baseline: don't cross the baseline!
                $crisis = intval($total - $max);
                if ($crisis < $rdLimit) {
                        $max = $total - $rdLimit; //critical point
                }
                // exception min and max value
                if ($min > $max) {
                        $min = $max;
                }
                $rand  = (int) rand($min, $max);
                if (!$isnull && $rand < 1) { //allow or not 0
                        $rand = 1;
                }
                //echo "total:{$total}, num:{$num}, rdLimit:{$rdLimit}, crisis:{$crisis}, min:{$min}, max:{$max}, rand:{$rand}<br />";
                return $rand;
        }
        /**
         * baseline on base of num
         * @param [type] $num
         * @return void
         */
        private function rdLimit($num)
        {
                $fluctuate = $this->fluctuate;
                return intval($num) * intval($fluctuate);
        }
        /**
         * random fluctuate by baseline [open when random fluctuate accessibility]
         * @param [int] $amount
         * @param [int] $num
         * @return void
         */
        private function rdFluctuate($total, $num)
        {
                $rdLimit = $this->rdLimit($num);
                if ($total < $rdLimit)
                        return 0;
                //range
                $fluctuate = (int) $this->fluctuate;
                $range = ($fluctuate * 2) - 1;
                $start = intval($total);
                $end   = intval($total - $range);
                $rand  = rand($start, $end);
                return $rand;
        }
        /**
         * get integer unit bits
         * @param [int] $digital
         * @param integer $idx
         * @return void
         */
        private function intUnit($digital, $idx = 0)
        {
                $digital = (int) $digital;
                $str = strval($digital);
                $len = strlen($str);
                $point = $idx + 1;
                $i = $len - $point;
                return isset($str[$i]) ? $str[$i] : 0;
        }
        /**
         * cut right unit number
         * @param [int] $digital
         * @param integer $toright
         * @return integer
         */
        private function rightUnit( $digital,$toright=0 ){
                $digital = (int) $digital;
                $toright = (int) $toright;
                $str = strval($digital);
                $len = strlen($str);
                if( $toright > $len )
                        return 0;
                return (int)substr( $str,0,$len-$toright);
        }

}