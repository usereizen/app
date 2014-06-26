<?php
/**
 * BaseTask
 *
 * provides common functionality for tasks
 *
 * @author Nelson Monterroso <nelson@wikia-inc.com>
 */

namespace Wikia\Tasks\Tasks;

use Wikia\Logger\Loggable;
use Wikia\Tasks\AsyncTaskList;
use Wikia\Tasks\Queues\PriorityQueue;

abstract class BaseTask {
	use Loggable;

	/** @var array calls this task will make */
	protected $calls = [];

	/** @var int when running, the user id of the user who is running this task. */
	protected $createdBy;

	/** @var \Title title this task is operating on */
	protected $title = null;

	/** @var array params needed to instantiate $this->title. */
	protected $titleParams = [];

	/** @var string when running, this task's id, or id of the task that this task is a subtask of */
	protected $taskId;

	/** @var string wrapper for AsyncTaskList->queue() */
	private $queueName = null;

	/** @var int wrapper for AsyncTaskList->wikiId() */
	private $wikiId = AsyncTaskList::DEFAULT_WIKI_ID;

	/** @var boolean wrapper for AsyncTaskList->dupCheck() */
	private $dupCheck = false;

	/** @var string wrapper for AsyncTaskList->delay() */
	private $delay = null;

	/**
	 * Do any additional work required to restore this class to its previous state. Useful when you want to avoid
	 * inserting large, serialized classes into rabbitmq
	 */
	public function init() {
		if (empty($this->titleParams)) {
			return;
		}

		$this->title = \Title::makeTitleSafe($this->titleParams['namespace'], $this->titleParams['dbKey']);
		if ( $this->title == null ) {
			throw new \Exception( "unable to instantiate title with id {$this->titleParams['dbKey']}" );
		}
	}

	/**
	 * set this task to call a method in this class. the first argument to this method should be the method to execute,
	 * and subsequent arguments are arguments passed to that method. Example: call('add', 2, 3) would call the method
	 * add with 2 and 3 as parameters.
	 *
	 * @return array [$this, order in which this call should be made]
	 * @throws \InvalidArgumentException when the first argument doesn't exist as a method in this class
	 */
	public function call(/** method, arg1, arg2, ...argN */) {
		$args = func_get_args();
		$method = array_shift($args);

		if (!method_exists($this, $method)) {
			throw new \InvalidArgumentException;
		}

		$this->calls []= [$method, $args];

		return [$this, count($this->calls) - 1];
	}

	/**
	 * execute a method in this class
	 *
	 * @param string $method the method to execute
	 * @param array $args arguments to pass to $method
	 * @return \Exception|mixed the results of calling the method with the supplied arguments, or the exception
	 * 	thrown when executing that method
	 * @throws \InvalidArgumentException when the method doesn't exist in this class
	 */
	public function execute($method, $args) {
		if (!method_exists($this, $method)) {
			throw new \InvalidArgumentException;
		}


		try {
			$result = call_user_func_array([$this, $method], $args);
		} catch (\Exception $e) {
			$result = $e;
		}

		return $result;
	}

	/**
	 * get a method call from the calls array
	 *
	 * @param int $index
	 * @return array [method, [args to method]]
	 * @throws \InvalidArgumentException when trying to get an undefined index
	 */
	public function getCall($index) {
		if (!isset($this->calls[$index])) {
			throw new \InvalidArgumentException;
		}

		return $this->calls[$index];
	}

	/**
	 * @return array black list of method names to hide on Special:Tasks
	 */
	public function getAdminNonExecuteables() {
		return ['__construct', 'init', 'getAdminNonExecuteables'];
	}

	public function createdBy($createdBy=null) {
		if ($createdBy !== null) {
			$this->createdBy = $createdBy;
		}

		return $this;
	}

	/**
	 * convenience method wrapping AsyncTaskList
	 *
	 * @return string|array the task's id or array of such IDs if the given wikiID is an array
	 */
	public function queue() {
		$taskLists = $this->convertToTaskLists();
		$taskIds = AsyncTaskList::batch($taskLists);

		return count($taskIds) == 1 ? $taskIds[0] : $taskIds;
	}

	/**
	 * convert this task to its AsyncTaskList(s) representation. A BaseTask will convert to multiple AsyncTaskList
	 * objects if $this->wikiId is an array
	 *
	 * @return array AsyncTaskList objects
	 */
	private function convertToTaskLists() {
		$wikiIds = (array) $this->wikiId;
		$taskLists = [];

		foreach ($wikiIds as $wikiId) {
			$taskList = new AsyncTaskList();

			foreach ($this->calls as $i => $call) {
				$taskList->add([$this, $i]);
			}

			$taskList->wikiId( $wikiId );

			if ($this->queueName) {
				$taskList->setPriority($this->queueName);
			}

			if ($this->dupCheck) {
				$taskList->dupCheck();
			}

			if ($this->delay) {
				$taskList->delay($this->delay);
			}

			if ($this->createdBy) {
				$taskList->createdBy($this->createdBy);
			}

			$taskLists[] = $taskList;
		}

		return $taskLists;
	}

	/**
	 * serialize this class so it can be read by celery
	 *
	 * @return array
	 */
	public function serialize() {
		$mirror = new \ReflectionClass($this);
		$result = [
			'class' => get_class($this),
			'calls' => $this->calls,
			'context' => [
				'titleParams' => $this->titleParams,
			]
		];

		$propertyMask = \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PUBLIC;
		foreach ($mirror->getProperties($propertyMask) as $property) {
			if ($property->class == 'Wikia\\Tasks\\Tasks\\BaseTask') {
				continue;
			}

			$property->setAccessible(true);
			$value = $property->getValue($this);
			$result['context'][$property->name] = is_object($value) ? serialize($value) : $value;
		}

		return $result;
	}

	/**
	 * unserialize this class's context
	 *
	 * @param $properties
	 * @param $calls
	 */
	public function unserialize($properties, $calls) {
		$mirror = new \ReflectionClass($this);

		$this->calls = $calls;

		foreach ($properties as $name => $value) {
			if ($mirror->hasProperty($name)) {
				$deserialized = @unserialize($value);
				$value = $deserialized === false ? $value : $deserialized;

				$property = $mirror->getProperty($name);
				$property->setAccessible(true);
				$property->setValue($this, $value);
			}
		}
	}

	/**
	 * set this task to run against a specific article/page/etc
	 * @param \Title $title
	 * @return $this
	 */
	public function title(\Title $title) {
		$this->titleParams = [
			'namespace' => $title->getNamespace(),
			'dbKey' => $title->getDBkey()
		];

		return $this;
	}

	/**
	 * @param $taskId
	 * @return $this
	 */
	public function taskId($taskId) {
		$this->taskId = $taskId;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTaskId() {
		return $this->taskId;
	}

	// following are wrappers that will eventually call the same functions in AsyncTaskList

	/**
	 * @see AsyncTaskList::wikiId
	 * @param $wikiId
	 * @return $this
	 */
	public function wikiId($wikiId) {
		$this->wikiId = $wikiId;
		return $this;
	}

	/**
	 * @see AsyncTaskList::prioritize
	 * @return $this
	 */
	public function prioritize() {
		return $this->setPriority(PriorityQueue::NAME);
	}

	/**
	 * @see AsyncTaskList::setPriority
	 * @param $queueName
	 * @return $this
	 */
	public function setPriority($queueName) {
		$this->queueName = $queueName;
		return $this;
	}

	/**
	 * @see AsyncTaskList::dupCheck
	 * @return $this
	 */
	public function dupCheck() {
		$this->dupCheck = true;
		return $this;
	}

	/**
	 * @see AsyncTaskList::delay
	 * @param $time
	 * @return $this
	 */
	public function delay($time) {
		$this->delay = $time;
		return $this;
	}

	// end AsyncTaskList wrappers

	/**
	 * queue a set of BaseTask objects
	 *
	 * @param array $tasks
	 * @return array task ids
	 */
	public static function batch(array $tasks) {
		$taskLists = [];

		foreach ($tasks as $task) {
			/** @var BaseTask $task $taskLists */
			$taskLists = array_merge($taskLists, $task->convertToTaskLists());
		}

		return AsyncTaskList::batch($taskLists);
	}
}