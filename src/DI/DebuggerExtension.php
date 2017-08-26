<?php
declare(strict_types=1);

namespace Trejjam\Debugger\DI;

use Nette;
use Trejjam;

class DebuggerExtension extends Nette\DI\CompilerExtension
{
	protected $default = [
		'logger'           => [],
		'storeAllError'    => FALSE,
		'exceptionStorage' => NULL,
		'blob'             => [],
	];

	protected $defaultLogger = [
		'mailService' => '@Nette\Mail\IMailer',
		'snoze'       => '1 day',
		'host'        => NULL, //NULL mean auto
		'path'        => '/log/',
	];

	protected $defaultBlob = [
		'client' => NULL,
		'prefix' => NULL,
	];

	protected $config_cache = NULL;

	protected function createConfig() : array
	{
		if ( !is_null($this->config_cache)) {
			return $this->config_cache;
		}

		$config = Nette\DI\Config\Helpers::merge($this->config, $this->default);
		$config['logger'] = Nette\DI\Config\Helpers::merge($config['logger'], $this->defaultLogger);
		$config['blob'] = Nette\DI\Config\Helpers::merge($config['blob'], $this->defaultBlob);

		Nette\Utils\Validators::assert($config, 'array');

		$this->config_cache = $config;

		return $config;
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
								])
				   ->addSetup('setStoreError', [$config['storeAllError']]);

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
