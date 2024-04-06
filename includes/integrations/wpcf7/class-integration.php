<?php

namespace WPCT_ERP_FORMS\WPCF7;

use WPCT_ERP_FORMS\Abstract\Integration as BaseIntegration;
use WPCT_ERP_FORMS\WPCF7\Fields\Iban\Field as IbanField;
use WPCT_ERP_FORMS\WPCF7\Fields\Conditional\Field as ConditionalField;
use WPCT_ERP_FORMS\WPCF7\Fields\ConditionalFile\Field as ConditionalFileField;

// Fields
require_once dirname(__FILE__, 3) . '/fields/wpcf7/iban/class-field.php';
require_once dirname(__FILE__, 3) . '/fields/wpcf7/conditional/class-field.php';
require_once dirname(__FILE__, 3) . '/fields/wpcf7/conditionalfile/class-field.php';

class Integration extends BaseIntegration
{
    public static $fields = [
        IbanField::class,
        ConditionalField::class,
        ConditionalFileField::class,
    ];

    protected function __construct()
    {
        parent::__construct();

        add_filter('wpcf7_before_send_mail', function ($form, &$abort, $submission) {
            $this->do_submission($submission, $form);
        }, 10, 3);

        add_filter('wpcf7_form_elements', function ($tags) {
            $plugin_url = plugin_dir_url(dirname(__FILE__, 4) . '/wpct-erp-forms.php');
            $script_url = $plugin_url . 'assets/js/wpcf7.js';
            ob_start();
?>
            <script src="<?= $script_url ?>" type="module"></script>
            <style>
                .wpcf7-form-control-conditional-wrap {
                    display: none
                }
                .wpcf7-form-control-conditional-wrap.visible {
                    display: block;
                }
            </style>
<?php
            $assets = ob_get_clean();
            return $tags . $assets;
        }, 90, 1);
    }

	public function serialize_field($field, $form)
	{
		return [
			'type' => $field->basetype,
			'name' => $field->rawname,
			'label' => $field->labels,
			'values' => $field->values,
			'id' => null,
		];
	}

    public function serialize_submission($submission, $form)
    {
        $data = $submission->get_posted_data();
        $data['id'] = $submission->get_posted_data_hash();
        foreach ($data as $key => $val) {
            if ((int) $val == $val) {
                $number = (float) $val;
                if ((string) $number === $val) {
                    $data[$key] = $number;
                }
            }
        }

        return $data;
    }

    public function serialize_form($form)
    {
        return [
            'id' => $form->id(),
            'title' => $form->title(),
			'fields' => array_map(function ($field) use ($form) {
				return $this->serialize_field($field, $form);
			}, $form->scan_form_tags()),
        ];
    }

    public function get_uploads($submission, $form)
    {
        $uploads = [];
        $uploads = $submission->uploaded_files();
        foreach ($uploads as $file_name => $paths) {
            if (!empty($paths)) {
				$uploads[$file_name] = [
					'path' => $paths[0],
					'is_multi' => false
				];
            }
        };

        return $uploads;
    }
}
