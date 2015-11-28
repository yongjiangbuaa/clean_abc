<?php
class TCCacheRequest extends TCRequest {
	/**
	 *
	 */
	public function execute() {
		return false;
	}
	
	public function get($key){
		return $this->getCacheInstance()->get($key);
	}

	public function set($key,$value,$expire = 3600){
		return $this->getCacheInstance()->set($key,$value,$expire);
	}
	
	public function setMulti(array $pairs,$expire = 3600){
		return $this->getCacheInstance()->setMulti($pairs,$expire);
	}
	
	public function delete($key){
		return $this->getCacheInstance()->delete($key);
	}
	
	public function increment($key,$inc = 1){
		return $this->getCacheInstance()->increment($key,$inc);
	}
	
	public function decrement($key,$inc = 1){
		return $this->getCacheInstance()->decrement($key,$inc);
	}
}

?>