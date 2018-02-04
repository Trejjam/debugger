<?php
declare(strict_types=1);

namespace Trejjam\Debugger\Azure;

final class Settings
{
	/**
	 * @var string
	 */
	private $name;
	/**
	 * @var string
	 */
	private $blobEndpointUri;
	/**
	 * @var string|null
	 */
	private $blobSecondaryEndpointUri;
	/**
	 * @var string|null
	 */
	private $sharedAccessSignature;

	public function __construct(
		string $name,
		string $blobEndpointUri,
		?string $blobSecondaryEndpointUri,
		?string $sharedAccessSignature
	) {
		$this->name = $name;
		$this->blobEndpointUri = $blobEndpointUri;
		$this->blobSecondaryEndpointUri = $blobSecondaryEndpointUri;
		$this->sharedAccessSignature = $sharedAccessSignature;
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getBlobEndpointUri() : string
	{
		return $this->blobEndpointUri;
	}

	public function getBlobSecondaryEndpointUri() : ?string
	{
		return $this->blobSecondaryEndpointUri;
	}

	public function getSharedAccessSignature() : ?string
	{
		return $this->sharedAccessSignature;
	}
}
