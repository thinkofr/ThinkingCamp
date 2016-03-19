<?php
namespace Home\Event;

class ErrorEvent {
	
	public function setErrorEvent($errno = 0, $errmsg = "success", $data = array()) {
		$ret = array ();
		$ret ['errno'] = $errno;
		$ret ['errmsg'] = $errmsg;
		$ret ['data'] = array ();
		if (isset($data) && is_array($data)) {
			$ret['data'] = $data;
		}
		return $ret;
	}
}
?>