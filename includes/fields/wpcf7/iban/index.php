<?php

namespace WPCT_ERP_FORMS\Fields\WPCF7\Iban;

use WPCT_ERP_FORMS\Fields\BaseField;
use Exception;

class Field extends BaseField
{
    /**
     * @var array @type Country codes
     */
    private $_countries = [
        'al' => 28,
        'ad' => 24,
        'at' => 20,
        'az' => 28,
        'bh' => 22,
        'be' => 16,
        'ba' => 20,
        'br' => 29,
        'bg' => 22,
        'cr' => 21,
        'hr' => 21,
        'cy' => 28,
        'cz' => 24,
        'dk' => 18,
        'do' => 28,
        'ee' => 20,
        'fo' => 18,
        'fi' => 18,
        'fr' => 27,
        'ge' => 22,
        'de' => 22,
        'gi' => 23,
        'gr' => 27,
        'gl' => 18,
        'gt' => 28,
        'hu' => 28,
        'is' => 26,
        'ie' => 22,
        'il' => 23,
        'it' => 27,
        'jo' => 30,
        'kz' => 20,
        'kw' => 30,
        'lv' => 21,
        'lb' => 28,
        'li' => 21,
        'lt' => 20,
        'lu' => 20,
        'mk' => 19,
        'mt' => 31,
        'mr' => 27,
        'mu' => 30,
        'mc' => 27,
        'md' => 24,
        'me' => 22,
        'nl' => 18,
        'no' => 15,
        'pk' => 24,
        'ps' => 29,
        'pl' => 28,
        'pt' => 25,
        'qa' => 29,
        'ro' => 24,
        'sm' => 27,
        'sa' => 24,
        'rs' => 22,
        'sk' => 24,
        'si' => 19,
        'es' => 24,
        'se' => 24,
        'ch' => 21,
        'tn' => 24,
        'tr' => 26,
        'ae' => 23,
        'gb' => 22,
        'vg' => 24,
    ];

    /**
     * @var array $type Char codes
     */
    private $_chars = [
        'a' => 10,
        'b' => 11,
        'c' => 12,
        'd' => 13,
        'e' => 14,
        'f' => 15,
        'g' => 16,
        'h' => 17,
        'i' => 18,
        'j' => 19,
        'k' => 20,
        'l' => 21,
        'm' => 22,
        'n' => 23,
        'o' => 24,
        'p' => 25,
        'q' => 26,
        'r' => 27,
        's' => 28,
        't' => 29,
        'u' => 30,
        'v' => 31,
        'w' => 32,
        'x' => 33,
        'y' => 34,
        'z' => 35
    ];

    public function register()
    {
        add_action('wpcf7_init', function () {
            $this->_register();
        });
    }

    private function _register()
    {
        wpcf7_add_form_tag(
            ['iban', 'iban*'],
            function ($tag) {
                return $this->handler($tag);
            },
            ['name-attr' => true]
        );

        add_filter('wpcf7_validate_iban*', function ($result, $tag) {
            return $this->validate($result, $tag);
        }, 20, 2);
        add_filter('wpcf7_validate_iban', function ($result, $tag) {
            return $this->validate($result, $tag);
        }, 20, 2);
    }

    private function handler($tag)
    {
        $atts = [
            'type' => 'text',
            'name' => $tag->name,
        ];

        $input = sprintf('<input %s />', wpcf7_format_atts($atts));
        return $input;
    }

    private function validate($result, $tag)
    {
        $value = $_POST[$tag->name];
        try {
            if (strlen($value) < 5) throw new Exception();
            $value = strtolower(str_replace(' ', '', $value));


            $country_exists = array_key_exists(substr($value, 0, 2), $this->_countries);
            $country_conform = strlen($value) == $this->_countries[substr($value, 0, 2)];

            if (!($country_exists && $country_conform)) throw new Exception();

            $moved_char = substr($value, 4) . substr($value, 0, 4);
            $move_char_array = str_split($moved_char);
            $new_string = '';

            foreach ($move_char_array as $key => $val) {
                if (!is_numeric($move_char_array[$key])) {
                    if (!isset($this->_chars[$val])) throw new Exception();
                    $move_char_array[$key] = $this->_chars[$val];
                }

                $new_string .= $move_char_array[$key];
            }

            if (bcmod($new_string, '97') != 1) {
                throw new Exception();
            }
        } catch (Exception) {
            $result->invalidate($tag, 'Invalid IBAN format');
        }
    }
}
