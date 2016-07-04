<?php
use Lib\DefaultCache;

/**
 * Created by PhpStorm.
 * User: peter
 * Date: 6/27/16
 * Time: 9:36 AM
 */
class DefaultCacheTest extends PHPUnit_Framework_TestCase
{

	private $options = [
		'cache' => [
			'cacheDir' => 'test_data/cache/'
		]
	];
	private $testArray = ['a' => '', 'b' => 2, 'c' => 12];

	/**
	 * @var DefaultCache
	 */
	private $cache;

	private $key;

	protected function setUp()
	{
		parent::setUp();
		$this->key = DefaultCache::DEFAULT_PREFIX . '.test.put';
		if (!is_dir('test_data')) {
			mkdir('test_data');
		}

		foreach (scandir('test_data') as $file)
			if (!in_array($file, ['.', '..']));
				//@unlink('test_data/' . $file);


		$this->cache = new DefaultCache();
		$this->cache->init($this->options);
	}

	public function testIsCacheInitializedCorrectly()
	{
		$this->assertTrue(is_dir($this->cache->getCacheDir()), 'Failed to create cache directory');
		$this->assertTrue(is_file($this->cache->getIndexFile()), 'Failed to create cache index file');
	}

	public function testGetIndex()
	{
		$this->assertTrue(is_array($this->cache->getIndex()), 'Index maybe corrupted');
	}

	public function testPutCacheEntryAndCreatesCacheFile()
	{
		$this->cache->push($this->key, json_encode($this->testArray, JSON_PRETTY_PRINT));
		$entry = $this->cache->getIndex()[$this->key];
		$this->assertTrue(is_array($entry), 'Entry not found or malformed');
		$this->assertTrue(file_exists($this->cache->getCacheDir() . $entry['file']), 'Entry file not found');
		return $entry;
	}

	/**
	 * @depends testPutCacheEntryAndCreatesCacheFile
	 */
	public function testGetCacheEntry()
	{
		$entry = $this->cache->get($this->key);
		$this->assertNotNull($entry, 'The result of get entry must not be null');
		$this->assertNotEmpty($entry, 'The result of get entry must not be empty');
		$entry = json_decode($entry, true);
		$this->assertTrue(is_array($entry), 'Entry not found for key: ' . $this->key);
        //repeat the same with a callback
        $entry = $this->cache->get($this->key, null, function($data){
            return json_decode($data, true);
        });
        $this->assertTrue(is_array($entry), 'Get entry with callback failed');

		return $this->cache->getIndex()[$this->key];
	}
	/**
	 * @depends testGetCacheEntry
	 * @param array $entry
	 */
	public function testPopDeletesEntryAndRemovesCacheFile(array $entry){
		$this->cache->pop($this->key);
		$this->assertTrue(empty($this->cache->getIndex()[$this->key]), 'The entry was not removed : ' . $this->key);
		$this->assertFalse(file_exists($this->cache->getCacheDir() . $entry['file']), 'The cache file could not be removed');
	}


	/**
	 * @depends testPopDeletesEntryAndRemovesCacheFile
	 */
	public function testPopWithNullKeyParameterRemovesOldestEntry(){
		$this->cache->push('test.1', json_encode($this->testArray));
		$this->cache->getIndex()['test.1']['created'] = 0;
		$this->cache->push('test.2', json_encode($this->testArray));
		$this->cache->pop();
		$this->assertFalse(isset($this->cache->getIndex()['test.1']), 'The oldest entry was not removed');
	}

	public function testGetSetting(){
		$this->assertTrue($this->cache->getSetting('max_size') === 20);
	}

	public function testMaxSizeAutomaticallyPrunes(){
		for($i = 0; $i < $this->cache->getSetting('max_size') + 5; $i++){
			$this->cache->push("test.$i", json_encode($this->testArray));
		}
		$this->assertTrue(count($this->cache->getIndex()) == $this->cache->getSetting('max_size'), 'Cache was not pruned');
	}

	/**
	 * @depends testPopWithNullKeyParameterRemovesOldestEntry
	 */
	public function testClearPurgesCache(){
		for ($i = 0; $i < 10; $i++){
			$this->cache->push("test.$i", json_encode($this->testArray));
		}
		$oldEntries = $this->cache->getIndex();
		$oldEntriesCopy = $oldEntries;
		$this->cache->clear();
		$this->assertEmpty($this->cache->getIndex(), 'Cache not successfully cleared');
		$aCacheFileStillExists = false;
		foreach ($oldEntriesCopy as $key => $value)
			$aCacheFileStillExists = file_exists($this->cache->getCacheDir() . $value['file']);

		$this->assertFalse($aCacheFileStillExists, 'Cache file(s) not removed');
	}

	public function tearDown()
	{
		parent::tearDown();
		//clear test_data_dir
	}

}
