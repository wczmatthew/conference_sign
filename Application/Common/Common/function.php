<?php 
/**
 * 通过http get的方式向服务器端请求数据
 * @param string $url 请求链接
 * @return object 
 */
    function httpGet($url){

    	$hasParameter = strpos($url,"?");
    	if($hasParameter === false){
    		$url = $url.'?client='.myCookie('client');
    		$url = $url.'&sysId='.C('SYS_ID');
        $userId = myCookie('userId');
    		if(!empty($userId)&&strpos($url,'userId')===false){
          $url = $url.'&userId='.$userId;
        }
    	}else{
    		$url = $url.'&client='.myCookie('client');
        $userId = myCookie('userId');
        if(!empty($userId)&&strpos($url,'userId')===false){
          $url = $url.'&userId='.$userId;
        }
    	}
    \Think\Log::write('httpGet.url:'.$url);
    	//初始化
		$ch = curl_init();
		//设置选项，包括URL
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT,5);

		
		//执行并获取HTML文档内容
		$output = curl_exec($ch);
		//释放curl句柄
		curl_close($ch);
		\Think\Log::write('output:'.$output,'INFO');

		$responseBlock = explode("\r\n\r\n",$output);
		$response_body = $responseBlock[count($responseBlock)-1];

		\Think\Log::write('body:'.$response_body,'INFO');

		$msg = json_decode($response_body);

		return $msg;
    }
/**
 * 通过http post的方式向服务器端请求数据
 * @param string $url 请求链接
 * @param array $post_data 请求参数数组
 * @return object 
 */
    function httpPost($url,$post_data){
    	$post_data['client'] = MyCookie('client');
    	$post_data['sysId'] = C('SYS_ID');
      $userId = myCookie('userId');
      if(!empty($userId)&&strpos($url,'userId')===false){
    	 $post_data['userId'] = $userId;
      }


		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT,5);
		// post数据
		curl_setopt($ch, CURLOPT_POST, 1);
		// post的变量
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);


		$output = curl_exec($ch);
		curl_close($ch);
		\Think\Log::write('output:'.$output,'INFO');

		$responseBlock = explode("\r\n\r\n",$output);
		$response_body = $responseBlock[count($responseBlock)-1];

		\Think\Log::write('body:'.$response_body,'INFO');


		$msg = json_decode($response_body);
		return $msg;
    }

    // function myCookie($cookieName){
    // 	return cookie($cookieName);
    // }
    // function myCookie($cookieName,$cookieValue){
    // 	return cookie($cookieName,$cookieValue);
    // }
  	function myCookie($cookieName,$cookieValue='',$options=null){
      $key = 'df5a6812aad5a32d321a23d5545e643a';
      //$cookieName = AESEncrypt($cookieName,$key);
      \Think\Log::write('encrypt_cookie_name:'.$cookieName,'INFO');
      if(!empty($cookieValue)){
        $cookieValue = AESEncrypt($cookieValue,$key);
        \Think\Log::write('encrypt_cookie_value:'.$cookieValue,'INFO');
      }
   		if($options === null){
   			if($cookieValue === ''){
          $temp = cookie($cookieName);
          if(!empty($temp)){
            $cookieValueDecrypt = AESDecrypt($temp,$key);
            \Think\Log::write('decrypt_cookie_value:'.$cookieValueDecrypt,'INFO');
            $temp = $cookieValueDecrypt;
          }
          return $temp;
   			}
   			return cookie($cookieName,$cookieValue,259200000);
   		}
      $options['expire'] = 259200000;
   		return cookie($cookieName,$cookieValue,$options);
    }


 function AESEncrypt($plaintext,$old_key){
    # 密钥应该是随机的二进制数据，
    # 开始使用 scrypt, bcrypt 或 PBKDF2 将一个字符串转换成一个密钥
    # 密钥是 16 进制字符串格式
    $key = pack('H*', $old_key);
    
    # 显示 AES-128, 192, 256 对应的密钥长度：
    #16，24，32 字节。
    $key_size =  strlen($key);
    //echo "Key size: " . $key_size . "\n";
    
    //$plaintext = "This string was AES-256 / CBC / ZeroBytePadding encrypted.";

    # 为 CBC 模式创建随机的初始向量
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    

    # 创建和 AES 兼容的密文（Rijndael 分组大小 = 128）
    # 仅适用于编码后的输入不是以 00h 结尾的
    # （因为默认是使用 0 来补齐数据）
    $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key,
                                 $plaintext, MCRYPT_MODE_CBC, $iv);

    # 将初始向量附加在密文之后，以供解密时使用
    $ciphertext = $iv . $ciphertext;
    
    # 对密文进行 base64 编码
    $ciphertext_hex = HEXEncode($ciphertext);
    return rtrim($ciphertext_hex);
  }

  function AESDecrypt($ciphertext_hex,$old_key){
    $key = pack('H*', $old_key);

    # 为 CBC 模式创建随机的初始向量
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    
    # --- 解密 ---
    
    $ciphertext_dec = HEXDecode($ciphertext_hex);
    
    # 初始向量大小，可以通过 mcrypt_get_iv_size() 来获得
    $iv_dec = substr($ciphertext_dec, 0, $iv_size);
    
    # 获取除初始向量外的密文
    $ciphertext_dec = substr($ciphertext_dec, $iv_size);

    # 可能需要从明文末尾移除 0
    $plaintext_dec = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key,
                                    $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
    return rtrim($plaintext_dec);
  }

   function HEXEncode($string){
    $hex='';
    for ($i=0; $i < strlen($string); $i++){
      $temp = dechex(ord($string[$i]));
      //很重要
      if(strlen($temp) == 1){
        $temp = '0'.$temp;
      }
      $hex .= $temp;
    }
    return $hex;
  }

  function HEXDecode($hex){
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2){
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
  }


    /**
  * 获取当前URL
  */
	function getSelfUrl() {
	$secure = (isset($_SERVER["HTTPS"]) && ((strcasecmp($_SERVER["HTTPS"], "on") === 0) || ($_SERVER["HTTPS"] == 1))) || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && (strcasecmp($_SERVER["HTTP_X_FORWARDED_PROTO"], "https") === 0)); $hostInfo = "";

	if ($secure) {
	$http = "https";
	}
	else {
	$http = "http";
	}

	if (isset($_SERVER["HTTP_HOST"])) {
	$hostInfo = $http . "://" . $_SERVER["HTTP_HOST"]; } else { $hostInfo = $http . "://" . $_SERVER["SERVER_NAME"];

	if ($secure) {
	$port = (isset($_SERVER["SERVER_PORT"]) ? (int) $_SERVER["SERVER_PORT"] : 443); } else { $port = (isset($_SERVER["SERVER_PORT"]) ? (int) $_SERVER["SERVER_PORT"] : 80); }

	if ((($port !== 80) && !$secure) || (($port !== 443) && $secure)) { $hostInfo .= ":" . $port; } }

	$requestUri = "";

	if (isset($_SERVER["HTTP_X_REWRITE_URL"])) { $requestUri = $_SERVER["HTTP_X_REWRITE_URL"]; } else if (isset($_SERVER["REQUEST_URI"])) { $requestUri = $_SERVER["REQUEST_URI"];

	if (!empty($_SERVER["HTTP_HOST"])) {
	if (strpos($requestUri, $_SERVER["HTTP_HOST"]) !== false) { $requestUri = preg_replace("/^\w+:\/\/[^\/]+/", "", $requestUri); } } else { $requestUri = preg_replace("/^(http|https):\/\/[^\/]+/i", "", $requestUri); } } else if (isset($_SERVER["ORIG_PATH_INFO"])) { $requestUri = $_SERVER["ORIG_PATH_INFO"];

	if (!empty($_SERVER["QUERY_STRING"])) {
	$requestUri .= "?" . $_SERVER["QUERY_STRING"]; } } else {
	exit("没有获取到当前的url");
	}

	return $hostInfo . $requestUri;
	}
    //获取用获状态
    function userAuth() {
        $url = C('SERVER_URL')."/user/get";
        $res = httpGet($url); 
        $user_auth = "";       
        if($res->code == "200"){
            $user = $res->info;
            $user_auth = $user->state;
        }  
        return $user_auth;
    }


/**
 * 检查权限
 * @param name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
 * @param uid  int           认证用户的id
 * @param relation string    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
 * @return boolean           通过验证返回true;失败返回false
 */
function cp_auth_check($uid,$name=null,$relation='or'){
  if(empty($uid)){
    return false;
  }
  
  $iauth_obj = new \Common\Lib\iAuth();

  //Controller访问权限验证相关  
  if(empty($name)){
    $url=strtolower(MODULE_NAME."/".CONTROLLER_NAME."/".ACTION_NAME);
    $menu_model=M("Menu");
    $res=$menu_model->where(array("URL"=>$url,"SYS_ID"=>C('SYS_ID')))->find();
    if($res==null){//无匹配时,不通过
      return true;
    }else{
      if($res['type']==0){//type=0即 公共功能时
        return true;
      }else{//typ=1时 需权限验证的菜单时

        $name=$res['id'];
      }
    }

  }
  return $iauth_obj->check($uid, $name, $relation);
}


function get_current_user_id(){
  //return $_SESSION['user_id'];
  return session('userId');

}

/**
 * 获取当前登录的管事员id
 * @return int
 */
function cp_get_current_user_id(){
  return get_current_user_id();
}

/**
 * 查询结果按key值排序，解决Chrome读取排序问题
 * @return json
 */
function cp_json($arr){
  json_encode($arr);        
  $list = [];
  foreach ($arr as $k => $v) {
     array_push($list, $v);
  }
  return $list;
}        


/**
 * 获取路径 正泰资讯模块，图片替换
 * @return string
 */
function imgUrlReplace($content = ''){
    if (!empty($content)) {
        $content = str_replace('<img src="/','<img src="',$content);
        $content = str_replace('<img alt="" src="/','<img alt="" src="',$content);
        $content = str_replace('<img src="ueditor/php/upload/image/', '<img src="'.C('CHINTNET_IMG').'/upload/ueditor/image/',$content);
    }
    return $content;
}   

/**
 * 工资数据解密
 * @return string
 */

function salary_decryption($n=""){
  $oname = "";
  try {   
    for($i = 0; $i < strlen($n); $i++) {


    }
  } catch (Exception $e) {   
    //$e->getMessage();   
    exit();   
  }   

}
/**
 * 工资查询密码加密
 * @return string
 */
function paylock($n){
    $oname = "";
    for($i = 0; $i <strlen($n); $i++){
        $intAsciiCode = ord(substr($n,$i,1));
        $intAsciiCode = ($intAsciiCode + 01) ^ 10;
        $strCharacter = chr($intAsciiCode);
        $Oname = $Oname.$strCharacter;
    }
    return $Oname;
}

/**
 * 工资查询密码加密
 * @return string
 */
function payunlock($n){
    $oname = "";
    for($i = 0; $i <strlen($n); $i++){
        $intAsciiCode = ord(substr($n,$i,1));
        $intAsciiCode = ($intAsciiCode - 10) ^ 01;
        $strCharacter = chr($intAsciiCode);
        $Oname = $Oname.$strCharacter;
    }
    return $Oname;
}

function salary_year(){
  $year = date("Y");
  if(date("m")=="01")
    $year = $year-1;
  return $year;
}
function salary_month(){
  $month = (int)date("m");
  $month = $month - 1;
  if($month == 0){
    $month = 12;
  }
  return $month;
}

function full_ygh($str){
  $ygh = "";
  for($i=0;$i<(8-strlen($str));$i++){
      $ygh = "0".$ygh;
  }
  $ygh = $ygh.$str;
  return $ygh;
}

/**
 * 判断是否在微信中打开
 * @return string
 */
function is_weixin(){ 
    if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
        return true;
    }  
        return false;
} 

/* 
 * author wczmatthew
 * 生成微信JS的签名
 * return array
 */
function getWXJsSign() {
  $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
  $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  
  $weObj = new Wap\Wechat();
  $signPackage = $weObj->getJsSign($url);
  return $signPackage;
}

/*
 *
 */
function getYmdHiFormat($type, $stime, $etime) {
    \Think\Log::write("function type => " . $type);
    if($type == C('TIME_ITEM_MONTH')) {
        $syear = substr($stime, 0, 4);
        $eyear = substr($etime, 0, 4);
        if($syear != $eyear) {
            return substr($stime, 0, 13) . ':00 到 ' . substr($etime, 0, 13) . ':00';
        } else {
            return substr($stime, 5, 13) . ':00 到 ' . substr($etime, 5, 13) . ':00';
        }
    } elseif($type == C('TIME_ITEM_DAY')) {
        return substr($stime, 0, 16) . ' 到 ' . substr($etime, 5, 11);
    }
}

/*
 *  根据year 和 week获取这周的起止时间
 */
function getWeekStartAndEnd ($year,$week=1) {
    header("Content-type:text/html;charset=utf-8");
    date_default_timezone_set("Asia/Shanghai");
    $year = (int)$year;
    $week = (int)$week;
    //按给定的年份计算本年周总数
    $date = new DateTime;
    $date->setISODate($year, $week);
    $weeks = max($date->format("W"),52);
    //如果给定的周数大于周总数或小于等于0
    if($week>$weeks || $week<=0){
        return false;
    }
    //如果周数小于10
    if($week<10){
        $week = '0'.$week;
    }
    //当周起止时间戳
    $timestamp['start'] = strtotime($year.'W'.$week);
    $timestamp['end'] = strtotime('+1 week -1 day',$timestamp['start']);
    //当周起止日期
    $timeymd['start'] = date("Y-m-d",$timestamp['start']);
    $timeymd['end'] = date("Y-m-d",$timestamp['end']);
    
    $timeymd['start'] = $timeymd['start'] . ' 00:00:00';
    $timeymd['end']   = $timeymd['end'] . ' 23:59:59';
    //返回起始时间戳
    //return $timestamp;
    //返回日期形式
    return $timeymd;
}

/*
 *  获取下一个周数
 */
function getPlusWeek($year, $week) {
    $week = $week + 1;
    $date = new DateTime;
    $date->setISODate($year, 53);
    $weeks = max($date->format("W"),52);
    //如果给定的周数大于周总数或小于等于0
    if($week>$weeks){
        $year += 1;
        $week = 1;
    }

    return array($year, $week);
}

/*
 *  获取不同模式下的除数
 */
function getAppItemDivisonBaseNum($t_item_type) {
    $div_num = 60.0;
    if($t_item_type == C('TIME_ITEM_DAY')) {
        $div_num = 60.0;
    } elseif($t_item_type == C('TIME_ITEM_MONTH')) {
        $div_num = 24.0;
    } elseif($t_item_type == C('TIME_ITEM_WEEK')) {
        $div_num = 24.0;
    }

    return $div_num;
}
function test() {
    $date = new DateTime;
    $date->setISODate(2017, 1);
    echo "日期 = " . $date->format('Y-m-d') . "<br/>";

}
 ?>
