<?php

# needs to be run using "php -d xdebug.coverage_enable=0 test_uopz.php new" in make uopz happy

ini_set('display_errors', 1);

$TEST = $argv[1];
$OPT = @$argv[2];

class a {}
class b {}

class TestStatic {
	static public function test() {
		echo "original " . __METHOD__ . PHP_EOL;
	}
}

class TestStatic2 extends TestStatic {

}

class TestAncestry1 {

}

class TestAncestry2 {
	public function test() {
		echo __METHOD__ . PHP_EOL;
	}
}

abstract class xyz1 {
	public function test() {
		echo __METHOD__ . PHP_EOL;
	}
}
abstract class xyz2 extends xyz1 {}
class xyz3 extends xyz2 {}
class yyy1 extends xyz1 {}
class yyy2 extends xyz2 {}
class yyy3 extends xyz3 {}

switch ($TEST) {
	case 'new':
		uopz_set_mock( a::class, b::class );

		assert( uopz_get_mock( a::class ) == b::class );

		$x = new a();
		var_dump( $x );

		assert( $x instanceof b );

		uopz_unset_mock( a::class );

		assert( new a() instanceof a );
		break;
	case 'static':
	case 'static2':
		$className = 'TestStatic';
		$runName = $TEST === 'static' ? 'call_static' : 'call_static2';
		$methodName = 'test';
		$savedName = 'savedTest';
		function call_static() {
			TestStatic::test();
			call_user_func(['TestStatic', 'test']);
			$t = new TestStatic();
			$t->test();
			echo method_exists('TestStatic', 'test') ? 'exists' : 'does not exist';
			echo "\n";
		}
		function call_static2() {
			TestStatic2::test();
			call_user_func(['TestStatic2', 'test']);
			$t = new TestStatic2();
			$t->test();
			echo method_exists('TestStatic2', 'test') ? 'exists' : 'does not exist';
			echo "\n";
		}
		call_static();

		uopz_set_return($className, $methodName, function() {
			echo "mocked TestStatic::test\n";
		}, true /* execute closure */);

		echo "### Mock in place\n";
		call_static();

		uopz_unset_return($className, $methodName);

		echo "### Mock removed\n";
		call_static();
		break;
	case 'ancestry':
		uopz_function('TestAncestry2', 'test', function() {
			echo "mocked TestAncestry2->test()\n";
		});
		(new TestAncestry2())->test();
//		(new TestAncestry1())->test();

		break;
	case 'deep':
		(new yyy1())->test();
		(new yyy2())->test();
		(new xyz3())->test();
		uopz_function('xyz1','test2',uopz_copy('xyz1','test'));
		uopz_backup('xyz1','test');
		uopz_function('xyz1','test',function() {
			echo "mocked xyz1->test()\n";
		});
		(new yyy1())->test();
		(new yyy2())->test();
		(new xyz3())->test();
		(new yyy1())->test2();
		(new yyy2())->test2();
		(new xyz3())->test2();
		uopz_restore('xyz1','test');
		uopz_delete('xyz1','test2');
		(new yyy1())->test();
		(new yyy2())->test();
		(new xyz3())->test();
		(new yyy1())->test2();
		(new yyy2())->test2();
		(new xyz3())->test2();
		break;
}
