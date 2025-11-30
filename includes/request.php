<?php
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.6.1                                                          |
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

$GLOBALS['_REQUEST'] = [];

const REQUEST_NOTRIM    = 1;
const REQUEST_ALLOWRAW  = 2;
const REQUEST_ALLOWHTML = 4;

class Request
{

	public static function getMethod()
	{
		return strtoupper($_SERVER['REQUEST_METHOD']);
	}

	/**
	 * Fetches and returns a given variable.
	 *
	 * The default behaviour is fetching variables depending on the
	 * current request method: GET and HEAD will result in returning
	 * an entry from $_GET, POST and PUT will result in returning an
	 * entry from $_POST.
	 *
	 * You can force the source by setting the $hash parameter:
	 *
	 * post    $_POST
	 * get     $_GET
	 * files   $_FILES
	 * cookie  $_COOKIE
	 * env     $_ENV
	 * server  $_SERVER
	 * method  via current $_SERVER['REQUEST_METHOD']
	 * default $_REQUEST
	 *
	 * @param   string   $name     Variable name.
	 * @param   mixed    $default  Default value if the variable does not exist.
	 * @param   string   $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD).
	 * @param   string   $type     Return type for the variable
	 * @param   integer  $mask     Filter mask for the variable.
	 *
	 * @return  mixed  Requested variable.
	 */
	public static function getVar($name, $default = null, $hash = 'default', $type = 'none', $mask = 0)
	{
		// Ensure hash and type are uppercase
		$hash = strtoupper($hash);

		if ($hash === 'METHOD')
		{
			$hash = strtoupper($_SERVER['REQUEST_METHOD']);
		}

		$type = strtoupper($type);
		$sig = $hash . $type . $mask;

		// Get the input hash
		switch ($hash)
		{
			case 'GET':
				$input = &$_GET;
				break;
			case 'POST':
				$input = &$_POST;
				break;
			case 'FILES':
				$input = &$_FILES;
				break;
			case 'COOKIE':
				$input = &$_COOKIE;
				break;
			case 'ENV':
				$input = &$_ENV;
				break;
			case 'SERVER':
				$input = &$_SERVER;
				break;
			default:
				$input = &$_REQUEST;
				$hash = 'REQUEST';
				break;
		}

		if (isset($GLOBALS['_REQUEST'][$name]['SET.' . $hash]) && ($GLOBALS['_REQUEST'][$name]['SET.' . $hash] === true))
		{
			// Get the variable from the input hash
			$var = (isset($input[$name]) && $input[$name] !== null) ? $input[$name] : $default;
			$var = self::_cleanVar($var, $mask, $type);
		}
		elseif (!isset($GLOBALS['_REQUEST'][$name][$sig]))
		{
			if (isset($input[$name]))
			{
				// Get the variable from the input hash and clean it
				$var = self::_cleanVar($input[$name], $mask, $type);

				$GLOBALS['_REQUEST'][$name][$sig] = $var;
			}
			elseif ($default !== null)
			{
				// Clean the default value
				$var = self::_cleanVar($default, $mask, $type);
			}
			else
			{
				$var = $default;
			}
		}
		else
		{
			$var = $GLOBALS['_REQUEST'][$name][$sig];
		}

		return $var;
	}

	/**
	 * Fetches and returns a given filtered variable. The integer
	 * filter will allow only digits and the - sign to be returned. This is currently
	 * only a proxy function for getVar().
	 *
	 * See getVar() for more in-depth documentation on the parameters.
	 *
	 * @param   string   $name     Variable name.
	 * @param   integer  $default  Default value if the variable does not exist.
	 * @param   string   $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD).
	 *
	 * @return  integer  Requested variable.
	 */
	public static function getInt($name, $default = 0, $hash = 'default')
	{
		return self::getVar($name, $default, $hash, 'int');
	}

	/**
	 * Fetches and returns a given filtered variable.  The float
	 * filter only allows digits and periods.  This is currently
	 * only a proxy function for getVar().
	 *
	 * See getVar() for more in-depth documentation on the parameters.
	 *
	 * @param   string  $name     Variable name.
	 * @param   float   $default  Default value if the variable does not exist.
	 * @param   string  $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD).
	 *
	 * @return  float  Requested variable.
	 *
	 */
	public static function getFloat($name, $default = 0.0, $hash = 'default')
	{
		return self::getVar($name, $default, $hash, 'float');
	}

	/**
	 * Fetches and returns a given filtered variable. The bool
	 * filter will only return true/false bool values. This is
	 * currently only a proxy function for getVar().
	 *
	 * See getVar() for more in-depth documentation on the parameters.
	 *
	 * @param   string   $name     Variable name.
	 * @param   boolean  $default  Default value if the variable does not exist.
	 * @param   string   $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD).
	 *
	 * @return  boolean  Requested variable.
	 *
	 */
	public static function getBool($name, $default = false, $hash = 'default')
	{
		return self::getVar($name, $default, $hash, 'bool');
	}

	/**
	 * Fetches and returns a given filtered variable. The word
	 * filter only allows the characters [A-Za-z_]. This is currently
	 * only a proxy function for getVar().
	 *
	 * See getVar() for more in-depth documentation on the parameters.
	 *
	 * @param   string  $name     Variable name.
	 * @param   string  $default  Default value if the variable does not exist.
	 * @param   string  $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD).
	 *
	 * @return  string  Requested variable.
	 */
	public static function getWord($name, $default = '', $hash = 'default')
	{
		return self::getVar($name, $default, $hash, 'word');
	}

	/**
	 * Cmd (Word and Integer) filter
	 *
	 * Fetches and returns a given filtered variable. The cmd
	 * filter only allows the characters [A-Za-z0-9.-_]. This is
	 * currently only a proxy function for getVar().
	 *
	 * See getVar() for more in-depth documentation on the parameters.
	 *
	 * @param   string  $name     Variable name
	 * @param   string  $default  Default value if the variable does not exist
	 * @param   string  $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
	 *
	 * @return  string  Requested variable
	 */
	public static function getCmd($name, $default = '', $hash = 'default')
	{
		return self::getVar($name, $default, $hash, 'cmd');
	}

	/**
	 * Fetches and returns a given filtered variable. The string
	 * filter deletes 'bad' HTML code, if not overridden by the mask.
	 * This is currently only a proxy function for getVar().
	 *
	 * See getVar() for more in-depth documentation on the parameters.
	 *
	 * @param   string   $name     Variable name
	 * @param   string   $default  Default value if the variable does not exist
	 * @param   string   $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
	 * @param   integer  $mask     Filter mask for the variable
	 *
	 * @return  string   Requested variable
	 */
	public static function getString($name, $default = '', $hash = 'default', $mask = 0)
	{
		// Cast to string, in case REQUEST_ALLOWRAW was specified for mask
		return (string) self::getVar($name, $default, $hash, 'string', $mask);
	}

	/**
	 * Set a variable in one of the request variables.
	 *
	 * @param   string   $name       Name
	 * @param   string   $value      Value
	 * @param   string   $hash       Hash
	 * @param   boolean  $overwrite  Boolean
	 *
	 * @return  string   Previous value
	 */
	public static function setVar($name, $value = null, $hash = 'method', $overwrite = true)
	{
		// If overwrite is true, makes sure the variable hasn't been set yet
		if (!$overwrite && array_key_exists($name, $_REQUEST))
		{
			return $_REQUEST[$name];
		}

		// Get the request hash value
		$hash = strtoupper($hash);

		if ($hash === 'METHOD')
		{
			$hash = strtoupper($_SERVER['REQUEST_METHOD']);
		}

		$previous = array_key_exists($name, $_REQUEST) ? $_REQUEST[$name] : null;

		switch ($hash)
		{
			case 'GET':
				$_GET[$name] = $value;
				$_REQUEST[$name] = $value;
				break;
			case 'POST':
				$_POST[$name] = $value;
				$_REQUEST[$name] = $value;
				break;
			case 'COOKIE':
				$_COOKIE[$name] = $value;
				$_REQUEST[$name] = $value;
				break;
			case 'FILES':
				$_FILES[$name] = $value;
				break;
			case 'ENV':
				$_ENV[$name] = $value;
				break;
			case 'SERVER':
				$_SERVER[$name] = $value;
				break;
		}

		// Clean global request var
		$GLOBALS['_REQUEST'][$name] = [];

		// Mark this variable as 'SET'		
		$GLOBALS['_REQUEST'][$name]['SET.' . $hash] = true;
		$GLOBALS['_REQUEST'][$name]['SET.REQUEST'] = true;	

		return $previous;
	}

	/**
	 * Fetches and returns a request array.
	 *
	 * The default behaviour is fetching variables depending on the
	 * current request method: GET and HEAD will result in returning
	 * $_GET, POST and PUT will result in returning $_POST.
	 *
	 * You can force the source by setting the $hash parameter:
	 *
	 * post     $_POST
	 * get      $_GET
	 * files    $_FILES
	 * cookie   $_COOKIE
	 * env      $_ENV
	 * server   $_SERVER
	 * method   via current $_SERVER['REQUEST_METHOD']
	 * default  $_REQUEST
	 *
	 * @param   string   $hash  to get (POST, GET, FILES, METHOD).
	 * @param   integer  $mask  Filter mask for the variable.
	 *
	 * @return  mixed    Request hash.
	 *
	 */
	public static function get($hash = 'default', $mask = 0)
	{
		$hash = strtoupper($hash);

		if ($hash === 'METHOD')
		{
			$hash = strtoupper($_SERVER['REQUEST_METHOD']);
		}

		switch ($hash)
		{
			case 'GET':
				$input = $_GET;
				break;

			case 'POST':
				$input = $_POST;
				break;

			case 'FILES':
				$input = $_FILES;
				break;

			case 'COOKIE':
				$input = $_COOKIE;
				break;

			case 'ENV':
				$input = &$_ENV;
				break;

			case 'SERVER':
				$input = &$_SERVER;
				break;

			default:
				$input = $_REQUEST;
				break;
		}

		return self::_cleanVar($input, $mask);
	}

	/**
	 * Sets a request variable.
	 *
	 * @param   array    $array      An associative array of key-value pairs.
	 * @param   string   $hash       The request variable to set (POST, GET, FILES, METHOD).
	 * @param   boolean  $overwrite  If true and an existing key is found, the value is overwritten, otherwise it is ignored.
	 *
	 * @return  void
	 */
	public static function set($array, $hash = 'default', $overwrite = true)
	{
		foreach ($array as $key => $value)
		{
			self::setVar($key, $value, $hash, $overwrite);
		}
	}


	/**
	 * Clean up an input variable.
	 *
	 * @param   mixed    $var   The input variable.
	 * @param   integer  $mask  Filter bit mask.
	 *                           1 = no trim: If this flag is cleared and the input is a string, the string will have leading and trailing
	 *                               whitespace trimmed.
	 *                           2 = allow_raw: If set, no more filtering is performed, higher bits are ignored.
	 *                           4 = allow_html: HTML is allowed, but passed through a safe HTML filter first. If set, no more filtering
	 *                               is performed. If no bits other than the 1 bit is set, a strict filter is applied.
	 * @param   string   $type  The variable type
	 *
	 * @return  mixed  Same as $var
	 */
	protected static function _cleanVar($var, $mask = 0, $type = null)
	{
		$mask = (int) $mask;

		// If the no trim flag is not set, trim the variable
		if (!($mask & 1) && is_string($var))
		{
			$var = trim($var);
		}

		// Now we handle input filtering
		if ($mask & 2)
		{
			// If the allow raw flag is set, do not modify the variable
		}
		elseif ($mask & 4)
		{
			$var = self::_clean($var, $type);
		}
		else
		{
			// Since no allow flags were set, we will apply the most strict filter to the variable
			// $tags, $attr, $tag_method, $attr_method, $xss_auto use defaults.
			$var = self::_clean($var, $type);
		}

		return $var;
	}

    protected static function _clean($source, $type = 'string')
	{
		if($type === null)
			$type = 'raw';

		// Handle the type constraint
		switch (strtoupper($type))
		{
			case 'INT':
			case 'INTEGER':
				// Only use the first integer value
				preg_match('/-?[0-9]+/', (string) $source, $matches);
				$result = @ (int) $matches[0];
				break;

			case 'UINT':
				// Only use the first integer value
				preg_match('/-?[0-9]+/', (string) $source, $matches);
				$result = @ abs((int) $matches[0]);
				break;

			case 'FLOAT':
			case 'DOUBLE':
				// Only use the first floating point value
				preg_match('/-?[0-9]+(\.[0-9]+)?/', (string) $source, $matches);
				$result = @ (float) $matches[0];
				break;

			case 'BOOL':
			case 'BOOLEAN':
				$result = (bool) $source;
				break;

			case 'WORD':
				$result = (string) preg_replace('/[^A-Z_]/i', '', $source);
				break;

			case 'ALNUM':
				$result = (string) preg_replace('/[^A-Z0-9]/i', '', $source);
				break;

			case 'CMD':
				$result = (string) preg_replace('/[^A-Z0-9_\.-]/i', '', $source);
				$result = ltrim($result, '.');
				break;

			case 'BASE64':
				$result = (string) preg_replace('/[^A-Z0-9\/+=]/i', '', $source);
				break;

			case 'STRING':
				$result = (string) $source;
				break;

			case 'HTML':
				$result = (string) $source;
				break;

			case 'ARRAY':
				$result = (array) $source;
				break;

			case 'PATH':
				$pattern = '/^[A-Za-z0-9_-]+[A-Za-z0-9_\.-]*([\\\\\/][A-Za-z0-9_-]+[A-Za-z0-9_\.-]*)*$/';
				preg_match($pattern, (string) $source, $matches);
				$result = @ (string) $matches[0];
				break;

			case 'RAW':
				$result = $source;
				break;

			default:
				// Are we dealing with an array?
				if (is_array($source))
				{
					foreach ($source as $key => $value)
					{
						// Filter element for XSS and other 'bad' code etc.
						if (is_string($value))
						{
							$source[$key] = $value;
						}
					}
					$result = $source;
				}
				else
				{
					// Or a string?
					if (is_string($source) && !empty($source))
					{
						// Filter source for XSS and other 'bad' code etc.
						$result = $source;
					}
					else
					{
						// Not an array or string.. return the passed parameter
						$result = $source;
					}
				}
				break;
		}

		return $result;
	}



}
