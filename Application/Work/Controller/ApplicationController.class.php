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

        $merge_users = $this->getAllUsersWithStatus($cs_id);
        $this->assign('merge_users', $merge_users);
        
        if ($cs_level == 0) {
            $this->display();
        } else {
            $this->display("showSignPageWithSecret");
        }
        
    }

    /*
     *  处理签到post
     *  只允许在名单上的用户签到
     */
    public function doConferenceSign() {
        $cs_id      = I('cs_id');
        $s_uname    = I('sign_username');

        $cs_info = M('conference')->where("`CS_ID` = '$cs_id'")->find();

        // 1.检查签名是否为空
        if (empty($s_uname)) {
            $result['code'] = 3;
            $result['msg']  = '签名不能为空';
            echo json_encode($result);
            exit;
        } 

        // 2.检查是否在名单内
        $in_ps_user = M('pre_sign_users')->where("`CS_ID` = '$cs_id' AND `USER_NAME` = '$s_uname'")->find();
        if(empty($in_ps_user)) {
            $result['code'] = 3;
            $result['msg']  = '您不在与会人员名单内';
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
            $sign_status = 1;
            if($cs_info['level'] > 0) {
                $sign_status = 2;
            }
            $add_res = M('sign_record')
                ->add(array('CS_ID' => $cs_id, 
                        'USER_NAME' => $s_uname, 
                        'SIGN_TIME' => $cur_time, 
                      'SIGN_STATUS' => $sign_status));

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
     *  处理申请页面
     */
    public function doAddConferenceSign() {
        $add_res = M('conference')->add($_POST);

        $result = array();
        $result['code']     = 0;
        $result['msg']      = '会议签到创建失败，请检查填写内容或与管理员联系！';
        if($add_res) {
            $result['code']     = 1;
            $result['msg']      = '会议签到创建成功！';
        }
        echo json_encode($result);
    }

    /*
     *  申请列表
     */
    public function conferenceSignList() {
        $conf_sign_list = M('conference')
            ->order('start_time desc')
            ->select();

        foreach($conf_sign_list as $key => $cs) {
            $cs_id = $cs['cs_id'];
            $cs_start_time = $cs['start_time'];

            // 统计已签到人员
            $condition1 = array("`CS_ID` = $cs_id AND `SIGN_STATUS` > 0");
            $had_cs_total   = M('sign_record')->where($condition1)->count();

            // 统计迟签到人员
            $condition2 = array("`CS_ID` = $cs_id AND `SIGN_TIME` > '$cs_start_time'");
            $late_cs_total  = M('sign_record')->where($condition2)->count();

            // 统计名单人员总数
            $condition3 = array("`CS_ID` = $cs_id");
            $cs_total = M('pre_sign_users')->where($condition3)->count();

            // 统计未签到人员
            $un_cs_total = $cs_total - $had_cs_total;

            $conf_sign_list[$key]['cs_total']       = $cs_total;
            $conf_sign_list[$key]['had_cs_total']   = $had_cs_total;
            $conf_sign_list[$key]['late_cs_total']  = $late_cs_total;
            $conf_sign_list[$key]['un_cs_total']    = $un_cs_total;
        }
        \Think\Log::write("conf_sign_list => " . dump($conf_sign_list, false));
        $this->assign('conf_sign_list', $conf_sign_list);
        $this->display();
    }

    /*
     *  某个会议签到申请详情页
     */
    public function confSignDetail() {
        $cs_id = I('cs_id');
        $this->assign('cs_id', $cs_id);
        
        if(empty($cs_id)) {
            $this->error("数据有误，请检查！");
        }

        $cs_info = M('conference')->where("`CS_ID` = $cs_id")->find();
        $this->assign('cs_info', $cs_info);

        $cs_pre_users = $this->getAllUsersWithStatus($cs_id);
        $this->assign('cs_pre_users', $cs_pre_users);

        // 总的参会人数
        //$total_cs_users = M('pre_sign_users')->where("`CS_ID` = $cs_id")->count();
        $total_cs_users = count($cs_pre_users);
        $this->assign('total_cs_users',     $total_cs_users);
        // 与会人数
        $total_yh_cs_users = M('sign_record')->where("`CS_ID` = '$cs_id' ")->count();
        $this->assign('total_yh_cs_users',   $total_yh_cs_users);

        $this->display();
    }

    /*
     *  获取某一会议签到 pre_sign_users & sign_record表中的所有用户
     *  这里也可以先将 预与会人员查出来，先全部将SIGN_STATUS 设置为0，然后与已签到表中的用户合并
     *  只允许 预与会人员名单中的用户才可以签到
     */
    private function getAllUsersWithStatus($cs_id) {
        // 2.查出与会人员名单
        // 2.1 先从预 与会人查，并与已签到记录表进行匹配
        $cs_pre_users = M('pre_sign_users')
            ->where("`CS_ID` = '$cs_id'")
            ->getField('USER_NAME, USER_NAME, CS_ID');
        if(!empty($cs_pre_users)) {
            foreach ($cs_pre_users as $key => $user) {
                $cs_uname = $user['user_name'];
                $sr_user = M('sign_record')->where("`CS_ID` = '$cs_id' AND `USER_NAME` = '$cs_uname'")->find();
                if(!empty($sr_user)) {
                    $cs_pre_users[$key]['sign_status'] =  $sr_user['sign_status'];
                }else {
                    $cs_pre_users[$key]['sign_status'] =  0;
                }
            }
        }
        
        return $cs_pre_users;
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

        echo "<br/>";
        $arr1 = null;
        //$arr2 = array('a' => 'a2', 'c' => 'c2');
        $arr2 = array(
            '王程展' => array(
                'user_name' => '王程展',
                'cs_id' => 3,
                'sign_status' => 2
            ),
        );

        print_r(array_merge($arr1, $arr2));

        echo "<br/>------------------------------<br/>";
        $cs_id = 2;

        $cs_pre_users = M('pre_sign_users')
            ->where("`CS_ID` = '$cs_id'")
            ->getField('USER_NAME, USER_NAME, CS_ID');
        foreach ($cs_pre_users as $key => $user) {
            $cs_uname = $user['user_name'];
            $sr_user = M('sign_record')->where("`CS_ID` = '$cs_id' AND `USER_NAME` = '$cs_uname'")->find();
            if(!empty($sr_user)) {
                $cs_pre_users[$key]['sign_status'] =  $sr_user['sign_status'];
            }else {
                $cs_pre_users[$key]['sign_status'] =  0;
            }
        }
        print_r("<br/>预 与会人员 => " . dump($cs_pre_users, false));
        // 2.2 从已签到表中将不在名单中的人加入
        $sr_users = M('sign_record')
            ->where("`CS_ID` = '$cs_id'")
            //->field('CS_ID, USER_NAME')
            ->getField('USER_NAME, USER_NAME, CS_ID, SIGN_STATUS');
        print_r("<br/>签到人员 => " . dump($sr_users, false));

        $merge_users = array_merge($cs_pre_users, $sr_users);
        print_r("<br/>合并人员 => " . dump($merge_users, false));
    }
}
