<?php
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.3.0                                                           |
 |                                                                         |
 | This program is free software: you can redistribute it and/or modify    |
 | it under the terms of the GNU General Public License as published by    |
 | the Free Software Foundation.                                           |
 |                                                                         |
 | This file forms part of the RubioTV software.                           |
 |                                                                         |
 | If you wish to use this file in another project or create a modified    |
 | version that will not be part of the RubioTV Software, you              |
 | may remove the exception above and use this source code under the       |
 | original version of the license.                                        |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            |
 | GNU General Public License for more details.                            |
 |                                                                         |
 | You should have received a copy of the GNU General Public License       |
 | along with this program.  If not, see http://www.gnu.org/licenses/.     |
 |                                                                         |
 +-------------------------------------------------------------------------+
 | Author: Jaime Rubio <jaime@rubiogafsi.com>                              |
 +-------------------------------------------------------------------------+
*/
namespace RubioTV\Framework\Language;

defined('_TVEXEC') or die;

use RubioTV\Framework\Factory;

class Text
{

    protected static $lang;    
    protected static $strings = array();

    
    public static function _($string, $jsSafe = false , $interpretBackSlashes = false)
    {
        if (!static::$lang){                                         
            static::$lang = Factory::getLanguage();
        }
                
        if (self::passSprintf($string, $interpretBackSlashes)) {
            return $string;
        }

        return static::$lang->_($string, $jsSafe , $interpretBackSlashes);
    }

    /**
     * Checks the string if it should be interpreted as sprintf and runs sprintf over it.
     *
     * @param   string   &$string               The string to translate.
     * @param   boolean  $interpretBackSlashes  To interpret backslashes (\\=\, \n=carriage return, \t=tabulation)
     *
     * @return  boolean  Whether the string be interpreted as sprintf
     *
     * @since   3.4.4
     */
    private static function passSprintf(&$string, $interpretBackSlashes = false)
    {
        // Check if string contains a comma
        if (empty($string) || strpos($string, ',') === false) {
            return false;
        }

        $string_parts = explode(',', $string);

        // Pass all parts through the Text translator
        foreach ($string_parts as $i => $str) {
            $string_parts[$i] = static::$lang->_($str, $interpretBackSlashes);
        }

        $first_part = array_shift($string_parts);

        // Replace custom named placeholders with sprintf style placeholders
        $first_part = preg_replace('/\[\[%([0-9]+):[^\]]*\]\]/', '%\1$s', $first_part);

        // Check if string contains sprintf placeholders
        if (!preg_match('/%([0-9]+\$)?s/', $first_part)) {
            return false;
        }

        $final_string = vsprintf($first_part, $string_parts);

        // Return false if string hasn't changed
        if ($first_part === $final_string) {
            return false;
        }

        $string = $final_string;

        return true;
    }

    /**
     * Translates a string into the current language.
     *
     * Examples:
     * `<?php echo Text::alt('JALL', 'language'); ?>` will generate a 'All' string in English but a "Toutes" string in French
     * `<?php echo Text::alt('JALL', 'module'); ?>` will generate a 'All' string in English but a "Tous" string in French
     *
     * @param   string   $string                The string to translate.
     * @param   string   $alt                   The alternate option for global string
     * @param   boolean  $interpretBackSlashes  To interpret backslashes (\\=\, \n=carriage return, \t=tabulation)
     *
     * @return  string  The translated string or the key if $script is true
     *
     * @since   1.7.0
     */
    public static function alt($string, $alt, $interpretBackSlashes = false)
    {
        if (Factory::getLanguage()->hasKey($string . '_' . $alt)) {
            $string .= '_' . $alt;
        }

        return static::_($string, $interpretBackSlashes);
    }

    /**
     * Passes a string thru a sprintf.
     *
     * Note that this method can take a mixed number of arguments as for the sprintf function.
     *
     * The last argument can take an array of options:
     *
     * array('interpretBackSlashes'=>boolean)
     *
     * where:
     *
     * interpretBackSlashes is a boolean to interpret backslashes \\->\, \n->new line, \t->tabulation.
     *
     * @param   string  $string  The format string.
     *
     * @return  string  The translated strings or the key if 'script' is true in the array of options.
     *
     * @since   1.7.0
     */
    public static function sprintf($string)
    {
        $args = \func_get_args();
        $count = \count($args);

        if (\is_array($args[$count - 1])) {
            $args[0] = static::$lang->_(
                $string,
                \array_key_exists('interpretBackSlashes', $args[$count - 1]) ? $args[$count - 1]['interpretBackSlashes'] : true
            );

        } else {
            $args[0] = static::$lang->_($string);
        }

        // Replace custom named placeholders with sprintf style placeholders
        $args[0] = preg_replace('/\[\[%([0-9]+):[^\]]*\]\]/', '%\1$s', $args[0]);

        return \call_user_func_array('sprintf', $args);
    }

    /**
     * Passes a string thru an printf.
     *
     * Note that this method can take a mixed number of arguments as for the sprintf function.
     *
     * @param   string  $string  The format string.
     *
     * @return  mixed
     *
     * @since   1.7.0
     */
    public static function printf($string)
    {
        $args = \func_get_args();
        $count = \count($args);

        if (\is_array($args[$count - 1])) {
            $args[0] = static::$lang->_(
                $string,
                \array_key_exists('interpretBackSlashes', $args[$count - 1]) ? $args[$count - 1]['interpretBackSlashes'] : true
            );
        } else {
            $args[0] = static::$lang->_($string);
        }

        return \call_user_func_array('printf', $args);
    }

}
