<?php


namespace Themecraft\WordPress\OpenIDConnect\OpenID;


class Client
{
	/** @var string  */
	protected $id;

	/** @var string  */
	protected $secret;

	public function __construct(string $id, string $secret)
	{
		$this->id = $id;
		$this->secret = $secret;
	}

	public function getClientId(): string
	{
		return $this->id;
	}

	public function getClientSecret(): string
	{
		return $this->secret;
	}
}
