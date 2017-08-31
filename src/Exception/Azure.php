<?php
declare(strict_types=1);

namespace Trejjam\Debugger\Exception;

use Nette;
use GuzzleHttp;
use MicrosoftAzure;

class Azure implements IStorage
{
	/**
	 * @var MicrosoftAzure\Storage\Blob\Internal\IBlob
	 */
	public $blobClient;
	/**
	 * @var string
	 */
	public $blobPrefix;

	public function __construct(
		MicrosoftAzure\Storage\Blob\Internal\IBlob $blobClient,
		string $blobPrefix
	) {
		$this->blobClient = $blobClient;
		$this->blobPrefix = $blobPrefix;
	}

	public function persist(string $localFile) : bool
	{
		$containerName = $this->getContainerName();

		$this->createContainerIfNotExist($containerName);

		$blobName = basename($localFile);
		$blockList = new MicrosoftAzure\Storage\Blob\Models\BlockList;

		$blockBlobId = md5_file($localFile) . Nette\Utils\Strings::padLeft(1, 16, '0');
		$blockList->addLatestEntry($blockBlobId);

		$this->blobClient->createBlobBlockAsync(
			$containerName,
			$blobName,
			$blockBlobId,
			file_get_contents($localFile)
		)->then(function () use ($containerName, $blobName, $blockList) {
			$this->blobClient
				->commitBlobBlocksAsync($containerName, $blobName, $blockList)
				->then(NULL, function (\Exception $reason) {
					throw $reason;
				})->wait();
		})->wait();

		return TRUE;
	}

	/**
	 * @param $containerName
	 */
	protected function createContainerIfNotExist(string $containerName)
	{
		$options = new MicrosoftAzure\Storage\Blob\Models\ListContainersOptions;
		$options->setPrefix($containerName);

		// List containers
		/** @var MicrosoftAzure\Storage\Blob\Models\ListContainersResult $containers */
		$containers = $this->blobClient
			->listContainersAsync($options)
			->then(NULL, function (\Exception $reason) {
				throw $reason;
			})->wait();

		foreach ($containers->getContainers() as $container) {
			if ($container->getName() === $containerName) {
				return;
			}
		}

		$this->blobClient
			->createContainerAsync($containerName)
			->then(NULL, function ($reason) {
				throw $reason;
			})->wait();
	}

	/**
	 * @return string
	 */
	public function getContainerName() : string
	{
		return $this->getContainerPrefix() . IStorage::TYPE_LOG;
	}

	private function getContainerPrefix() : string
	{
		if (empty($this->blobPrefix)) {
			return '';
		}

		return $this->blobPrefix . '-';
	}
}
