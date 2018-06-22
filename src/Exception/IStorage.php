<?php
declare(strict_types=1);

namespace Trejjam\Debugger\Exception;

interface IStorage
{
	const TYPE_LOG = 'log';

	const HTML_EXT = '.html';

	public function persist(string $localFile) : bool;

	public function getExceptionUrl(string $exceptionFile) : string;
}
