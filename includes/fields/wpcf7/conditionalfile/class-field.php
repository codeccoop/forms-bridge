<?php

namespace WPCT_ERP_FORMS\WPCF7\Fields\ConditionalFile;

use WPCT_ERP_FORMS\Abstract\Field as BaseField;
use WPCF7_FormTag;

require 'class-rule.php';

class Field extends BaseField
{
    static private $tag_callbacks = [
        [
            'tags' => [
                'file', 'file*'
            ],
            'function' => 'wpcf7_file_form_tag_handler'
        ],
    ];

    protected function __construct()
    {
        add_action('wpcf7_swv_create_schema', [$this, 'add_rules'], 20, 2);

        add_filter('wpcf7_swv_available_rules', function ($rules) {
            $rules['conditionalfile'] = 'WCPT_WPCF7_ConditionalFile_Rule';
            return $rules;
        });
    }

    public function init()
    {
        if (!function_exists('wpcf7_add_form_tag')) return;

        wpcf7_add_form_tag(
            ['conditionalfile', 'conditionalfile*'],
            [$this, 'handler'],
            [
                'name-attr' => true,
                'file-uploading' => true,
            ]
        );
    }

    public function handler($tag)
    {
        $data = array_merge([], (array) $tag);

        $tag_type = $tag->is_required() ? 'file*' : 'file';
        $tag_basetype = 'file';
        $standard_options = [];
        foreach ($tag->options as $option) {
            if (strstr($option, 'conditions:')) {
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

    public function add_rules($schema, $form)
    {
        $tags = $form->scan_form_tags([
            'basetype' => [
                'conditionalfile'
            ]
        ]);

        $available_rules = wpcf7_swv_available_rules();
        foreach ($tags as $tag) {
            if ($tag->is_required()) {
                $schema->add_rule(
                    wpcf7_swv_create_rule('conditionalfile', array(
                        'field' => $tag->name,
                        'error' => wpcf7_get_message('invalid_required'),
                        'rule' => $available_rules['requiredfile'],
                        'condition' => 'requiredfile'
                    ))
                );
            }

            $schema->add_rule(
                wpcf7_swv_create_rule('file', [
                    'field' => $tag->name,
                    'accept' => explode(',', wpcf7_acceptable_filetypes(
                        $tag->get_option('filetypes'),
                        'attr'
                    )),
                    'error' => wpcf7_get_message('upload_file_type_invalid'),
                ])
            );

            $schema->add_rule(
                wpcf7_swv_create_rule('maxfilesize', [
                    'field' => $tag->name,
                    'threshold' => $tag->get_limit_option(),
                    'error' => wpcf7_get_message('upload_file_too_large'),
                ])
            );
        }
    }
}
