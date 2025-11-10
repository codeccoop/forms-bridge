<?php
/**
 * Class BaseIntegrationTest
 *
 * @package forms-bridge-tests
 */

use FORMS_BRIDGE\Integration;

/**
 * Common integration test case methods.
 */
abstract class BaseIntegrationTest extends WP_UnitTestCase {
	public const NAME = 'base';

	/**
	 * Fetch form objects from the database.
	 *
	 * @return (opbject|array)[]
	 */
	abstract protected static function get_forms();

	/**
	 * Registers a new form on the database.
	 *
	 * @param array|object $config Form config.
	 *
	 * @return int Form ID.
	 */
	abstract protected static function add_form( $config );

	/**
	 * Delete a form from de database by ID.
	 *
	 * @param array|object $form Form object or array.
	 *
	 * @return bool 1 if OK, 0 if KO.
	 */
	abstract protected static function delete_form( $form );

	/**
	 * Retrive a form instance by title.
	 *
	 * @param string $title Form title.
	 *
	 * @return array|object|null
	 *
	 * @throws Exception If form not found.
	 */
	protected static function get_form( $title ) {
		$forms = static::get_forms();

		foreach ( $forms as $form ) {
			if ( is_array( $form ) ) {
				if ( isset( $form['title'] ) && $form['title'] === $title ) {
					return $form;
				} elseif ( isset( $form['settings']['title'] ) && $form['settings']['title'] === $title ) {
					return $form;
				}
			} elseif ( is_object( $form ) && property_exists( $form, 'title' ) ) {
				if ( is_callable( array( $form, 'title' ) ) && $form->title() === $title ) {
					return $form;
				} elseif ( ! is_callable( array( $form, 'title' ) ) && $form->title === $title ) {
					return $form;
				}
			} elseif ( is_object( $form ) && property_exists( $form, 'post_title' ) && $form->post_title === $title ) {
				return $form;
			}
		}

		throw new Exception( esc_html( "Form {$title} not found" ) );
	}

	protected function assertField( $field, $type, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'basetype'    => $type,
				'schema'      => ( $args['is_file'] ?? false ) ? null : 'string',
				'required'    => true,
				'is_file'     => false,
				'is_multi'    => false,
				'conditional' => false,
			)
		);

		$this->assertSame( $type, $field['type'] );
		$this->assertSame( $args['basetype'], $field['basetype'] );

		if ( $args['schema'] && ! $args['is_file'] ) {
			$this->assertSame( $args['schema'], $field['schema']['type'] );
		} else {
			$this->assertNull( $args['schema'] );
		}

		$flags = array( 'required', 'is_file', 'is_multi', 'conditional' );
		foreach ( $flags as $flag ) {
			if ( $args[ $flag ] ) {
				$this->assertTrue( $field[ $flag ] );
			} else {
				$this->assertFalse( $field[ $flag ] );
			}
		}

		if ( isset( $args['format'] ) ) {
			$this->assertSame( $args['format'], $field['format'] );
		}

		if ( isset( $args['options'] ) ) {
			if ( is_int( $args['options'] ) ) {
				$this->assertEquals( $args['options'], count( $field['options'] ) );
			} else {
				$l = count( $args['options'] );
				for ( $i = 0; $i < $l; ++$i ) {
					$this->assertSame( $args['options'][ $i ]['label'], $field['options'][ $i ]['label'] );
					$this->assertSame( $args['options'][ $i ]['value'], $field['options'][ $i ]['value'] );
				}
			}
		}

		if ( isset( $args['label'] ) ) {
			$this->assertSame( $args['label'], $field['label'] );
		} else {
			$this->assertTrue( ! empty( $field['label'] ) );
		}
	}

	/**
	 * Return a map of unserialized objects from the integration tests store.
	 *
	 * @return array<string, object>
	 */
	public static function store() {
		$dir = dirname( __DIR__, 1 ) . '/data/' . static::NAME;

		$store = array();

		if ( ! is_dir( $dir ) ) {
			return $store;
		}

		foreach ( array_diff( scandir( $dir ), array( '..', '.' ) ) as $filename ) {
			$name     = explode( '.', $filename )[0];
			$filepath = $dir . '/' . $filename;
			$content  = file_get_contents( $filepath );

			if ( str_ends_with( $filepath, '.json' ) ) {
				$store[ $name ] = json_decode( $content, true );
			} elseif ( str_ends_with( $filepath, '.php.txt' ) ) {
				$store[ $name ] = unserialize( $content );
			} elseif ( str_ends_with( $filepath, '.php' ) ) {
				$store[ $name ] = include $filepath;
			}
		}

		return $store;
	}

	public static function set_up_before_class() {
		Integration::update_registry( array( static::NAME => true ) );

		$store = self::store();
		foreach ( $store as $name => $object ) {
			if ( ! str_ends_with( $name, '-form' ) ) {
				continue;
			}

			$form_id = static::add_form( $object );

			if ( ! $form_id ) {
				throw new Exception( 'Unable to create Form' );
			}
		}
	}

	public static function tear_down_after_class() {
		$forms = static::get_forms();

		foreach ( $forms as $form ) {
			static::delete_form( $form );
		}

		Integration::update_registry( array( static::NAME => false ) );
	}

	/**
	 * Serializes a form object as an array of data.
	 *
	 * @param object|array $form Integration's form representation.
	 *
	 * @return array.
	 */
	public function serialize_form( $form ) {
		$integration = Integration::integration( static::NAME );
		return $integration->serialize_form( $form );
	}

	/**
	 * Serializes the form's submission data.
	 *
	 * @param array|object $submission Integration form subsission representation.
	 * @param array        $form_data Form data.
	 *
	 * @return array Submission data.
	 */
	public function serialize_submission( $submission, $form_data ) {
		$integration = Integration::integration( static::NAME );
		return $integration->serialize_submission( $submission, $form_data );
	}
}
