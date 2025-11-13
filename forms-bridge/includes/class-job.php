<?php
/**
 * Class Job
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use ParseError;
use Error;
use ReflectionFunction;
use WP_Error;
use WP_Post;
use FBAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * A job is a link in a workflow chain with a subroutine that performs mutations
 * on the chain input data or side effects when it runs.
 */
class Job {

	/**
	 * Handles the job post type name.
	 *
	 * @var string
	 */
	const TYPE = 'fb-job';

	/**
	 * Handles the job addon space.
	 *
	 * @var string
	 */
	protected $addon;

	/**
	 * Handles the job ID. This should be unique and is the result of the concatenation
	 * of the addon slug and the job name.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Handles the job data. The data is validated before it's stored. If the validation
	 * fails, the value is a WP_Error instance.
	 *
	 * @var array|WP_Error
	 */
	private $data;

	/**
	 * Pointer to the next job on the workflow chain.
	 *
	 * @var Job|null
	 */
	private $next = null;

	/**
	 * Noop function definition as a placeholder for job data defaults.
	 *
	 * @param array $payload Job payload.
	 *
	 * @return array
	 */
	public static function noop( $payload ) {
		return $payload;
	}

	/**
	 * Job's schema public getter.
	 *
	 * @return array
	 */
	public static function schema() {
		return array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'job',
			'type'                 => 'object',
			'properties'           => array(
				'name'        => array(
					'title'       => _x( 'Name', 'Job schema', 'forms-bridge' ),
					'description' => __(
						'Internal name of the job',
						'forms-bridge'
					),
					'type'        => 'string',
					'minLength'   => 1,
				),
				'title'       => array(
					'title'       => _x( 'Title', 'Job schema', 'forms-bridge' ),
					'description' => __(
						'Public title of the job',
						'forms-bridge'
					),
					'type'        => 'string',
					'minLength'   => 1,
				),
				'description' => array(
					'title'       => _x( 'Description', 'Job schema', 'forms-bridge' ),
					'description' => __(
						'Short description of the job effects',
						'forms-bridge'
					),
					'type'        => 'string',
					'default'     => '',
				),
				'method'      => array(
					'title'       => _x( 'Method', 'Job schema', 'forms-bridge' ),
					'description' => __(
						'Name of the function with the job subroutine',
						'forms-bridge'
					),
					'type'        => 'string',
					'minLength'   => 1,
				),
				'input'       => array(
					'title'       => _x( 'Input', 'Job schema', 'forms-bridge' ),
					'description' => __(
						'Input fields interface schema of the job',
						'forms-bridge'
					),
					'type'        => 'array',
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'name'     => array(
								'type'      => 'string',
								'minLength' => 1,
							),
							'required' => array( 'type' => 'boolean' ),
							'schema'   => array(
								'type'                 => 'object',
								'properties'           => array(
									'type'                 => array(
										'type' => 'string',
										'enum' => array(
											'string',
											'integer',
											'number',
											'array',
											'object',
											'boolean',
											'null',
										),
									),
									'items'                => array(
										'type'            => array( 'array', 'object' ),
										'additionalProperties' => true,
										'additionalItems' => true,
									),
									'properties'           => array(
										'type' => 'object',
										'additionalProperties' => true,
									),
									'maxItems'             => array( 'type' => 'integer' ),
									'minItems'             => array( 'type' => 'integer' ),
									'additionalProperties' => array(
										'type' => 'boolean',
									),
									'additionalItems'      => array( 'type' => 'boolean' ),
									'required'             => array(
										'type'            => 'array',
										'items'           => array( 'type' => 'string' ),
										'additionalItems' => true,
									),
								),
								'required'             => array( 'type' ),
								'additionalProperties' => false,
								'default'              => array( 'type' => 'string' ),
							),
						),
						'required'             => array( 'name', 'schema' ),
						'additionalProperties' => false,
					),
					'default'     => array(),
				),
				'output'      => array(
					'title'       => _x( 'Output', 'Job schema', 'forms-bridge' ),
					'description' => __(
						'Output fields interface schema of the job',
						'forms-bridge'
					),
					'type'        => 'array',
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'name'     => array(
								'type'      => 'string',
								'minLength' => 1,
							),
							'requires' => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'schema'   => array(
								'type'                 => 'object',
								'properties'           => array(
									'type'                 => array(
										'type' => 'string',
										'enum' => array(
											'string',
											'integer',
											'number',
											'array',
											'object',
											'boolean',
											'null',
										),
									),
									'items'                => array(
										'type'            => array( 'array', 'object' ),
										'additionalProperties' => true,
										'additionalItems' => true,
									),
									'properties'           => array(
										'type' => 'object',
										'additionalProperties' => true,
									),
									'maxItems'             => array( 'type' => 'integer' ),
									'minItems'             => array( 'type' => 'integer' ),
									'additionalProperties' => array(
										'type' => 'boolean',
									),
									'additionalItems'      => array( 'type' => 'boolean' ),
								),
								'required'             => array( 'type' ),
								'additionalProperties' => false,
								'default'              => array( 'type' => 'string' ),
							),
						),
						'required'             => array( 'name', 'schema' ),
						'additionalProperties' => false,
					),
					'default'     => array(),
				),
				'snippet'     => array(
					'title'       => _x( 'Snippet', 'Job schema', 'forms-bridge' ),
					'description' => __(
						'PHP code representation of the job subroutine',
						'forms-bridge'
					),
					'type'        => 'string',
				),
				'post_id'     => array( 'type' => 'integer' ),
			),
			'additionalProperties' => false,
			'required'             => array(
				'name',
				'title',
				'description',
				'input',
				'output',
				'method',
				'snippet',
			),
		);
	}

	/**
	 * Enqueue the job instance as the last element of the workflow chain.
	 *
	 * @param string[] $workflow Array with job names.
	 * @param string   $addon Job addon namespace.
	 *
	 * @return Job|null
	 */
	public static function from_workflow( $workflow, $addon ) {
		$workflow_jobs = array();
		$jobs          = FBAPI::get_addon_jobs( $addon );

		$i = count( $workflow ) - 1;
		while ( isset( $workflow[ $i ] ) ) {
			$job_name = $workflow[ $i ];

			foreach ( $jobs as $job ) {
				if ( $job->name === $job_name ) {
					$workflow_jobs[] = $job;
					break;
				}
			}

			--$i;
		}

		$next = null;
		foreach ( $workflow_jobs as $job ) {
			$job = clone $job;
			$job->chain( $next );
			$next = $job;
		}

		return $next;
	}

	/**
	 * Returns the source code of the body of a function.
	 *
	 * @param string $method Function name.
	 *
	 * @return string Function body source code.
	 */
	private static function reflect_method( $method ) {
		if ( ! function_exists( $method ) ) {
			return '';
		}

		$reflection = new ReflectionFunction( $method );

		$file      = $reflection->getFileName();
		$from_line = $reflection->getStartLine();
		$to_line   = $reflection->getEndLine();

		$snippet = implode(
			'',
			array_slice( file( $file ), $from_line - 1, $to_line - $from_line + 1 )
		);

		$_snippet = strstr( $snippet, '{' );
		if ( $_snippet ) {
			$snippet = substr( $_snippet, 1 );
		}

		$i = strlen( $snippet );
		while ( true ) {
			--$i;

			if ( '}' === $snippet[ $i ] || $i <= 0 ) {
				break;
			}
		}

		$snippet = substr( $snippet, 0, $i );

		$indentation = '';
		if ( preg_match( '/^\s+/', $snippet, $matches ) ) {
			$indentation = preg_replace( '/(\n|\t)+/', '', $matches[0] );
		}

		$snippet = trim( $snippet );
		$snippet = preg_replace( '/return \$payload;$/', '', $snippet );

		return $indentation . trim( $snippet );
	}

	/**
	 * Wraps a code snippet inside a function declaration and evaluate the code to register
	 * it on the process. The function name is based on the job and addon names.
	 *
	 * @param string $snippet Code snippet.
	 * @param string $name Name of the job.
	 * @param string $addon Name of the addon.
	 *
	 * @return string Method name.
	 */
	private static function load_snippet( $snippet, $name, $addon ) {
		$id = $addon . '_' . $name;

		try {
			$method_name = str_replace( '-', '_', "forms_bridge_job_{$id}" );

			$method  =
				'if (!function_exists(\'' . $method_name . '\')) {' . "\n";
			$method .=
				'function ' . $method_name . '($payload, $bridge) {' . "\n";
			$method .= $snippet . "\n";
			$method .= 'return $payload;' . "\n";
			$method .= "}\n}\n";

			// phpcs:disable Squiz.PHP.Eval.Discouraged
			eval( $method );
			return $method_name;
		} catch ( ParseError $e ) {
			Logger::log( "Syntax error on {$id} job snippet", Logger::ERROR );
			Logger::log( $e, Logger::ERROR );
		} catch ( Error $e ) {
			Logger::log( "Error while loading {$id} job snippet", Logger::ERROR );
			Logger::log( $e, Logger::ERROR );
		}
	}

	/**
	 * Gets the job config data from a post.
	 *
	 * @param WP_Post $post Post instance.
	 *
	 * @return array Job config data.
	 */
	private static function data_from_post( $post ) {
		return array(
			'name'        => $post->post_name,
			'title'       => $post->post_title,
			'description' => $post->post_excerpt,
			'input'       =>
				(array) ( get_post_meta( $post->ID, '_job-input', true ) ?: array() ),
			'output'      =>
				(array) ( get_post_meta( $post->ID, '_job-output', true ) ?: array() ),
			'snippet'     => $post->post_content,
			'post_id'     => $post->ID,
		);
	}

	/**
	 * Sets the job addon and name attributes, validates the data and enqueue themself to the job public
	 * filter getters.
	 *
	 * @param array  $data Job data.
	 * @param string $addon Addon name.
	 */
	public function __construct( $data, $addon ) {
		if ( $data instanceof WP_Post ) {
			$data = self::data_from_post( $data );
		}

		$this->addon = $addon;
		$this->data  = $this->validate( $data );

		if ( $this->is_valid ) {
			$this->id = $addon . '-' . $data['name'];
		}
	}

	/**
	 * Magic method to proxy private attributes.
	 *
	 * @param string $name Attribute name.
	 *
	 * @return mixed Attribute value or null.
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'id':
				return $this->id;
			case 'addon':
				return $this->addon;
			case 'next':
				return $this->next;
			case 'data':
				return $this->data;
			case 'is_valid':
				return ! is_wp_error( $this->data ) &&
					Addon::addon( $this->addon ) !== null;
			default:
				if ( ! $this->is_valid ) {
					return;
				}

				return $this->data[ $name ] ?? null;
		}
	}

	/**
	 * Sets the next job on the chain.
	 *
	 * @param Job $job Job instance to be queued as the next item of a workflow chain.
	 */
	public function chain( $job ) {
		$this->next = $job;
	}

	/**
	 * Gets the payload from the previous workflow stage and runs the job against it.
	 *
	 * @param array       $payload Payload data.
	 * @param Form_Bridge $bridge Workflow's bridge owner instance.
	 * @param array       $mutations Bridge's mutations.
	 *
	 * @return array|null Payload after job.
	 */
	public function run( $payload, $bridge, $mutations = null ) {
		$original = $payload;

		if ( null === $mutations ) {
			$mutations = array_slice( $bridge->mutations, 1 );
		}

		if ( $this->missing_requireds( $payload ) ) {
			$next_job = $this->next;
			if ( $next_job ) {
				$mutations = array_slice( $mutations, 1 );
				$payload   = $next_job->run( $payload, $bridge, $mutations );
			}

			return $payload;
		}

		$method  = $this->method;
		$payload = $method( $payload, $bridge, $this );

		if ( empty( $payload ) ) {
			return;
		} elseif ( is_wp_error( $payload ) ) {
			$error = $payload;
			do_action( 'forms_bridge_on_failure', $bridge, $error, $original );
			return;
		}

		$payload = $this->output_payload( $payload );

		$mutation = array_shift( $mutations ) ?: array();
		$payload  = $bridge->apply_mutation( $payload, $mutation );

		$next_job = $this->next;
		if ( $next_job ) {
			$payload = $next_job->run( $payload, $bridge, $mutations );
		}

		return $payload;
	}

	/**
	 * Job data serializer to be used on REST API response.
	 *
	 * @return array
	 */
	public function data() {
		if ( ! $this->is_valid ) {
			return;
		}

		return array_merge(
			array(
				'id'    => $this->id,
				'addon' => $this->addon,
			),
			$this->data
		);
	}

	/**
	 * Gets the job's post ID from database.
	 *
	 * @return int|null Int if it's stored on the database, null otherwise.
	 */
	private function get_post_id() {
		// phpcs:disable WordPress.DB.SlowDBQuery
		$ids = get_posts(
			array(
				'post_type'              => self::TYPE,
				'name'                   => $this->name,
				'meta_key'               => '_fb-addon',
				'meta_value'             => $this->addon,
				'fields'                 => 'ids',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'update_menu_item_cache' => false,
			)
		);
		// phpcs:enable

		if ( count( $ids ) ) {
			return $ids[0];
		}
	}

	/**
	 * Save the job config to the database as a custom post entry.
	 *
	 * @return int|WP_Error Post ID or WP_Error in case of insert errors.
	 */
	public function save() {
		if ( ! $this->is_valid ) {
			return $this->data;
		}

		$post_arr = array(
			'post_type'    => self::TYPE,
			'post_name'    => $this->name,
			'post_title'   => $this->title,
			'post_excerpt' => $this->description,
			'post_content' => $this->snippet,
			'post_status'  => 'publish',
		);

		$post_id = $this->get_post_id();
		if ( $post_id ) {
			$post_arr['ID'] = $post_id;
			$post_id        = wp_update_post( $post_arr, true );
		} else {
			$post_id = wp_insert_post( $post_arr, true );
		}

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_fb-addon', $this->addon );
			update_post_meta( $post_id, '_job-input', $this->input );
			update_post_meta( $post_id, '_job-output', $this->output );
		}

		return $post_id;
	}

	/**
	 * Delete the job config from the database and restore its default configuration.
	 * If the job does not exists a file-based configuration, the job will be deleted.
	 *
	 * @return bool
	 */
	public function reset() {
		$post_id = $this->get_post_id();

		if ( ! $post_id ) {
			return false;
		}

		return wp_delete_post( $post_id, true ) instanceof WP_Post;
	}

	/**
	 * Vaildates the data against the job schema.
	 *
	 * @param array $data Job data.
	 *
	 * @return array|WP_Error Validation result.
	 */
	private function validate( $data ) {
		$schema = self::schema();

		if (
			isset( $data['name'], $data['snippet'] ) &&
			is_string( $data['snippet'] )
		) {
			$data['method'] = self::load_snippet(
				$data['snippet'],
				$data['name'],
				$this->addon
			);
		} elseif ( isset( $data['method'] ) && function_exists( $data['method'] ) ) {
				$data['snippet'] = self::reflect_method( $data['method'] );
		} else {
			$data['method']  = array( '\FORMS_BRIDGE\Job', 'noop' );
			$data['snippet'] = '';
		}

		$data = wpct_plugin_sanitize_with_schema( $data, $schema );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( ! function_exists( $data['method'] ) ) {
			return new WP_Error(
				'method_is_not_function',
				__( 'Job method is not a function', 'forms-bridge' ),
				$data
			);
		}

		return $data;
	}

	/**
	 * Checks if payload compains with the required fields of the job.
	 *
	 * @param array $payload Input payload of the job.
	 *
	 * @return boolean
	 */
	private function missing_requireds( $payload ) {
		$requireds = array_filter(
			$this->input,
			function ( $input_field ) {
				return $input_field['required'] ?? false;
			}
		);

		foreach ( $requireds as $required ) {
			if ( ! isset( $payload[ $required['name'] ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Removes attributes from the payload that are not present on the job output config.
	 *
	 * @param array $payload Job result payload.
	 *
	 * @return array Filtered payload.
	 */
	private function output_payload( $payload ) {
		$input_fields = array_column( $this->input, 'name' );

		foreach ( $input_fields as $input_field ) {
			foreach ( $this->output as $output_field ) {
				if ( $input_field === $output_field['name'] ) {
					if ( is_array( $output_field['requires'] ?? null ) ) {
						$requires = array_filter(
							$output_field['requires'],
							function ( $name ) use ( $input_fields ) {
								return false === array_search( $name, $input_fields, true );
							}
						);

						if ( count( $requires ) ) {
							break;
						}
					}

					$persist = true;
					break;
				}
			}

			if ( ! isset( $persist ) ) {
				unset( $payload[ $input_field ] );
			}
		}

		return $payload;
	}
}
