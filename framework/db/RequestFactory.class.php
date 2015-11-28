<?php
require_once FRAMEWORK . '/db/TCRequest.class.php';

class RequestFactory {
	/**
	 *
	 * @param AppConfig $config
	 * @return TCGetRequest
	 */
	public static function createGetRequest(AppConfig $config){
		require_once FRAMEWORK . '/db/request/TCGetRequest.class.php';
		return new TCGetRequest($config);
	}
	/**
	 *
	 * @param AppConfig $config
	 * @return TCInsertRequest
	 */
	public static function createInsertRequest(AppConfig $config){
		require_once FRAMEWORK . '/db/request/TCInsertRequest.class.php';
		return new TCInsertRequest($config);
	}
	/**
	 *
	 * @param AppConfig $config
	 * @return TCUpdateRequest
	 */
	public static function createUpdateRequest(AppConfig $config){
		require_once FRAMEWORK . '/db/request/TCUpdateRequest.class.php';
		return new TCUpdateRequest($config);
	}
	/**
	 *
	 * @param AppConfig $config
	 * @return TCReplaceRequest
	 */
	public static function createReplaceRequest(AppConfig $config){
		require_once FRAMEWORK . '/db/request/TCReplaceRequest.class.php';
		return new TCReplaceRequest($config);
	}
	/**
	 *
	 * @param AppConfig $config
	 * @return TCDeleteRequest
	 */
	public static function createDeleteRequest(AppConfig $config){
		require_once FRAMEWORK . '/db/request/TCDeleteRequest.class.php';
		return new TCDeleteRequest($config);
	}
	/**
	 * 产生一个删除缓存的request
	 * @param AppConfig $config
	 * @return TCPurgeRequest
	 */
	public static function createPurgeRequest(AppConfig $config){
		require_once FRAMEWORK . '/db/request/TCPurgeRequest.class.php';
		return new TCPurgeRequest($config);
	}
	
	/**
	 * 产生一个缓存操作的request
	 * @param AppConfig $config
	 * @return TCCacheRequest
	 */
	public static function createCacheRequest(AppConfig $config){
		require_once FRAMEWORK . '/db/request/TCCacheRequest.class.php';
		return new TCCacheRequest($config);
	}
}

?>