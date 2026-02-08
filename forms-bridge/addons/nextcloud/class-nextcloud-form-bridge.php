<?php
/**
 * Class Nextcloud_Form_Bridge
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

use FBAPI;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implementation for the Nextcloud JSON-RPC api.
 */
class Nextcloud_Form_Bridge extends Form_Bridge {

	/**
	 * Bridge constructor with addon name provisioning.
	 *
	 * @param array $data Bridge data.
	 */
	public function __construct( $data ) {
		parent::__construct( $data, 'nextcloud' );
	}

	/**
	 * Downloads the file from nextcloud and stream its contents to the bridge filepath.
	 *
	 * @param Backend|null $backend Backend object.
	 *
	 * @return string|WP_Error Filepath or error.
	 */
	private function download_file( $backend = null ) {
		if ( ! $backend ) {
			$backend = $this->backend;
		}

		$filepath = $this->filepath();

		$response = $backend->get(
			rawurlencode( $this->endpoint ),
			array(),
			array(),
			array(
				'stream'   => true,
				'filename' => $filepath,
			)
		);

		if ( is_wp_error( $response ) ) {
			if ( is_file( $filepath ) ) {
				wp_delete_file( $filepath );
			}

			return $response;
		}

		$mime_type = mime_content_type( $filepath );
		if ( 'text/csv' !== $mime_type ) {
			wp_delete_file( $filepath );
			return new WP_Error( 'mimetype_error', 'File is not CSV', array( 'filepath' => $filepath ) );
		}

		return $filepath;
	}

	/**
	 * Returns the bridge local backup file path.
	 *
	 * @param bool &$touched Pointer to handle if the file has been touched boolean value.
	 *
	 * @return string|WP_Error File path or WP_Error if no write permissions.
	 */
	private function filepath( &$touched = false ) {
		$uploads = Forms_Bridge::upload_dir() . '/nextcloud';

		if ( ! is_dir( $uploads ) ) {
			if ( ! wp_mkdir_p( $uploads, 755 ) ) {
				return new WP_Error(
					'file_permission_error',
					'Can not create the uploads directory',
					array( 'directory' => $uploads ),
				);
			}
		}

		$endpoint = ltrim( $this->data['endpoint'], '/' );
		$name     = str_replace( '/', '-', $endpoint );
		$filepath = $uploads . '/' . $name;

		if ( ! str_ends_with( strtolower( $filepath ), '.csv' ) ) {
			$filepath .= '.csv';
		}

		if ( ! is_file( $filepath ) ) {
			// phpcs:disable WordPress.WP.AlternativeFunctions
			$result = touch( $filepath );
			// phpcs:enable

			if ( ! $result ) {
				return new WP_Error(
					'file_permission_error',
					'Can not create the local file',
					array( 'filepath' => $filepath ),
				);
			}

			$touched = true;
		} else {
			$touched = false;
		}

		return $filepath;
	}

	/**
	 * Returns the bridge table headers.
	 *
	 * @return array|null
	 */
	public function table_headers() {
		$filepath = $this->filepath( $touched );

		if ( is_wp_error( $filepath ) ) {
			return $filepath;
		}

		$dav_modified = $touched ? time() + 3600 : $this->get_dav_modified_date();
		if ( is_wp_error( $dav_modified ) ) {
			$dav_modified = time() + 3600;
		}

		if ( $touched || filemtime( $filepath ) < $dav_modified ) {
			$filepath = $this->download_file();

			if ( is_wp_error( $filepath ) ) {
				return;
			}
		}

		// phpcs:disable WordPress.WP.AlternativeFunctions
		$stream = fopen( $filepath, 'r' );
		$line   = fgets( $stream );
		fclose( $stream );
		// phpcs:enable

		if ( false === $line ) {
			return;
		}

		return $this->decode_row( $line );
	}

	/**
	 * Returns the remote file modification date.
	 *
	 * @param Backend $backend Bridge backend instance.
	 *
	 * @return integer|null
	 */
	private function get_dav_modified_date( $backend = null ) {
		if ( ! $backend ) {
			$backend = $this->backend();
		}

		$response = $backend->head( rawurlencode( $this->endpoint ) );

		if ( is_wp_error( $response ) ) {
			$error_data = $response->get_error_data();

			$code = $error_data['response']['response']['code'] ?? null;
			if ( 404 !== $code ) {
				return $response;
			}

			return;
		}

		$last_modified = $response['headers']['last-modified'] ?? null;
		if ( ! $last_modified ) {
			return;
		}

		return strtotime( $last_modified );
	}

	/**
	 * Generates a heaaders csv row from a payload.
	 *
	 * @param array $payload Bridge payload.
	 *
	 * @return string
	 */
	private function payload_to_headers( $payload ) {
		$payload = $this->flatten_payload( $payload );
		return $this->encode_row( array_keys( $payload ) );
	}

	/**
	 * Encode the payload as a csv row following the sheet headers columns order.
	 *
	 * @param array $payload Bridge payload.
	 *
	 * @return string
	 */
	private function payload_to_row( $payload ) {
		$headers = $this->table_headers();
		if ( ! is_array( $headers ) ) {
			$headers = array_keys( $payload );
		}

		$row = array();
		foreach ( $headers as $header ) {
			$row[] = $payload[ $header ] ?? '';
		}

		return $this->encode_row( $row );
	}

	/**
	 * Returns a list of values as a comma separated values string.
	 *
	 * @param array $row List of values.
	 *
	 * @return string
	 */
	private function encode_row( $row ) {
		return implode(
			',',
			array_map(
				fn( $value ) => wp_json_encode(
					$value,
					JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
				),
				$row
			)
		);
	}

	/**
	 * Returns a csv row as a list of values.
	 *
	 * @param string $row Comma separated values string.
	 *
	 * @return array
	 */
	private function decode_row( $row ) {
		$row = preg_replace( '/\n+/', '', $row );
		return array_map(
			function ( $value ) {
				$value = trim( $value );
				if ( ! $value ) {
					return $value;
				}

				$decoded = json_decode( $value );
				if ( $decoded ) {
					return $decoded;
				}

				return $value;
			},
			explode( ',', $row )
		);
	}

	/**
	 * Adds a row to the local sheet file.
	 *
	 * @param array $payload Bridge payload.
	 */
	private function add_row( $payload ) {
		$row = $this->payload_to_row( $payload );

		// phpcs:disable WordPress.WP.AlternativeFunctions
		$filepath = $this->filepath();
		$sock     = fopen( $filepath, 'r' );
		$cursor   = -1;
		fseek( $sock, $cursor, SEEK_END );
		$char = fgetc( $sock );
		fclose( $sock );

		if ( "\n" !== $char && "\r" !== $char ) {
			$row = "\n" . $row;
		}

		file_put_contents( $filepath, $row, FILE_APPEND );
		// phpcs:enable
	}

	/**
	 * Sends the payload to the backend.
	 *
	 * @param array $payload Submission data.
	 * @param array $attachments Submission attachments.
	 *
	 * @return array|WP_Error Http
	 */
	public function submit( $payload = array(), $attachments = array() ) {
		if ( ! $this->is_valid ) {
			return new WP_Error(
				'invalid_bridge',
				'Bridge data is invalid',
				(array) $this->data,
			);
		}

		$backend  = $this->backend;
		$endpoint = rawurlencode( $this->endpoint );

		if ( ! $backend ) {
			return new WP_Error(
				'invalid_backend',
				'Bridge has no valid backend',
				(array) $this->data,
			);
		}

		if ( 'PUT' === $this->method ) {
			$payload = self::flatten_payload( $payload );

			$filepath = $this->filepath( $touched );

			if ( is_wp_error( $filepath ) ) {
				return $filepath;
			}

			$dav_modified = $this->get_dav_modified_date( $backend );
			if ( is_wp_error( $dav_modified ) ) {
				return $dav_modified;
			}

			if ( ! $dav_modified ) {
				$headers = $this->payload_to_headers( $payload );
				$row     = $this->payload_to_row( $payload );
				$csv     = implode( "\n", array( $headers, $row ) );

				// phpcs:disable WordPress.WP.AlternativeFunctions
				file_put_contents( $filepath, $csv );
				// phpcs:enable

				$response = $backend->put( $endpoint, $csv );
			} elseif ( $touched ) {
				$headers = $this->payload_to_headers( $payload );
				$row     = $this->payload_to_row( $payload );
				$csv     = implode( "\n", array( $headers, $row ) );

				// phpcs:disable WordPress.WP.AlternativeFunctions
				file_put_contents( $filepath, $csv );
				// phpcs:enable

				$response = $backend->put( $endpoint, $csv );
			} else {
				$local_modified = filemtime( $filepath );

				if ( $dav_modified > $local_modified ) {
					$filepath = $this->download_file( $backend );

					if ( is_wp_error( $filepath ) ) {
						return $filepath;
					}
				}

				$this->add_row( $payload );

				// phpcs:disable WordPress.WP.AlternativeFunctions
				$csv = file_get_contents( $filepath );
				// phpcs:enable

				$bom = pack( 'H*', 'EFBBBF' );
				$csv = preg_replace( "/^$bom/", '', trim( $csv ) );

				$response = $backend->put( $endpoint, $csv );
			}

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			// phpcs:disable WordPress.WP.AlternativeFunctions
			touch( $filepath, time() );
			// phpcs:enable

			return $response;
		}

		$method = $this->method;

		$allowed_methods = array( 'GET', 'DELETE', 'MOVE', 'MKCOL', 'PROPFIND' );
		if ( ! in_array( $method, $allowed_methods, true ) ) {
			return new WP_Error(
				'method_not_allowed',
				sprintf(
					/* translators: %s: method name */
					__( 'HTTP method %s is not allowed', 'forms-bridge' ),
					sanitize_text_field( $this->method )
				),
				array( 'method' => $this->method )
			);
		}

		return $backend->$method( $endpoint, $payload );
	}

	/**
	 * Sheets are flat, if payload has nested arrays, flattens it and concatenate its keys
	 * as field names.
	 *
	 * @param array  $payload Submission payload.
	 * @param string $path Prefix to prepend to the field name.
	 *
	 * @return array Flattened payload.
	 */
	private static function flatten_payload( $payload, $path = '' ) {
		$flat = array();
		foreach ( $payload as $field => $value ) {
			$key   = $path . $field;
			$value = self::flatten_value( $value, $key );

			if ( ! is_array( $value ) ) {
				$flat[ $key ] = $value;
			} else {
				foreach ( $value as $_key => $_val ) {
					$flat[ $_key ] = $_val;
				}
			}
		}

		return $flat;
	}

	/**
	 * Returns array values as a flat vector of play key values.
	 *
	 * @param mixed  $value Payload value.
	 * @param string $path Hierarchical path to the value.
	 *
	 * @return mixed
	 */
	private static function flatten_value( $value, $path = '' ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		if ( wp_is_numeric_array( $value ) ) {
			$simple_items = array_filter( $value, fn( $item ) => ! is_array( $item ) );

			if ( count( $simple_items ) === count( $value ) ) {
				return implode( ',', $value );
			}
		}

		return self::flatten_payload( $value, $path . '.' );
	}

	/**
	 * Retrives the bridge's backend instance with the base url formated to point
	 * to the root of the nextcloud webdav API.
	 *
	 * @return Backend|null
	 */
	protected function backend() {
		if ( ! $this->is_valid ) {
			return;
		}

		$backend = FBAPI::get_backend( $this->data['backend'] );
		if ( ! $backend ) {
			return;
		}

		$base_url = $backend->base_url;
		$base_url = substr( $base_url, 0, strpos( $base_url, '/remote.php', 0 ) ?: strlen( $base_url ) );

		$credential = $backend->credential;
		if ( ! $credential || 'Basic' !== $credential->schema ) {
			return;
		}

		$user     = rawurlencode( $credential->client_id );
		$base_url = rtrim( $base_url, '/' ) . "/remote.php/dav/files/{$user}/";

		return $backend->clone( array( 'base_url' => $base_url ) );
	}
}
