<?php
/**
 * Plugin Name:       Formward Forms
 * Plugin URI:        https://formward.eu/frameworks/wordpress
 * Description:       Add EU-hosted, GDPR-clean contact forms to WordPress with a simple [formward_form] shortcode. Submissions are processed on servers in Sweden. No tracking, no US data transfer.
 * Version:           0.2.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Formward
 * Author URI:        https://formward.eu
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       formward-forms
 *
 * @package Formward
 */

// Guard against direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default Formward endpoint base. The submission endpoint is built as
 * <base>/f/<form-id> and accepts a standard HTML POST.
 */
if ( ! defined( 'FORMWARD_DEFAULT_ENDPOINT' ) ) {
	define( 'FORMWARD_DEFAULT_ENDPOINT', 'https://forms.formward.eu' );
}

/**
 * Default Formward application base. The REST API lives at
 * <app-base>/api/v1/* and authenticates with a Bearer API key. This is a
 * different host from the public form-submission endpoint above.
 */
if ( ! defined( 'FORMWARD_DEFAULT_APP_BASE' ) ) {
	define( 'FORMWARD_DEFAULT_APP_BASE', 'https://formward.eu' );
}

/**
 * Option keys stored via the WordPress options API.
 */
if ( ! defined( 'FORMWARD_OPT_FORM_ID' ) ) {
	define( 'FORMWARD_OPT_FORM_ID', 'formward_form_id' );
}
if ( ! defined( 'FORMWARD_OPT_ENDPOINT' ) ) {
	define( 'FORMWARD_OPT_ENDPOINT', 'formward_endpoint_base' );
}
if ( ! defined( 'FORMWARD_OPT_API_KEY' ) ) {
	define( 'FORMWARD_OPT_API_KEY', 'formward_api_key' );
}
if ( ! defined( 'FORMWARD_OPT_APP_BASE' ) ) {
	define( 'FORMWARD_OPT_APP_BASE', 'formward_app_base' );
}

/* -------------------------------------------------------------------------
 * Settings (Settings -> Formward)
 * ---------------------------------------------------------------------- */

/**
 * Register the settings page under the Settings menu.
 *
 * @return void
 */
function formward_add_settings_page() {
	add_options_page(
		__( 'Formward', 'formward-forms' ),
		__( 'Formward', 'formward-forms' ),
		'manage_options',
		'formward',
		'formward_render_settings_page'
	);
}
add_action( 'admin_menu', 'formward_add_settings_page' );

/**
 * Register settings, a section, and fields using the Settings API.
 *
 * @return void
 */
function formward_register_settings() {
	register_setting(
		'formward_settings',
		FORMWARD_OPT_FORM_ID,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'formward_sanitize_form_id',
			'default'           => '',
		)
	);

	register_setting(
		'formward_settings',
		FORMWARD_OPT_ENDPOINT,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'formward_sanitize_endpoint',
			'default'           => FORMWARD_DEFAULT_ENDPOINT,
		)
	);

	register_setting(
		'formward_settings',
		FORMWARD_OPT_API_KEY,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'formward_sanitize_api_key',
			'default'           => '',
		)
	);

	register_setting(
		'formward_settings',
		FORMWARD_OPT_APP_BASE,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'formward_sanitize_app_base',
			'default'           => FORMWARD_DEFAULT_APP_BASE,
		)
	);

	add_settings_section(
		'formward_main_section',
		__( 'Formward settings', 'formward-forms' ),
		'formward_settings_section_intro',
		'formward'
	);

	add_settings_field(
		FORMWARD_OPT_FORM_ID,
		__( 'Default Form ID', 'formward-forms' ),
		'formward_field_form_id',
		'formward',
		'formward_main_section'
	);

	add_settings_field(
		FORMWARD_OPT_ENDPOINT,
		__( 'Endpoint base', 'formward-forms' ),
		'formward_field_endpoint',
		'formward',
		'formward_main_section'
	);

	add_settings_field(
		FORMWARD_OPT_API_KEY,
		__( 'Formward API key', 'formward-forms' ),
		'formward_field_api_key',
		'formward',
		'formward_main_section'
	);

	add_settings_field(
		FORMWARD_OPT_APP_BASE,
		__( 'API base URL', 'formward-forms' ),
		'formward_field_app_base',
		'formward',
		'formward_main_section'
	);
}
add_action( 'admin_init', 'formward_register_settings' );

/**
 * Sanitize a Formward form ID. Form IDs are URL path segments, so we keep
 * only characters that are safe and expected there.
 *
 * @param string $value Raw submitted value.
 * @return string Sanitized form ID.
 */
function formward_sanitize_form_id( $value ) {
	$value = is_string( $value ) ? trim( $value ) : '';
	// Allow letters, numbers, dash and underscore only.
	$value = preg_replace( '/[^A-Za-z0-9_-]/', '', $value );

	return (string) $value;
}

/**
 * Sanitize the endpoint base URL. Falls back to the default when empty or
 * invalid, and never allows a trailing slash so we can append /f/<id> cleanly.
 *
 * @param string $value Raw submitted value.
 * @return string Sanitized URL.
 */
function formward_sanitize_base_url( $value, $default ) {
	$value = is_string( $value ) ? trim( $value ) : '';
	if ( '' === $value ) {
		return $default;
	}

	// HTTPS only for a privacy-focused plugin: contact-form data and bearer-token
	// API calls must never traverse plaintext. http:// is permitted ONLY for
	// localhost / 127.0.0.1 so local development still works.
	$host     = wp_parse_url( $value, PHP_URL_HOST );
	$is_local = in_array( strtolower( (string) $host ), array( 'localhost', '127.0.0.1' ), true );
	$schemes  = $is_local ? array( 'https', 'http' ) : array( 'https' );

	$value = esc_url_raw( $value, $schemes );
	if ( '' === $value ) {
		return $default;
	}

	return untrailingslashit( $value );
}

function formward_sanitize_endpoint( $value ) {
	return formward_sanitize_base_url( $value, FORMWARD_DEFAULT_ENDPOINT );
}

/**
 * Sanitize a Formward API key. Keys are opaque tokens; we keep only the
 * characters that can legitimately appear in one (letters, numbers, and the
 * common token punctuation) and never echo the raw value back unmasked.
 *
 * @param string $value Raw submitted value.
 * @return string Sanitized API key.
 */
function formward_normalize_api_key( $value ) {
	$value = is_string( $value ) ? trim( $value ) : '';
	// Allow letters, numbers and the token-safe separators . _ -
	return (string) preg_replace( '/[^A-Za-z0-9._-]/', '', $value );
}

function formward_sanitize_api_key( $value ) {
	// Settings API save callback. The key field is rendered EMPTY (the real key is
	// never sent to the browser), so an empty submit means "keep the current key".
	// An explicit "clear" checkbox wipes it. options.php verifies the
	// settings_fields() nonce before any sanitize callback runs.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Settings API verifies the settings_fields() nonce before sanitize callbacks run.
	if ( ! empty( $_POST['formward_api_key_clear'] ) ) {
		return '';
	}
	$value = is_string( $value ) ? trim( $value ) : '';
	if ( '' === $value ) {
		// Empty submit → preserve the existing key (the field is intentionally blank).
		return (string) get_option( FORMWARD_OPT_API_KEY, '' );
	}
	return formward_normalize_api_key( $value );
}

/**
 * Sanitize the API base URL (the dashboard/app host that serves /api/v1/*).
 * Falls back to the default when empty or invalid; never keeps a trailing
 * slash so we can append /api/v1/... cleanly.
 *
 * @param string $value Raw submitted value.
 * @return string Sanitized URL.
 */
function formward_sanitize_app_base( $value ) {
	return formward_sanitize_base_url( $value, FORMWARD_DEFAULT_APP_BASE );
}

/**
 * Intro copy for the settings section.
 *
 * @return void
 */
function formward_settings_section_intro() {
	echo '<p>' . esc_html__(
		'Formward is an EU-hosted, GDPR-clean form backend (servers in Sweden). Create a form at formward.eu, copy its Form ID, and paste it below. Then drop the [formward_form] shortcode on any page or post.',
		'formward-forms'
	) . '</p>';
}

/**
 * Render the Default Form ID field.
 *
 * @return void
 */
function formward_field_form_id() {
	$value = get_option( FORMWARD_OPT_FORM_ID, '' );
	?>
	<input
		type="text"
		id="<?php echo esc_attr( FORMWARD_OPT_FORM_ID ); ?>"
		name="<?php echo esc_attr( FORMWARD_OPT_FORM_ID ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		class="regular-text"
		autocomplete="off"
	/>
	<p class="description">
		<?php esc_html_e( 'Your default Formward Form ID. The shortcode "id" attribute overrides this per form.', 'formward-forms' ); ?>
	</p>
	<?php
}

/**
 * Render the Endpoint base field.
 *
 * @return void
 */
function formward_field_endpoint() {
	$value = get_option( FORMWARD_OPT_ENDPOINT, FORMWARD_DEFAULT_ENDPOINT );
	?>
	<input
		type="url"
		id="<?php echo esc_attr( FORMWARD_OPT_ENDPOINT ); ?>"
		name="<?php echo esc_attr( FORMWARD_OPT_ENDPOINT ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		class="regular-text"
		placeholder="<?php echo esc_attr( FORMWARD_DEFAULT_ENDPOINT ); ?>"
	/>
	<p class="description">
		<?php esc_html_e( 'Optional. Leave as the default unless Formward gives you a custom endpoint. Submissions post to <base>/f/<form-id>.', 'formward-forms' ); ?>
	</p>
	<?php
}

/**
 * Render the API key field. The stored value is shown masked so it is not
 * exposed in full on screen or in page source; the real value stays in options.
 *
 * @return void
 */
function formward_field_api_key() {
	$value  = (string) get_option( FORMWARD_OPT_API_KEY, '' );
	$masked = formward_mask_api_key( $value );
	?>
	<input
		type="password"
		id="<?php echo esc_attr( FORMWARD_OPT_API_KEY ); ?>"
		name="<?php echo esc_attr( FORMWARD_OPT_API_KEY ); ?>"
		value=""
		class="regular-text"
		autocomplete="new-password"
		spellcheck="false"
		placeholder="<?php echo esc_attr( '' !== $masked ? $masked : '' ); ?>"
	/>
	<p class="description">
		<?php
		echo wp_kses(
			__( 'Create a key under <strong>Dashboard → API keys</strong> at formward.eu (scopes <code>forms:read</code> and <code>submissions:read</code>) and paste it here. It lets this plugin list your forms and recent submissions in WP admin. Read-only. It is never shown to site visitors.', 'formward-forms' ),
			array(
				'strong' => array(),
				'code'   => array(),
			)
		);
		?>
	</p>
	<?php
	if ( '' !== $masked ) {
		?>
		<p class="description">
			<?php
			printf(
				/* translators: %s: masked API key */
				wp_kses( __( 'Saved key: <code>%s</code>. Leave the field blank to keep it.', 'formward-forms' ), array( 'code' => array() ) ),
				esc_html( $masked )
			);
			?>
		</p>
		<label for="formward_api_key_clear">
			<input type="checkbox" id="formward_api_key_clear" name="formward_api_key_clear" value="1" />
			<?php esc_html_e( 'Clear the saved API key', 'formward-forms' ); ?>
		</label>
		<?php
	}
}

/**
 * Render the API base URL field.
 *
 * @return void
 */
function formward_field_app_base() {
	$value = (string) get_option( FORMWARD_OPT_APP_BASE, FORMWARD_DEFAULT_APP_BASE );
	?>
	<input
		type="url"
		id="<?php echo esc_attr( FORMWARD_OPT_APP_BASE ); ?>"
		name="<?php echo esc_attr( FORMWARD_OPT_APP_BASE ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		class="regular-text"
		placeholder="<?php echo esc_attr( FORMWARD_DEFAULT_APP_BASE ); ?>"
	/>
	<p class="description">
		<?php esc_html_e( 'Optional. The Formward app host that serves the REST API. Leave as the default unless Formward gave you a custom one. The plugin calls <base>/api/v1/forms.', 'formward-forms' ); ?>
	</p>
	<?php
}

/**
 * Mask an API key for display, keeping only a short prefix and suffix so the
 * admin can recognise which key is saved without revealing it in full.
 *
 * @param string $key Raw key.
 * @return string Masked key, or empty string when no key is set.
 */
function formward_mask_api_key( $key ) {
	$key = (string) $key;
	$len = strlen( $key );
	if ( 0 === $len ) {
		return '';
	}
	if ( $len <= 8 ) {
		return str_repeat( '•', $len );
	}

	return substr( $key, 0, 4 ) . str_repeat( '•', 6 ) . substr( $key, -4 );
}

/**
 * Read and sanitize the saved API key.
 *
 * @return string API key, or empty string when not configured.
 */
function formward_get_api_key() {
	// Read path: normalise only (never the $_POST-aware save sanitizer).
	return formward_normalize_api_key( get_option( FORMWARD_OPT_API_KEY, '' ) );
}

/**
 * Read and sanitize the saved API base URL.
 *
 * @return string API base URL without a trailing slash.
 */
function formward_get_app_base() {
	return formward_sanitize_app_base( get_option( FORMWARD_OPT_APP_BASE, FORMWARD_DEFAULT_APP_BASE ) );
}

/* -------------------------------------------------------------------------
 * REST API client (read-only)
 * ---------------------------------------------------------------------- */

/**
 * Perform an authenticated GET against the Formward REST API.
 *
 * @param string $path  API path beginning with a slash, e.g. "/api/v1/forms".
 * @param array  $query Optional query args appended to the URL.
 * @return array|WP_Error Decoded `data` array on success, WP_Error otherwise.
 */
function formward_api_get( $path, $query = array() ) {
	$api_key = formward_get_api_key();
	if ( '' === $api_key ) {
		return new WP_Error(
			'formward_no_key',
			__( 'No Formward API key is configured. Add one under Settings → Formward.', 'formward-forms' )
		);
	}

	$url = formward_get_app_base() . $path;
	if ( ! empty( $query ) ) {
		// add_query_arg() URL-encodes the values itself; do not pre-encode.
		$url = add_query_arg( $query, $url );
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Accept'        => 'application/json',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$json = json_decode( $body, true );

	if ( $code < 200 || $code >= 300 ) {
		$message = __( 'The Formward API returned an error.', 'formward-forms' );
		if ( is_array( $json ) && isset( $json['error']['message'] ) && is_string( $json['error']['message'] ) ) {
			$message = $json['error']['message'];
		} elseif ( 401 === $code || 403 === $code ) {
			$message = __( 'Authentication failed. Check that your API key is valid and has the right scopes.', 'formward-forms' );
		}
		return new WP_Error( 'formward_http_' . $code, $message, array( 'status' => $code ) );
	}

	if ( ! is_array( $json ) || ! isset( $json['data'] ) || ! is_array( $json['data'] ) ) {
		return new WP_Error(
			'formward_bad_response',
			__( 'Unexpected response from the Formward API.', 'formward-forms' )
		);
	}

	return $json['data'];
}

/* -------------------------------------------------------------------------
 * Admin menu: Formward → Forms / Submissions
 * ---------------------------------------------------------------------- */

/**
 * Register the top-level "Formward" admin menu with Forms and Submissions
 * subpages. These are read-only views backed by the REST API.
 *
 * @return void
 */
function formward_add_admin_menu() {
	add_menu_page(
		__( 'Formward', 'formward-forms' ),
		__( 'Formward', 'formward-forms' ),
		'manage_options',
		'formward-forms',
		'formward_render_forms_page',
		'dashicons-feedback',
		58
	);

	add_submenu_page(
		'formward-forms',
		__( 'Forms', 'formward-forms' ),
		__( 'Forms', 'formward-forms' ),
		'manage_options',
		'formward-forms',
		'formward_render_forms_page'
	);

	add_submenu_page(
		'formward-forms',
		__( 'Submissions', 'formward-forms' ),
		__( 'Submissions', 'formward-forms' ),
		'manage_options',
		'formward-submissions',
		'formward_render_submissions_page'
	);
}
add_action( 'admin_menu', 'formward_add_admin_menu' );

/**
 * Shared notice shown when no API key is configured.
 *
 * @return void
 */
function formward_render_missing_key_notice() {
	?>
	<div class="notice notice-warning">
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: settings page URL */
					__( 'No Formward API key is set. <a href="%s">Add one under Settings → Formward</a> to list your forms and submissions here.', 'formward-forms' ),
					esc_url( admin_url( 'options-general.php?page=formward' ) )
				),
				array( 'a' => array( 'href' => array() ) )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Render a WP_Error as an admin notice.
 *
 * @param WP_Error $error Error to display.
 * @return void
 */
function formward_render_error_notice( $error ) {
	?>
	<div class="notice notice-error">
		<p><?php echo esc_html( $error->get_error_message() ); ?></p>
	</div>
	<?php
}

/**
 * Render the "Formward → Forms" page: lists the account's forms (name + id)
 * fetched from GET /api/v1/forms so the admin can copy a form ID.
 *
 * @return void
 */
function formward_render_forms_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Formward Forms', 'formward-forms' ); ?></h1>
	<?php
	if ( '' === formward_get_api_key() ) {
		formward_render_missing_key_notice();
		echo '</div>';
		return;
	}

	$forms = formward_api_get( '/api/v1/forms' );
	if ( is_wp_error( $forms ) ) {
		formward_render_error_notice( $forms );
		echo '</div>';
		return;
	}

	if ( empty( $forms ) ) {
		echo '<p>' . esc_html__( 'No forms found on this account yet. Create one in the Formward dashboard.', 'formward-forms' ) . '</p></div>';
		return;
	}
	?>
		<p class="description"><?php esc_html_e( 'These are the forms on your Formward account. Copy a Form ID to use it in the [formward_form] shortcode or as the default Form ID.', 'formward-forms' ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Name', 'formward-forms' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Form ID', 'formward-forms' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Created', 'formward-forms' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Submissions', 'formward-forms' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $forms as $form ) : ?>
				<?php
				if ( ! is_array( $form ) ) {
					continue;
				}
				$form_id   = isset( $form['id'] ) ? (string) $form['id'] : '';
				$form_name = isset( $form['name'] ) ? (string) $form['name'] : '';
				$created   = isset( $form['createdAt'] ) ? formward_format_date( (string) $form['createdAt'] ) : '';
				$subs_url  = add_query_arg(
					array(
						'page'    => 'formward-submissions',
						'form_id' => $form_id,
					),
					admin_url( 'admin.php' )
				);
				?>
				<tr>
					<td><?php echo esc_html( '' !== $form_name ? $form_name : __( '(untitled)', 'formward-forms' ) ); ?></td>
					<td><code><?php echo esc_html( $form_id ); ?></code></td>
					<td><?php echo esc_html( $created ); ?></td>
					<td>
						<a href="<?php echo esc_url( $subs_url ); ?>"><?php esc_html_e( 'View submissions', 'formward-forms' ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Format an ISO-8601 date string using the site's date/time format and
 * timezone. Falls back to the raw value when it cannot be parsed.
 *
 * @param string $iso ISO-8601 timestamp.
 * @return string Localised date string.
 */
function formward_format_date( $iso ) {
	$iso = trim( (string) $iso );
	if ( '' === $iso ) {
		return '';
	}
	$ts = strtotime( $iso );
	if ( false === $ts ) {
		return $iso;
	}

	$format = get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i' );

	return wp_date( $format, $ts );
}

/**
 * Build a short, human-readable preview of a submission payload for the list
 * table. Keys and values are flattened and truncated; output is plain text and
 * escaped by the caller.
 *
 * @param mixed $payload Decoded payload (expected associative array).
 * @return string Plain-text preview.
 */
function formward_payload_preview( $payload ) {
	if ( ! is_array( $payload ) ) {
		return is_scalar( $payload ) ? (string) $payload : '';
	}

	$parts = array();
	foreach ( $payload as $key => $val ) {
		// Skip internal Formward fields.
		if ( is_string( $key ) && '_' === substr( $key, 0, 1 ) ) {
			continue;
		}
		if ( is_array( $val ) ) {
			$val = wp_json_encode( $val );
		}
		$val = (string) $val;
		$val = trim( preg_replace( '/\s+/', ' ', $val ) );
		if ( '' === $val ) {
			continue;
		}
		if ( strlen( $val ) > 80 ) {
			$val = substr( $val, 0, 77 ) . '…';
		}
		$parts[] = $key . ': ' . $val;
		if ( count( $parts ) >= 4 ) {
			break;
		}
	}

	return implode( ' · ', $parts );
}

/**
 * Render the "Formward → Submissions" page: a form selector plus a read-only
 * list table of recent submissions for the chosen form.
 *
 * @return void
 */
function formward_render_submissions_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Formward Submissions', 'formward-forms' ); ?></h1>
	<?php
	if ( '' === formward_get_api_key() ) {
		formward_render_missing_key_notice();
		echo '</div>';
		return;
	}

	$forms = formward_api_get( '/api/v1/forms' );
	if ( is_wp_error( $forms ) ) {
		formward_render_error_notice( $forms );
		echo '</div>';
		return;
	}

	// Collect the IDs the account actually owns. We only ever request a form ID
	// that appears in this list, so the GET param can never become an arbitrary
	// API path segment.
	$valid_ids = array();
	foreach ( $forms as $form ) {
		if ( is_array( $form ) && isset( $form['id'] ) && '' !== (string) $form['id'] ) {
			$valid_ids[] = (string) $form['id'];
		}
	}

	// Selected form: read-only GET param, sanitised, then constrained to a known
	// form ID. No nonce is needed: this is an idempotent, read-only navigation
	// GET that performs no state change.
	$requested = isset( $_GET['form_id'] ) ? formward_sanitize_form_id( wp_unslash( $_GET['form_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only navigation GET; value is unslashed, strictly sanitised to [A-Za-z0-9_-], then constrained to the account's own form IDs.
	$selected  = in_array( $requested, $valid_ids, true ) ? $requested : '';

	// Default to the first form when none selected (or the request was invalid).
	if ( '' === $selected && ! empty( $valid_ids ) ) {
		$selected = $valid_ids[0];
	}
	?>
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="formward-submissions" />
			<label for="formward-form-select"><strong><?php esc_html_e( 'Form:', 'formward-forms' ); ?></strong></label>
			<select name="form_id" id="formward-form-select">
			<?php foreach ( $forms as $form ) : ?>
				<?php
				if ( ! is_array( $form ) || ! isset( $form['id'] ) ) {
					continue;
				}
				$fid   = (string) $form['id'];
				$fname = isset( $form['name'] ) && '' !== (string) $form['name'] ? (string) $form['name'] : $fid;
				?>
				<option value="<?php echo esc_attr( $fid ); ?>" <?php selected( $selected, $fid ); ?>><?php echo esc_html( $fname ); ?></option>
			<?php endforeach; ?>
			</select>
			<?php submit_button( __( 'Show submissions', 'formward-forms' ), 'secondary', '', false ); ?>
		</form>
	<?php
	if ( '' === $selected ) {
		echo '<p>' . esc_html__( 'No forms available. Create one in the Formward dashboard first.', 'formward-forms' ) . '</p></div>';
		return;
	}

	$submissions = formward_api_get( '/api/v1/forms/' . rawurlencode( $selected ) . '/submissions', array() );
	if ( is_wp_error( $submissions ) ) {
		formward_render_error_notice( $submissions );
		echo '</div>';
		return;
	}

	$table = new Formward_Submissions_List_Table( is_array( $submissions ) ? $submissions : array() );
	$table->prepare_items();
	echo '<h2 class="screen-reader-text">' . esc_html__( 'Recent submissions', 'formward-forms' ) . '</h2>';
	$table->display();
	?>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Submissions list table
 * ---------------------------------------------------------------------- */

/**
 * Lazily define the WP_List_Table subclass used by the submissions viewer.
 * WP_List_Table is only loaded in admin, so we guard the definition behind a
 * function that runs on admin init.
 *
 * @return void
 */
function formward_load_list_table() {
	if ( class_exists( 'Formward_Submissions_List_Table' ) ) {
		return;
	}
	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	}

	/**
	 * Read-only list table of recent Formward submissions.
	 */
	class Formward_Submissions_List_Table extends WP_List_Table {

		/**
		 * Submission rows from the REST API.
		 *
		 * @var array
		 */
		private $rows;

		/**
		 * Constructor.
		 *
		 * @param array $rows Submission rows.
		 */
		public function __construct( $rows = array() ) {
			$this->rows = is_array( $rows ) ? $rows : array();
			parent::__construct(
				array(
					'singular' => 'submission',
					'plural'   => 'submissions',
					'ajax'     => false,
				)
			);
		}

		/**
		 * Define the table columns.
		 *
		 * @return array
		 */
		public function get_columns() {
			return array(
				'created' => __( 'Received', 'formward-forms' ),
				'status'  => __( 'Status', 'formward-forms' ),
				'preview' => __( 'Preview', 'formward-forms' ),
				'spam'    => __( 'Spam', 'formward-forms' ),
			);
		}

		/**
		 * Prepare the items for display. All data is already fetched; we just
		 * wire it into the table with a single (full) page.
		 *
		 * @return void
		 */
		public function prepare_items() {
			$this->_column_headers = array( $this->get_columns(), array(), array() );
			$this->items           = $this->rows;
		}

		/**
		 * Message shown when there are no submissions.
		 *
		 * @return void
		 */
		public function no_items() {
			esc_html_e( 'No submissions found for this form yet.', 'formward-forms' );
		}

		/**
		 * Render the "Received" column (created date).
		 *
		 * @param array $item Submission row.
		 * @return string
		 */
		public function column_created( $item ) {
			$created = isset( $item['createdAt'] ) ? formward_format_date( (string) $item['createdAt'] ) : '';
			return esc_html( $created );
		}

		/**
		 * Render the "Status" column.
		 *
		 * @param array $item Submission row.
		 * @return string
		 */
		public function column_status( $item ) {
			$status = isset( $item['status'] ) ? (string) $item['status'] : '';
			return esc_html( $status );
		}

		/**
		 * Render the "Preview" column (compact payload preview).
		 *
		 * @param array $item Submission row.
		 * @return string
		 */
		public function column_preview( $item ) {
			$payload = isset( $item['payload'] ) ? $item['payload'] : array();
			$preview = formward_payload_preview( $payload );
			if ( '' === $preview ) {
				return '<span class="description">' . esc_html__( '(empty)', 'formward-forms' ) . '</span>';
			}
			return esc_html( $preview );
		}

		/**
		 * Render the "Spam" column (spam score, if present).
		 *
		 * @param array $item Submission row.
		 * @return string
		 */
		public function column_spam( $item ) {
			if ( ! isset( $item['spamScore'] ) || null === $item['spamScore'] || '' === $item['spamScore'] ) {
				return '—';
			}
			return esc_html( (string) $item['spamScore'] );
		}

		/**
		 * Fallback column renderer (escapes any unmapped scalar value).
		 *
		 * @param array  $item        Submission row.
		 * @param string $column_name Column key.
		 * @return string
		 */
		public function column_default( $item, $column_name ) {
			$value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
			return is_scalar( $value ) ? esc_html( (string) $value ) : '';
		}
	}
}
add_action( 'admin_init', 'formward_load_list_table' );

/**
 * Render the settings page form.
 *
 * @return void
 */
function formward_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'formward_settings' );
			do_settings_sections( 'formward' );
			submit_button();
			?>
		</form>
		<hr />
		<h2><?php esc_html_e( 'Using the shortcode', 'formward-forms' ); ?></h2>
		<p>
			<?php
			echo wp_kses(
				__( 'Insert <code>[formward_form]</code> to render a contact form using your default Form ID.', 'formward-forms' ),
				array( 'code' => array() )
			);
			?>
		</p>
		<p>
			<?php
			echo wp_kses(
				__( 'Override per form: <code>[formward_form id="abc123" redirect_url="/thank-you"]</code>.', 'formward-forms' ),
				array( 'code' => array() )
			);
			?>
		</p>
		<h2><?php esc_html_e( 'Browse your account', 'formward-forms' ); ?></h2>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: Forms page URL, 2: Submissions page URL */
					__( 'With an API key set, use <a href="%1$s">Formward → Forms</a> to copy a Form ID and <a href="%2$s">Formward → Submissions</a> to view recent submissions, read-only, inside WP admin.', 'formward-forms' ),
					esc_url( admin_url( 'admin.php?page=formward-forms' ) ),
					esc_url( admin_url( 'admin.php?page=formward-submissions' ) )
				),
				array( 'a' => array( 'href' => array() ) )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Add a "Settings" action link on the Plugins list row.
 *
 * @param array $links Existing action links.
 * @return array Modified links.
 */
function formward_plugin_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'options-general.php?page=formward' ) ),
		esc_html__( 'Settings', 'formward-forms' )
	);
	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'formward_plugin_action_links' );

/* -------------------------------------------------------------------------
 * Shortcode: [formward_form]
 * ---------------------------------------------------------------------- */

/**
 * Render the Formward contact form.
 *
 * Supported attributes:
 *   id           Form ID (overrides the saved default).
 *   redirect_url Relative or absolute URL mapped to the hidden _redirect field.
 *   button       Submit button label.
 *   class        Extra CSS class on the <form> element.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string HTML markup, or an empty string when no form ID is configured.
 */
function formward_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id'           => '',
			'redirect_url' => '',
			'button'       => __( 'Send message', 'formward-forms' ),
			'class'        => '',
		),
		$atts,
		'formward_form'
	);

	// Resolve the form ID: explicit attribute, else the saved default.
	$form_id = formward_sanitize_form_id( $atts['id'] );
	if ( '' === $form_id ) {
		$form_id = formward_sanitize_form_id( get_option( FORMWARD_OPT_FORM_ID, '' ) );
	}

	// Without a form ID there is nothing to post to.
	if ( '' === $form_id ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p class="formward-notice">' . esc_html__(
				'Formward: set a default Form ID under Settings → Formward, or pass id="…" to the shortcode.',
				'formward-forms'
			) . '</p>';
		}

		return '';
	}

	$endpoint_base = formward_sanitize_endpoint( get_option( FORMWARD_OPT_ENDPOINT, FORMWARD_DEFAULT_ENDPOINT ) );
	$action_url    = $endpoint_base . '/f/' . rawurlencode( $form_id );

	$button_label = sanitize_text_field( $atts['button'] );
	// Support multiple space-separated classes, sanitising each individually
	// (sanitize_html_class mangles a multi-class string into one token).
	$extra_classes = array_filter( preg_split( '/\s+/', (string) $atts['class'] ) );
	$extra_classes = array_map( 'sanitize_html_class', $extra_classes );
	$form_class    = trim( 'formward-form ' . implode( ' ', $extra_classes ) );

	// Stable, unique field IDs so multiple forms on one page keep valid labels.
	$uid = wp_unique_id( 'formward-' );

	ob_start();
	?>
	<form
		class="<?php echo esc_attr( $form_class ); ?>"
		action="<?php echo esc_url( $action_url ); ?>"
		method="POST"
		accept-charset="UTF-8"
	>
		<?php if ( '' !== $atts['redirect_url'] ) : ?>
			<input type="hidden" name="_redirect" value="<?php echo esc_url( $atts['redirect_url'] ); ?>" />
		<?php endif; ?>

		<?php /* Honeypot: must stay empty. Hidden off-screen, not display:none. */ ?>
		<div class="formward-gotcha" aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
			<label for="<?php echo esc_attr( $uid ); ?>-gotcha"><?php esc_html_e( 'Leave this field empty', 'formward-forms' ); ?></label>
			<input
				type="text"
				id="<?php echo esc_attr( $uid ); ?>-gotcha"
				name="_gotcha"
				tabindex="-1"
				autocomplete="off"
			/>
		</div>

		<p class="formward-field">
			<label for="<?php echo esc_attr( $uid ); ?>-name"><?php esc_html_e( 'Name', 'formward-forms' ); ?></label>
			<input
				type="text"
				id="<?php echo esc_attr( $uid ); ?>-name"
				name="name"
				autocomplete="name"
				required
			/>
		</p>

		<p class="formward-field">
			<label for="<?php echo esc_attr( $uid ); ?>-email"><?php esc_html_e( 'Email', 'formward-forms' ); ?></label>
			<input
				type="email"
				id="<?php echo esc_attr( $uid ); ?>-email"
				name="email"
				autocomplete="email"
				required
			/>
		</p>

		<p class="formward-field">
			<label for="<?php echo esc_attr( $uid ); ?>-message"><?php esc_html_e( 'Message', 'formward-forms' ); ?></label>
			<textarea
				id="<?php echo esc_attr( $uid ); ?>-message"
				name="message"
				rows="6"
				required
			></textarea>
		</p>

		<p class="formward-actions">
			<button type="submit"><?php echo esc_html( $button_label ); ?></button>
		</p>
	</form>
	<?php

	return (string) ob_get_clean();
}
add_shortcode( 'formward_form', 'formward_shortcode' );
