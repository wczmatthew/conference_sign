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
        \Think\Log::write('_POST => ' . dump($_POST, false));
        $cs_id      = I('cs_id');
        $s_uname    = I('sign_username');

        $cs_info = M('conference')->where("`CS_ID` = '$cs_id'")->find();

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
            $sign_status = 1;
            if($cs_info['level'] > 0) {
                $sign_status = 2;
            }
            $add_res = M('sign_record')->add(array('CS_ID' => $cs_id, 'USER_NAME' => $s_uname, 'SIGN_TIME' => $cur_time, 'SIGN_STATUS' => $sign_status));
            \Think\Log::write('签到成功 => ' . dump($add_res, false));
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
        $this->assign('conf_sign_list', $conf_sign_list);
        $this->display();
    }

    /*
     *  某个会议签到申请详情页
     */
    public function confSignDetail() {
        $cs_id = I('cs_id');

        if(empty($cs_id)) {
            $this->error("数据有误，请检查！");
        }

        $cs_info = M('conference')->where("`CS_ID` = $cs_id")->find();
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

        // 总的参会人数
        $total_cs_users = M('pre_sign_users')->where("`CS_ID` = $cs_id")->count();;
        $this->assign('total_cs_users',     $total_cs_users);
        // 与会人数
        $total_yh_cs_users = M('sign_record')->where("`CS_ID` = '$cs_id' ")->count();
        $this->assign('total_yh_cs_users',   $total_yh_cs_users);

        $this->display();
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
