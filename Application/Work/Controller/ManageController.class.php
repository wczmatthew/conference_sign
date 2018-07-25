<?php
/*  
 *  申请状态页
 */
namespace Work\Controller;
//use Wap\AppController;
use Think\Controller;

class ManageController extends Controller {

    public function __construct(){
        parent::__construct();
    }

    /*
     *  展示导入名单页面
     */
    public function index() {
    	\Think\Log::write("_GET => " . dump($_GET, false));
    	
    	$this->display();
    }

    public function doAddCSUsersCode() {
    	$code = trim(I('code'));
    	// 1.处理动态码
    	if(empty($code)) {
    		$this->error('动态码不能为空');
    		exit;
    	}

    	$cs_code = M('conference_code')->where("`CODE` = '$code'")->order("`CREATE_TIME` DESC")->find();
    	if(!$cs_code) {
    		$this->error('不存在此动态码');
    		exit;
    	}
    	$cs_id = $cs_code['cs_id'];
    	/*
    	$cur_time = time();
    	$cs_time = strtotime($cs_code['create_time']);
    	if ($cs_time + 60 * 30> $cur_time) {
    		$this->error('动态码有效期(30分钟)已过，请重新生成');
    	}
    	*/
    	
    	// 2. 处理文本
    	$file = $_FILES['userlist_file'];
    	\Think\Log::write('_FILE => ' . dump($file, false));
    	if ($file["error"] > 0) {
    		$this->error('文本上传错误，请重新上传');
    	}

    	$this->isValidFormatFile($file);

    	$des_path_file = $this->saveFile($file, $code, $cs_id);

    	\Think\Log::write("目标路径：" . $des_path_file);

    	// 开始读取文件并进行用户名单输出处理
    	$do_res = $this->doPreSignUsers($des_path_file, $cs_id);

    	if($do_res) {
    		$this->success('导入成功');
    	} else {
    		$this->error('导入失败');
    	}
    }

    private function isValidFormatFile($file) {
    	$file_type = $file['type'];
    	\Think\Log::write('type = ' . $file_type);
    	if ($file_type != 'text/plain' || $this->getFormatPostfix($file) != 'txt') {
    		$this->error('非法文件，请上传txt文本文件');
    	}
    }

    private function saveFile($file, $d_code, $cs_id) {
    	$file_name = $file['name'];
    	$tmp_fname = '' . $cs_id . '_' . $d_code . '.txt';	//31_动态码.txt
    	$des_path_file = "Public/uploads/".$tmp_fname;

    	if (file_exists($des_path_file)) {
    		$this->error('文件已经存在，请检查');
    	} else {
    		move_uploaded_file($file['tmp_name'], $des_path_file);
    	}

    	return $des_path_file;
    }

    private function getFormatPostfix($file) {
    	$file_name = $file['name'];
    	//获得文件扩展名
		$temp_arr = explode(".", $file_name);
		$file_ext = array_pop($temp_arr);
		$file_ext = trim($file_ext);
		$file_ext = strtolower($file_ext);

		return $file_ext;
    }

    private function doPreSignUsers($file_name, $cs_id) {
    	$pre_sign_file = fopen($file_name, "r") or die("unable to open file");
    	$sign_name_arr = array();
    	// 读取每行 处理
    	while(!feof($pre_sign_file)) {
    		$sign_name = fgets($pre_sign_file);
    		\Think\Log::write('sign_name ='.$sign_name.'*');
    		$sign_name = trim(substr($sign_name,0,-1));
    		if($sign_name != null && $sign_name != '') {
    			$sign_name_arr[] = array(
    					'CS_ID' 	=> $cs_id,
    					'USER_NAME'	=> $sign_name);
    		}
    	}
    	fclose($pre_sign_file);
    	$res = M('pre_sign_users')->addAll($sign_name_arr);

    	return $res;
    }
}