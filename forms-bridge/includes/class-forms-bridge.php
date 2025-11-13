<?php
/**
 * Class Forms_Bridge
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use Error;
use Exception;
use WP_Error;
use WPCT_PLUGIN\Plugin as Base_Plugin;
use FBAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Forms Bridge plugin.
 */
class Forms_Bridge extends Base_Plugin {

	/**
	 * Handles the plugin db version option name.
	 *
	 * @var string
	 */
	const DB_VERSION = 'forms-bridge-version';

	/**
	 * Handles plugin settings class name.
	 *
	 * @var string
	 */
	const STORE = '\FORMS_BRIDGE\Settings_Store';

	/**
	 * Handle plugin menu class name.
	 *
	 * @var string
	 */
	const MENU = '\FORMS_BRIDGE\Menu';

	/**
	 * Handles the current bridge instance. Available only during form submissions.
	 *
	 * @var Form_Bridge|null
	 */
	private static $current_bridge;

	/**
	 * Initializes integrations, addons and setup plugin hooks.
	 *
	 * @param mixed[] ...$args Constructor arguments.
	 */
	protected function construct( ...$args ) {
		parent::construct( ...$args );

		Addon::load_addons();
		Integration::load_integrations();

		add_action(
			'admin_enqueue_scripts',
			static function ( $admin_page ) {
				if ( 'settings_page_forms-bridge' === $admin_page ) {
					self::admin_enqueue_scripts();
				}
			}
		);

		add_filter(
			'plugin_action_links',
			static function ( $links, $file ) {
				if ( 'forms-bridge/forms-bridge.php' !== $file ) {
					return $links;
				}

				$url   = 'https://formsbridge.codeccoop.org/documentation/';
				$label = __( 'Documentation', 'forms-bridge' );
				$link  = sprintf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( $url ),
					esc_html( $label )
				);
				array_push( $links, $link );

				return $links;
			},
			15,
			2
		);

		add_action(
			'forms_bridge_on_failure',
			static function ( $bridge, $error, $payload, $attachments = array() ) {
				self::notify_error( $bridge, $error, $payload, $attachments );
			},
			99,
			4
		);

		add_action( 'init', array( self::class, 'load_data' ), 0, 0 );

		add_action(
			'in_plugin_update_message-forms-bridge/forms-bridge.php',
			function ( $plugin_data, $response ) {
				if ( 'forms-bridge' !== $response->slug ) {
					return;
				}

				if (
					! preg_match(
						'/^(\d+)\.\d+\.\d+$/',
						$response->new_version,
						$matches
					)
				) {
					return;
				}

				$new_version = $matches[1];
				$db_version  = get_option( self::DB_VERSION, '1.0.0' );

				if ( ! preg_match( '/^(\d+)\.\d+\.\d+$/', $db_version, $matches ) ) {
					return;
				}

				$from_version = $matches[1];

				if ( $new_version > $from_version ) {
					echo '<br /><b>' .
						'&nbsp' .
						esc_html(
							__(
								'This is a major release and while tested thoroughly you might experience conflicts or lost data. We recommend you back up your data before updating and check your configuration after updating.',
								'forms-bridge'
							),
						) .
						'</b>';
				}
			},
			10,
			2
		);
	}

	/**
	 * Plugin activation callback. Stores the plugin version on the database
	 * if it doesn't exists.
	 */
	public static function activate() {
		$version = get_option( self::DB_VERSION );
		if ( false === $version ) {
			update_option( self::DB_VERSION, self::version(), true );
		}
	}

	/**
	 * Init hook callabck. Checks if the stored db version mismatch the current plugin version
	 * and, if it is, performs db migrations.
	 */
	protected static function init() {
		$db_version = get_option( self::DB_VERSION );
		if ( self::version() !== $db_version && ! defined( 'WP_TESTS_DOMAIN' ) ) {
			self::do_migrations();
		}
	}

	/**
	 * Data loader.
	 */
	public static function load_data() {
		$data_dir = self::path() . '/data';

		foreach ( array_diff( scandir( $data_dir ), array( '.', '..' ) ) as $file ) {
			$filepath = "{$data_dir}/{$file}";
			if ( is_file( $filepath ) && is_readable( $filepath ) ) {
				require_once $filepath;
			}
		}
	}

	/**
	 * Enqueue admin client scripts
	 */
	private static function admin_enqueue_scripts() {
		$version = self::version();

		wp_enqueue_script(
			'forms-bridge',
			plugins_url( 'assets/plugin.bundle.js', self::index() ),
			array(
				'react',
				'react-jsx-runtime',
				'wp-api-fetch',
				'wp-components',
				'wp-dom-ready',
				'wp-element',
				'wp-i18n',
				'wp-api',
			),
			$version,
			array( 'in_footer' => true )
		);

		wp_set_script_translations(
			'forms-bridge',
			'forms-bridge',
			self::path() . 'languages'
		);

		wp_enqueue_style( 'wp-components' );

		wp_enqueue_style(
			'highlight-js',
			'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/github.min.css',
			array(),
			'11.11.1'
		);

		wp_enqueue_script(
			'highlight-js',
			'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js',
			array(),
			'11.11.1',
			true,
		);
	}

	/**
	 * Public access to the current bridge reference.
	 *
	 * @return Form_Bridge|null
	 */
	public static function current_bridge() {
		return self::$current_bridge;
	}

	/**
	 * Proceed with the submission sub-routine.
	 *
	 * @throws Error     In case email error notification fails.
	 * @throws Exception In case email error notification fails.
	 */
	public static function do_submission() {
		$form_data = FBAPI::get_current_form();

		if ( ! $form_data ) {
			return;
		}

		if ( empty( $form_data['bridges'] ) ) {
			return;
		}

		Logger::log( 'Form data' );
		Logger::log(
			array(
				'id'     => $form_data['id'],
				'title'  => $form_data['title'],
				'fields' => array_map(
					function ( $field ) {
						return $field['name'] ?? '';
					},
					$form_data['fields']
				),
			)
		);

		$bridges = $form_data['bridges'];

		$submission = FBAPI::get_submission();
		Logger::log( 'Form submission' );
		Logger::log( $submission );

		$uploads = FBAPI::get_uploads();
		Logger::log( 'Submission uploads' );
		Logger::log( $uploads );

		if ( empty( $submission ) && empty( $uploads ) ) {
			return;
		}

		foreach ( $bridges as $bridge ) {
			if ( ! $bridge->enabled ) {
				Logger::log(
					'Skip submission for disabled bridge ' . $bridge->name
				);
				continue;
			}

			self::$current_bridge = $bridge;

			try {
				$attachments = apply_filters(
					'forms_bridge_attachments',
					self::attachments( $uploads ),
					$bridge
				);

				if ( ! empty( $attachments ) ) {
					$content_type = (string) $bridge->content_type;
					if (
						in_array(
							strtolower( $content_type ),
							array(
								'application/json',
								'application/x-www-form-urlencoded',
							),
							true
						)
					) {
						$attachments = self::stringify_attachments(
							$attachments
						);
						foreach ( $attachments as $name => $value ) {
							$submission[ $name ] = $value;
						}
						$attachments = array();
						Logger::log( 'Submission after attachments stringify' );
						Logger::log( $submission );
					}
				}

				$payload = $bridge->add_custom_fields( $submission );
				Logger::log( 'Submission payload with bridge custom fields' );
				Logger::log( $payload );

				$bridge->prepare_mappers( $form_data );
				$payload = $bridge->apply_mutation( $payload );
				Logger::log( 'Submission payload after mutation' );
				Logger::log( $payload );

				$prune_empties = apply_filters(
					'forms_bridge_prune_empties',
					true,
					$bridge
				);

				if ( $prune_empties ) {
					$payload = self::prune_empties( $payload );
					Logger::log( 'Submission payload after prune empties' );
					Logger::log( $payload );
				}

				$workflow = $bridge->workflow;
				if ( $workflow ) {
					$payload = $workflow->run( $payload, $bridge );

					if ( empty( $payload ) ) {
						Logger::log( 'Skip empty payload after bridge workflow' );
						continue;
					}

					Logger::log( 'Payload after workflow' );
					Logger::log( $payload );
				}

				$payload = apply_filters(
					'forms_bridge_payload',
					$payload,
					$bridge
				);

				if ( empty( $payload ) ) {
					Logger::log( 'Skip empty payload after user filter' );
					continue;
				}

				Logger::log( 'Bridge payload' );
				Logger::log( $payload );

				$skip = apply_filters(
					'forms_bridge_skip_submission',
					false,
					$bridge,
					$payload,
					$attachments
				);

				if ( $skip ) {
					Logger::log( 'Skip submission' );
					continue;
				}

				do_action(
					'forms_bridge_before_submission',
					$bridge,
					$payload,
					$attachments
				);

				$response = $bridge->submit( $payload, $attachments );

				$error = is_wp_error( $response ) ? $response : null;
				if ( $error ) {
					do_action(
						'forms_bridge_on_failure',
						$bridge,
						$error,
						$payload,
						$attachments
					);
				} else {
					Logger::log( 'Submission response' );
					Logger::log( $response );

					do_action(
						'forms_bridge_after_submission',
						$bridge,
						$response,
						$payload,
						$attachments
					);
				}
			} catch ( Error | Exception $e ) {
				$message = $e->getMessage();
				if ( 'notification_error' === $message ) {
					throw $e;
				}

				$error = new WP_Error(
					'internal_server_error',
					$e->getMessage(),
					array(
						'file' => $e->getFile(),
						'line' => $e->getLine(),
					)
				);

				do_action(
					'forms_bridge_on_failure',
					$bridge,
					$error,
					$payload ?? $submission,
					$attachments ?? array()
				);
			} finally {
				self::$current_bridge = null;
			}
		}
	}

	/**
	 * Clean up submission empty fields.
	 *
	 * @param array $submission_data Submission data.
	 *
	 * @return array Submission data without empty fields.
	 */
	private static function prune_empties( $submission_data ) {
		foreach ( $submission_data as $key => $val ) {
			if ( '' === $val || null === $val ) {
				unset( $submission_data[ $key ] );
			}
		}

		return $submission_data;
	}

	/**
	 * Transform collection of uploads to an attachments map.
	 *
	 * @param array $uploads Collection of uploaded files.
	 *
	 * @return array Map of uploaded files.
	 */
	private static function attachments( $uploads ) {
		$attachments = array();

		foreach ( $uploads as $name => $upload ) {
			if ( $upload['is_multi'] ) {
								$len = count( $uploads[ $name ]['path'] );
				for ( $i = 1; $i <= $len; $i++ ) {
					$attachments[ $name . '_' . $i ] = $upload['path'][ $i - 1 ];
				}
			} else {
				$attachments[ $name ] = $upload['path'];
			}
		}

		return $attachments;
	}

	/**
	 * Returns the attachments array with each attachment path replaced with its
	 * content as a base64 encoded string. For each file on the list, adds an
	 * additonal field with the file name on the response.
	 *
	 * @param array $attachments Submission attachments data.
	 *
	 * @return array Array with base64 encoded file contents and file names.
	 */
	private static function stringify_attachments( $attachments ) {
		foreach ( $attachments as $name => $path ) {
			if ( ! is_file( $path ) || ! is_readable( $path ) ) {
				continue;
			}

			$suffix = '';
			if ( preg_match( '/_\d+$/', $name, $matches ) ) {
				$suffix = $matches[0];
				$name   = substr( $name, 0, -strlen( $suffix ) );
			}

			$filename                                     = basename( $path );
			$content                                      = file_get_contents( $path );
			$attachments[ $name . $suffix ]               = base64_encode( $content );
			$attachments[ $name . '_filename' . $suffix ] = $filename;
		}

		return $attachments;
	}

	/**
	 * Sends error notifications to the email receiver.
	 *
	 * @param Form_Bridge $bridge Bridge instance.
	 * @param WP_Error    $error Error instance.
	 * @param array       $payload Submission data.
	 * @param array       $attachments Submission attachments.
	 *
	 * @throws Exception  When email notification fails.
	 */
	private static function notify_error(
		$bridge,
		$error,
		$payload,
		$attachments = array()
	) {
		$email = Settings_Store::setting( 'general' )->notification_receiver;

		if ( empty( $email ) ) {
			return;
		}

		$skip = apply_filters(
			'forms_bridge_skip_error_notification',
			false,
			$error,
			$bridge,
			$payload,
			$attachments
		);

		if ( $skip ) {
			Logger::log( 'Skip error notification' );
			return;
		}

		$form_data = $bridge->form;
		$payload   = wp_json_encode( $payload, JSON_PRETTY_PRINT );
		$error     = wp_json_encode(
			array(
				'error'   => $error->get_error_message(),
				'context' => $error->get_error_data(),
			),
			JSON_PRETTY_PRINT
		);

		Logger::log( 'Bridge submission error', Logger::ERROR );
		Logger::log( $error, Logger::ERROR );

		$to      = $email;
		$subject = 'Forms Bridge Error';
		$body    = "Form ID: {$form_data['id']}\n";
		$body   .= "Form title: {$form_data['title']}\n";
		$body   .= "Bridge name: {$bridge->name}\n";
		$body   .= "Payload: {$payload}\n";
		$body   .= "Error: {$error}\n";

		$from_email = get_option( 'admin_email' );
		$headers    = array( "From: Forms Bridge <{$from_email}>" );

		Logger::log( 'Error notification' );
		Logger::log( $body );

		$success = wp_mail( $to, $subject, $body, $headers, $attachments );
		if ( ! $success ) {
			throw new Exception( 'notification_error' );
		}
	}

	/**
	 * Apply db migrations on plugin upgrades.
	 */
	private static function do_migrations() {
		$action = sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) );
		if ( 'heartbeat' === $action && wp_doing_ajax() ) {
			return;
		}

		$from = get_option( self::DB_VERSION, self::version() );

		if ( ! preg_match( '/^\d+\.\d+\.\d+$/', $from ) ) {
			Logger::log( 'Invalid db plugin version', Logger::ERROR );
			return;
		}

		$to = self::version();

		$migrations      = array();
		$migrations_path = self::path() . '/migrations';

		$as_int = fn( $version ) => (int) str_replace( '.', '', $version );

		foreach (
			array_diff( scandir( $migrations_path ), array( '.', '..' ) )
			as $migration
		) {
			$version = pathinfo( $migrations_path . '/' . $migration )['filename'];

			if ( $as_int( $version ) > $as_int( $to ) ) {
				break;
			}

			if ( ! empty( $migrations ) ) {
				$migrations[] = $migration;
				continue;
			}

			if (
				$as_int( $version ) > $as_int( $from ) &&
				$as_int( $version ) <= $as_int( $to )
			) {
				$migrations[] = $migration;
			}
		}

		sort( $migrations );
		foreach ( $migrations as $migration ) {
			include $migrations_path . '/' . $migration;
		}

		update_option( self::DB_VERSION, $to );
	}

	/**
	 * Gets the path to the plugin namespaced upload directory.
	 *
	 * @return string
	 */
	public static function upload_dir() {
		$dir = wp_upload_dir()['basedir'] . '/forms-bridge';

		if ( ! is_dir( $dir ) ) {
			if ( ! wp_mkdir_p( $dir ) ) {
				return;
			}
		}

		return $dir;
	}
}
