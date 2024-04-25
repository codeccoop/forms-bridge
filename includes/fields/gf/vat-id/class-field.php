<?php

namespace WPCT_ERP_FORMS\GF\Fields\VatID;

use Exception;
use GF_Field;

class GFField extends GF_Field
{
	// https://gist.github.com/afgomez/5691823#file-validate_spanish_id-js-L88
    private static $_dni_regex = '/^(\d{8})([A-Z])$/';
    private static $_cif_regex = '/^([ABCDEFGHJKLMNPQRSUVW])(\d{7})([0-9A-J])$/';
    private static $_nie_regex = '/^[XYZ]\d{7,8}[A-Z]$/';

    /**
     * @var string $type The field type.
     */
    public $type = 'vat-id-field';

    /**
     * Return the field title, for use in the form editor.
     *
     * @return string
     */
    public function get_form_editor_field_title()
    {
        return esc_attr__('VAT ID');
    }

    /**
     * Assign the field button to the Advanced Fields group.
     *
     * @return array
     */
    public function get_form_editor_button()
    {
        return [
            'group' => 'advanced_fields',
            'text'  => $this->get_form_editor_field_title(),
        ];
    }

    /**
     * The settings which should be available on the field in the form editor.
     *
     * @return array
     */
    public function get_form_editor_field_settings()
    {
        return [
            'conditional_logic_field_setting',
            'error_message_setting',
            'label_setting',
            'label_placement_setting',
            'admin_label_setting',
            'size_setting',
            'password_field_setting',
            'rules_setting',
            'visibility_setting',
            'duplicate_setting',
            'default_value_setting',
            'placeholder_setting',
            'description_setting',
            'css_class_setting',
			'prepopulate_field_setting',
        ];
    }

    /**
     * Enable this field for use with conditional logic.
     *
     * @return bool
     */
    public function is_conditional_logic_supported()
    {
        return true;
    }

    /**
     * Define the fields inner markup.
     *
     * @param array        $form The Form Object currently being processed.
     * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
     * @param null|array   $entry Null or the Entry Object currently being edited.
     *
     * @return string
     */
    public function get_field_input($form, $value = '', $entry = null)
    {
        $form_id = absint($form['id']);
        $is_entry_detail = $this->is_entry_detail();
        $is_form_editor  = $this->is_form_editor();

        $html_input_type = 'text';

        if ($this->enablePasswordInput && !$is_entry_detail) {
            $html_input_type = 'password';
        }

        $logic_event = ''; // !$is_form_editor && !$is_entry_detail ? $this->get_conditional_logic_event('keyup') : '';
        $id = (int) $this->id;
        $field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

        $value = esc_attr($value);
        $size = $this->size;
        $class_suffix = $is_entry_detail ? '_admin' : '';
        $class = $size . $class_suffix;

        $tabindex = $this->get_tabindex();
        $disabled_text = $is_form_editor ? 'disabled="disabled"' : '';
        $placeholder_attribute = $this->get_field_placeholder_attribute();
        $required_attribute = $this->isRequired ? 'aria-required="true"' : '';
        $invalid_attribute = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';

        $input = "<input name='input_{$id}' id='{$field_id}' type='{$html_input_type}' value='{$value}' class='{$class}' {$tabindex} {$logic_event} {$placeholder_attribute} {$required_attribute} {$invalid_attribute} {$disabled_text}/>";

        return sprintf("<div class='ginput_container ginput_container_text'>%s</div>", $input);
    }

    /**
     * Validate Field
     */
    public function validate($value, $form)
    {
        try {
            if (strlen($value) < 5) {
                throw new Exception();
            }

            $value = strtolower(str_replace(' ', '', $value));
            $value = preg_replace('/\s/', '', strtoupper($value));

			$valid = false;
            $type = $this->id_type($value);
            switch ($type) {
                case 'dni':
                    $valid = $this->validate_dni($value);
                    break;
                case 'nie':
                    $valid = $this->validate_nie($value);
                    break;
                case 'cif':
                    $valid = $this->validate_cif($value);
                    break;
            }

            if (!$valid) {
                throw new Exception();
            }
        } catch (Exception $e) {
            $this->failed_validation = true;
            $this->validation_message = empty($this->errorMessage) ? __('The VAT number you\'ve inserted is not valid.', 'wpct-erp-forms') : $this->errorMessage;
        }
    }

    private function id_type($value)
    {
        if (preg_match(GFField::$_dni_regex, $value)) {
            return 'dni';
        } elseif (preg_match(GFField::$_nie_regex, $value)) {
            return 'nie';
        } elseif (preg_match(GFField::$_cif_regex, $value)) {
            return 'cif';
        }
    }

    private function validate_dni($value)
    {
        $dni_letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $number = (int) substr($value, 0, 8);
        $index = $number % 23;
        $letter = substr($dni_letters, $index, 1);

        return $letter == substr($value, 8, 9);
    }

    private function validate_nie($value)
    {
        $nie_prefix = substr($value, 0, 1);

        switch ($nie_prefix) {
            case  'X':
                $nie_prefix = 0;
                break;
            case 'Y':
                $nie_prefix = 1;
                break;
            case 'Z':
                $nie_prefix = 2;
                break;
        }

        return $this->validate_dni($nie_prefix . substr($value, 1, 9));
    }

    private function validate_cif($value)
    {
        preg_match(GFField::$_cif_regex, $value, $matches);
        $letter = $matches[1];
        $number = $matches[2];
        $control = $matches[3];

        $even_sum = 0;
        $odd_sum = 0;
        $n = null;

        for ($i = 0; $i < strlen($number); $i++) {
            $n = (int) $number[$i];

            if ($i % 2 === 0) {
                // Odd positions are multiplied first.
                $n *= 2;
                // If the multiplication is bigger than 10 we need to adjust
                $odd_sum += $n < 10 ? $n : $n - 9;

                // Even positions
                // Just sum them
            } else {
                $even_sum += $n;
            }
        }

        $control_digit = 10 - (int) substr(strval($even_sum +  $odd_sum), -1);
        $control_letter = substr('JABCDEFGHI', $control_digit, 1);

        if (preg_match('/[ABEH]/', $letter)) {
            // Control must be a digit
            return (int) $control === $control_digit;
        } elseif (preg_match('/[KPQS]/', $letter)) {
            // Control must be a letter
            return (string) $control === $control_letter;
        } else {
            // Can be either
            return (int) $control === $control_digit || (string) $control === $control_letter;
        }
    }
}
