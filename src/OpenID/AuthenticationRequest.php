<?php


namespace Themecraft\WordPress\OpenIDConnect\OpenID;


class AuthenticationRequest
{
	/** @var  Client */
	protected $client;

	/** @var  Provider */
	protected $provider;

	/** @var  string */
	protected $flow;

	function __construct(Client $client, Provider $provider, $flow = 'code')
	{
		$this->client = $client;
		$this->provider = $provider;
		$this->flow = $flow;
	}

	function getRedirectUri(): string
	{
		// Generate and store state = (id_state, provider_info, flow)

		$requestParams = [
			'state' => '123', // to maintain state between the request and the callback. Avoid XSRF attacks.
			'client_id' => $this->client->getClientId(),
			'scope' => 'openid'. $this->provider->supportsScope('email') ? ' email' : '',
			// TODO site_url or home_url ?
			'redirect_uri' => site_url() . '/wp-login.php', // MUST match the redirection URI provided by the Client (this siteapp) when it was pre-registered at the OpenID provider. NO query parameters are accepted!!!
		];

		if ($this->flow == 'code') {
			$requestParams['response_type'] = 'code'; // Authorization Code returned
		} elseif ($this->flow == 'implicit') {
			$requestParams['response_type'] = 'id_token'; // no Access Token returned
			$requestParams['nonce'] = 'stufaiu123h34 ei1u 3h31u2i';
		} elseif ($this->flow == 'hybrid') {

		}

		return $this->provider->getAuthorizationEndpoint() . '?'. http_build_query($requestParams);
	}

	static function fromState(string $state, ?string $codeOrToken): AuthenticationRequest
	{
		// authorization code flow!!

//		return new AuthenticationRequest();
	}

	public function verifyNonce(string $nonce): bool
	{
		// TODO
	}
}
