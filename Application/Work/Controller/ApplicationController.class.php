<?php
/*  
 *  申请状态页
 */
namespace Work\Controller;
//use Wap\AppController;
use Think\Controller;

class ApplicationController extends Controller {

    public function __construct(){
        parent::__construct();

    }

    /*
     *  展示签到页面
     */
    public function showSignPage() {
        $cs_id = I('cs_id');
        // 1.查出会议详情
        $cs_info = M('conference')->where("`CS_ID` = '$cs_id'")->find();
        $cs_level = $cs_info['level'];
        $this->assign('cs_info', $cs_info);

        // 2.查出与会人员名单
        $cs_pre_users = M('pre_sign_users')->where("`CS_ID` = '$cs_id'")->select();
        foreach ($cs_pre_users as $key => $user) {
            $cs_uname = $user['user_name'];
            $sr_user = M('sign_record')->where("`CS_ID` = '$cs_id' AND `USER_NAME` = '$cs_uname'")->find();
            if(!empty($sr_user)) {
                $cs_pre_users[$key]['sign_status'] =  $sr_user['sign_status'];
            }else {
                $cs_pre_users[$key]['sign_status'] =  0;
            }
        }
        $this->assign('cs_pre_users', $cs_pre_users);
        
        if ($cs_level == 0) {
            $this->display();
        } else {
            $this->display("showSignPageWithSecret");
        }
        
    }

    /*
     *  处理签到post
     */
    public function doConferenceSign() {
        $cs_id      = I('cs_id');
        $s_uname    = I('sign_username');

        if (empty($s_uname)) {
            $result['code'] = 3;
            $result['msg']  = '签名不能为空';
            echo json_encode($result);
            exit;
        } 

        // 先查询用户是否已签到
        $rec = M('sign_record')->where("`CS_ID` = '$cs_id' AND `USER_NAME` = '$s_uname'")->find();
        $result = array();
        if(!empty($rec)) {
            $result['code'] = 2;
            $result['msg']  = '您已签到过，欢迎参加此次会议！';
        } else {
            $cur_time = date('Y-m-d H:i:s', time());
            $add_res = M('sign_record')->add(array('CS_ID' => $cs_id, 'USER_NAME' => $s_uname, 'SIGN_TIME' => $cur_time));
            $result['code'] = 1;
            $result['msg']  = '签到成功，欢迎参加此次会议';
        }

        echo json_encode($result);
    }

    /*
     *  申请页面
     */
    public function addConferenceSign() {


        $this->display();
    }

    /*
     *  对每个申请进行时间处理，以获取对应在li中的height和top值
     *  返回起止时间段内的height top值数组
     */
    private function getDayHourOrHourMinList($application, $ti_start_time, $ti_end_time) {
        $app_start_time = $application['start_time'];
        $app_end_time   = $application['end_time'];
        
        // 设置当前这个item的颜色值
        $app_obj_color = $this->getAppItemColor($app_start_time, $app_end_time);

        // 对开始时间在段内、结束时间在段外 | 开始时间在段外、结束时间在段内 进行处理，Y-m-d H:i:s
        $tab_start_time = $this->transTimeToFullFormat($ti_start_time);
        $tab_end_time   = $this->transTimeToFullFormat($ti_end_time);
        
        // 根据tab页的起止段，设置app的起止时间
        $new_app_times = $this->getAppStartEndTimeInTab($app_start_time, $app_end_time, $tab_start_time, $tab_end_time);
        
        $app_start_time = $new_app_times[0];
        $app_end_time   = $new_app_times[1];

        // 获取用于循环tab中时间段的起止时间
        $app_items_start_end_values = $this->getComputeAppItemsIteratorStartToEndIndex($app_start_time, $app_end_time);

        // 设置MONTH | DAY | WEEK 模式下的计算top height的除法分母60 | 24
        $div_num = $this->getAppItemDivisonBaseNum();

        

        return $this->getAppItemsHeightTopColorList($app_items_start_end_values, $app_obj_color, $div_num);
    }

    /*
     *  根据参数p_time，获取tab页的名称，比如月2017-08 | 日2017-10-31
     *  这里获取4个，方便进行当月|日的申请查询
     */
    private function getCurDate($p_time) {
        // 获取到tab页的展示类型 月份或者日期
        $tab_type = C('TIME_ITEM_TYPE');
        $three_tab_arr = array();
        if(empty($p_time)) {
            if($this->t_item_type == C('TIME_ITEM_MONTH') || $this->t_item_type == C('TIME_ITEM_DAY')) {
                $cur_time = date('Y-m-d H:i:s',time());
            } elseif($this->t_item_type == C('TIME_ITEM_WEEK')) {
                $cur_time = date('Y-W', time());
            }
        }else {
            $cur_time = $p_time;
        }

        if($this->t_item_type == C('TIME_ITEM_MONTH') || $this->t_item_type == C('TIME_ITEM_DAY')) {
            $cur_time = strtotime($cur_time);
            $cur_year   = date('Y', $cur_time);     // 当前年    
            $cur_month  = date('m', $cur_time);     // 当前月份
            $cur_day    = date('d', $cur_time);     // 当前天
        } elseif($this->t_item_type == C('TIME_ITEM_WEEK')) {
            $cur_time_arr = explode('-', $cur_time);
            $cur_year   = $cur_time_arr[0];
            $cur_week   = $cur_time_arr[1];
        }

        $num = C('TAB_NUM') + 1;
        if($this->t_item_type == C('TIME_ITEM_MONTH')) {
            for($i = 0; $i < $num; $i++) {
                $cur_item   = $cur_year . '-' . $cur_month;   // 加1个月
                $day_num    = $this->getMonthDayNums($cur_year, $cur_month);
                if($cur_month == 12) {
                    $cur_year   += 1;
                    $cur_month   = 1;
                } else {
                    $cur_month  += 1;
                }
                
                $three_tab_arr[] = array("name" => $cur_item, "day_num" => $day_num);
                // 对可能的1-9进行补零操作
                $cur_year   = str_pad($cur_year,    2,"0",STR_PAD_LEFT);
                $cur_month  = str_pad($cur_month,   2,"0",STR_PAD_LEFT);
                $cur_day    = str_pad($cur_day,     2,"0",STR_PAD_LEFT);
            }
        } elseif($this->t_item_type == C('TIME_ITEM_DAY')) {
            for($i = 0; $i < $num; $i++) {
                $cur_item = $cur_year . '-' . $cur_month . '-' . $cur_day;    // 加一天
                $three_tab_arr[] = array("name" => $cur_item);
                // 经过+1天后的新日期
                $new_date_arr = $this->getPlusDay($cur_year, $cur_month, $cur_day);
                // 对可能的9进行补零操作
                $cur_year   = str_pad($new_date_arr[0],2,"0",STR_PAD_LEFT);
                $cur_month  = str_pad($new_date_arr[1],2,"0",STR_PAD_LEFT);
                $cur_day    = str_pad($new_date_arr[2],2,"0",STR_PAD_LEFT);
            }
        } elseif($this->t_item_type == C('TIME_ITEM_WEEK')) {
            for($i = 0; $i < $num; $i++) {
                $cur_item           = $cur_year . ' 第' . $cur_week . '周';             // 加一周
                $cur_item_short     = $cur_year . '-' . $cur_week; 
                $three_tab_arr[]    = array("name" => $cur_item, "name_short" => $cur_item_short);
                // 经过+1周后的新日期
                $new_date_arr = getPlusWeek($cur_year, $cur_week);

                $cur_year   = str_pad($new_date_arr[0],2,"0",STR_PAD_LEFT);
                $cur_week   = $new_date_arr[1];
            }
        }

        return $three_tab_arr;
    }

    /*
     *  补齐时间日期格式为完整的YYYY-mm-dd HH:ii:ss
     *  参数形式为 YYYY-mm | YYYY-mm-dd
     */
    private function checkDateFormat($date){
        //匹配日期格式
        if (preg_match (C('DATE_FORMAT_PATTERN'), $date, $parts)){
            //检测是否为日期
            /*
            if(checkdate($parts[2],$parts[3],$parts[1])) {
                return true;
            }else {
                return false;
            }
            */
            /*
            if(C('TIME_ITEM_TYPE') == C('TIME_ITEM_MONTH') || C('TIME_ITEM_TYPE') == C('TIME_ITEM_DAY')) {
                $new_date = date('Y-m-d H:i:s', strtotime($date));
            } elseif(C('TIME_ITEM_TYPE') == C('TIME_ITEM_WEEK')) {
                $new_date = $date;
                //$new_date = getWeekStartAndEnd($year, $month);
                \Think\Log::write("new_date => " . dump($new_date, false));
            }*/
            return $date;
        } else {
            return false;
        }
    }

    /*
     *  获取完整格式的时间格式
     */
    private function transTimeToFullFormat($date) {
        $new_date = date('Y-m-d H:i:s', strtotime($date));
        return $new_date;
    }

    /*
     *  获取申请对象的键值对
     *  如果参数为空，即不提供键，将返回第一个申请对象
     */
    private function getDefaultAppObj($p_app_obj_key = null){
        $objs = C('APPLICATION_OBJ');
        if(empty($objs)) {
            return false;
        }
        if(empty($p_app_obj_key)) {
            $index = 0;
            foreach($objs as $key=>$value) {
                if($index == 0) {
                    return array('key'=>$key, 'value'=>$value);
                }
            }
        } else {
            $tmp_value = $objs[$p_app_obj_key];
            if($tmp_value) {
                return array('key'=>$p_app_obj_key, 'value'=>$tmp_value);
            }else {
                return false;
            }
        }
        
        return false;
    }

    /*
     * 暂不检测
    private function checkdate($year, $month, $day) {
        $result = true;
        if((!empty($year) && $year >= 2017)) {
            $result &= true;
        } else {
            $result &= false;
        }

        $month = (int)($month);
        if(!($month) && $month >= 0 && $month <= 1) {
            $result = true;
        } else {

        }
    }
    */

    /*
     *  计算某一天的下一天的时间
     *  根据月份不同 闰年等情况进行计算
     *  返回下一天的年-月-日值
     */
    private function getPlusDay($year, $month, $day) {
        for($i=1; $i<=4; $i++) {
            if(in_array($month, C('MONTH_LIST_'.$i))) {
                if($i == 1) {
                    if($day == 31) {
                        $day = 1;
                        $month += 1;
                    } else {
                        $day += 1;
                    }
                }
                if($i == 2) {
                    if($day == 30) {
                        $day = 1;
                        $month += 1;
                    } else {
                        $day += 1;
                    }
                }
                if($i == 3) {
                    if($year % 4 != 0) {
                        if($day == 28) {
                            $day = 1;
                            $month += 1;
                        } else {
                            $day += 1;
                        }
                    } else {
                        if($day == 29) {
                            $day = 1;
                            $month += 1;
                        } else {
                            $day += 1;
                        }
                    }
                }
                if($i == 4) {
                    if($day == 31) {
                        $year += 1;
                        $month = 1;
                        $day = 1;
                    } else {
                        $day += 1;
                    }
                }
                break;
            }
        }

        return array($year, $month, $day);
    }

    /*
     *  获取一个datetime类型的年月日时分秒
     */
    private function getYMDHIS($datetime) {
        $cur_time = strtotime($datetime);
        $cur_year   = date('Y', $cur_time);     // 当前年    
        $cur_month  = date('m', $cur_time);     // 当前月份
        $cur_day    = date('d', $cur_time);     // 当前天
        $cur_hour   = date('H', $cur_time);
        $cur_min    = date('i', $cur_time);

        return array($cur_year, $cur_month, $cur_day, $cur_hour, $cur_min);
    }

    /*
     *  检查开始时间是否在一天的有效小时段内
     *  param:$datetime = array( Y, m, d, H, i)
     */
    private function checkValideStartHour(&$datetime) {
        if(!is_array($datetime)) {
            return false;
        }
        $vld_day_hour_start = C('DAY_HOUR_START');

        $hour   = $datetime[3];
        $min    = $datetime[4];

        if($hour < $vld_day_hour_start) {
            $datetime[3] = str_pad($vld_day_hour_start,2,"0",STR_PAD_LEFT);
            $datetime[4] = str_pad(0,2,"0",STR_PAD_LEFT);
        }
    }
    
    /*
     *  检查结束时间是否在一天的有效小时段内
     *  param:$datetime = array( Y, m, d, H, i)
     */
    private function checkValideEndHour(&$datetime) {
        if(!is_array($datetime)) {
            return false;
        }
        $vld_day_hour_end   = C('DAY_HOUR_END');

        $hour   = $datetime[3];
        $min    = $datetime[4];

        if($hour > $vld_day_hour_end) {
            $datetime[3] = str_pad($vld_day_hour_end,2,"0",STR_PAD_LEFT);
            $datetime[4] = str_pad(0,2,"0",STR_PAD_LEFT);
        }
    }
    
    /*
     *  获取某个月的天数
     */
    public function getMonthDayNums($year, $month) {
        // 循环4个月份队列
        for($i=1; $i<=4; $i++) {
            if(in_array($month, C('MONTH_LIST_'.$i))) {
                if($i == 1) {   // 1 3 5 7 8 10 
                    $day_num = 31;
                }
                if($i == 2) {   // 4 6 9 11
                    $day_num = 30;
                }
                if($i == 3) {   // 2
                    if($year % 4 != 0) {
                        $day_num = 28;
                    } else {
                        $day_num = 29;
                    }
                }
                if($i == 4) {   // 12
                    $day_num = 31;
                }
                break;
            }
        }

        return $day_num;
    }

    /*
     *  当前tab的时间段内，处理app的起止时间在tab的时间段内的起止时间，只处理2,3,4三种情况即可
     *  *号表示是时间段 
     *  1.  |___*****___|   => |___*****___|
     *  2.  **|***______|   => |***______|
     *  3.  |______***|**   => |______***|__
     *  4.  ***|*****|***   => ___|*****|___
     */
    private function getAppStartEndTimeInTab($app_start_time, $app_end_time, $tab_start_time, $tab_end_time) {
        $new_app_start_time = $app_start_time;
        $new_app_end_time   = $app_end_time;
        $app_start_time_arr = $this->getYMDHIS($app_start_time);
        if($this->t_item_type == C('TIME_ITEM_MONTH')) {
            //  |______***|**   => |______***|__
            if ($app_start_time >= $tab_start_time && $app_end_time >= $tab_end_time) {
                // 设置到月末最后一天
                $app_cur_month_day  = $this->getMonthDayNums($app_start_time_arr[0], $app_start_time_arr[1]);
                $app_cur_month_day  = str_pad($app_cur_month_day,2,"0",STR_PAD_LEFT);
                $new_app_end_time   = date("Y-m", strtotime($tab_end_time) -10).'-'.$app_cur_month_day . ' 23:59:59'; 
            } 
            //  **|***______|   => |***______|
            elseif ($app_start_time < $tab_start_time && $app_end_time > $tab_start_time && $app_end_time < $tab_end_time){
                $new_app_start_time   = $tab_start_time;
            } 
            //  ***|*****|***   => ___|*****|___
            elseif ($app_start_time < $tab_start_time && $app_end_time > $tab_end_time ) {
                
                $new_app_start_time   = $tab_start_time;
                $tab_month  = date('m', strtotime($tab_start_time));
                $tab_year   = date('Y', strtotime($tab_start_time));
                $app_cur_month_day  = $this->getMonthDayNums($tab_year, $tab_month);
                $app_cur_month_day  = str_pad($app_cur_month_day,2,"0",STR_PAD_LEFT);
                $new_app_end_time    = date("Y-m", strtotime($tab_end_time) -10).'-'.$app_cur_month_day . ' 23:59:59'; 
            }
        }
        // 比如查看11月的app,有一个app 的起止时间为 2017-11-09 08:00 至 2017-11-11 09:00，那就设置结束时间为2017-11-09 22:00 
        elseif($this->t_item_type == C('TIME_ITEM_DAY')) {
            if ($app_start_time >= $tab_start_time && $app_end_time >= $tab_end_time) {
                // 设置到当前的22点
                $new_app_end_time     = date("Y-m-d", strtotime($tab_start_time)) . ' ' . C('DAY_HOUR_END') .':00:00'; 
            } elseif ($app_start_time < $tab_start_time && $app_end_time > $tab_start_time  && $app_end_time < $tab_end_time){
                // 设置到当前的8点
                $new_app_start_time   = date("Y-m-d", strtotime($tab_start_time)) . ' ' . str_pad(C('DAY_HOUR_START'),2,"0",STR_PAD_LEFT) .':00:00';
            } elseif ($app_start_time < $tab_start_time && $app_end_time > $tab_end_time) {
                // 设置到当前的8点
                $new_app_start_time   = date("Y-m-d", strtotime($tab_start_time)) . ' ' . C('DAY_HOUR_START').':00:00';
                // 设置到当前的22点
                $new_app_end_time     = date("Y-m-d", strtotime($tab_start_time)) . ' ' . C('DAY_HOUR_END') .':00:00'; 
            }
        }
        // 比如查看47周(11-20日~11-26日)的app,有一个app的起止时间为2017-11-17到2017-11-28，那就要设置结束时间为2017-11-19
        elseif($this->t_item_type == C('TIME_ITEM_WEEK')) {
            // |______***|**   => |______***|__
            if ($app_start_time >= $tab_start_time && $app_end_time >= $tab_end_time) {
                // 设置到当前周最后一天
                $tab_end_time_sjc = strtotime($tab_end_time) - 1;   // 减去1秒
                $tab_end_time_tmp = date('Y-m-d H:i:s', $tab_end_time_sjc);
                $new_app_end_time     = $tab_end_time_tmp; 
            } 
            //  **|***______|   => |***______|
            elseif ($app_start_time < $tab_start_time && $app_end_time > $tab_start_time  && $app_end_time < $tab_end_time){
                // 设置到当前的8点
                $new_app_start_time   = $tab_start_time;
            } 
            // //  ***|*****|***   => ___|*****|___
            elseif ($app_start_time < $tab_start_time && $app_end_time > $tab_end_time) {
                // 设置到当前的8点
                $new_app_start_time   = $tab_start_time;
                // 设置到当前的22点
                $tab_end_time_sjc = strtotime($tab_end_time) - 1;   // 减去1秒
                $tab_end_time_tmp = date('Y-m-d H:i:s', $tab_end_time_sjc);
                $new_app_end_time     = $tab_end_time_tmp; 
            }
        }

        return array($new_app_start_time, $new_app_end_time);
    }

    /*
     *  设置item的颜色，检查与当前时间的比较
     */
    private function getAppItemColor($app_start_time, $app_end_time) {
        $app_obj_color  = '';
        $cur_datetime   = '';
        if($this->t_item_type == C('TIME_ITEM_DAY')) {
            $cur_datetime = date('Y-m-d H:i', time());
        } elseif($this->t_item_type == C('TIME_ITEM_MONTH')) {
            $cur_datetime = date('Y-m-d H', time());
        } elseif($this->t_item_type == C('TIME_ITEM_WEEK')) {
            $cur_datetime = date('Y-m-d H', time());
        }
        // 对申请在当前时间下的状态进行颜色设置
        // a.当前时间大于结束时间,表示结束，使用红色
        if($cur_datetime > $app_end_time) {
            $app_obj_color = C('APP_OBJ_STATUS_END');
        }
        // b.当前时间大于开始时间，小于结束时间，表示正在进行，使用绿色
        elseif($cur_datetime >= $app_start_time && $cur_datetime <= $app_end_time) {
            $app_obj_color = C('APP_OBJ_STATUS_GO');
        }
        // c.当前时间小于开始时间，表示预约中，使用蓝色
        elseif($cur_datetime < $app_start_time) {
            $app_obj_color = C('APP_OBJ_STATUS_ORDER');
        }

        return $app_obj_color;
    }

    /*
     *  设置item的除余基数，根据MONTH | DAY 不同，设置为24 或 60
     */
    private function getAppItemDivisonBaseNum() {
        $div_num = 60.0;
        if($this->t_item_type == C('TIME_ITEM_DAY')) {
            $div_num = 60.0;
        } elseif($this->t_item_type == C('TIME_ITEM_MONTH')) {
            $div_num = 24.0;
        } elseif($this->t_item_type == C('TIME_ITEM_WEEK')) {
            $div_num = 24.0;
        }

        return $div_num;
    }

    /*
     *  设置计算item的for循环的起止值
     *  MONTH | DAY 模式不同
     */
    private function getComputeAppItemsIteratorStartToEndIndex($app_start_time, $app_end_time) {
        $app_start_time_arr = $this->getYMDHIS($app_start_time);
        $app_end_time_arr   = $this->getYMDHIS($app_end_time);

        if($this->t_item_type == C('TIME_ITEM_DAY')) {
            // 获取配置的一天起止时间
            $day_hour_start = C('DAY_HOUR_START');
            $day_hour_end   = C('DAY_HOUR_END');

            // 对起止时间进行检查
            $this->checkValideStartHour($app_start_time_arr);
            $this->checkValideEndHour($app_end_time_arr);

            $app_start_time_obj_1   = $app_start_time_arr[3];   // 时
            $app_start_time_obj_2   = $app_start_time_arr[4];   // 分

            $app_end_time_obj_1     = $app_end_time_arr[3];
            $app_end_time_obj_2     = $app_end_time_arr[4];
        } elseif($this->t_item_type == C('TIME_ITEM_MONTH')) {
            $app_start_time_obj_1   = $app_start_time_arr[2];   // 天
            $app_start_time_obj_2   = $app_start_time_arr[3];   // 时

            $app_end_time_obj_1     = $app_end_time_arr[2];
            $app_end_time_obj_2     = $app_end_time_arr[3];
        } elseif($this->t_item_type == C('TIME_ITEM_WEEK')) {
            // 获取星期几
            $app_start_time_obj_week_1  = date("w",strtotime($app_start_time));
            $app_end_time_obj_week_1    = date("w", strtotime($app_end_time));

            $app_start_time_obj_week_1  = $app_start_time_obj_week_1 == 0 ? 7:$app_start_time_obj_week_1;
            $app_end_time_obj_week_1    = $app_end_time_obj_week_1 == 0 ? 7:$app_end_time_obj_week_1;

            $app_start_time_obj_1   = $app_start_time_obj_week_1;   // 天
            $app_start_time_obj_2   = $app_start_time_arr[3];   // 时

            $app_end_time_obj_1     = $app_end_time_obj_week_1;
            $app_end_time_obj_2     = $app_end_time_arr[3];
        }

        return array($app_start_time_obj_1, $app_start_time_obj_2, $app_end_time_obj_1, $app_end_time_obj_2);
    }

    /*
     *  计算item在对应段间的height top color值，返回列表
     */
    private function getAppItemsHeightTopColorList($app_items_start_end_values, $app_obj_color, $div_num) {
        $app_start_time_obj_1   = $app_items_start_end_values[0];
        $app_start_time_obj_2   = $app_items_start_end_values[1];
        $app_end_time_obj_1     = $app_items_start_end_values[2];
        $app_end_time_obj_2     = $app_items_start_end_values[3];

        $day_hour_or_hour_min_list = array();
        $interval = C('ITEM_INTERVAL');
        $k = $div_num / $interval;

        // MONTH 同一个天 | DAY 同一个小时
        if($app_start_time_obj_1 != $app_end_time_obj_1) {
            for($j=$app_start_time_obj_1;$j<=$app_end_time_obj_1;$j++) {
                // 获取间隔
                // 开始时间在第几个小段内
                $sub_item_dot_index = 0;
                // 2017-11-07 01:00 ~~~ 2017-11-08 17:00
                // 首个元素 比如 2017-11-07 01:00 ~~ 23:00
                if($j == $app_start_time_obj_1) {
                    // 检查开始时间落在那个subitem中，并加入到list中
                    for($m = 1; $m <= $k; $m++) {
                        $sub_item_dot_time = $m * $interval; // 12点
                        // 检查落在第几小段中
                        if($app_start_time_obj_2 <= $sub_item_dot_time) {
                            $sub_item_dot_index = $m;
                            break;
                        } 
                    }
                    // 开始时间超过小段的时间 9:00-12:00
                    $sub_item_nums  = $sub_item_dot_index * $interval;
                    $color_height   = (($sub_item_nums - $app_start_time_obj_2) / $interval) * $this->li_height;
                    $color_top      = (($interval - ($sub_item_nums - $app_start_time_obj_2))/$interval)*$this->li_height;
                    
                    $j_tmp = (int)($j) * $k + $sub_item_dot_index;   // 9*2+0
                    $day_hour_or_hour_min_list[$j_tmp] = array('height' => $color_height, 'top' => $color_top, 'color' => $app_obj_color);

                    // 2017-11-07 12:00 ~~ 23:00
                    // 再处理被完全占满的subitem
                    for($o=$sub_item_dot_index+1; $o<=$k; $o++) {
                        $color_height   = $this->li_height;
                        $color_top      = 0;
                        
                        $j_tmp = (int)($j) * $k + $o;   // 9*2+0
                        $day_hour_or_hour_min_list[$j_tmp] = array('height' => $color_height, 'top' => $color_top, 'color' => $app_obj_color);
                    }
                } 
                // 针对最后一个item处理  2017-11-08 00:00 ~~ 23:00
                elseif($j == $app_end_time_obj_1) {
                    for($m = 1; $m <= $k; $m++) {
                        $sub_item_dot_time = $m * $interval; // 12点
                        // 检查落在第几小段中
                        if($app_end_time_obj_2 <= $sub_item_dot_time) {
                            $sub_item_dot_index = $m;
                        } 
                    }
                    $left_sub_item_num  = ($sub_item_dot_index-1) * $interval;
                    $color_height       = (($app_end_time_obj_2 - $left_sub_item_num)/$interval) * $this->li_height;
                    $color_top          = 0;

                    $j_tmp = (int)($j) * $k + $sub_item_dot_index;   // 14*2+0
                    $day_hour_or_hour_min_list[$j_tmp] = array('height' => $color_height, 'top' => $color_top, 'color' => $app_obj_color);

                    for($o=1; $o<$sub_item_dot_index; $o++) {
                        $color_height   = $this->li_height;
                        $color_top      = 0;
                        
                        $j_tmp = (int)($j) * $k + $o;   // 14*2+1
                        $day_hour_or_hour_min_list[$j_tmp] = array('height' => $color_height, 'top' => $color_top, 'color' => $app_obj_color);
                    }
                } 
                // 针对中间的item处理，对subitem全部占满
                else {
                    for($m=1; $m<=$k; $m++) {
                        $color_height   = $this->li_height;
                        $color_top      = 0;
                        
                        $j_tmp = (int)($j) * $k + $m;   // 9*2+1
                        $day_hour_or_hour_min_list[$j_tmp] = array('height' => $color_height, 'top' => $color_top, 'color' => $app_obj_color);
                    }
                }
            }
        } 
        // 在同一个item中，比如2017-11-15 09:00 ~~~ 2017-11-15 21:00
        else {
            $j = $app_start_time_obj_1;
            // 检查开始时间落在那个subitem中
            $m = 1;
            for($m = 1; $m <= $k; $m++) {
                
                $sub_item_dot_time = $m * $interval; // 12点
                // 检查落在第几小段中
                if((int)($app_start_time_obj_2) <= $sub_item_dot_time) {
                    $sub_item_dot_index = $m;
                    break;
                } 
            }
            
            // 检查结束时间落在哪个subitem中
            $n = 1;
            for($n = 1; $n <= $k; $n++) {
                $sub_item_dot_time = $n * $interval; // 12点
                // 检查落在第几小段中
                if((int)($app_end_time_obj_2) <= $sub_item_dot_time) {
                    $sub_item_dot_index = $n;
                    break;
                } 
            }

            if($m != $n) {
                // 开始时间超过小段的时间 9:00-12:00
                $sub_item_nums  = $m * $interval;
                $color_height   = (($sub_item_nums - $app_start_time_obj_2) / $interval) * $this->li_height;
                $color_top      = (($interval - ($sub_item_nums - $app_start_time_obj_2))/$interval)*$this->li_height;
                
                $j_tmp = (int)($j) * $k + $m;   // 9*2+0
                $day_hour_or_hour_min_list[$j_tmp] = array('height' => $color_height, 'top' => $color_top, 'color' => $app_obj_color);

                // 结束时间超过小段的时间 12:00-17:00
                $sub_item_nums  = ($n-1) * $interval;
                $color_height   = (($app_end_time_obj_2 - $sub_item_nums)/$interval) * $this->li_height;
                $color_top      = 0;

                $j_tmp = (int)($j) * $k + $n;   // 14*2+0
                $day_hour_or_hour_min_list[$j_tmp] = array('height' => $color_height, 'top' => $color_top, 'color' => $app_obj_color);

                // 中间，全部填满
                for($o = $m+1; $o < $n; $o++) {
                    $color_height   = $this->li_height;
                    $color_top      = 0;
                    
                    $j_tmp = (int)($j) * $k + $o;   // 12*2+1
                    $day_hour_or_hour_min_list[$j_tmp] = array('height' => $color_height, 'top' => $color_top, 'color' => $app_obj_color);
                }
            } else {
                $color_height   = ($app_end_time_obj_2 - $app_start_time_obj_2)/$interval * $this->li_height;
                $color_top      = ($app_start_time_obj_2/$interval) * $this->li_height;

                $j_tmp = (int)($j) * $k + $m;
                $day_hour_or_hour_min_list[$j_tmp] = array('height' => $color_height, 'top' => $color_top, 'color' => $app_obj_color);
            }
        }

        return $day_hour_or_hour_min_list;
    }

    /*
     *  根据标签名 2017-01 | 2017-46 | 2017-12-20
     *  获取这个段内的起止时间
     */
    private function getTabStartAndEndTime($start_tab_item, $next_tab_item) {
        if($this->t_item_type == C('TIME_ITEM_MONTH') || $this->t_item_type == C('TIME_ITEM_DAY')) {
            $ti_start_time  = $start_tab_item['name'];
            $ti_end_time    = $next_tab_item['name'];
        } elseif($this->t_item_type == C('TIME_ITEM_WEEK')) {
            $cur_year_week_arr  = explode('-', $start_tab_item['name_short']);
            $next_year_week_arr = explode('-', $next_tab_item['name_short']);

            $cur_week_start_end_time_arr    = getWeekStartAndEnd($cur_year_week_arr[0], $cur_year_week_arr[1]);
            $next_week_start_end_time_arr   = getWeekStartAndEnd($next_year_week_arr[0], $next_year_week_arr[1]);

            $ti_start_time = $cur_week_start_end_time_arr['start'];
            $ti_end_time   = $next_week_start_end_time_arr['start'];
        }

        return array('start_time' => $ti_start_time, 'end_time' => $ti_end_time);
    }

    public function test() {
        /*
        $arr = getWeekStartAndEnd(2017, 52);
        echo "time = " . dump($arr, false);

        echo "<br/>";

        test();
    
        header("Content-type:text/html;charset=utf-8");
        date_default_timezone_set("Asia/Shanghai");
        $year = (int)$_GET['year'];
        $week = (int)$_GET['week'];
        $weeks = date("W", mktime(0, 0, 0, 12, 29, 2019));
        echo $year . '年一共有' . $weeks . '周<br />';
        */

        $a = 0.5;
        $b = 1 / $a;
        $c = 22;
        if ($c > 20) {
            echo "小于20<br/>" ;
        } elseif($c > 40) {
            echo "小于40<br/>";
        }
        echo "a =  " . $b;
    }
}
