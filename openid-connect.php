<?php
/*
 * Plugin Name: OpenID Connect
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


//
register_activation_hook( __FILE__, function () {

} );
//
//register_deactivation_hook( __FILE__, function () {
//
//} );
//
register_uninstall_hook(__FILE__, 'openidconnet_uninstall');
function openidconnect_uninstall() {

}

require_once __DIR__ . '/vendor/autoload.php';

use Themecraft\WordPress\OpenIDConnect\Settings;


define('PROVIDERS', [
	'https://accounts.google.com' => [
		'id' => '709076598341-473789sl6ci08t8mrrskssfa0rnl642l.apps.googleusercontent.com',
		'secret' => 'q8pKa7ohqPyW1mLSOEwJrufo'
	]
]);

add_action( 'login_form', function () { ?>
	<p>
		<label for="openid_id"><?php _e( 'OpenID Connect' ); ?><br />
		<input type="text" name="openid_id" class="input" size="20" /></label>
	</p>
<?php });


add_action( 'login_form_login', function () {
	$identifier = $_REQUEST['openid_id'];

	if (empty($identifier))
		return;

	// TODO implement normalization steps presented in section 2.1.2

	[$resource, $host] = explode('@', $identifier, 2);
	$rel = 'http://openid.net/specs/connect/1.0/issuer';

	// Use WebFinger to find the issuer URL
	if ($host === 'gmail.com')
		// Because they don't implement WebFinger for Gmail users
		$issuer = 'https://accounts.google.com';
	else {
		// GET https://$host/.well-known/webfinger? resource=$identifier &rel=$rel
		$issuer = 'https://themecraft.studio';
	}

	// If provider not configured i.e. truested, the following throws exception
	$provider = \Themecraft\WordPress\OpenIDConnect\OpenID\Provider::getByIssuer($issuer);

	if ($provider->getIssuer() !== $issuer)
		throw new Exception(sprintf('Issuer not matching %s !== %s', $issuer, $provider->getIssuer()));

	if (!$provider->supportsScope('email') && !$provider->supportsClaim('email')) {
		// We are unable to authenticated the user by email.
		throw new Exception('Unable to authenticate user %s by email', $identifier);
	}
	// Claims are returned in the ID Token or from the UserInfo endpoint

	// 2. The returned Issuer location MUST be a URI RFC 3986 [RFC3986] with a scheme
	// component that MUST be https, a host component, and optionally, port and path
	// components and no query or fragment components.

	// userinfo is *optional*. Do we need it? yes, to fetch the user email

	if (!$provider->supportsResponseType('id_token'))
		throw new Exception('Provider does not support implicit flow'); // response_types_supported is required in the response
	if (!$provider->supportsResponseType('code'))
		throw new Exception('Provider does not support authentication code flow');

	// Make an authorization request using the authorization code flow
	$requestParams = [
		'scope' => 'openid'. $provider->supportsScope('email') ? ' email' : '',
//		'response_type' => 'code',
		'response_type' => 'id_token',
		'nonce' => 'stufaiu123h34 ei1u 3h31u2i',
		'redirect_uri' => site_url() . '/wp-login.php', // MUST match the redirection URI provided by the Client (this siteapp) when it was pre-registered at the OpenID provider. NO query parameters are accepted!!!
		'client_id' => $provider->getClientId(),
		'state' => '123', // to maintain state between the request and the callback. Avoid XSRF attacks.
	];

	wp_redirect($provider->getAuthorizationEndpoint() . '?'. http_build_query($requestParams));
	exit();
});

add_filter( 'authenticate', function ($user, $email) {
	if ( $user instanceof WP_User )
		return $user;

	if (!array_key_exists('state', $_GET) || !array_key_exists('code', $_GET))
		return $user;

	$authorizationCode = $_GET['code'];

	// 1. verify state is valid
	// 2. get the issuer/provider from the state
	$issuer = 'https://accounts.google.com'; // remove me
	$provider = \Themecraft\WordPress\OpenIDConnect\OpenID\Provider::getByIssuer($issuer);
	$provider->getAccessToken($authorizationCode);

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

Settings::register();
