<?php
/**
 * Created by PhpStorm.
 * User: peter
 * Date: 6/23/16
 * Time: 12:24 PM
 */

namespace Lib;


/**
 * Interface Cache
 * <p>All
 */
interface Cache
{

	/**
	 * @param array $options
	 * key => value options for cache options
	 * Default options:
	 *	max_size : 20
	 * 		Maximum number of entries in the cache
	 * 	max_age : 86400
	 * 		Life time of entry in cache
	 * @return mixed
	 */
	public function init(array $options);

	/**
	 * @param $key
	 * @param $value
	 * @return boolean
	 * Returns true if it was successful and false on failure
	 */
	public function push($key, $value);


	/**
	 * @param $key
	 * @param null $default
	 * Returns this if the entry is not found
	 * @param callable $callback. Processes the raw data from cache
	 * @return mixed Cache entry
	 * Cache entry
	 */
	public function get($key, $default = null, callable  $callback = null);

	/**
	 * @param $key
	 * @return null | array Cache entry
	 */
	public function getEntry($key);

	/**
	 * Remove an entry from the cache
	 * @param $key
	 * @return mixed
	 */
	public function pop($key);

	/**
	 * @return mixed
	 * Purge the cache
	 */
	public function clear();
}
