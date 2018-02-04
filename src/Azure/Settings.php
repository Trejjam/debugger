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
	 * @var null|string
	 */
	private $blobSecondaryEndpointUri;

	public function __construct(
		string $name,
		string $blobEndpointUri,
		?string $blobSecondaryEndpointUri
	) {
		$this->name = $name;
		$this->blobEndpointUri = $blobEndpointUri;
		$this->blobSecondaryEndpointUri = $blobSecondaryEndpointUri;
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
}
