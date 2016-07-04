<?php
/**
 * Created by PhpStorm.
 * User: peter
 * Date: 6/23/16
 * Time: 12:25 PM
 */

namespace Lib;

use Exception;

class DefaultCache implements Cache
{

	const DEFAULT_PREFIX = 'default.cache';

	private $settings = [
		'max_size' => 20,
		'max_age' => 86400,
		'cacheDir' => 'cache/',
	];

	private $indexFile;
	private $index;


	/**
	 * @param array $options
	 * key => value options for cache options
	 * Default options:
	 *    max_size : 20
	 *        Maximum number of entries in the cache
	 *    max_age : 86400
	 *        Life time of entry in cache
	 * @return mixed
	 * @throws \Exception if cacheDir could not be created
	 */
	public function init(array $options)
	{
		if (!empty($options['cache'])) {
			$this->settings = array_merge($this->settings, $options['cache']);
		}
		if (!is_dir($this->settings['cacheDir'])) {
			if (mkdir($this->settings['cacheDir'], 0777, true)) {

			} else {
				throw new \Exception('Failed to create cache directory, check your permissions');
			}
		}

		$this->indexFile = $this->f(self::DEFAULT_PREFIX . '.cache.index');
		if (!is_file($this->indexFile))
			file_put_contents($this->indexFile, '{}');

	}

	/**
	 * @param $key
	 * @param $value
	 * @return bool|void
	 * @throws Exception If $value if not a string
	 */
	public function push($key, $value)
	{
		if (!is_string($value))
			throw new \Exception('Only strings are allowed to maintain integrity');

		$this->index();

		if (count($this->index) >= $this->settings['max_size'] || array_key_exists($key, $this->index)) {
			$this->pop();
		}

		$entry = [
			'file' => self::DEFAULT_PREFIX . '.' . md5($key),
			'created' => microtime(true)
		];

		$this->index[$key] = $entry;
		file_put_contents($this->f($entry['file']), $value);
		$this->updateIndex();
	}

	private function index()
	{
		if (empty($this->index))
			$this->index = json_decode(file_get_contents($this->indexFile), true);
	}

	private function f($file)
	{
		return $this->settings['cacheDir'] . $file;
	}

	/**
	 * @return &array
	 */
	public function &getIndex(){
		$this->index();
		return $this->index;
	}

	public function getSetting($key = null)
	{
		if(isset($this->settings[$key]))
			return $this->settings[$key];

		return null;
	}

	public function getCacheDir()
	{
		return $this->settings['cacheDir'];
	}

	public function getIndexFile()
	{
		return $this->indexFile;
	}

    /**
     * @param $key
     * @param null $default
     * Returns this if the entry is not found
     * @param callable $callback
     * @return mixed Cache entry
     * Cache entry
     */
	public function get($key, $default = null, callable $callback = null)
	{
		$this->index();
		if (!empty($this->index[$key])) {
		    $this->index[$key]['created'] = microtime(true);
            $this->updateIndex();
			$data = file_get_contents($this->f($this->index[$key]['file']));
		}else{
		    $data = $default;
        }

		return $callback !== null ? $callback($data) : $data;
	}


	public function getEntry($key){
		$this->index();
		if(empty($this->index[$key])){
			return null;
		}
		return $this->index[$key];
	}

	/**
	 * Remove an entry from the cache
	 * @param $key . If null remove the oldest entry
	 * @return mixed
	 */
	public function pop($key = null)
	{
		$this->index();
		if (!$key) {
			$created = -1;
			$oldest = [];
			foreach ($this->index as $key => $value) {
				if ($value['created'] < $created || $created === -1) {
					$created = $value['created'];
					$oldest['key'] = $key;
					$oldest['value'] = $value;
				}
			}

			if (!empty($oldest)) {
				unlink($this->f($oldest['value']['file']));
				unset($this->index[$oldest['key']]);
			}
		} else {
			if (isset($this->index[$key])) {
				unlink($this->f($this->index[$key]['file']));
				unset($this->index[$key]);
			}
		}
		$this->updateIndex();
	}

	/**
	 * @return mixed
	 * Purge the cache
	 */
	public function clear()
	{
		$this->index();
		$keys = array_keys($this->index);
		for ($i = count($keys) - 1; $i >= 0; $i--){
			unlink($this->f($this->index[$keys[$i]]['file']));
			unset($this->index[$keys[$i]]);
		}
		$this->updateIndex();
	}

	private function updateIndex()
	{
		file_put_contents($this->indexFile, !empty($this->index) ? json_encode($this->index) : '{}', LOCK_EX);
	}
}
