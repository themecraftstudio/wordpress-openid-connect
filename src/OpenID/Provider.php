<?php

namespace Themecraft\WordPress\OpenIDConnect\OpenID;

use Exception;
use GuzzleHttp\Client;
use Themecraft\WordPress\OpenIDConnect\Exception\UnknownProviderException;
use Themecraft\WordPress\OpenIDConnect\Settings;

class Provider
{
	/** @var string */
	private $issuer;

	/** @var  string */
	private $clientId;

	/** @var string */
	private $clientSecret;

	/** @var array */
	protected $metadata;

	/** @var  string */
	protected $accessToken;

	/** @var ProviderMetadata */
	protected $configuration;

	public function __construct(string $issuer) {
		$this->issuer = $issuer;
		$this->clientId = $clientId = '';
		$this->clientSecret = $clientSecret = '';

		$this->configuration = new ProviderMetadata($issuer);
	}

	public function supportsResponseType(string $type): bool
	{
		return in_array($type, $this->configuration->getSupportedResponseTypes());
	}

	public function supportsClaim(string $claim): bool
	{
		return in_array($claim, $this->configuration->getSupportedClaims());
	}

	public function supportsScope(string $scope): ?bool
	{
		return in_array($scope, $this->configuration->getSupportedScopes());
	}

	protected function getTokenEndpoint(): ?string
	{
		return $this->configuration->getTokenEndpoint();
	}

	public function getIssuer(): string
	{
		return $this->configuration->getIssuer();
	}

	public function getAuthorizationEndpoint()
	{
		return $this->configuration->getAuthorizationEndpoint();
	}

	public function supportsTokenEndpointAuthMethod(string $method): ?bool
	{
		return in_array($method, $this->configuration->getTokenEndpointSupportedAuthMethods());
	}

	public function getAccessToken(?string $code): string
	{
		if ($this->accessToken && $this->accessTokenIsValid())
			return $this->accessToken;

		// check openid configuration supports 'client_secret_basic' in 'token_endpoint_auth_methods_supported'
		// else check for 'client_secret_post'

		if (!$this->supportsTokenEndpointAuthMethod('client_secret_post'))
			// we are screwed
			throw new Exception('client_secret_post not supported by the token endpoint. So sad.');

		$client = new Client();
		$result = $client->post($this->getTokenEndpoint(), [ 'form_params' => [
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => site_url() . '/wp-login.php',
			'client_id' => $this->getClientId(),
			'client_secret' => $this->getClientSecret()
		]]);
		$result = json_decode($result->getBody());

		if ($result->token_type !== 'Bearer')
			throw new Exception('Expecting token_type to be Bearer, got '. $result->token_type .' instead');

		$accessToken = $result->access_token;
		$idToken = $result->id_token;

		if (empty($accessToken) || empty($idToken))
			throw new Exception('empty access token or id token');

		// Validate access token and id tokens as per 3.1.3.5

		// decode id token and check whether there is the email
		// alternatively use the userinfo endpoint
		// FAIL: there should not be a path for which we can't fetch the email
		//       as preconditions where checked in login_form_login action ('email' is in claims_supported or scopes_supported)


		return $this->accessToken = $accessToken;
	}
}
