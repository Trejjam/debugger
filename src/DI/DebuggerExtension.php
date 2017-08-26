<?php
declare(strict_types=1);

namespace Trejjam\Debugger\DI;

use Nette;
use Trejjam;

class DebuggerExtension extends Nette\DI\CompilerExtension
{
	protected $default = [
		'logger'           => [
			'mailService' => '@Nette\Mail\IMailer',
			'snoze'       => '1 day',
			'host'        => NULL, //NULL mean auto
			'path'        => '/log/',
		],
		'exceptionStorage' => NULL,
		'blob'             => [
			'client' => NULL,
			'prefix' => NULL,
		],
	];

	protected function createConfig()
	{
		$this->config += $this->getContainerBuilder()->expand($this->default);

		Nette\Utils\Validators::assert($this->config, 'array');

		return $this->config;
	}

	public function loadConfiguration()
	{
		parent::loadConfiguration();

		$builder = $this->getContainerBuilder();
		$config = $this->createConfig();

		$tracyLogger = $builder->getDefinition('tracy.logger');
		$tracyLogger->setFactory([
									 Trejjam\Debugger\Debugger::class,
									 'getLogger',
								 ])
					->addSetup('setEmailClass', [$config['logger']['mailService']])
					->addSetup('setEmailSnooze', [$config['logger']['snoze']])
					->addSetup('setHost', [$config['logger']['host']])
					->addSetup('setPath', [$config['logger']['path']]);

		$blueScreen = $builder->getDefinition('tracy.blueScreen');
		$blueScreen->setFactory([
									Trejjam\Debugger\Debugger::class,
									'getBlueScreen',
								]);

		if ( !is_null($config['exceptionStorage'])) {
			if ($config['exceptionStorage'] === 'azure') {
				$builder->addDefinition($this->prefix('storage'))
						->setClass(Trejjam\Debugger\Exception\Azure::class)
						->setArguments(
							[
								$config['blob']['client'],
								$config['blob']['prefix'],
							]
						)
						->setAutowired(FALSE);
			}
			else {
				$builder->addDefinition($this->prefix('storage'))
						->setFactory($config['exceptionStorage']);
			}

			$blueScreen->addSetup('setLogStorage', [$this->prefix('@storage')]);
		}
	}
}
