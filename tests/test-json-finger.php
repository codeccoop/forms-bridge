<?php
/**
 * Class JsonFingerTest
 *
 * @package forms-bridge-tests
 */

use FORMS_BRIDGE\JSON_Finger;

class JsonFingerTest extends WP_UnitTestCase {
	private function reducer( $values, $expected ) {
		return array_reduce(
			$values,
			function ( $carry, $value ) use ( $expected ) {
				if ( is_array( $value ) ) {
					return $carry && $this->reducer( $value, $expected );
				}

				return $carry && $value === $expected;
			},
			true,
		);
	}

	private static function provider() {
		return array(
			array( 'foo', 1, array( 'foo' => 'boofoo' ) ),
			array( 'foo.bar', 2, array( 'foo' => array( 'bar' => 'boofoo' ) ) ),
			array( 'foo[].bar', 3, array( 'foo' => array( array( 'bar' => 'boofoo' ) ) ) ),
			array( 'foo[0].bar', 3, array( 'foo' => array( array( 'bar' => 'boofoo' ) ) ) ),
			array( 'foo.bar[]', 3, array( 'foo' => array( 'bar' => array( 'boofoo' ) ) ) ),
			array( 'foo.bar[0]', 3, array( 'foo' => array( 'bar' => array( 'boofoo' ) ) ) ),
			array( '[].foo', 2, array( array( 'foo' => 'boofoo' ) ) ),
			array( '[0].foo', 2, array( array( 'foo' => 'boofoo' ) ) ),
			array( '[].foo[].bar', 4, array( array( 'foo' => array( array( 'bar' => 'boofoo' ) ) ) ) ),
			array( '[0].foo[0].bar', 4, array( array( 'foo' => array( array( 'bar' => 'boofoo' ) ) ) ) ),
			array( '[0][0].foo', 3, array( array( array( 'foo' => 'boofoo' ) ) ) ),
			array( '[][].foo', 3, array( array( array( 'foo' => 'boofoo' ) ) ) ),
			array( '["f oo"].bar', 2, array( 'f oo' => array( 'bar' => 'boofoo' ) ) ),
			array( '[][].foo', 3, array( array( array( 'foo' => 'boofoo' ) ) ) ),
			array( '[0][0].foo', 3, array( array( array( 'foo' => 'boofoo' ) ) ) ),
			array( '["Hello World"]', 1, array( 'Hello World' => 'boofoo' ) ),
			array( 'foo[].bar[]', 4, array( 'foo' => array( array( 'bar' => array( 'boofoo' ) ) ) ) ),
			array( 'foo[0].bar[0]', 4, array( 'foo' => array( array( 'bar' => array( 'boofoo' ) ) ) ) ),
		);
	}

	public function test_pointer_parse() {
		$tests = $this->provider();

		foreach ( $tests as $test ) {
			list($pointer, $chunks_count) = $test;

			$chunks = JSON_Finger::parse( $pointer );
			$this->assertSame( count( $chunks ), $chunks_count );
		}
	}

	public function test_pointer_build() {
		$tests = $this->provider();

		foreach ( $tests as $test ) {
			list($pointer) = $test;

			$chunks = JSON_Finger::parse( $pointer );
			$this->assertSame( JSON_Finger::pointer( $chunks ), $pointer );
		}
	}

	public function test_finger_getter() {
		$tests = $this->provider();

		foreach ( $tests as $test ) {
			list($pointer, , $payload) = $test;

			if ( strpos( $pointer, '[]' ) !== false ) {
				continue;
			}

			$finger = new JSON_Finger( $payload );
			$this->assertSame( $finger->get( $pointer ), 'boofoo' );
		}
	}

	public function test_finger_setter() {
		$tests = $this->provider();

		foreach ( $tests as $test ) {
			$pointer = $test[0];

			if ( strpos( $pointer, '[]' ) !== false ) {
				continue;
			}

			$finger = new JSON_Finger( array() );
			$finger->set( $pointer, true );

			$this->assertTrue( $finger->get( $pointer ) );
		}
	}

	public function test_expansion_getters() {
		$tests = $this->provider();

		foreach ( $tests as $test ) {
			list($pointer, , $payload) = $test;

			if ( strpos( $pointer, '[]' ) === false ) {
				continue;
			}

			$finger = new JSON_Finger( $payload );
			$values = $finger->get( $pointer );

			$this->assertTrue( $this->reducer( $values, 'boofoo' ) );
		}
	}

	public function test_expansion_setters() {
		$tests = $this->provider();

		foreach ( $tests as $test ) {
			list($pointer, , $payload) = $test;

			if ( strpos( $pointer, '[]' ) === false ) {
				continue;
			}

			$finger = new JSON_Finger( $payload );
			$finger->set( $pointer, 'qwerty' );
			$values = $finger->get( $pointer );

			$this->assertTrue( $this->reducer( $values, 'qwerty' ) );
		}
	}
}
