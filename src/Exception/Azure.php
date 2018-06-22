<?php
declare(strict_types=1);

namespace Trejjam\Debugger\Exception;

use Mangoweb\Clock\Clock;
use Nette;
use MicrosoftAzure\Storage\Blob;
use MicrosoftAzure\Storage\Common\Internal\Utilities;
use MicrosoftAzure\Storage\Common\Internal\Resources;

class Azure implements IStorage
{
	/**
	 * @var Blob\Internal\IBlob
	 */
	private $blobClient;
	/**
	 * @var string
	 */
	private $accountName;
	/**
	 * @var string
	 */
	private $blobPrefix;
	/**
	 * @var string
	 */
	private $whitelistIp;
	/**
	 * @var Blob\BlobSharedAccessSignatureHelper
	 */
	private $blobSharedAccessSignatureHelper;

	public function __construct(
		Blob\Internal\IBlob $blobClient,
		string $accountName,
		string $blobPrefix,
		string $whitelistIp,
		Blob\BlobSharedAccessSignatureHelper $blobSharedAccessSignatureHelper
	) {
		$this->blobClient = $blobClient;
		$this->accountName = $accountName;
		$this->blobPrefix = $blobPrefix;
		$this->whitelistIp = $whitelistIp;
		$this->blobSharedAccessSignatureHelper = $blobSharedAccessSignatureHelper;
	}

	public function persist(string $localFile) : bool
	{
		$containerName = $this->getContainerName();

		$this->createContainerIfNotExist($containerName);

		$blobName = basename($localFile);
		$blockList = new Blob\Models\BlockList;

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

	protected function createContainerIfNotExist(string $containerName)
	{
		$options = new Blob\Models\ListContainersOptions;
		$options->setPrefix($containerName);

		// List containers
		/** @var Blob\Models\ListContainersResult $containers */
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

	public function getExceptionUrl(string $exceptionFile) : string
	{
		$start = Clock::now();
		$expire = Clock::addDays(7);

		$blobName = basename($exceptionFile);

		$resourceName = "{$this->getContainerName()}/{$blobName}";

		$sas = $this->blobSharedAccessSignatureHelper->generateBlobServiceSharedAccessSignatureToken(
			'b',
			$resourceName,
			'r',
			Utilities::isoDate($expire),
			Utilities::isoDate($start),
			$this->whitelistIp,
			'https'
		);

		return "https://{$this->accountName}." . Resources::BLOB_BASE_DNS_NAME . "/{$resourceName}?{$sas}";
	}
}
