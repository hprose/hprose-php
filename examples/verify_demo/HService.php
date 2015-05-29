<?php
include ('hprose/Hprose.php');
class HService {
	const CLASS_PREFIX = 'HS';
    const API_PREFIX = 'api';
    const APP_ID = 'appId';
    const APP_KEY = 'appKey';
	protected $_appid = null;
	protected $_sign = null;
	protected $_timestamp = null;
	protected $_module = null;

	protected function verify(){
		//验证应用ID是否有存在并通过appid在从数据库中提取应用的Key
        if($this->_appid != self::APP_ID){
            return "appId error";
        }

		//判断时间戳是否比上次访问大
		if($this->_timestamp < $_SESSION['timestamp']){
            return 'timestamp error';
		}

		//加密后跟sign对比,客户端sign也必须以此加密
		$oSign = sha1(self::APP_KEY.$this->_timestamp);
		if($oSign !== $this->_sign){
            return 'sign error';
		}

		//纪录本次使用的签名，用来跟下次签名对比，防止重复使用
		$_SESSION['sign'] = $oSign;
		$_SESSION['timestamp'] = $this->_timestamp;

		return true;
	}
	
	public function run($module, $methodName, $pramas=null, $sign=null){
		$this->_appid = $sign['appid'];
		$this->_sign = $sign['sign'];
		$this->_timestamp = $sign['timestamp'];
		$this->_module = self::CLASS_PREFIX . ucfirst($module);

		// //如果没有通过接口安全验证返回FALSE
		if($this->verify() !== true){
			return $this->verify();
		}

		#如果你框架有自动加载机制请注释掉以下代码
		include $this->_module . ".php";
		#如果你框架有自动加载机制请注释掉以上代码

		//判断请求模块是否存在
		if(!class_exists($this->_module)){
			return 'module error';
		}

		//实例化服务模型
		$cls = new $this->_module;

		//初始化api名称,判断请求方法是否存在
		$methodName = self::API_PREFIX . ucfirst($methodName);
		if(!method_exists($cls, $methodName)){
			return 'method error';
		}

		//判断参数是否数组
		if(empty($pramas) || !is_array($pramas)){
			$pramas = [];
		}
		return call_user_func_array([$cls,$methodName],$pramas);
	}
}


$service = new HproseHttpServer();
$service->add(new HService());
$service->start();

