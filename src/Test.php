<?php

namespace BlackFox2;

abstract class Test {

	/** @var string name of the set of tests */
	public $name = 'Unknown tests';

	/** @var array $tests key — method name, value — test description */
	public $tests = [];

	public function __construct() {
		$ReflectionClass = new \ReflectionClass(static::class);
		$this->name = $this->name ?: $ReflectionClass->getName();
		$methods = $ReflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
		foreach ($methods as $method) {
			if (substr($method->name, 0, 4) <> 'Test') {
				continue;
			}
			$comment = $method->getDocComment();
			if (!$comment) {
				$this->tests[$method->name] = $method->name;
			} else {
				$this->tests[$method->name] = trim(substr($comment, 3, -2));
			}
		}
		reset($this->tests);
	}

	/**
	 * @param $test
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public function Invoke($test) {
		return (new \ReflectionMethod(static::class, $test))->invoke($this);
	}

	public function Run($test) {
		$time1 = microtime(true);
		try {
			$result = [
				'STATUS' => 'SUCCESS',
				'RESULT' => $this->Invoke($test),
			];
		} catch (Exception $error) {
			$result = [
				'STATUS' => 'FAILURE',
				'RESULT' => $error->getMessages(),
			];
		} catch (\Exception $error) {
			$result = [
				'STATUS' => 'FAILURE',
				'RESULT' => $error->getMessage(),
			];
		}
		$time2 = microtime(true);
		$result['TIME'] = ceil(($time2 - $time1) * 10) / 10;
		return $result;
	}

	public function RunNext() {
		$test = key($this->tests);
		if ($test === null) return false;
		$result = $this->Run($test);
		$result['CODE'] = $test;
		$result['NAME'] = $this->tests[$test];
		next($this->tests);
		return $result;
	}

	public function RunAll() {
		$results = [];
		foreach ($this->tests as $test => $name) {
			$results[$test] = $this->Run($test);
			$results[$test]['CODE'] = $test;
			$results[$test]['NAME'] = $name;
		}
		return $results;
	}

	public function RunAllForClient() {
		echo "Test: {$this->name}\r\n";
		echo "\r\n";
		$success = 0;
		$failure = 0;
		while ($result = $this->RunNext()) {
			if ($result['STATUS'] === 'SUCCESS') {
				echo "SUCCESS - {$result['NAME']} ({$result['TIME']})\r\n";
				$success++;
			} else {
				echo "FAILURE - {$result['NAME']} ({$result['TIME']}):\r\n";
				echo is_array($result['RESULT']) ? print_r($result['RESULT'], true) : "\t" . $result['RESULT'];
				echo "\r\n";
				$failure++;
			}
		}
		echo "\r\n";
		echo "Summary: {$success} SUCCESSES | {$failure} FAILURES \r\n";
		echo "=======================================================================================\r\n";
	}
}