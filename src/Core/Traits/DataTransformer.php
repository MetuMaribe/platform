<?php

/**
 * Ushahidi Data Transformer Trait
 *
 * Gives objects a new `transform($data, $definition)` method, which can be
 * used to ensure data type consistency.
 *
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi\Platform
 * @copyright  2014 Ushahidi
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License Version 3 (AGPL3)
 */

namespace Ushahidi\Core\Traits;

trait DataTransformer
{
	/**
	 * Transform a string into an email, removing all characters that cannot
	 * exist in an email address.
	 *
	 * @return String $value
	 * @return String
	 */
	protected static function transformEmail($value)
	{
		return filter_var($value, FILTER_SANITIZE_EMAIL);
	}

	/**
	 * Transforms a JSON string to native type. Objects will be represented
	 * with associative arrays.
	 *
	 * @param  String $value
	 * @return Mixed
	 */
	protected static function transformJson($value)
	{
		if (!is_string($value)) {
			return $value;
		}
		return json_decode($value, true);
	}

	/**
	 * Transform a string to a slug, replacing non-alphanumeric characters
	 * with dashes.
	 *
	 * @param  String $value
	 * @return String
	 */
	protected static function transformSlug($value)
	{
		// Anything not a letter or number is replaced with a single space
		$value = preg_replace('/[^\pL\PN-]++/', ' ', $value);

		// ... make it lowercase
		$value = strtolower($value);

		// ... and replace spaces with hypens
		return str_replace(' ', '-', $value);
	}

	/**
	 * Transform a string into a URL, removing all characters that cannot
	 * exist in a URL address.
	 *
	 * @return String $value
	 * @return String
	 */
	protected static function transformUrl($value)
	{
		return filter_var($value, FILTER_SANITIZE_URL);
	}

	/**
	 * Transforms a date(time) string to a UNIX timestamp.
	 *
	 * @param  String $value
	 * @return Integer
	 */
	protected static function transformTimestamp($value)
	{
		// Convert a date string to a timestamp
		return strtotime($value);
	}

	/**
	 * Get the custom transformer name for a type, if it exists.
	 *
	 * Custom transform types are denoted by prepending the type with a star:
	 *
	 *    'foo' => '*custom',
	 *
	 * This example would call `static::transformCustom` on the `foo` value.
	 *
	 * @param  String $type
	 * @return Boolean
	 */
	protected function getCustomTransformer($type)
	{
		if ('*' === $type[0]) {
			return 'transform' . ucfirst(substr($type, 1));
		}
	}

	/**
	 * Transform an array of data, setting correct types to ensure consistency.
	 *
	 * NOTE: Unless an anonymous function is used, null values in the data will
	 * be ignored! Any definition that uses a closure will always be executed.
	 *
	 * @param  Array $data
	 * @return Array
	 */
	protected function transform(Array $data)
	{
		$definition = $this->getDefinition();

		foreach ($data as $key => $val) {
			if (!isset($definition[$key])) {
				continue;
			}

			if ($definition[$key] instanceof \Closure) {
				// Closures are always executed, regardless of value type.
				$data[$key] = $definition[$key]($val);
			} elseif (is_array($val) && is_array($definition[$key])) {
				// Arrays can be recursively transformed.
				$data[$key] = $this->transform($data[$key], $definition[$key]);
			} elseif (null !== $val) {
				if ($func = $this->getCustomTransformer($definition[$key])) {
					// Use a custom transformer for this type.
					$data[$key] = static::$func($data[$key]);
				} else {
					// Cast the value to the specified type.
					settype($data[$key], $definition[$key]);
				}
			}
		}

		return $data;
	}

	/**
	 * Return the transform definition for this object:
	 *
	 *     return [
	 *         'id'       => 'int',
	 *         'username' => 'string',
	 *         'role'     => 'string',
	 *         'email'    => function($val) { return filter_val($val, FILTER_SANITIZE_EMAIL); }
	 *     ];
	 *
	 * @return Array
	 */
	abstract protected function getDefinition();
}
