<?php
/**
 * Class Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use Error;
use TypeError;
use WPCT_PLUGIN\Singleton;
use FBAPI;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Base addon class to be used by addons.
 */
class Addon extends Singleton {

	/**
	 * Handles acitve addons instance references.
	 *
	 * @var array<string, Addon>
	 */
	private static $addons = array();

	/**
	 * Handles addon's registry option name.
	 *
	 * @var string
	 */
	private const REGISTRY = 'forms_bridge_addons';

	/**
	 * Handles addon public name.
	 *
	 * @var string
	 */
	const TITLE = '';

	/**
	 * Handles addon's API name.
	 *
	 * @var string
	 */
	const NAME = '';

	/**
	 * Handles addon's custom bridge class name.
	 *
	 * @var string
	 */
	const BRIDGE = '\FORMS_BRIDGE\Form_Bridge';

	/**
	 * Addon's default config getter.
	 *
	 * @return array
	 */
	public static function schema() {
		$bridge_schema = FBAPI::get_bridge_schema( static::NAME );

		return array(
			'type'       => 'object',
			'properties' => array(
				'title'       => array( 'type' => 'string' ),
				'description' => array(
					'type'    => 'string',
					'default' => '',
				),
				'bridges'     => array(
					'type'    => 'array',
					'items'   => $bridge_schema,
					'default' => array(),
				),
			),
			'required'   => array( 'title', 'bridges' ),
		);
	}

	/**
	 * Addon's default data getter.
	 *
	 * @return array
	 */
	protected static function defaults() {
		return array(
			'title'   => static::TITLE,
			'bridges' => array(),
		);
	}

	/**
	 * Public singleton initializer.
	 *
	 * @param mixed[] ...$args Array of class constructor arguments.
	 */
	final public static function setup( ...$args ) {
		return static::get_instance( ...$args );
	}

	/**
	 * Public addons registry getter.
	 *
	 * @return array Addons registry state.
	 */
	private static function registry() {
		$state      = get_option( self::REGISTRY, array( 'rest' => true ) ) ?: array();
		$addons_dir = FORMS_BRIDGE_ADDONS_DIR;
		$addons     = array_diff( scandir( $addons_dir ), array( '.', '..' ) );

		$registry = array();
		foreach ( $addons as $addon ) {
			$addon_dir = "{$addons_dir}/{$addon}";
			if ( ! is_dir( $addon_dir ) ) {
				continue;
			}

			$index = "{$addon_dir}/class-{$addon}-addon.php";
			if ( is_file( $index ) && is_readable( $index ) ) {
				$registry[ $addon ] = boolval( $state[ $addon ] ?? false );
			}
		}

		return $registry;
	}

	/**
	 * Updates the addons' registry state.
	 *
	 * @param array<string, boolean> $addons Addons registry state.
	 */
	private static function update_registry( $addons = array() ) {
		$registry = self::registry();
		foreach ( $addons as $addon => $enabled ) {
			if ( ! isset( $registry[ $addon ] ) ) {
				continue;
			}

			$registry[ $addon ] = (bool) $enabled;
		}

		update_option( self::REGISTRY, $registry );
	}

	/**
	 * Public addons list getter.
	 *
	 * @return Addon[] List of enabled addon instances.
	 */
	final public static function addons() {
		$addons = array();
		foreach ( self::$addons as $addon ) {
			if ( $addon->enabled ) {
				$addons[] = $addon;
			}
		}

		return $addons;
	}

	/**
	 * Addon instances getter.
	 *
	 * @param string $name Addon name.
	 *
	 * @return Addon|null
	 */
	final public static function addon( $name ) {
		return self::$addons[ $name ] ?? null;
	}

	/**
	 * Public addons loader.
	 */
	final public static function load_addons() {
		$addons_dir = FORMS_BRIDGE_ADDONS_DIR;
		$registry   = self::registry();
		foreach ( $registry as $addon => $enabled ) {
			require_once "{$addons_dir}/{$addon}/class-{$addon}-addon.php";

			if ( $enabled ) {
				self::$addons[ $addon ]->load();
			}
		}

		Settings_Store::ready(
			function ( $store ) {
				$store::use_getter(
					'general',
					function ( $data ) {
						$registry = self::registry();
						$addons   = array();
						foreach ( self::$addons as $name => $addon ) {
							$logo_path = FORMS_BRIDGE_ADDONS_DIR . '/' . $addon::NAME . '/assets/logo.png';

							if ( is_file( $logo_path ) && is_readable( $logo_path ) ) {
								$logo = plugin_dir_url( $logo_path ) . 'logo.png';
							} else {
								$logo = '';
							}

							$addons[ $name ] = array(
								'name'    => $name,
								'title'   => $addon::TITLE,
								'enabled' => $registry[ $name ] ?? false,
								'logo'    => $logo,
							);
						}

						ksort( $addons );
						$addons = array_values( $addons );

						$addons = apply_filters( 'forms_bridge_addons', $addons );
						return array_merge( $data, array( 'addons' => $addons ) );
					}
				);

				$store::use_setter(
					'general',
					function ( $data ) {
						if ( ! isset( $data['addons'] ) || ! is_array( $data['addons'] ) ) {
							return $data;
						}

						$registry = array();
						foreach ( $data['addons'] as $addon ) {
							$registry[ $addon['name'] ] = (bool) $addon['enabled'];
						}

						self::update_registry( $registry );

						unset( $data['addons'] );
						return $data;
					},
					9
				);
			}
		);
	}

	/**
	 * Middelware to the addon settings validation method to filter out of domain
	 * setting updates.
	 *
	 * @param array $data Setting data.
	 *
	 * @return array Validated setting data.
	 */
	private static function sanitize_setting( $data ) {
		if ( ! isset( $data['bridges'] ) ) {
			return $data;
		}

		$data['bridges'] = static::sanitize_bridges( $data['bridges'] );

		return $data;
	}

	/**
	 * Apply bridges setting data sanitization and validation.
	 *
	 * @param array $bridges Collection of bridges data.
	 *
	 * @return array
	 */
	private static function sanitize_bridges( $bridges ) {
		$uniques   = array();
		$sanitized = array();

		$schema = FBAPI::get_bridge_schema( static::NAME );
		foreach ( $bridges as $bridge ) {
			$bridge['name'] = trim( $bridge['name'] );
			if ( in_array( $bridge['name'], $uniques, true ) ) {
				continue;
			}

			$bridge = static::sanitize_bridge( $bridge, $schema );
			if ( $bridge ) {
				$sanitized[] = $bridge;
				$uniques[]   = $bridge['name'];
			}
		}

		return $sanitized;
	}

	/**
	 * Common bridge sanitization method.
	 *
	 * @param array $bridge Bridge data.
	 * @param array $schema Bridge schema.
	 *
	 * @return array
	 */
	protected static function sanitize_bridge( $bridge, $schema ) {
		$backends = Settings_Store::setting( 'http' )->backends ?: array();

		foreach ( $backends as $candidate ) {
			if ( $candidate['name'] === $bridge['backend'] ) {
				$backend = $candidate;
				break;
			}
		}

		if ( ! isset( $backend ) ) {
			$bridge['backend'] = '';
		}

		static $forms;
		if ( null === $forms ) {
			$forms = FBAPI::get_forms();
		}

		foreach ( $forms as $candidate ) {
			if ( $candidate['_id'] === $bridge['form_id'] ) {
				$form = $candidate;
				break;
			}
		}

		if ( ! isset( $form ) ) {
			$bridge['form_id'] = '';
		}

		$bridge['mutations'] = array_slice(
			$bridge['mutations'],
			0,
			count( $bridge['workflow'] ) + 1
		);

		$l = count( $bridge['workflow'] );
		for ( $i = 0; $i <= $l; $i++ ) {
			$bridge['mutations'][ $i ] = $bridge['mutations'][ $i ] ?? array();
		}

		$bridge['is_valid'] =
			$bridge['form_id'] &&
			$bridge['backend'] &&
			$bridge['method'] &&
			$bridge['endpoint'];

		$bridge['enabled'] = boolval( $bridge['enabled'] ?? true );

		return $bridge;
	}

	/**
	 * Handles the enabled state of the addon instance.
	 *
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * Private class constructor. Add addons scripts as dependency to the
	 * plugin's scripts and setup settings hooks.
	 *
	 * @param mixed[] ...$args Array of class constructor arguments.
	 */
	protected function construct( ...$args ) {
		if ( empty( static::NAME ) || empty( static::TITLE ) ) {
			Logger::log( 'Skip invalid addon registration', Logger::DEBUG );
			Logger::log(
				'Addon name and title const are required',
				Logger::ERROR
			);
			return;
		}

		self::$addons[ static::NAME ] = $this;
	}

	/**
	 * Loads the addon.
	 */
	public function load() {
		add_action(
			'init',
			static function () {
				self::load_data();
			},
			5,
			0
		);

		add_filter(
			'forms_bridge_templates',
			static function ( $templates, $addon = null ) {
				if ( ! wp_is_numeric_array( $templates ) ) {
					$templates = array();
				}

				if ( $addon && static::NAME !== $addon ) {
					return $templates;
				}

				foreach ( static::load_templates() as $template ) {
					$templates[] = $template;
				}

				return $templates;
			},
			10,
			2
		);

		add_filter(
			'forms_bridge_jobs',
			static function ( $jobs, $addon = null ) {
				if ( ! wp_is_numeric_array( $jobs ) ) {
					$jobs = array();
				}

				if ( $addon && static::NAME !== $addon ) {
					return $jobs;
				}

				foreach ( static::load_jobs() as $job ) {
					$jobs[] = $job;
				}

				return $jobs;
			},
			10,
			2
		);

		add_filter(
			'forms_bridge_bridges',
			static function ( $bridges, $addon = null ) {
				if ( ! wp_is_numeric_array( $bridges ) ) {
					$bridges = array();
				}

				if ( $addon && static::NAME !== $addon ) {
					return $bridges;
				}

				$setting = static::setting();
				if ( ! $setting ) {
					return $bridges;
				}

				foreach ( $setting->bridges ?: array() as $bridge_data ) {
					$bridge_class = static::BRIDGE;
					$bridges[]    = new $bridge_class( $bridge_data, static::NAME );
				}

				return $bridges;
			},
			10,
			2
		);

		Settings_Store::register_setting(
			static function ( $settings ) {
				$schema            = static::schema();
				$schema['name']    = static::NAME;
				$schema['default'] = static::defaults();

				$settings[] = $schema;
				return $settings;
			}
		);

		Settings_Store::ready(
			static function ( $store ) {
				$store::use_getter(
					static::NAME,
					static function ( $data ) {
						$templates = FBAPI::get_addon_templates( static::NAME );
						$jobs      = FBAPI::get_addon_jobs( static::NAME );

						return array_merge(
							$data,
							array(
								'templates' => array_map(
									static function ( $template ) {
										return array(
											'title'        => $template->title,
											'name'         => $template->name,
											'integrations' => $template->integrations,
										);
									},
									$templates
								),
								'jobs'      => array_map(
									static function ( $job ) {
										return array(
											'title' => $job->title,
											'name'  => $job->name,
										);
									},
									$jobs
								),
							)
						);
					}
				);

				$store::use_setter(
					static::NAME,
					static function ( $data ) {
						if ( ! is_array( $data ) ) {
							return $data;
						}

						unset( $data['templates'] );
						unset( $data['jobs'] );

						return static::sanitize_setting( $data );
					},
					9
				);
			}
		);

		$this->enabled = true;
	}

	/**
	 * Addon's setting name getter.
	 *
	 * @return string
	 */
	final protected static function setting_name() {
		return 'forms-bridge_' . static::NAME;
	}

	/**
	 * Addon's setting instance getter.
	 *
	 * @return Setting|null Setting instance.
	 */
	final protected static function setting() {
		return Forms_Bridge::setting( static::NAME );
	}

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Target backend name.
	 *
	 * @return boolean|WP_Error
	 */
	public function ping( $backend ) {
		Logger::log( 'This adddon bridges has not known ping endpoint', Logger::ERROR );
		return false;
	}

	/**
	 * Performs a GET request against the backend endpoint and retrive the response data.
	 *
	 * @param string $endpoint Target endpoint name.
	 * @param string $backend Target backend name.
	 *
	 * @return array|WP_Error
	 */
	public function fetch( $endpoint, $backend ) {
		$bridge_class = static::BRIDGE;

		$bridge = new $bridge_class(
			array(
				'name'     => '__' . self::NAME . '-' . time(),
				'endpoint' => $endpoint,
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		return $bridge->submit();
	}

	/**
	 * Performs an introspection of the backend endpoint and returns API fields
	 * and accepted content type.
	 *
	 * @param string      $endpoint Target endpoint name.
	 * @param string      $backend Target backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array|WP_Error
	 */
	public function get_endpoint_schema( $endpoint, $backend, $method = null ) {
		return array();
	}

	/**
	 * Performs an introspection of the backend API and returns a list of available endpoints.
	 *
	 * @param string      $backend Target backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array|WP_Error
	 */
	public function get_endpoints( $backend, $method = null ) {
		return array();
	}

	/**
	 * Get posts from the database based on a post type and an addon name.
	 *
	 * @param string $post_type Post type slug.
	 * @param string $addon Addon name.
	 *
	 * @return WP_Post[]
	 */
	private static function autoload_posts( $post_type, $addon ) {
		if ( ! in_array( $post_type, array( Form_Bridge_Template::TYPE, Job::TYPE ), true ) ) {
			return array();
		}

		// phpcs:disable WordPress.DB.SlowDBQuery
		return get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'meta_key'       => '_fb-addon',
				'meta_value'     => $addon,
			)
		);
		// phpcs:enable
	}

	/**
	 * Autoload config files from a given addon's directory. Used to load
	 * template and job config files.
	 *
	 * @param string   $dir Path of the target directory.
	 * @param string[] $extensions Allowed file extensions.
	 *
	 * @return array Array with data from files.
	 */
	private static function autoload_dir( $dir, $extensions = array( 'php', 'json' ) ) {
		if ( ! is_readable( $dir ) || ! is_dir( $dir ) ) {
			return array();
		}

		static $load_cache;

		$files = array();
		foreach ( array_diff( scandir( $dir ), array( '.', '..' ) ) as $file ) {
			$file_path = $dir . '/' . $file;

			if ( is_file( $file_path ) && is_readable( $file_path ) ) {
				$files[] = $file_path;
			}
		}

		$loaded = array();
		foreach ( $files as $file_path ) {
			$file = basename( $file_path );
			$name = pathinfo( $file )['filename'];
			$ext  = pathinfo( $file )['extension'] ?? null;

			if ( ! in_array( $ext, $extensions, true ) ) {
				continue;
			}

			if ( isset( $load_cache[ $file_path ] ) ) {
				$loaded[] = $load_cache[ $file_path ];
				continue;
			}

			$data = null;
			if ( 'php' === $ext ) {
				$data = include_once $file_path;
			} elseif ( 'json' === $ext ) {
				// phpcs:disable Generic.CodeAnalysis.EmptyStatement
				try {
					$content = file_get_contents( $file_path );
					$data    = json_decode( $content, true, JSON_THROW_ON_ERROR );
				} catch ( TypeError ) {
					// pass.
				} catch ( Error ) {
					// pass.
				}
				// phpcs:enable
			}

			if ( is_array( $data ) ) {
				$data['name']             = $name;
				$loaded[]                 = $data;
				$load_cache[ $file_path ] = $data;
			}
		}

		return $loaded;
	}

	/**
	 * Loads addon's bridge data.
	 */
	private static function load_data() {
		$dir = FORMS_BRIDGE_ADDONS_DIR . '/' . static::NAME . '/data';
		self::autoload_dir( $dir );
	}

	/**
	 * Loads addon's bridge templates.
	 *
	 * @return Form_Bridge_Template[].
	 */
	private static function load_templates() {
		$dir = FORMS_BRIDGE_ADDONS_DIR . '/' . static::NAME . '/templates';

		$directories = apply_filters(
			'forms_bridge_template_directories',
			array(
				$dir,
				Forms_Bridge::path() . 'includes/templates',
				get_stylesheet_directory() . '/forms-bridge/templates/' . static::NAME,
			),
			static::NAME
		);

		$templates = array();
		foreach ( $directories as $dir ) {
			if ( ! is_dir( $dir ) ) {
				continue;
			}

			foreach ( self::autoload_dir( $dir ) as $template ) {
				$template['name']               = sanitize_title( $template['name'] );
				$templates[ $template['name'] ] = $template;
			}
		}

		foreach (
			self::autoload_posts( 'fb-bridge-template', static::NAME )
			as $template_post
		) {
			$template[ $template->post_name ] = $template_post;
		}

		$templates = array_values( $templates );

		$templates = apply_filters(
			'forms_bridge_load_templates',
			$templates,
			static::NAME
		);

		$loaded = array();
		foreach ( $templates as $template ) {
			if (
				is_array( $template ) &&
				isset( $template['data'], $template['name'] )
			) {
				$template = array_merge(
					$template['data'],
					array(
						'name' => $template['name'],
					)
				);
			}

			$template = new Form_Bridge_Template( $template, static::NAME );

			if ( $template->is_valid ) {
				$loaded[] = $template;
			}
		}

		return $loaded;
	}

	/**
	 * Addon's jobs loader.
	 *
	 * @return Job[]
	 */
	private static function load_jobs() {
		$dir = FORMS_BRIDGE_ADDONS_DIR . '/' . static::NAME . '/jobs';

		$directories = apply_filters(
			'forms_bridge_job_directories',
			array(
				$dir,
				Forms_Bridge::path() . '/includes/jobs',
				get_stylesheet_directory() . '/forms-bridge/jobs/' . static::NAME,
			),
			static::NAME
		);

		$jobs = array();
		foreach ( $directories as $dir ) {
			if ( ! is_dir( $dir ) ) {
				continue;
			}

			foreach ( self::autoload_dir( $dir ) as $job ) {
				$job['name']          = sanitize_title( $job['name'] );
				$jobs[ $job['name'] ] = $job;
			}
		}

		foreach ( self::autoload_posts( 'fb-job', static::NAME ) as $job_post ) {
			$jobs[ $job_post->post_name ] = $job_post;
		}

		$jobs = array_values( $jobs );

		$jobs = apply_filters( 'forms_bridge_load_jobs', $jobs, static::NAME );

		$loaded = array();
		foreach ( $jobs as $job ) {
			if ( is_array( $job ) && isset( $job['data'], $job['name'] ) ) {
				$job = array_merge( $job['data'], array( 'name' => $job['name'] ) );
			}

			$job = new Job( $job, static::NAME );

			if ( $job->is_valid ) {
				$loaded[] = $job;
			}
		}

		return $loaded;
	}

	// phpcs:disable
	// public static function get_api()
	// {
	// $__FILE__ = (new ReflectionClass(static::class))->getFileName();
	// $file = dirname($__FILE__) . '/api.php';

	// if (!is_file($file) || !is_readable($file)) {
	// return [];
	// }

	// $source = file_get_contents($file);
	// $tokens = token_get_all($source);

	// $functions = [];
	// $nextStringIsFunc = false;
	// $inClass = false;
	// $bracesCount = 0;

	// foreach ($tokens as $token) {
	// switch ($token[0]) {
	// case T_CLASS:
	// $inClass = true;
	// break;
	// case T_FUNCTION:
	// if (!$inClass) {
	// $nextStringIsFunc = true;
	// }
	// break;

	// case T_STRING:
	// if ($nextStringIsFunc) {
	// $nextStringIsFunc = false;
	// $functions[] = $token[1];
	// }
	// break;
	// case '(':
	// case ';':
	// $nextStringIsFunc = false;
	// break;
	// case '{':
	// if ($inClass) {
	// $bracesCount++;
	// }
	// break;

	// case '}':
	// if ($inClass) {
	// $bracesCount--;
	// if ($bracesCount === 0) {
	// $inClass = false;
	// }
	// }
	// break;
	// }
	// }

	// return $functions;
	// }
}
