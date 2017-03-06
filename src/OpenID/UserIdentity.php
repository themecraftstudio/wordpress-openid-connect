<?php


namespace Themecraft\WordPress\OpenIDConnect\OpenID;


// Represented by a token
class UserIdentity {

	public function __construct($id_token, /* avoids new instatiation ? */$provider_instance = null ) {
		// already contains the provider so we can retrieve metadata
		// or instantiate it
	}

	public function getClaim() {

	}
}
