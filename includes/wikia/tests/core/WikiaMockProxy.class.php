<?php

/**
 * MockProxy class to encapsulate the runkit functionality that allows us to override static constructors
 *
 * It manages the construction of objects and function mapping from a generic MockProxy wrapper to the specific Mock object we are using
 *
 * _mockClassName is used by __call to figure out what the original class is because get_class() and get_called_class() dont have it
 * $instances is an array of class name -> mock instance mappings
 *   Title => Mock_Title (String=>object)
 *   Mock_Title_2e631b09 => Mock_Title (String=>object)
 *
 * @author Władysław Bodzek <wladek@wikia-inc.com>
 */
class WikiaMockProxy {

	const CLASS_CONSTRUCTOR = 'constructor';
	const STATIC_METHOD = 'static_method';
	const DYNAMIC_METHOD = 'dynamic_method';
	const GLOBAL_FUNCTION = 'global_function';

	const PROP_STATE = 'state';
	const PROP_ACTION = 'action';

	const SAVED_PREFIX = '_saved_';

	/**
	 * Active WikiaMockProxy instance
	 *
	 * @var WikiaMockProxy
	 */
	public static $instance;

	protected $enabled = false;

	protected $mocks = array();

	/**
	 * Get a handler for class constructor
	 *
	 * @param $className string Class name
	 * @return WikiaMockProxyAction
	 */
	public function getClassConstructor( $className ) {
		return $this->get( array(
			'type' => self::CLASS_CONSTRUCTOR,
			'className' => $className,
		), array(
			'functionName' => '__construct',
		) );
	}

	/**
	 * Get a handler for static method/constructor
	 *
	 * @param $className string Class Name
	 * @param $methodName string Method name
	 * @return WikiaMockProxyAction
	 */
	public function getStaticMethod( $className, $methodName ) {
		// PLATFORM-280 - make sure a static method is mocked
		if (!is_callable("{$className}::{$methodName}")) {
			throw new WikiaException("Only static methods can be mocked via WikiaBaseTest::mockClass - got {$className}::{$methodName}");
		}

		return $this->get( array(
			'type' => self::STATIC_METHOD,
			'className' => $className,
			'methodName' => $methodName
		) );
	}

	/**
	 * Get a handler for regular method
	 *
	 * @param $className string Class Name
	 * @param $methodName string Method name
	 * @return WikiaMockProxyAction
	 */
	public function getMethod( $className, $methodName ) {
		return $this->get( array(
			'type' => self::DYNAMIC_METHOD,
			'className' => $className,
			'functionName' => $methodName
		) );
	}

	/**
	 * Get a handler for global function
	 *
	 * @param $functionName string Function name
	 * @return WikiaMockProxyAction
	 */
	public function getGlobalFunction( $functionName ) {
		return $this->get( array(
			'type' => self::GLOBAL_FUNCTION,
			'functionName' => $functionName
		) );
	}

	/**
	 * Get a handler for given event
	 *
	 * @param $type string Event type
	 * @param $params mixed Event params
	 * @return WikiaMockProxyAction
	 */
	protected function get( $baseData, $extraData = array() ) {
		$type = $baseData['type'];
		$id = implode('|',$baseData);

		if ( empty( $this->mocks[$type][$id] ) ) {
			$action = new WikiaMockProxyAction($type,$id,$this,
				array_merge( $baseData, $extraData ));
			// no need to update state of this action here because all actions start as inactive
			// action sends notification when it's being configured
			$this->mocks[$type][$id] = array(
				self::PROP_STATE => false,
				self::PROP_ACTION => $action,
			);
		}
		return $this->mocks[$type][$id][self::PROP_ACTION];
	}

	protected function retrieve( $type, $params = null ) {
		$id = implode('|',func_get_args());

		if ( !isset( $this->mocks[$type][$id] ) ) {
			return false;
		}

		return $this->mocks[$type][$id][self::PROP_ACTION];
	}

	/**
	 * (internal use only)
	 *
	 * @param WikiaMockProxyAction $action
	 */
	public function notify( WikiaMockProxyAction $action ) {
		$type = $action->getEventType();
		$id = $action->getEventId();
		$currentState = $this->mocks[$type][$id][self::PROP_STATE];
		$desiredState = $this->enabled && $action->isActive();

		if ( $currentState != $desiredState ) {
//			var_dump(($desiredState ? 'enable' : 'disable') . ': ' . $id);
			try {
				$this->updateState( $type, $id, $desiredState );
				$this->mocks[$type][$id][self::PROP_STATE] = $desiredState;
			} catch (Exception $e) {
				echo $e->getMessage() . PHP_EOL;
				echo $e->getTraceAsString() . PHP_EOL;
			}
		}
	}

	protected function updateState( $type, $id, $state ) {
//		var_dump([$type, $id, $state]);
		$parts = explode('|',$id);
		switch ($type) {
			case self::STATIC_METHOD:
			case self::DYNAMIC_METHOD:
				$className = $parts[1];
				$methodName = $parts[2];
				$savedName = self::SAVED_PREFIX . $methodName;
				$typeText = $type === self::STATIC_METHOD ? '::' : '->';
				if ( $state ) { // enable
					echo "\n[MOCK   ] $className$typeText$methodName\n";
					is_callable( "{$className}::{$methodName}" );
					if ( method_exists( $className, $savedName ) ) {
						throw new Exception("Cannot override a function twice");
					}
					$flags = ZEND_ACC_PUBLIC | ( $type == self::STATIC_METHOD ? ZEND_ACC_STATIC : 0);
					$newMethod = $this->getExecuteClosure($type,$id, $type === self::DYNAMIC_METHOD);
					if ( method_exists( $className, $methodName ) ) {
						echo "\n[UBACKUP] $className$typeText$methodName\n";
						$flags = uopz_flags($className, $methodName, ZEND_ACC_FETCH);
						uopz_function($className, $savedName, $newMethod, $flags, false);
						uopz_rename($className, $methodName, $savedName);
					} else {
						uopz_function($className, $methodName, $newMethod, $flags, true);
					}
				} else { // disable
					echo "\n[RESTORE] $className$typeText$methodName\n";
					if ( method_exists( $className, $savedName ) ) {
						echo "\n[UBACKUP] $className$typeText$methodName\n";
						echo "\n[RESTORE] (rename) $className $savedName $methodName\n";
						uopz_rename( $className, $savedName, $methodName );
						uopz_delete( $className, $savedName );
						uopz_restore( $className, $methodName );
					} else {
						uopz_delete
						echo "\n[RESTORE] (delete) $className $methodName\n";
						uopz_delete( $className, $methodName );
					}
				}
				break;
			case self::GLOBAL_FUNCTION:
				$functionName = $parts[1];
				list($namespace,$baseName) = self::parseGlobalFunctionName($functionName);
				$functionName = $namespace . $baseName;
				$savedName = $namespace . self::SAVED_PREFIX . $baseName;
				if ( $state ) { // enable
					echo "\n[MOCK   ] $functionName\n";
					if ( function_exists($savedName) ) {
						throw new Exception("Cannot override a function twice");
					}
					if ( function_exists($functionName) ) {
						uopz_rename($functionName, $savedName);
					}
					$tempName = "WikiaMockProxyTempFuncName"; // workaround for namespaces functions
//					uopz_function($tempName, $this->getExecuteClosure($type,$id));
//					uopz_rename($tempName,$functionName);
					uopz_function($functionName, $this->getExecuteClosure($type,$id));
				} else { // disable
					echo "\n[RESTORE] $functionName\n";
					if ( function_exists($savedName) ) {
						uopz_rename($savedName, $functionName); // restore the original
						uopz_delete($savedName);
					} else {
						uopz_delete($functionName);
					}
				}
				break;
		}
	}

	protected function getExecuteCallCode( $type, $id, $passThis = false ) {
		$replace = array( '\'' => '\\\'', '\\' => '\\\\' );
		$type = strtr($type,$replace);
		$id = strtr($id,$replace);

		$passThisCode = $passThis ? ',$this' : '';
		return "return WikiaMockProxy::\$instance->execute('{$type}','{$id}',func_get_args(){$passThisCode});";
	}

	protected function getExecuteClosure( $type, $id, $passThis = false ) {
//		$code = $this->getExecuteCallCode($type, $id, $passThis);
//		return create_function('', $code);
		return WikiaMockProxy_executeClosure($type, $id, $passThis);
	}

	public static function parseGlobalFunctionName( $functionName ) {
		$last = strrpos($functionName,'\\');
		if ( $last === false ) {
			return [ '', $functionName ];
		} else {
			return [ ltrim( substr( $functionName, 0, $last + 1 ), '\\' ), substr( $functionName, $last + 1 ) ];
		}
	}

	/**
	 * (internal use only)
	 * Execute the specified action
	 *
	 * @param $type string Event type
	 * @param $id string Event ID
	 * @param $args array Arguments
	 * @return mixed Return value
	 * @throws Exception
	 */
	public function execute( $type, $id, $args, $context = null ) {
		if ( !isset($this->mocks[$type][$id]) ) {
			throw new Exception("WikiaMockProxy did not find action definition for: \"{$type}/{$id}\"");
		}

		/** @var $action WikiaMockProxyAction */
		$action = $this->mocks[$type][$id][self::PROP_ACTION];
		return $action->execute($args,$context);
	}

	public function callOriginalGlobalFunction( $functionName, $args ) {
		$invocationOptions = array(
			'functionName' => $functionName,
			'arguments' => $args,
		);
		return (new WikiaMockProxyInvocation($invocationOptions))->callOriginal();
	}

	public function callOriginalMethod( $object, $functionName, $args ) {
		$invocationOptions = array(
			'object' => $object,
			'functionName' => $functionName,
			'arguments' => $args,
		);
		return (new WikiaMockProxyInvocation($invocationOptions))->callOriginal();
	}

	public function callOriginalStaticMethod( $className, $functionName, $args ) {
		$invocationOptions = array(
			'className' => $className,
			'functionName' => $functionName,
			'arguments' => $args,
		);
		return (new WikiaMockProxyInvocation($invocationOptions))->callOriginal();
	}

	public function enable() {
		if ( !empty( self::$instance ) ) {
			if ( self::$instance == $this ) {
				return;
			}
			throw new Exception("Another WikiaMockProxy is already enabled");
		}

		// enable this instance
		self::$instance = $this;
		$this->enabled = true;
		uopz_overload(ZEND_NEW, 'WikiaMockProxy::overload');
//		var_dump(['uopz_overload', 'install']);
		foreach ($this->mocks as $list1) {
			foreach ($list1 as $type => $mock) {
				$this->notify($mock[self::PROP_ACTION]);
			}
		}
	}

	public function disable() {
		if ( self::$instance != $this ) {
			if ( self::$instance == null ) {
				return;
			}
			throw new Exception("Another WikiaMockProxy is enabled now");
		}
//		var_dump('disable start');

		// disable this instance
		$this->enabled = false;
		foreach ($this->mocks as $list1) {
			foreach ($list1 as $type => $mock) {
//				var_dump('notify: ' . (string)$mock[self::PROP_ACTION]);
				$this->notify($mock[self::PROP_ACTION]);
			}
		}
		uopz_overload(ZEND_NEW, null);
//		var_dump(['uopz_overload', 'uninstall']);
		self::$instance = null;
//		var_dump('disable finish');

		foreach ([
					 'DatabaseBase' => 'base  ',
					 'DatabaseMysqlBase' => 'mysqlb',
					 'DatabaseMysqli' => 'mysqli'
				 ] as $className => $text) {
			$methodReflection = new ReflectionMethod( $className, 'selectRow' );
			$status = $methodReflection->getName();
			$flags = $methodReflection->isStatic() ? 's' : 'd';
			echo "[DSTATUS $text] $flags $status\n";
		}
	}

	// Because overload is called _immediately_ before the __construct function
	// we can use a static instance to hold the instance of whatever class we are overloading
	// We have to do this because overload returns a string with the class name and not an object (grr)
	static public function overload(&$className) {
//		var_dump($className);
		/** @var $action WikiaMockProxyAction */
		if ( self::$instance
			&& ($action = self::$instance->retrieve(self::CLASS_CONSTRUCTOR,$className) )
			&& $action->isActive()
		) {
			$type = $action->getEventType();
			$id = $action->getEventId();
			try {
				$className = self::$instance->execute($type,$id,array());
			} catch (Exception $e) {
				echo "WikiaMockProxy::overload: caught exception: {$e->getMessage()}\n";
				echo $e->getTraceAsString();
				echo "\n";
			}
		}
	}

}


function WikiaMockProxy_executeClosure( $type, $id, $passThis = false ) {
	return function() use ($type, $id, $passThis) {
		echo "\n[CALL   ] $id\n";
		debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$instance = WikiaMockProxy::$instance;
//		var_dump(['execute',$type, $id, func_get_args()]);
		if ( $passThis ) {
			return $instance->execute($type, $id, func_get_args(), $this );
		} else {
			return $instance->execute($type, $id, func_get_args() );
		}
	};
}