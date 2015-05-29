<?php
/**
 * 需要发布的接口类
 */
class HSModule{
	/**
	 * 以api开头的public方法将被自动发布
	 */
	public function apiMethod($params){
		return $params;
	}
}