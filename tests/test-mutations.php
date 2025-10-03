<?php

/**
 * Class MappersTest
 */

use FORMS_BRIDGE\Form_Bridge;

/**
 * Field Mappers test case.
 */
class MutationsTest extends WP_UnitTestCase
{
    private function payload()
    {
        return [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'age' => 38,
            'choices' => [true, false, false],
            'address' => 'Carrer de les Camèlies',
            'city' => 'Barcelona',
            'street' => 'Carrer de Balmes',
            'state' => 'Barcelona',
            'country' => 'Spain',
            'T&S' => true,
        ];
    }

    private function bridge($mutations = [])
    {
        $bridge = new Form_Bridge([
            'name' => 'mutations-test',
            'backend' => 'backend',
            'mutations' => $mutations,
        ]);

        if (!$bridge->is_valid) {
            throw new Exception($bridge->data->get_error_message());
        }

        return $bridge;
    }

    public function test_name_concat()
    {
        $payload = $this->payload();

        $mutations = [
            [
                [
                    'from' => 'firstname',
                    'to' => 'name[0]',
                    'cast' => 'string',
                ],
                [
                    'from' => 'lastname',
                    'to' => 'name[1]',
                    'cast' => 'string',
                ],
                [
                    'from' => 'name',
                    'to' => 'name',
                    'cast' => 'concat',
                ],
            ],
        ];

        $bridge = $this->bridge($mutations);
        $payload = $bridge->apply_mutation($payload);

        $this->assertEquals('John Doe', $payload['name']);
        $this->assertTrue(!isset($payload['firstname']));
        $this->assertTrue(!isset($payload['lastname']));
    }

    public function test_json_ponter_mappers()
    {
        $payload = $this->payload();

        $mutations = [
            [
                [
                    'from' => 'address',
                    'to' => 'address.street',
                    'cast' => 'string',
                ],
                [
                    'from' => 'city',
                    'to' => 'address.city',
                    'cast' => 'string',
                ],
                [
                    'from' => 'state',
                    'to' => 'address.state',
                    'cast' => 'string',
                ],
                [
                    'from' => 'country',
                    'to' => 'address.country',
                    'cast' => 'string',
                ],
            ],
        ];

        $bridge = $this->bridge($mutations);
        $payload = $bridge->apply_mutation($payload);

        $this->assertTrue(is_array($payload['address']));

        $this->assertTrue(isset($payload['address']['street']));
        $this->assertEquals('Carrer de les Camèlies', $payload['address']['street']);

        $this->assertTrue(isset($payload['address']['city']));
        $this->assertEquals('Barcelona', $payload['address']['city']);

        $this->assertTrue(isset($payload['address']['state']));
        $this->assertEquals('Barcelona', $payload['address']['state']);

        $this->assertTrue(isset($payload['address']['country']));
        $this->assertEquals('Spain', $payload['address']['country']);
    }

    public function test_logical_reducers()
    {
        $payload = $this->payload();

        $mutations = [
            [
                [
                    'from' => 'choices',
                    'to' => 'AND',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'AND',
                    'to' => 'AND',
                    'cast' => 'and',
                ],
                [
                    'from' => 'choices',
                    'to' => 'OR',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'OR',
                    'to' => 'OR',
                    'cast' => 'or',
                ],
                [
                    'from' => 'choices',
                    'to' => 'XOR',
                    'cast' => 'xor',
                ],
            ],
        ];

        $bridge = $this->bridge($mutations);
        $payload = $bridge->apply_mutation($payload);

        $this->assertFalse($payload['AND']);
        $this->assertTrue($payload['OR']);
        $this->assertTrue($payload['XOR']);
    }

    public function test_conditional_mappers()
    {
        $payload = $this->payload();

        $mutations = [
            [
                [
                    'from' => '?foo',
                    'to' => 'bar',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'a',
                    'to' => 'b',
                    'cast' => 'integer',
                ],
            ],
        ];

        $bridge = $this->bridge($mutations);
        $payload = $bridge->apply_mutation($payload);

        $this->assertTrue(!isset($payload['foo']));
        $this->assertTrue(!isset($payload['bar']));
        $this->assertEquals(0, $payload['b']);
    }

    public function test_expanded_cast()
    {
        $payload = $this->payload();

        $mutations = [
            [
                [
                    'from' => 'choices[]',
                    'to' => 'choices',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'choices',
                    'to' => 'score',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'score',
                    'to' => 'score',
                    'cast' => 'sum',
                ],
            ],
        ];

        $bridge = $this->bridge($mutations);
        $payload = $bridge->apply_mutation($payload);

        $this->assertEquals(1, $payload['choices'][0]);
        $this->assertEquals(0, $payload['choices'][1]);
        $this->assertEquals(1, $payload['score']);
    }
}
