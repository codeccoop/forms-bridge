<?php
/**
 * Class REST_Settings_Controller
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use WP_Error;
use WP_REST_Server;
use WPCT_PLUGIN\REST_Settings_Controller as Base_Controller;
use FBAPI;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Plugin REST API controller. Handles routes registration, permissions
 * and request callbacks.
 */
class REST_Settings_Controller extends Base_Controller {

	/**
	 * Handles the current introspection request data as json.
	 *
	 * @var string|null
	 */
	private static $introspection_data = null;

	/**
	 * Inherits the parent initialized and register the post types route
	 */
	protected static function init() {
		parent::init();
		self::register_forms_route();
		self::register_schema_route();
		self::register_template_routes();
		self::register_job_routes();
		self::register_backend_routes();
	}

	/**
	 * Registers form REST API routes.
	 */
	private static function register_forms_route() {
		register_rest_route(
			'forms-bridge/v1',
			'/forms',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static function () {
					return self::forms();
				},
				'permission_callback' => array( self::class, 'permission_callback' ),
			)
		);
	}

	/**
	 * Registers json schemas REST API routes.
	 */
	private static function register_schema_route() {
		foreach ( Addon::addons() as $addon ) {
			if ( ! $addon->enabled ) {
				continue;
			}

			$addon = $addon::NAME;
			register_rest_route(
				'forms-bridge/v1',
				"/{$addon}/schemas",
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => static function () use ( $addon ) {
						return self::addon_schemas( $addon );
					},
					'permission_callback' => array( self::class, 'permission_callback' ),
				)
			);
		}

		register_rest_route(
			'forms-bridge/v1',
			'/http/schemas',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static function () {
					return self::http_schemas();
				},
				'permission_callback' => array( self::class, 'permission_callback' ),
			)
		);
	}

	/**
	 * Registers templates REST API routes.
	 */
	private static function register_template_routes() {
		foreach ( Addon::addons() as $addon ) {
			if ( ! $addon->enabled ) {
				continue;
			}

			$addon = $addon::NAME;

			$schema = Form_Bridge_Template::schema( $addon );
			$args   = array();

			foreach ( $schema['properties'] as $name => $prop_schema ) {
				$args[ $name ]             = $prop_schema;
				$args[ $name ]['required'] = in_array(
					$name,
					$schema['required'],
					true
				);
			}

			register_rest_route(
				'forms-bridge/v1',
				"/{$addon}/templates/(?P<name>[a-zA-Z0-9-_]+)",
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => static function ( $request ) use ( $addon ) {
							return self::get_template( $addon, $request );
						},
						'permission_callback' => array(
							self::class,
							'permission_callback',
						),
						'args'                => array(
							'name' => $args['name'],
						),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => static function ( $request ) use ( $addon ) {
							return self::save_template( $addon, $request );
						},
						'permission_callback' => array(
							self::class,
							'permission_callback',
						),
						'args'                => $args,
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => static function ( $request ) use ( $addon ) {
							return self::reset_template( $addon, $request );
						},
						'permission_callback' => array(
							self::class,
							'permission_callback',
						),
						'args'                => array(
							'name' => $args['name'],
						),
					),
				)
			);

			register_rest_route(
				'forms-bridge/v1',
				"/{$addon}/templates/(?P<name>[a-zA-Z0-9-_]+)/use",
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => static function ( $request ) use ( $addon ) {
						return self::use_template( $addon, $request );
					},
					'permission_callback' => array(
						self::class,
						'permission_callback',
					),
					'args'                => array(
						'name'        => $args['name'],
						'integration' => array(
							'description' => __(
								'Target integration',
								'forms-bridge'
							),
							'type'        => 'string',
							'required'    => true,
						),
						'fields'      => $args['fields'],
					),
				)
			);

			register_rest_route(
				'forms-bridge/v1',
				"/{$addon}/templates/(?P<name>[a-zA-Z0-9-_]+)/options",
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => static function ( $request ) use ( $addon ) {
						return self::get_template_options( $addon, $request );
					},
					'permission_callback' => array(
						self::class,
						'permission_callback',
					),
					'args'                => array(
						'name'       => $args['name'],
						'backend'    => FBAPI::get_backend_schema(),
						'credential' => FBAPI::get_credential_schema(),
					),
				)
			);
		}
	}

	/**
	 * Registers jobs REST API routes.
	 */
	private static function register_job_routes() {
		foreach ( Addon::addons() as $addon ) {
			if ( ! $addon->enabled ) {
				continue;
			}

			$addon = $addon::NAME;

			$schema = Job::schema();
			$args   = array();

			foreach ( $schema['properties'] as $name => $prop_schema ) {
				$args[ $name ]             = $prop_schema;
				$args[ $name ]['required'] = in_array(
					$name,
					$schema['required'],
					true
				);
			}

			register_rest_route(
				'forms-bridge/v1',
				"/{$addon}/jobs/workflow",
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => static function ( $request ) use ( $addon ) {
						return self::get_jobs( $addon, $request );
					},
					'permission_callback' => array( self::class, 'permission_callback' ),
					'args'                => array(
						'jobs' => array(
							'description' => __(
								'Array of job names',
								'forms-bridge'
							),
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'uniqueItems' => true,
							'minItems'    => 1,
							'required'    => true,
						),
					),
				)
			);

			register_rest_route(
				'forms-bridge/v1',
				"/{$addon}/jobs/(?P<name>[a-zA-Z0-9-_]+)",
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => static function ( $request ) use ( $addon ) {
							return self::get_job( $addon, $request );
						},
						'permission_callback' => array(
							self::class,
							'permission_callback',
						),
						'args'                => array(
							'name' => $args['name'],
						),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => static function ( $request ) use ( $addon ) {
							return self::save_job( $addon, $request );
						},
						'permission_callback' => array(
							self::class,
							'permission_callback',
						),
						'args'                => $args,
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => static function ( $request ) use ( $addon ) {
							return self::reset_job( $addon, $request );
						},
						'permission_callback' => array(
							self::class,
							'permission_callback',
						),
						'args'                => array(
							'name' => $args['name'],
						),
					),
				)
			);
		}
	}

	/**
	 * Registers http backends REST API routes.
	 */
	private static function register_backend_routes() {
		foreach ( Addon::addons() as $addon ) {
			if ( ! $addon->enabled ) {
				continue;
			}

			$addon = $addon::NAME;

			// $schema = Form_Bridge_Template::schema($addon);
			// $args = [];

			// foreach ($schema['properties'] as $name => $prop_schema) {
			// $args[$name] = $prop_schema;
			// $args[$name]['required'] = in_array(
			// $name,
			// $schema['required'],
			// true
			// );
			// }

			register_rest_route(
				'forms-bridge/v1',
				"/{$addon}/backend/ping",
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => static function ( $request ) use ( $addon ) {
							return self::ping_backend( $addon, $request );
						},
						'permission_callback' => array( self::class, 'permission_callback' ),
						'args'                => array(
							'backend'    => FBAPI::get_backend_schema(),
							'credential' => FBAPI::get_credential_schema(),
						),
					),
				)
			);

			register_rest_route(
				'forms-bridge/v1',
				"/{$addon}/backend/endpoints",
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => static function ( $request ) use ( $addon ) {
							return self::get_backend_endpoints( $addon, $request );
						},
						'permission_callback' => array( self::class, 'permission_callback' ),
						'args'                => array(
							'backend' => FBAPI::get_backend_schema(),
							'method'  => array(
								'description' => __( 'HTTP method used to filter the list of endpoints', 'forms-bridge' ),
								'type'        => 'string',
							),
						),
					),
				),
			);

			register_rest_route(
				'forms-bridge/v1',
				"/{$addon}/backend/endpoint/schema",
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => static function ( $request ) use ( $addon ) {
							return self::get_endpoint_schema( $addon, $request );
						},
						'permission_callback' => array( self::class, 'permission_callback' ),
						'args'                => array(
							'backend'  => FBAPI::get_backend_schema(),
							'endpoint' => array(
								'description' => __( 'Target endpoint name', 'forms-bridge' ),
								'type'        => 'string',
								'required'    => true,
							),
							'method'   => array(
								'description' => __( 'HTTP method', 'forms-bridge' ),
								'type'        => 'string',
							),
						),
					),
				)
			);
		}
	}

	/**
	 * Callback for GET requests to the forms endpoint.
	 *
	 * @return array
	 */
	private static function forms() {
		$forms = FBAPI::get_forms();
		return array_map(
			static function ( $form ) {
				unset( $form['bridges'] );
				return $form;
			},
			$forms
		);
	}

	/**
	 * Callback for GET requests to the job endpoint. Retrive a job from
	 * the database.
	 *
	 * @param string          $addon Addon name.
	 * @param WP_REST_Request $request Current REST request instance.
	 *
	 * @return array|WP_Error
	 */
	private static function get_job( $addon, $request ) {
		$job = FBAPI::get_job( $request['name'], $addon );
		if ( empty( $job ) ) {
			return self::not_found();
		}

		return $job->data();
	}

	/**
	 * Callback for POST requests to the job endpoint. Inserts a new job
	 * on the database.
	 *
	 * @param string          $addon Addon name.
	 * @param WP_REST_Request $request Current REST request instance.
	 *
	 * @return array|WP_Error
	 */
	private static function save_job( $addon, $request ) {
		$data         = $request->get_json_params();
		$data['name'] = $request['name'];

		$post_id = FBAPI::save_job( $data, $addon );
		if ( ! $post_id ) {
			return self::bad_request();
		}

		$post = get_post( $post_id );
		return ( new Job( $post, $addon ) )->data();
	}

	/**
	 * Callback for DELETE requests to the job endpoint. Removes a job
	 * from the database.
	 *
	 * @param string          $addon Addon name.
	 * @param WP_REST_Request $request Current REST request instance.
	 *
	 * @return array|WP_Error
	 */
	private static function reset_job( $addon, $request ) {
		$job = FBAPI::get_job( $request['name'], $addon );

		if ( ! $job ) {
			return self::not_found();
		}

		$reset = $job->reset();
		if ( ! $reset ) {
			return $job->data();
		}

		$job = FBAPI::get_job( $request['name'], $addon );
		if ( $job ) {
			return $job->data();
		}

		return array();
	}

	/**
	 * Callback for GET requests to the jobs endpoint. Retrives the list
	 * of available addon jobs.
	 *
	 * @param string          $addon Addon name.
	 * @param WP_REST_Request $request Current REST request instance.
	 *
	 * @return array|WP_Error Jobs data.
	 */
	private static function get_jobs( $addon, $request ) {
		$jobs = array();
		foreach ( FBAPI::get_addon_jobs( $addon ) as $job ) {
			if ( in_array( $job->name, $request['jobs'], true ) ) {
				$jobs[] = $job->data();
			}
		}

		if ( count( $jobs ) !== count( $request['jobs'] ) ) {
			$backup = $jobs;

			$jobs  = array();
			$index = 0;
			foreach ( $request['jobs'] as $name ) {
				foreach ( $backup as $job ) {
					if ( $job['name'] === $name ) {
						$jobs[] = $job;
						break;
					}
				}

				if ( ! isset( $jobs[ $index ] ) || $jobs[ $index ]['name'] !== $name ) {
					$jobs[] = array(
						'addon'       => $addon,
						'id'          => $addon . '-' . $name,
						'name'        => $name,
						'title'       => '',
						'description' => '',
						'method'      => '',
						'input'       => array(),
						'output'      => array(),
					);
				}

				++$index;
			}
		}

		return $jobs;
	}

	/**
	 * Callback for GET requests to the templates endpoint.
	 *
	 * @param string          $addon Addon name.
	 * @param WP_REST_Request $request Current REST request instance.
	 *
	 * @return array|WP_Error Template data.
	 */
	private static function get_template( $addon, $request ) {
		$template = FBAPI::get_template( $request['name'], $addon );
		if ( empty( $template ) ) {
			return self::not_found(
				__( 'Template is unknown', 'forms-bridge' ),
				array(
					'name'  => $request['name'],
					'addon' => $addon,
				)
			);
		}

		return $template->data();
	}

	/**
	 * Callback for POST requests to the template endpoint. Inserts a new template
	 * in the database.
	 *
	 * @param string          $addon Addon name.
	 * @param WP_REST_Request $request Current REST request instance.
	 *
	 * @return array|WP_Error
	 */
	private static function save_template( $addon, $request ) {
		$data         = $request->get_json_params();
		$data['name'] = $request['name'];

		$result = FBAPI::save_template( $data, $addon );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array( 'success' => true );
	}

	/**
	 * Callback for DELETE requests to the template endpoint. Removes a template
	 * from the database.
	 *
	 * @param string $addon Addon name.
	 * @param string $name Template name.
	 *
	 * @return array|WP_Error
	 */
	private static function reset_template( $addon, $name ) {
		$template = FBAPI::get_template( $name, $addon );

		if ( ! $template ) {
			return self::not_found();
		}

		$result = $template->reset();

		if ( ! $result ) {
			return self::internal_server_error();
		}

		$template = FBAPI::get_template( $name, $addon );
		if ( $template ) {
			return $template->data();
		}
	}

	/**
	 * Callback for POST requests to the templates endpoint.
	 *
	 * @param string          $addon Name of the owner addon of the template.
	 * @param WP_REST_Request $request Current REST request instance.
	 *
	 * @return array|WP_Error Template use result.
	 */
	private static function use_template( $addon, $request ) {
		$name        = $request['name'];
		$fields      = $request['fields'];
		$integration = $request['integration'];

		$template = FBAPI::get_template( $name, $addon );
		if ( empty( $template ) ) {
			return self::not_found();
		}

		if ( ! in_array( $integration, $template->integrations, true ) ) {
			return self::bad_request();
		}

		$result = $template->use( $fields, $integration );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array( 'success' => true === $result );
	}

	/**
	 * Callback to the fetch template options endpoint. It searches for template
	 * fields with dynamic options and fetch its values from the backend.
	 *
	 * @param Addon           $addon Addon instance.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array|WP_Error
	 */
	private static function get_template_options( $addon, $request ) {
		$handler = self::prepare_addon_backend_request_handler(
			$addon,
			$request
		);

		if ( is_wp_error( $handler ) ) {
			return $handler;
		}

		list( $addon, $backend ) = $handler;

		$template = FBAPI::get_template( $request['name'], $addon::NAME );
		if ( ! $template ) {
			Logger::log( 'Template not found', Logger::ERROR );
			return self::not_found();
		}

		if ( ! $template->is_valid ) {
			Logger::log( 'Invalid template', Logger::ERROR );
			return self::bad_request();
		}

		$field_options = array();
		$fields        = $template->fields;
		foreach ( $fields as $field ) {
			$endpoint = $field['options']['endpoint'] ?? null;
			if ( $endpoint ) {
				if ( is_string( $field['options']['finger'] ) ) {
					$finger = array(
						'value' => $field['options']['finger'],
					);
				} else {
					$finger = $field['options']['finger'];
				}

				$value_pointer = $finger['value'];

				if ( ! JSON_Finger::validate( $value_pointer ) ) {
					Logger::log( 'Fetch template options error: Invalid value json pointer', Logger::ERROR );
					return self::internal_server_error();
				}

				$label_pointer = $finger['label'] ?? $finger['value'];

				if ( ! JSON_Finger::validate( $label_pointer ) ) {
					Logger::log( 'Fetch template options error: Invalid label json pointer', Logger::ERROR );
					return self::internal_server_error();
				}

				$response = $addon->fetch( $endpoint, $backend );

				if ( is_wp_error( $response ) ) {
					$error = self::internal_server_error();
					$error->add(
						$response->get_error_code(),
						$response->get_error_message(),
						$response->get_error_data()
					);

					Logger::log( 'Fetch template options error response', Logger::ERROR );
					Logger::log( $error, Logger::ERROR );

					return $error;
				}

				$options = array();
				$data    = $response['data'];

				$json_finger = new JSON_Finger( $data );

				$values = $json_finger->get( $value_pointer );

				if ( ! wp_is_numeric_array( $values ) ) {
					Logger::log( 'Not found template options error response', Logger::ERROR );
					Logger::log( $response, Logger::ERROR );
					return self::not_found();
				}

				foreach ( $values as $value ) {
					$options[] = array(
						'value' => $value,
						'label' => $value,
					);
				}

				$labels = $json_finger->get( $label_pointer );
				if (
					wp_is_numeric_array( $labels ) &&
					count( $labels ) === count( $values )
				) {
					$l = count( $labels );
					for ( $i = 0; $i < $l; $i++ ) {
						$options[ $i ]['label'] = $labels[ $i ];
					}
				}

				$field_options[] = array(
					'ref'     => $field['ref'],
					'name'    => $field['name'],
					'options' => $options,
				);
			}
		}

		return $field_options;
	}

	/**
	 * Performs a request validation and sanitization
	 *
	 * @param string          $addon Target addon name.
	 * @param WP_REST_Request $request Request instance.
	 *
	 * @return array{0:Addon, 1:string}|WP_Error
	 */
	private static function prepare_addon_backend_request_handler(
		$addon,
		$request
	) {
		$addon = FBAPI::get_addon( $addon );
		if ( ! $addon ) {
			return self::bad_request();
		}

		$backend = wpct_plugin_sanitize_with_schema(
			$request['backend'],
			FBAPI::get_backend_schema()
		);

		if ( is_wp_error( $backend ) ) {
			return self::bad_request();
		}

		$introspection_data = array( 'backend' => $backend );

		Backend::temp_registration( $backend );

		$credential = $request['credential'];
		if ( ! empty( $credential ) ) {
			$credential = wpct_plugin_sanitize_with_schema(
				$credential,
				FBAPI::get_credential_schema( $addon )
			);

			if ( is_wp_error( $credential ) ) {
				return self::bad_request();
			}

			Credential::temp_registration( $credential );
			$backend['credential'] = $credential['name'];

			$introspection_data['credential'] = $credential;
		} elseif ( ! empty( $backend['credential'] ) ) {
			$credential = FBAPI::get_credential( $backend['credential'] );

			if ( $credential ) {
				$introspection_data['credential'] = $credential->data();
			}
		}

		self::$introspection_data = wp_json_encode( $introspection_data );
		return array( $addon, $backend['name'] );
	}

	/**
	 * Callback to the backend ping endpoint.
	 *
	 * @param string          $addon Addon name.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array|WP_Error
	 */
	private static function ping_backend( $addon, $request ) {
		$handler = self::prepare_addon_backend_request_handler( $addon, $request );

		if ( is_wp_error( $handler ) ) {
			return $handler;
		}

		list( $addon, $backend ) = $handler;

		$result = self::cache_lookup( $addon::NAME, $backend, 'ping' );
		if ( null !== $result ) {
			return $result;
		}

		$result = $addon->ping( $backend );

		if ( is_wp_error( $result ) ) {
			$error = self::bad_request();
			$error->add(
				$result->get_error_code(),
				$result->get_error_message(),
				$result->get_error_data()
			);

			return $error;
		}

		return self::cache_response(
			array( $addon::NAME, $backend, 'ping' ),
			array( 'success' => $result ),
			$addon->introspection_cache_expiration( 'ping' ),
		);
	}

	/**
	 * Backend endpoints route callback.
	 *
	 * @param string          $addon Addon name.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array|WP_Error
	 */
	private static function get_backend_endpoints( $addon, $request ) {
		$handler = self::prepare_addon_backend_request_handler( $addon, $request );

		if ( is_wp_error( $handler ) ) {
			return $handler;
		}

		list( $addon, $backend ) = $handler;

		$endpoints = self::cache_lookup( $addon::NAME, $backend, 'endpoints' );
		if ( null !== $endpoints ) {
			return $endpoints;
		}

		$endpoints = $addon->get_endpoints( $backend, $request['method'] );

		if ( is_wp_error( $endpoints ) ) {
			$error = self::internal_server_error();
			$error->add(
				$endpoints->get_error_code(),
				$endpoints->get_error_message(),
				$endpoints->get_error_data()
			);

			return $error;
		}

		return self::cache_response(
			array( $addon::NAME, $backend, 'endpoints' ),
			$endpoints,
			$addon->introspection_cache_expiration( 'endpoints' ),
		);
	}

	/**
	 * Backend endpoint schema route callback.
	 *
	 * @param string          $addon Addon name.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array|WP_Error
	 */
	private static function get_endpoint_schema( $addon, $request ) {
		$handler = self::prepare_addon_backend_request_handler( $addon, $request );

		if ( is_wp_error( $handler ) ) {
			return $handler;
		}

		list( $addon, $backend ) = $handler;

		$introspection_data             = json_decode( self::$introspection_data, true );
		$introspection_data['endpoint'] = $request['endpoint'];
		self::$introspection_data       = wp_json_encode( $introspection_data );

		$schema = self::cache_lookup( $addon::NAME, $backend, 'schema' );
		if ( null !== $schema ) {
			return $schema;
		}

		$schema = $addon->get_endpoint_schema( $request['endpoint'], $backend, $request['method'] );

		if ( is_wp_error( $schema ) ) {
			$error = self::internal_server_error();
			$error->add(
				$schema->get_error_code(),
				$schema->get_error_message(),
				$schema->get_error_data()
			);

			return $error;
		}

		return self::cache_response(
			array( $addon::NAME, $backend, 'schema' ),
			$schema,
			$addon->introspection_cache_expiration( 'schema' ),
		);
	}

	/**
	 * Callback of the addon schemas endpoint.
	 *
	 * @param string $name Addon name.
	 *
	 * @return array
	 */
	private static function addon_schemas( $name ) {
		$bridge = FBAPI::get_bridge_schema( $name );
		return array( 'bridge' => $bridge );
	}

	/**
	 * Callback of the http schemas endpoint.
	 *
	 * @return array
	 */
	private static function http_schemas() {
		$backend    = FBAPI::get_backend_schema();
		$credential = FBAPI::get_credential_schema();
		return array(
			'backend'    => $backend,
			'credential' => $credential,
		);
	}

	/**
	 * Lokkup for a cached introspection response.
	 *
	 * @param string[] ...$keys Introspection request keys: addon and backend names.
	 *
	 * @return array|null Cached introspection response.
	 */
	private static function cache_lookup( ...$keys ) {
		if ( Logger::is_active() ) {
			return null;
		}

		$key       = 'fb-introspection-' . sanitize_title( implode( '-', array_filter( $keys ) ) );
		$transient = get_transient( $key );
		if ( ! $transient ) {
			return null;
		}

		if ( $transient['key'] === self::$introspection_data ) {
			return $transient['data'];
		} else {
			delete_transient( $key );
		}
	}

	/**
	 * Cache an introspection response data.
	 *
	 * @param string[] $keys Introspection request keys: addon and backend names.
	 * @param array    $data Response data.
	 * @param int      $expiration Cache expiration time in seconds.
	 *
	 * @return array Cached data.
	 */
	private static function cache_response( $keys, $data, $expiration ) {
		if ( ! $expiration ) {
			return $data;
		}

		$key = 'fb-introspection-' . sanitize_title( implode( '-', array_filter( $keys ) ) );

		$transient_data = array(
			'key'  => self::$introspection_data,
			'data' => $data,
		);

		set_transient( $key, $transient_data, $expiration );
		return $data;
	}
}
