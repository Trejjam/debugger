<?php
declare(strict_types=1);

namespace Trejjam\Debugger;

use Tracy;
use Trejjam;

class BlueScreen extends Tracy\BlueScreen
{
	/**
	 * @var bool
	 */
	protected $storeError;
	/**
	 * @var Trejjam\Debugger\Exception\IStorage
	 */
	protected $storage;

	/**
	 * @param bool $storeError
	 */
	public function setStoreError(bool $storeError = TRUE)
	{
		$this->storeError = $storeError;
	}

	/**
	 * @param Trejjam\Debugger\Exception\IStorage|NULL $storage
	 */
	public function setLogStorage(Trejjam\Debugger\Exception\IStorage $storage = NULL)
	{
		$this->storage = $storage;
	}

	/**
	 * Renders blue screen.
	 *
	 * @param  \Exception|\Throwable
	 *
	 * @return void
	 */
	public function render($exception)
	{
		if ($this->storeError) {
			if ($exception instanceof \ErrorException) {
				$severity = $exception->getSeverity();

				Debugger::getLogger()
						->log($exception,
							  ($severity & Debugger::$logSeverity) === $severity
								  ? Tracy\ILogger::ERROR
								  : Tracy\ILogger::EXCEPTION
						);
			}
			else {
				Debugger::getLogger()->log($exception, Tracy\ILogger::EXCEPTION);
			}
		}

		parent::render($exception);
	}

	/**
	 * Renders blue screen to file (if file exists, it will not be overwritten).
	 *
	 * @param \Exception|\Throwable $exception
	 * @param string                $file path
	 *
	 * @return void
	 */
	public function renderToFile($exception, $file)
	{
		parent::renderToFile($exception, $file);

		if ( !is_null($this->storage)) {
			$this->storage->persist($file);
		}
	}
}
