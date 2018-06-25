<?php
declare(strict_types=1);

namespace Trejjam\Debugger;

final class FatalErrorHook
{
	public function __invoke(\Throwable $exception)
	{
		if (Debugger::$productionMode === FALSE) {
			Debugger::log($exception, Logger::EXCEPTION);
		}
	}
}
