<?php


namespace Themecraft\WordPress\OpenIDConnect;


use Themecraft\WordPress\OpenIDConnect\OpenID\Provider;

class Settings
{
	protected const PREFIX = 'openidconnect';
	protected const TEXT_DOMAIN = 'openid-connect';
	protected const GROUP = 'openidconnect';
	protected const CAPABILITY = 'manage_options';

	private static $registered = false;

	public static function register(): void
	{
		if (static::$registered)
			return;

		add_action( 'admin_menu', function () { static::registerOptionsPage(); } );

		add_action( 'admin_init', function () {
			register_setting( static::GROUP, static::getOptionName('providers'));

			add_settings_section(
				'section_providers', // slug name
				__( 'PRoviders Section title', static::TEXT_DOMAIN ), // title
				function () { ?>
					<div>section callback rendering</div>
				<?php },
				static::GROUP
			);

			// register a new field in the "wporg_section_developers" section, inside the "wporg" page
			$optionName = static::getOptionName('providers');
			add_settings_field(
				$optionName,
				__( 'Field title', static::TEXT_DOMAIN ),
				function ($args) use ($optionName) {
					$providers = get_option($optionName, [ 0 => ['issuer' => '', 'id' => '', 'secret' => '']]);
					foreach ($providers as $id => $provider): ?>

					<input type="text" name="<?php printf('%s[%s][issuer]', $optionName, $id) ?>" value="<?php echo $provider['issuer'] ?>"/>
					<input type="text" name="<?php printf('%s[%s][id]', $optionName, $id) ?>" value="<?php echo $provider['id'] ?>"/>
					<input type="text" name="<?php printf('%s[%s][secret]', $optionName, $id) ?>" value="<?php echo $provider['secret'] ?>"/>

				<?php endforeach; },
				static::GROUP,
				'section_providers' // section slug
			);
		});

		static::$registered = true;
	}

	protected static function renderProvidersField(): void {
		echo '<p>stuff</p>';
	}

	protected static function registerOptionsPage(): void
	{
		add_options_page(
			__('OpenID Connect', static::TEXT_DOMAIN),
			__('OpenID Connect', static::TEXT_DOMAIN),
			static::CAPABILITY,
			static::GROUP,
			function () { ?>
				<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<form action="options.php" method="post">
					<?php
					// output security fields for the registered setting "wporg"
					settings_fields( static::GROUP );
					// output setting sections and their fields
					// (sections are registered for "wporg", each field is registered to a specific section)
					do_settings_sections( static::GROUP );
					// output save settings button
					submit_button( 'Save Settings' );
					?>
				</form>
				</div>
			<?php }
		);
	}

	protected static function getOptionName(string $name): string
	{
		return sprintf('%s_%s', static::PREFIX, $name);
	}


	public static function hasProvider(string $issuer): bool
	{
		$providers = get_option(static::getOptionName('providers'));
		foreach ($providers as $provider)
			if ($provider['issuer'] === $issuer)
				return true;

		return false;
	}

	/**
	 * @param string $issuer
	 *
	 * @return array [client_secret, client_id]
	 */
	public static function getProviderSettings(string $issuer): array
	{
		$providers = get_option(static::getOptionName('providers'));
		foreach ( $providers as $provider ) {
			if ($provider['issuer'] === $issuer)
				return ['client_id' => $provider['id'], 'client_secret' => $provider['secret']];
		}
	}

	public static function uninstall(): void
	{
		delete_option('openidconnect_providers');
	}

}
