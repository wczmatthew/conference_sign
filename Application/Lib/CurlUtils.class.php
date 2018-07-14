<?php 
namespace Lib;
/**
* 
*/
class CurlUtils  
{
	
	public static $enctype_application = "Content-Type: application/x-www-form-urlencoded;charset=UTF-8";
	public static $enctype_multipart = "Content-Type: multipart/form-data;charset=UTF-8";

	function __construct()
	{
		# code...
	}

	static function httpGet($url){
    	\Think\Log::write('httpGet.url:'.$url);
    	//初始化
		$ch = curl_init();
		//设置选项，包括URL
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT,5);
		//fiddler代理
		//curl_setopt($ch,CURLOPT_PROXY,'127.0.0.1:8888');
		
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
 * @param array $header 请求头
 * @return object 
 */
    static function httpPost($url,$post_data,$enctype="Content-Type: application/x-www-form-urlencoded;charset=UTF-8"){
    	\Think\Log::write("post_data:".dump($post_data,false),'INFO');
    	if($enctype == CurlUtils::$enctype_application){
    		$data = null;
	    	foreach ($post_data as $key => $value) {
	    		$key = urlencode($key);
	    		$value = urlencode($value);
	    		if($data == null){
	    			$data = $key."=".$value;
	    		}else{
	    			$data .= "&".$key."=".$value;
	    		}
	    	}
	    	$ch = curl_init();
			//var_dump($post_data);
			curl_setopt($ch, CURLOPT_URL, $url);
			//fiddler代理
			//curl_setopt($ch,CURLOPT_PROXY,'127.0.0.1:8888');
			//
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, $enctype);
			curl_setopt($ch, CURLOPT_TIMEOUT,10);
			// post数据
			//curl_setopt($ch, CURLOPT_POST, 1);
			// post的变量		
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    	}else if ($enctype == CurlUtils::$enctype_multipart) {
    		$ch = curl_init();
			//var_dump($post_data);
			curl_setopt($ch, CURLOPT_URL, $url);
			//fiddler代理
			//curl_setopt($ch,CURLOPT_PROXY,'127.0.0.1:8888');
			//
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, $enctype);
			curl_setopt($ch, CURLOPT_TIMEOUT,10);
			// post数据
			//curl_setopt($ch, CURLOPT_POST, 1);
			// post的变量		
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    	}

		$output = curl_exec($ch);
		curl_close($ch);
		\Think\Log::write('output:'.$output,'INFO');

		$responseBlock = explode("\r\n\r\n",$output);
		$response_body = $responseBlock[count($responseBlock)-1];

		\Think\Log::write('body:'.$response_body,'INFO');

		$msg = json_decode($response_body);

		return $msg;
    }
}
?>

    