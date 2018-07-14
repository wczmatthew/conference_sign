<?php 
namespace Lib;
/**
* 
*/
class OpenApi 
//实现接口的同时实现jsonSerialize方法，可改写json_encode()的值
//json_encode()不能对对象的protect或private参数进行操作
//implements \JsonSerializable 
{
	public static $type_data = "data";
	public static $type_file = "file";
	public $itype;//接口编号，当为上传文件时为10000
	public $time;//请求时间，格式yyyyMMddHHmmss
	public $cono;//企业微洽号
	public $data;
	public $etype = 1;//加密方式 1-AES 2-BASE64
	public $sign;//MD5签名md5(itype + time + cono + data + private_key)
	public $dtype = 1;//开发者类型:1企业开发者 2平台开发者 
	public $dkey;//开发者key（平台开发者必填）
	public $uploadFile;//文件流（上传文件时使用）,application/octet-stream
	public $mid;
	/**
	 * [__construct description]
	 * @param [type] $cono [description]  
	 * 301
	 * @param [type] $dkey [description] 
	 * 6d21c5a9c38224defebf0bc52064ca5f
	 */
	public function __construct($cono="301",$dkey="6d21c5a9c38224defebf0bc52064ca5f")
	{
		$this->cono = $cono;
		$this->dkey = $dkey;
	}

	public function setForm($itype,$data){
		$this->itype = $itype;
		$data_str = json_encode($data,JSON_UNESCAPED_UNICODE);
		//echo $data_str;
		$encrypt_data = \Lib\EncryptUtils::AESEncrypt($data_str,$this->dkey);
		//var_dump($encrypt_data);
		//var_dump(\Lib\EncryptUtils::AESDecrypt($encrypt_data,$this->dkey));
		$this->data = $encrypt_data;
		
		return $this;
	}


	public function generate(){
		$this->time = date("YmdHis");
		//var_dump($this->time);
		//var_dump($this->itype . $this->time . $this->cono . $this->data . $this->dkey);
		$this->sign = md5($this->itype . $this->time . $this->cono . $this->data . $this->dkey);
		return $this;
	}

	public function request(
		//$type=null,
		$uploadFile=null,$mid=null
		){
		// if($type == null){
		// 	$enctype = \Lib\CurlUtils::$enctype_application;	
		// }
		// else if($type == OpenApi::$type_data){
		// 	$enctype = \Lib\CurlUtils::$enctype_application;	
		// }else if($type == OpenApi::$type_file){
		// 	$enctype == \Lib\CurlUtils::$enctype_multipart;
		// }

		if($uploadFile == null){
			$enctype = \Lib\CurlUtils::$enctype_application;
			$url = "http://10.10.100.95/openapi.do";
		}else{
			$enctype = \Lib\CurlUtils::$enctype_multipart;
			$url = "http://10.10.100.95/openupload.do";
			$this->mid = $mid;
		}
		
		$params["itype"] = $this->itype;
		$params["time"] = $this->time;
		$params["cono"] = $this->cono;
		$params["data"] = $this->data;
		$params["etype"] = $this->etype;//加密方式 1-AES 2-BASE64
		$params["sign"] = $this->sign;//MD5签名md5(itype + time + cono + data + private_key)
		$params["dtype"] = $this->dtype;//开发者类型:1企业开发者 2平台开发者 
		$params["dkey"] = $this->dkey;//开发者key（平台开发者必填）
		$params["uploadFile"] = $uploadFile;
		$params["mid"] =$this->mid;
		//$params["content-type"] = "application/x-www-form-urlencoded";

		$response = \Lib\CurlUtils::httpPost($url,$params,$enctype);

		$result = array();
		\Think\Log::write("register_response:".dump($response,false));
		
		$result["isSuccess"] = $response->success;
		$result["errorMsg"] = $response->error;
		$result["data"] = $response->data;

		return $result;
	}

	 // public function jsonSerialize() {

	 // }


}

 ?>