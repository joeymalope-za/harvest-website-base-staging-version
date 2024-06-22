<?php

namespace WWBP\App\Helpers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class ParseInput
{
    /**
     * Sanitize then Validate is an ID.
     * 
     * @param string $input
     * @param bool $die
     * 
     * @return string|null|void
     */
    public static function id($input, $die = false)
    {
        $input = sanitize_text_field($input);

        if (!empty($input) && strlen($input) <= 20 && preg_match('/^[1-9][0-9]*$/', $input)) {
            return $input;
        } else {
            if ($die == true) {
                die('Error: Invalid ID');
            } else {
                return null;
            }
        }
    }

    /**
     * Sanitize then Validate is a Positive number.
     * 
     * @param string $input
     * @param bool $die
     * 
     * @return string|null|void
     */
    public static function number($input, $die = false)
    {
        $input = sanitize_text_field($input);

        if (strlen($input) == 0) return null;

        if (is_numeric($input) && $input >= 0) {
            return $input;
        } else {
            if ($die == true) {
                die('Error: Invalid Number');
            } else {
                return null;
            }
        }
    }

    /**
     * Sanitize Text.
     * 
     * @param string $input
     * @param bool $die
     * 
     * @return string|null|void
     */
    public static function text($input)
    {
        $input = sanitize_text_field($input);

        return $input;
    }
}
