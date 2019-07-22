<?php

class Cls_session{
	function __construct(){
		session_start();
	}
    public function __set($name, $value)
    {
        $_SESSION[$name] = $value;
    }
    public function __get($name){
    	return $_SESSION[$name] ;
    }
}
