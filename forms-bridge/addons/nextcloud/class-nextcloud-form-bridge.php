<?php

namespace FORMS_BRIDGE;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implementation for the Nextcloud JSON-RPC api.
 */
class Nextcloud_Form_Bridge extends Form_Bridge {

	public function __construct( $data ) {
		parent::__construct( $data, 'nextcloud' );
	}

	private function filepath( &$touched = false ) {
		$uploads = Forms_Bridge::upload_dir() . '/nextcloud';

		if ( ! is_dir( $uploads ) ) {
			if ( ! mkdir( $uploads, 755 ) ) {
				return;
			}
		}

		$endpoint = preg_replace( '/^\/+/', '', $this->data['endpoint'] );
		$name     = str_replace( '/', '-', $endpoint );
		$filepath = $uploads . '/' . $name;

		if ( ! is_file( $filepath ) ) {
			$touched = true;
			$result  = touch( $filepath );

			if ( ! $result ) {
				return new WP_Error( 'file_permission_error' );
			}
		}

		return $filepath;
	}

	public function table_headers() {
		$filepath = $this->filepath();

		if ( is_wp_error( $filepath ) ) {
			return $filepath;
		}

		$stream = fopen( $filepath, 'r' );
		$line   = fgets( $stream );
		fclose( $stream );

		if ( $line === false ) {
			return;
		}

		return $this->decode_row( $line );
	}

	private function get_dav_modified_date( $backend ) {
		$response = $backend->head( $this->endpoint );

		if ( is_wp_error( $response ) ) {
			$error_data = $response->get_error_data();

			$code = $error_data['response']['response']['code'] ?? null;
			if ( $code !== 404 ) {
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

	private function payload_to_headers( $payload ) {
		return $this->encode_row( array_keys( $payload ) );
	}

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

	private function encode_row( $row ) {
		return implode(
			',',
			array_map(
				fn( $value ) => json_encode(
					$value,
					JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
				),
				$row
			)
		);
	}

	private function decode_row( $row ) {
		$row = preg_replace( '/\n+/', '', $row );
		return array_map(
			function ( $value ) {
				if ( $decoded = json_decode( $value ) ) {
					return $decoded;
				}

				return $value;
			},
			explode( ',', $row )
		);
	}

	private function add_row( $payload ) {
		$row = $this->payload_to_row( $payload );

		$filepath = $this->filepath();
		$sock     = fopen( $filepath, 'r' );
		$cursor   = -1;
		fseek( $sock, $cursor, SEEK_END );
		$char = fgetc( $sock );
		fclose( $sock );

		if ( $char !== "\n" && $char !== "\r" ) {
			$row = "\n" . $row;
		}

		file_put_contents( $filepath, $row, FILE_APPEND );
	}

	/**
	 * Submits submission to the backend.
	 *
	 * @param array $payload Submission data.
	 * @param array $attachments Submission attachments.
	 *
	 * @return array|WP_Error Http
	 */
	public function submit( $payload = array(), $attachments = array() ) {
		if ( ! $this->is_valid ) {
			return new WP_Error( 'invalid_bridge' );
		}

		$backend = $this->backend;

		if ( ! $backend ) {
			return new WP_Error( 'invalid_bridge' );
		}

		$payload = self::flatten_payload( $payload );

		add_filter(
			'http_bridge_backend_url',
			function ( $url, $backend ) {
				if ( $backend->name === $this->data['backend'] ) {
					$credential = $backend->credential;
					if ( ! $credential ) {
						return;
					}

					$user  = $credential->client_id;
					[$pre] = explode( $this->endpoint, $url );
					$url   =
						preg_replace( '/\/+$/', '', $pre ) .
						"/remote.php/dav/files/{$user}/" .
						preg_replace( '/^\/+/', '', $this->endpoint );
				}

				return $url;
			},
			10,
			2
		);

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

			file_put_contents( $filepath, $csv );
			$response = parent::submit( $csv );
		} elseif ( $touched ) {
				$headers = $this->payload_to_headers( $payload );
				$row     = $this->payload_to_row( $payload );
				$csv     = implode( "\n", array( $headers, $row ) );

				file_put_contents( $filepath, $csv );
				$response = parent::submit( $csv );
		} else {
			$local_modified = filemtime( $filepath );

			if ( $dav_modified > $local_modified ) {
				$response = $backend->get(
					$this->endpoint,
					array(),
					array(),
					array(
						'stream'   => true,
						'filename' => $filepath,
					)
				);

				if ( is_wp_error( $response ) ) {
					return $response;
				}
			}

			$this->add_row( $payload );

			$csv      = file_get_contents( $filepath );
			$response = parent::submit( $csv );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		touch( $filepath, time() );
		return $response;
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
}
