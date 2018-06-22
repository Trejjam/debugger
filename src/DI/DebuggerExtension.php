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
		'autoRun'          => [],
	];

	protected $defaultLogger = [
		'mailService' => '@Nette\Mail\IMailer',
		'snoze'       => '1 day',
		'host'        => NULL, //NULL mean auto
		'path'        => '/log/',
	];

	protected $defaultBlob = [
		'client'                          => NULL,
		'accountName'                     => NULL,
		'prefix'                          => NULL,
		'whitelistIp'                     => NULL,
		'blobSharedAccessSignatureHelper' => NULL,
	];

	protected $defaultAutoRun = [
		'tracyLogger' => TRUE,
		'blueScreen'  => TRUE,
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
		$config['autoRun'] = Nette\DI\Config\Helpers::merge($config['autoRun'], $this->defaultAutoRun);

		Nette\Utils\Validators::assert($config, 'array');
		Nette\Utils\Validators::assert($config['autoRun'], 'bool[]');

		$this->config_cache = $config;

		return $config;
	}

	public function loadConfiguration() : void
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
		$blueScreen->setFactory(
			[
				Trejjam\Debugger\Debugger::class,
				'getBlueScreen',
			]
		)->addSetup('setStoreError', [$config['storeAllError']]);

		if ($config['autoRun']['tracyLogger']) {
			$tracyLogger->addTag('run');
		}
		if ($config['autoRun']['blueScreen']) {
			$blueScreen->addTag('run');
		}

		if ( !is_null($config['exceptionStorage'])) {
			if ($config['exceptionStorage'] === 'azure') {
				$builder->addDefinition($this->prefix('storage'))
						->setFactory(Trejjam\Debugger\Exception\Azure::class)
						->setType(Trejjam\Debugger\Exception\IStorage::class)
						->setArguments(
							[
								$config['blob']['client'],
								$config['blob']['accountName'],
								$config['blob']['prefix'],
								$config['blob']['whitelistIp'],
								$config['blob']['blobSharedAccessSignatureHelper'],
							]
						)
						->setAutowired(FALSE);
			}
			else {
				$builder->addDefinition($this->prefix('storage'))
						->setFactory($config['exceptionStorage'])
						->setType(Trejjam\Debugger\Exception\IStorage::class)
						->setAutowired(FALSE);
			}

			$tracyLogger->addSetup('setLogStorage', [$this->prefix('@storage')]);
			$blueScreen->addSetup('setLogStorage', [$this->prefix('@storage')]);
		}
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class) : void
	{
		$initialize = $class->getMethod('initialize');

		$initialize->addBody(
			'Trejjam\Debugger\Debugger::$onFatalError[] = function (\Throwable $exception) {'
			. 'if (Trejjam\Debugger\Debugger::$productionMode === FALSE) Trejjam\Debugger\Debugger::log($exception, self::EXCEPTION);'
			. '};'
		);
	}
}
