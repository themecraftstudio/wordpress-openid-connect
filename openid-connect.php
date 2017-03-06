<?php
/*
 * Plugin Name: OpenID Connect Authenticator
 * Plugin URI:  https://developer.wordpress.org/plugins/openid-connect/
 * Description: Enable users to authenticate using any OpenID Connect compliant provider
 * Version:     0.0.1
 * Author:      Themecraft
 * Author URI:  https://themecraft.studio/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: openid-connect
 * Domain Path: /languages

 {Plugin Name} is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 2 of the License, or
 any later version.

 {Plugin Name} is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with {Plugin Name}. If not, see {URI to Plugin License}.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Themecraft\WordPress\OpenIDConnect\OpenID\AuthenticationRequest;
use Themecraft\WordPress\OpenIDConnect\OpenID\Client;
use Themecraft\WordPress\OpenIDConnect\OpenID\Provider;
use Themecraft\WordPress\OpenIDConnect\Settings;


/**
 * Activation hook
 */
register_activation_hook( __FILE__, function () {

} );
/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, function () {

} );
/**
 * Uninstall hook
 */
register_uninstall_hook(__FILE__, 'openidconnet_uninstall');
function openidconnect_uninstall() {
	Settings::uninstall();
}

// Register admin settings
Settings::register();


/**
 *
 */
add_action( 'login_form', function () { ?>
	<p>
		<label for="openid_id"><?php _e( 'OpenID Connect' ); ?><br />
		<input type="text" name="openid_id" class="input" size="20" /></label>
	</p>
<?php });


/**
 *
 */
add_action( 'login_form_login', function () {
	$identifier = $_REQUEST['openid_id'];

	if (empty($identifier))
		return;

	if ($host === 'gmail.com')
		// Because they don't implement WebFinger for Gmail users
		$issuer = 'https://accounts.google.com';
	else {
		// GET https://$host/.well-known/webfinger? resource=$identifier &rel=$rel
		$issuer = 'https://themecraft.studio';
	}

	// If provider not configured i.e. truested, the following throws exception
	if (!Settings::hasProvider($issuer))
		throw new \Exception('provider not found');

	$settings = Settings::getProviderSettings($issuer);

	$provider = new Provider($issuer);

	if (!$provider->supportsScope('email') && !$provider->supportsClaim('email')) {
		// We are unable to authenticated the user by email.
		throw new Exception('Unable to authenticate user %s by email', $identifier);
	}

	// userinfo is *optional*. Do we need it? yes, to fetch the user email
	if (!$provider->supportsResponseType('id_token'))
		throw new Exception('Provider does not support implicit flow'); // response_types_supported is required in the response
	if (!$provider->supportsResponseType('code'))
		throw new Exception('Provider does not support authentication code flow');

	$client = new Client($settings['client_id'], $settings['client_secret']);

	$authRequest = new AuthenticationRequest($client, $provider, $flow = 'code');

	wp_redirect($authRequest->getRedirectUri());
	exit();
});

/**
 *
 */
add_filter( 'authenticate', function ($user, $email) {
	if ( $user instanceof WP_User )
		return $user;

	if (!array_key_exists('state', $_GET) || !array_key_exists('code', $_GET))
		return $user;

	$code = $_GET['code'];
	$nonce = $_GET['nonce'];
	$state = $_GET['state'];
	$id_token = $_GET['id_token'];

	if ($code) {
		$authRequest = AuthenticationRequest::fromState($state, $code); // authorization code flow
	} else if ($nonce) {
		// implicit flow??
		$authRequest = AuthenticationRequest::fromState($state, $id_token);
		$authRequest->verifyNonce($nonce);
	}

	// $entity = $authRequest->getEntity()
	//   NOTE: access token is kinda related to OAuth where the goal is to authorize
	//         the client and as such the client receives a token it can use to access the resource.
	//         OpenID goal is to bind an identity (made of certain claims) to the user/entity visiting the website
	           //===> from an authentication perspective, the end result is an 'identity' i.e. certain assertions about the user
	//      new Entity($id_token)

	// $entity->getClaim($entity, 'email)

	// MAP to WP_User using email or other means
	// Fetch user email
	$email = 'ettore@themecraft.studio';
	if (true) {
		// userinfo endpoint is defined)
	} else {
		// should be in the id_token even though they are not mandatory
	}

	if (email_exists($email))
		$user = get_user_by( 'email', $email );

	return $user;
}, 21, 2 );


// TODO hook form errors messages if error and state are defined. error_description is optional.
