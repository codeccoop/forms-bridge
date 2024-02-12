<?php

namespace WPCT_ERP_FORMS\WPCF7\Fields\Conditional;

use WPCT_ERP_FORMS\Abstract\Field as BaseField;
use WPCF7_FormTag;

require 'class-rule.php';

class Field extends BaseField
{
    static private $tag_callbacks = [
        [
            'tags' => [
                'text', 'text*', 'email', 'email*', 'url', 'url*', 'tel', 'tel*',
            ],
            'function' => 'wpcf7_text_form_tag_handler'
        ],
        [
            'tags' => [
                'textarea', 'textarea*'
            ],
            'function' => 'wpcf7_textarea_form_tag_handler'
        ],
        [
            'tags' => [
                'checkbox', 'checkbox*', 'radio'
            ],
            'function' => 'wpcf7_checkbox_form_tag_handler'
        ],
        [
            'tags' => [
                'select', 'select*'
            ],
            'function' => 'wpcf7_select_form_tag_handler'
        ],
        [
            'tags' => [
                'date', 'date*'
            ],
            'function' => 'wpcf7_date_form_tag_handler'
        ],
        [
            'tags' => [
                'number', 'number*', 'range', 'range*'
            ],
            'function' => 'wpcf7_number_form_tag_handler'
        ],
        [
            'tags' => [
                'hidden'
            ],
            'function' => 'wpcf7_hidden_form_tag_handler'
        ],
        [
            'tags' => [
                'count'
            ],
            'function' => 'wpcf7_count_form_tag_handler'
        ],
        [
            'tags' => [
                'iban', 'iban*'
            ],
            'function' => 'wpct7_iban_form_tag_handler'
        ]
    ];

    protected function __construct()
    {
        add_action('wpcf7_swv_create_schema', [$this, 'add_rules'], 20, 2);

        add_filter('wpcf7_swv_available_rules', function ($rules) {
            $rules['conditional'] = 'WCPT_WPCF7_Conditional_Rule';
            return $rules;
        });
    }

    public function init()
    {
        if (!function_exists('wpcf7_add_form_tag')) return;

        wpcf7_add_form_tag(
            ['conditional', 'conditional*'],
            [$this, 'handler'],
            ['name-attr' => true]
        );
    }

    public function handler($tag)
    {
        $data = array_merge([], (array) $tag);

        $tag_type = $this->get_tag_type($tag);
        $tag_basetype = $this->get_tag_basetype($tag);
        $standard_options = [];
        foreach ($tag->options as $option) {
            if (strstr($option, 'type:') || strstr($option, 'conditions:')) {
                continue;
            } else {
                array_push($standard_options, $option);
            }
        }

        $data['options'] = $standard_options;
        $data['type'] = $tag_type;
        $data['basetype'] = $tag_basetype;

        $base_tag = new WPCF7_FormTag($data);
        $callback = array_values(array_map(function ($tag_callback) {
            return $tag_callback['function'];
        }, array_filter(static::$tag_callbacks, function ($tag_callback) use ($base_tag) {
            return in_array($base_tag->type, $tag_callback['tags'], true);
        })));
        if (count($callback) > 0) $callback = $callback[0];
        else return '';

        $html_atts = [
            'class' => 'wpcf7-form-control wpcf7-form-control-conditional',
            'data-conditions' => $tag->get_option('conditions')[0],
            'type' => $tag_type,
            'name' => $tag->name
        ];

        $input = call_user_func($callback, $base_tag);
        $meta = sprintf('<span hidden aria-hidden="true" class="wpcf7-form-control-conditional" data-name="%s" %s ></span>', $tag->name, wpcf7_format_atts($html_atts));
        return $input . $meta;
    }

    private function get_tag_basetype($tag)
    {
        $type = $this->get_tag_type($tag);
        return preg_replace('/\*$/', '', $type);
    }

    private function get_tag_type($tag)
    {
        $tag = (object) $tag;

        $type = null;
        foreach ($tag->options as $option) {
            if (strstr($option, 'type:')) {
                $type = substr($option, 5);
                break;
            }
        }

        return $type;
    }

    public function add_rules($schema, $form)
    {
        $tags = $form->scan_form_tags([
            'basetype' => [
                'conditional'
            ]
        ]);

        $available_rules = wpcf7_swv_available_rules();
        foreach ($tags as $tag) {
            $base_type = $this->get_tag_basetype($tag);

            if ($tag->is_required()) {
                $schema->add_rule(
                    wpcf7_swv_create_rule('conditional', [
                        'field' => $tag->name,
                        'error' => wpcf7_get_message('invalid_required'),
                        'type' => $base_type,
                        'rule' => $available_rules['required'],
                        'condition' => 'required',
                    ])
                );
            }

            if ($base_type === 'email') {
                $schema->add_rule(
                    wpcf7_swv_create_rule('email', [
                        'field' => $tag->name,
                        'error' => wpcf7_get_message('invalid_email'),
                        'type' => $base_type,
                    ])
                );
            }

            if ('url' === $base_type) {
                $schema->add_rule(
                    wpcf7_swv_create_rule('url', [
                        'field' => $tag->name,
                        'error' => wpcf7_get_message('invalid_url'),
                        'type' => $base_type,
                    ])
                );
            }

            if ('tel' === $base_type) {
                $schema->add_rule(
                    wpcf7_swv_create_rule('tel', [
                        'field' => $tag->name,
                        'error' => wpcf7_get_message('invalid_tel'),
                        'type' => $base_type,
                    ])
                );
            }

            if ($minlength = $tag->get_minlength_option()) {
                $schema->add_rule(
                    wpcf7_swv_create_rule('minlength', [
                        'field' => $tag->name,
                        'threshold' => absint($minlength),
                        'error' => wpcf7_get_message('invalid_too_short'),
                        'type' => $base_type,
                    ])
                );
            }

            if ($maxlength = $tag->get_maxlength_option()) {
                $schema->add_rule(
                    wpcf7_swv_create_rule('maxlength', [
                        'field' => $tag->name,
                        'threshold' => absint($maxlength),
                        'error' => wpcf7_get_message('invalid_too_long'),
                        'type' => $base_type,
                    ])
                );
            }

            if ('date' === $base_type) {
                $schema->add_rule(
                    wpcf7_swv_create_rule('date', [
                        'field' => $tag->name,
                        'error' => wpcf7_get_message('invalid_date'),
                        'type' => $base_type,
                    ])
                );

                $min = $tag->get_date_option('min');
                $max = $tag->get_date_option('max');

                if (false !== $min) {
                    $schema->add_rule(
                        wpcf7_swv_create_rule('mindate', [
                            'field' => $tag->name,
                            'threshold' => $min,
                            'error' => wpcf7_get_message('date_too_early'),
                            'type' => $base_type,
                        ])
                    );
                }

                if (false !== $max) {
                    $schema->add_rule(
                        wpcf7_swv_create_rule('maxdate', [
                            'field' => $tag->name,
                            'threshold' => $max,
                            'error' => wpcf7_get_message('date_too_late'),
                            'type' => $base_type,
                        ])
                    );
                }
            }

            if ('number' === $base_type) {
                $schema->add_rule(
                    wpcf7_swv_create_rule('number', [
                        'field' => $tag->name,
                        'error' => wpcf7_get_message('invalid_number'),
                        'type' => $base_type,
                    ])
                );

                $min = $tag->get_option('min', 'signed_num', true);
                $max = $tag->get_option('max', 'signed_num', true);

                if ('range' === $tag->basetype) {
                    if (!wpcf7_is_number($min)) {
                        $min = '0';
                    }

                    if (!wpcf7_is_number($max)) {
                        $max = '100';
                    }
                }

                if (wpcf7_is_number($min)) {
                    $schema->add_rule(
                        wpcf7_swv_create_rule('minnumber', [
                            'field' => $tag->name,
                            'threshold' => $min,
                            'error' => wpcf7_get_message('number_too_small'),
                            'type' => $base_type,
                        ])
                    );
                }

                if (wpcf7_is_number($max)) {
                    $schema->add_rule(
                        wpcf7_swv_create_rule('maxnumber', [
                            'field' => $tag->name,
                            'threshold' => $max,
                            'error' => wpcf7_get_message('number_too_large'),
                            'type' => $base_type,
                        ])
                    );
                }
            }
        }
    }
}
