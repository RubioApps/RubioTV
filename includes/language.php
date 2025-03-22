<?php
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.5.1                                                           |
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
namespace RubioTV\Framework;

defined('_TVEXEC') or die;

class Language
{
    protected static $languages = [];
    protected $default = 'en-GB';
    protected $orphans = [];
    protected $metadata = null;
    protected $locale = null;
    protected $lang = null;
    protected $paths = [];
    protected $errorfiles = [];
    protected $strings = [];
    protected $used = [];
    protected $counter = 0;
    protected $transliterator = null;
    protected $pluralSuffixesCallback = null;
    protected $ignoredSearchWordsCallback = null;
    protected $lowerLimitSearchWordCallback = null;
    protected $upperLimitSearchWordCallback = null;
    protected $searchDisplayedCharactersNumberCallback = null;


    public function __construct( $lang = null)
    {
        $this->strings = [];
        
        if(!$lang){
            $lang = $this->detectLanguage();
        }
        $this->lang = $lang;
        $this->metadata = $this->getMetadata($this->lang);
        $this->locale = $this->getLocale();

 
        $this->load();
    }

    public function detectLanguage()
    {
        if (isset($_GET["hl"]))
        {
            $knownLangs = $this->getKnownLanguages();               
            foreach ( $knownLangs as $lang)
            {                
                if($_GET["hl"] ==  $lang['tag'])
                {                    
                    return ($lang['tag']);
                }
            }
        }         
        
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLangs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $systemLangs = $this->getLanguages();

            foreach ($browserLangs as $browserLang) {
                // Slice out the part before ; on first step, the part before - on second, place into array
                $browserLang = substr($browserLang, 0, strcspn($browserLang, ';'));
                $primary_browserLang = substr($browserLang, 0, 2);

                foreach ($systemLangs as $systemLang) {
                    // Take off 3 letters iso code languages as they can't match browsers' languages and default them to en
                    $Jinstall_lang = $systemLang->lang_code;

                    if (\strlen($Jinstall_lang) < 6) {
                        if (strtolower($browserLang) == strtolower(substr($systemLang->lang_code, 0, \strlen($browserLang)))) {
                            return $systemLang->lang_code;
                        } elseif ($primary_browserLang == substr($systemLang->lang_code, 0, 2)) {
                            $primaryDetectedLang = $systemLang->lang_code;
                        }
                    }
                }

                if (isset($primaryDetectedLang)) {
                    return $primaryDetectedLang;
                }
            }
        }
    }

    /**
     * Returns a list of known languages for an area
     *
     * @param   string  $basePath  The basepath to use
     *
     * @return  array  key/value pair with the language file and real name.
     *
     * @since   1.0.0
     */
    public function getKnownLanguages($basePath = TV_BASE)
    {
        return $this->parseLanguageFiles($this->getLanguagePath($basePath));
    }    

    /**
     * Searches for language directories within a certain base dir.
     *
     * @param   string  $dir  directory of files.
     *
     * @return  array  Array holding the found languages as filename => real name pairs.
     *
     * @since   1.0.0
     */
    public function parseLanguageFiles($dir = null)
    {
        $languages = [];

        // Search main language directory for subdirectories
        foreach (glob($dir . '/*', GLOB_NOSORT | GLOB_ONLYDIR) as $directory) {
            // But only directories with lang code format
            if (preg_match('#/[a-z]{2,3}-[A-Z]{2}$#', $directory)) {
                $dirPathParts = pathinfo($directory);
                $file         = $directory . '/langmetadata.xml';

                if (!is_file($file)) {
                    $file = $directory . '/' . $dirPathParts['filename'] . '.xml';
                }

                if (!is_file($file)) {
                    continue;
                }

                try {
                    // Get installed language metadata from xml file and merge it with lang array
                    $metadata = $this->parseXMLLanguageFile($file);
                    if (count($metadata)) {
                        $languages = array_replace($languages, array($dirPathParts['filename'] => $metadata));
                    }
                } catch (\RuntimeException $e) {
                    // Ignore it
                }
            }
        }

        return $languages;
    }
    
    /**
     * Parse XML file for language information.
     *
     * @param   string  $path  Path to the XML files.
     *
     * @return  array  Array holding the found metadata as a key => value pair.
     *
     * @since   1.0.0
     */
    public function parseXMLLanguageFile($path)
    {
        if (!is_readable($path)) {
            throw new \RuntimeException('File not found or not readable');
        }

        // Try to load the file
        $xml = simplexml_load_file($path);

        if (!$xml) {
            return [];
        }

        // Check that it's a metadata file
        if ((string) $xml->getName() !== 'metafile') {
            return [];
        }

        $metadata = [];

        foreach ($xml->metadata->children() as $child) {
            $metadata[$child->getName()] = (string) $child;
        }

        return $metadata;
    }    

    
    /**
     * Get available languages
     *
     * @param   string  $key  Array key
     *
     * @return  array  An array of published languages
     *
     * @since   1.0.0
     */
    public function getLanguages($key = 'default')
    {

        if (empty($this->languages)) {
            $languages[$key] = [];
            $knownLangs = $this->getKnownLanguages(TV_BASE);

            foreach ($knownLangs as $metadata) {
                // Take off 3 letters iso code languages as they can't match browsers' languages and default them to en
                $obj = new \stdClass();
                $obj->lang_code = $metadata['tag'];
                $languages[$key][] = $obj;
            }
        }
        return $languages[$key];
    }

    /**
     * Get the current array of loaded strings
     */
    public function getStrings()
    {
        return $this->strings;
    }    
        
    /**
     * Translate function, mimics the php gettext (alias _) function.
     *
     * The function checks if $jsSafe is true, then if $interpretBackslashes is true.
     *
     * @param   string   $string                The string to translate
     * @param   boolean  $jsSafe                Parameter to add slashes to the string that will be rendered as JavaScript.
     *                                          However, set as "false" if the string is going to be encoded by json_encode().
     * @param   boolean  $interpretBackSlashes  Interpret \t and \n
     *
     * @return  string  The translation of the string
     *
     * @since   1.0.0
     */
    public function _($string, $jsSafe = false, $interpretBackSlashes = true)
    {
        // Detect empty string
        if ($string == '') {
            return '';
        }

        $key = strtoupper($string);
       
        if (isset($this->strings[$key])) {
            $string = $this->strings[$key];
        } 

        if ($jsSafe) {
            // Javascript filter
            $string = addslashes($string);
        } elseif ($interpretBackSlashes) {
            if(!is_array($string)){
                if (strpos($string, '\\') !== false) {
                    // Interpret \n and \t characters
                    $string = str_replace(array('\\\\', '\t', '\n'), array("\\", "\t", "\n"), $string);
                }
            }
        }

        return $string;
    }

    /**
     * Get the path to a language
     *
     * @param   string  $basePath  The basepath to use.
     * @param   string  $language  The language tag.
     *
     * @return  string  language related path or null.
     *
     * @since   1.0.0
     */
    public function getLanguagePath($basePath = TV_BASE, $language = null)
    {
        return $basePath . '/local' . (!empty($language) ? '/' . $language : '');
    }

    /**
     * Returns an associative array holding the metadata.
     *
     * @param   string  $lang  The name of the language.
     *
     * @return  mixed  If $lang exists return key/value pair with the language metadata, otherwise return NULL.
     *
     * @since   1.0.0
     */
    public function getMetadata($lang)
    {
        
        $file = $this->getLanguagePath(TV_BASE, $lang) . '/langmetadata.xml';
        
        if (!is_file($file)) {
            $file = $this->getLanguagePath(TV_BASE, $lang) . '/' . $lang . '.xml';
        }

        $result = null;

        if (is_file($file)) {
            $result = $this->parseXMLLanguageFile($file);
        }

        if (empty($result)) {
            return;
        }

        return $result;
    }    
    
    /**
     * Parse strings from a language file.
     *
     * @param   string   $fileName  The language ini file path.
     * @param   boolean  $debug     If set to true debug language ini file.
     *
     * @return  array  The strings parsed.
     *
     * @since   1.0.0
     */
    public function parseIniFile($fileName)
    {
        // Check if file exists.
        if (!is_file($fileName)) {
            return [];
        }

        $disabledFunctions = explode(',', ini_get('disable_functions'));
        $isParseIniFileDisabled = \in_array('parse_ini_file', array_map('trim', $disabledFunctions));
        
        if (!\function_exists('parse_ini_file') || $isParseIniFileDisabled) {
            $contents = file_get_contents($fileName);            
            $strings = @parse_ini_string($contents);
        } else {            
            $strings = @parse_ini_file($fileName);
        }

        return \is_array($strings) ? $strings : [];
    }
    
    /**
     * Transliterate function
     *
     * This method processes a string and replaces all accented UTF-8 characters by unaccented
     * ASCII-7 "equivalents".
     *
     * @param   string  $string  The string to transliterate.
     *
     * @return  string  The transliteration of the string.
     *
     * @since   1.0.0
     */
    
    public function transliterate($string)
    {
        // First check for transliterator provided by translation
        if ($this->transliterator !== null) {
            $string = \call_user_func($this->transliterator, $string);

            // Check if all symbols were transliterated (contains only ASCII), otherwise continue
            if (!preg_match('/[\\x80-\\xff]/', $string)) {
                return $string;
            }
        }

        // Run our transliterator for common symbols,
        // This need to be executed before native php transliterator, because it may not have all required transliterators
                
        $string = mb_convert_encoding($string,'ASCII','UTF-8');

        // Check if all symbols were transliterated (contains only ASCII),
        // Otherwise try to use native php function if available
        if (preg_match('/[\\x80-\\xff]/', $string) && function_exists('transliterator_transliterate') && function_exists('iconv')) {
            return iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $string));
        }

        return strtolower($string);
    }

    /**
     * Getter for transliteration function
     *
     * @return  callable  The transliterator function
     *
     * @since   1.0.0
     */
    public function getTransliterator()
    {
        return $this->transliterator;
    }

    /**
     * Set the transliteration function.
     *
     * @param   callable  $function  Function name or the actual function.
     *
     * @return  callable  The previous function.
     *
     * @since   1.0.0
     */
    public function setTransliterator(callable $function)
    {
        $previous = $this->transliterator;
        $this->transliterator = $function;

        return $previous;
    }

    /**
     * Returns an array of suffixes for plural rules.
     *
     * @param   integer  $count  The count number the rule is for.
     *
     * @return  array    The array of suffixes.
     *
     * @since   1.0.0
     */
    public function getPluralSuffixes($count)
    {
        if ($this->pluralSuffixesCallback !== null) {
            return \call_user_func($this->pluralSuffixesCallback, $count);
        } else {
            return array((string) $count);
        }
    }

    /**
     * Getter for pluralSuffixesCallback function.
     *
     * @return  callable  Function name or the actual function.
     *
     * @since   1.0.0
     */
    public function getPluralSuffixesCallback()
    {
        return $this->pluralSuffixesCallback;
    }

    /**
     * Set the pluralSuffixes function.
     *
     * @param   callable  $function  Function name or actual function.
     *
     * @return  callable  The previous function.
     *
     * @since   1.0.0
     */
    public function setPluralSuffixesCallback(callable $function)
    {
        $previous = $this->pluralSuffixesCallback;
        $this->pluralSuffixesCallback = $function;

        return $previous;
    }

    /**
     * Returns an array of ignored search words
     *
     * @return  array  The array of ignored search words.
     *
     * @since   1.0.0
     */
    public function getIgnoredSearchWords()
    {
        if ($this->ignoredSearchWordsCallback !== null) {
            return \call_user_func($this->ignoredSearchWordsCallback);
        } else {
            return [];
        }
    }

    /**
     * Getter for ignoredSearchWordsCallback function.
     *
     * @return  callable  Function name or the actual function.
     *
     * @since   1.0.0
     */
    public function getIgnoredSearchWordsCallback()
    {
        return $this->ignoredSearchWordsCallback;
    }

    /**
     * Setter for the ignoredSearchWordsCallback function
     *
     * @param   callable  $function  Function name or actual function.
     *
     * @return  callable  The previous function.
     *
     * @since   1.0.0
     */
    public function setIgnoredSearchWordsCallback(callable $function)
    {
        $previous = $this->ignoredSearchWordsCallback;
        $this->ignoredSearchWordsCallback = $function;

        return $previous;
    }

    /**
     * Returns a lower limit integer for length of search words
     *
     * @return  integer  The lower limit integer for length of search words (3 if no value was set for a specific language).
     *
     * @since   1.0.0
     */
    public function getLowerLimitSearchWord()
    {
        if ($this->lowerLimitSearchWordCallback !== null) {
            return \call_user_func($this->lowerLimitSearchWordCallback);
        } else {
            return 3;
        }
    }

    /**
     * Getter for lowerLimitSearchWordCallback function
     *
     * @return  callable  Function name or the actual function.
     *
     * @since   1.0.0
     */
    public function getLowerLimitSearchWordCallback()
    {
        return $this->lowerLimitSearchWordCallback;
    }

    /**
     * Setter for the lowerLimitSearchWordCallback function.
     *
     * @param   callable  $function  Function name or actual function.
     *
     * @return  callable  The previous function.
     *
     * @since   1.0.0
     */
    public function setLowerLimitSearchWordCallback(callable $function)
    {
        $previous = $this->lowerLimitSearchWordCallback;
        $this->lowerLimitSearchWordCallback = $function;

        return $previous;
    }

    /**
     * Returns an upper limit integer for length of search words
     *
     * @return  integer  The upper limit integer for length of search words (200 if no value was set or if default value is < 200).
     *
     * @since   1.0.0
     */
    public function getUpperLimitSearchWord()
    {
        if ($this->upperLimitSearchWordCallback !== null && \call_user_func($this->upperLimitSearchWordCallback) > 200) {
            return \call_user_func($this->upperLimitSearchWordCallback);
        }

        return 200;
    }

    /**
     * Getter for upperLimitSearchWordCallback function
     *
     * @return  callable  Function name or the actual function.
     *
     * @since   1.0.0
     */
    public function getUpperLimitSearchWordCallback()
    {
        return $this->upperLimitSearchWordCallback;
    }

    /**
     * Setter for the upperLimitSearchWordCallback function
     *
     * @param   callable  $function  Function name or the actual function.
     *
     * @return  callable  The previous function.
     *
     * @since   1.0.0
     */
    public function setUpperLimitSearchWordCallback(callable $function)
    {
        $previous = $this->upperLimitSearchWordCallback;
        $this->upperLimitSearchWordCallback = $function;

        return $previous;
    }

    /**
     * Returns the number of characters displayed in search results.
     *
     * @return  integer  The number of characters displayed (200 if no value was set for a specific language).
     *
     * @since   1.0.0
     */
    public function getSearchDisplayedCharactersNumber()
    {
        if ($this->searchDisplayedCharactersNumberCallback !== null) {
            return \call_user_func($this->searchDisplayedCharactersNumberCallback);
        } else {
            return 200;
        }
    }

    /**
     * Getter for searchDisplayedCharactersNumberCallback function
     *
     * @return  callable  Function name or the actual function.
     *
     * @since   1.0.0
     */
    public function getSearchDisplayedCharactersNumberCallback()
    {
        return $this->searchDisplayedCharactersNumberCallback;
    }

    /**
     * Setter for the searchDisplayedCharactersNumberCallback function.
     *
     * @param   callable  $function  Function name or the actual function.
     *
     * @return  callable  The previous function.
     *
     * @since   1.0.0
     */
    public function setSearchDisplayedCharactersNumberCallback(callable $function)
    {
        $previous = $this->searchDisplayedCharactersNumberCallback;
        $this->searchDisplayedCharactersNumberCallback = $function;

        return $previous;
    }

    /**
     * Loads a single language file and appends the results to the existing strings
     *
     * @param   string   $extension  The extension for which a language file should be loaded.
     * @param   string   $basePath   The basepath to use.
     * @param   string   $lang       The language to load, default null for the current language.
     * @param   boolean  $reload     Flag that will force a language to be reloaded if set to true.
     * @param   boolean  $default    Flag that force the default language to be loaded if the current does not exist.
     *
     * @return  boolean  True if the file has successfully loaded.
     *
     * @since   1.0.0
     */
    public function load($basePath = TV_BASE, $lang = null, $reload = false, $default = true)
    {
        // If language is null set as the current language.
        if (!$lang) {
            $lang = $this->lang;
        }
         
        // Load the default language first if we're not debugging and a non-default language is requested to be loaded
        // with $default set to true
        if (($lang != $this->default) && $default) {
            $this->load( $basePath, $this->default, false, true);
        }

        $path = $this->getLanguagePath($basePath, $lang);

        $filenames = [];
        $filenames[] = "$path/rubiotv.ini";
        $filenames[] = "$path/$lang.ini";        

        foreach ($filenames as $filename) {
            if (isset($this->paths[$filename]) && !$reload) {
                // This file has already been tested for loading.
                $result = $this->paths[$filename];
            } else {
                // Load the language file
                $result = $this->loadLanguage($filename);
            }
            if ($result) {
                return true;
            }
        }
        return false;
    }

    /**
     * Loads a language file.
     *
     * This method will not note the successful loading of a file - use load() instead.
     *
     * @param   string  $fileName   The name of the file.
     * @param   string  $extension  The name of the extension.
     *
     * @return  boolean  True if new strings have been added to the language
     *
     * @see     Language::load()
     * @since   1.0.0
     */
    protected function loadLanguage($fileName)
    {
        $this->counter++;
        
        $result  = false;
        $strings = $this->parse($fileName);

        if ($strings !== array()) {
            $this->strings = array_replace($this->strings, $strings);
            $result = true;
        } 

        $this->strings = $strings;               

        $this->paths[$fileName] = $result;


        return $result;
    }

    /**
     * Parses a language file.
     *
     * @param   string  $fileName  The name of the file.
     *
     * @return  array  The array of parsed strings.
     *
     * @since   1.0.0
     */
    protected function parse($fileName)
    {
        $strings = $this->parseIniFile($fileName);
        return $strings;
    }

    
    public function get($property, $default = null)
    {
        if (isset($this->metadata[$property])) {
            return $this->metadata[$property];
        }

        return $default;
    }

    /**
     * Get a back trace.
     *
     * @return array
     *
     * @since   1.0.0
     */
    protected function getTrace()
    {
        return \function_exists('debug_backtrace') ? debug_backtrace() : [];
    }

    /**
     * Getter for Name.
     *
     * @return  string  Official name element of the language.
     *
     * @since   1.0.0
     */
    public function getName()
    {
        return $this->metadata['name'];
    }

    /**
     * Get a list of language files that have been loaded.
     *
     * @param   string  $extension  An optional extension name.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getPaths($extension = null)
    {
        if (isset($extension)) {
            if (isset($this->paths[$extension])) {
                return array($this->paths[$extension]);
            }
            return [];
        }

        return $this->paths;
    }

    /**
     * Get a list of language files that are in error state.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getErrorFiles()
    {
        return $this->errorfiles;
    }

    /**
     * Getter for the language tag (as defined in RFC 3066)
     *
     * @return  string  The language tag.
     *
     * @since   1.0.0
     */
    public function getTag()
    {
        return $this->metadata['tag'];
    }

    /**
     * Getter for the calendar type
     *
     * @return  string  The calendar type.
     *
     * @since   3.7.0
     */
    public function getCalendar()
    {
        if (isset($this->metadata['calendar'])) {
            return $this->metadata['calendar'];
        } else {
            return 'gregorian';
        }
    }

    /**
     * Get the RTL property.
     *
     * @return  boolean  True is it an RTL language.
     *
     * @since   1.0.0
     */
    public function isRtl()
    {
        return (bool) $this->metadata['rtl'];
    }

    /**
     * Get the default language code.
     *
     * @return  string  Language code.
     *
     * @since   1.0.0
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set the default language code.
     *
     * @param   string  $lang  The language code.
     *
     * @return  string  Previous value.
     *
     * @since   1.0.0
     */
    public function setDefault($lang)
    {
        $previous = $this->default;
        $this->default = $lang;

        return $previous;
    }

    /**
     * Get the list of orphaned strings if being tracked.
     *
     * @return  array  Orphaned text.
     *
     * @since   1.0.0
     */
    public function getOrphans()
    {
        return $this->orphans;
    }

    /**
     * Get the list of used strings.
     *
     * Used strings are those strings requested and found either as a string or a constant.
     *
     * @return  array  Used strings.
     *
     * @since   1.0.0
     */
    public function getUsed()
    {
        return $this->used;
    }

    /**
     * Determines is a key exists.
     *
     * @param   string  $string  The key to check.
     *
     * @return  boolean  True, if the key exists.
     *
     * @since   1.0.0
     */
    public function hasKey($string)
    {
        if ($string === null) {
            return false;
        }

        return isset($this->strings[strtoupper($string)]);
    }

    /**
     * Get the language locale based on current language.
     *
     * @return  array  The locale according to the language.
     *
     * @since   1.0.0
     */
    public function getLocale()
    {
        if (!isset($this->locale)) {
            $locale = str_replace(' ', '', $this->metadata['locale'] ?? '');

            if ($locale) {
                $this->locale = explode(',', $locale);
            } else {
                $this->locale = false;
            }
        }

        return $this->locale;
    }

    /**
     * Get the first day of the week for this language.
     *
     * @return  integer  The first day of the week according to the language
     *
     * @since   1.0.0
     */
    public function getFirstDay()
    {
        return (int) ($this->metadata['firstDay'] ?? 0);
    }

    /**
     * Get the weekends days for this language.
     *
     * @return  string  The weekend days of the week separated by a comma according to the language
     *
     * @since   1.0.0
     */
    public function getWeekEnd()
    {
        return $this->metadata['weekEnd'] ?? '0,6';
    }
}
