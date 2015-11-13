<?php

class Overloader {
	static public function overload( &$className ) {
		if ($className == 'a') $className = new b();
	}
}

class a {}
class b {}

uopz_overload(ZEND_NEW, 'Overloader::overload' );

$x = new a();
var_dump($x);

uopz_overload(ZEND_NEW, null);