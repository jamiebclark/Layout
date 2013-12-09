<?php
require_once('PhpClosure.php');

class AssetMinify {
	public $forceOverwrite = false;
	
	const PLUGIN_NAME = 'Layout';
	
	function __construct() {
		if (isset($_GET['clearCache'])) {
			$this->forceOverwrite = true;
			$this->clearCache('-1 week');
		}
	}
	
	public function minify($files, $type = 'css') {
		$return = array();
		$minFiles = array();
		foreach ($files as $file => $config) {
			if (is_numeric($file)) {
				$file = $config;
				$config = array();
			}
			if (is_file($this->getPath($file, $type))) {
				$minFiles[] = $file;
			} else {
				debug($this->getPath($file, $type));
				if (!empty($minFiles)) {
					$return[] = $this->getCacheFile($minFiles, $type);
				}
				$return[] = $file;
				$minFiles = array();
			}
		}
		if (!empty($minFiles)) {
			$return[] = $this->getCacheFile($minFiles, $type);
		}	
		return $return;
	}

	public function getCacheDir($type, $forWeb = true, $filename = null) {
		if ($forWeb) {
			//$dir = 'Layout.min/';
			$dir = "Layout./$type-min/";
		} else {
			$dir = $this->_getPluginDir() . 'webroot' . DS . $type . DS . 'min' . DS;
		}
		if (!empty($filename)) {
			$dir .= $filename;
		}
		return $dir;	
	}
	

	public function clearCache($age = null) {
		$this->_clearCache($age, $this->getCacheDir('js', false));
		$this->_clearCache($age, $this->getCacheDir('css', false));
	}

	/**
	 * Auto-deletes older files in cache folder
	 *
	 * @include int|null $age the expire time
	 * @include string|null $dir the directory where to look. Defaults to root cache directory
	 * @include bool $deleteOnEmpty If directory is empty and set to true, deletes the directory
	 * @return int Remaining file count
	 **/	
	private function _clearCache($age = null, $dir = null, $deleteOnEmpty = false) {
		$cutoff = !empty($age) ? strtotime($age) : true;
		$handle = opendir($dir);
		$fileCount = 0;
		while (false !== ($entry = readdir($handle))) {
			if ($entry == '.' || $entry == '..' || $entry == 'empty') {
				continue;
			}
			$path = $dir . $entry;
			if (is_dir($path)) {
				$fileCount += $this->clearCache($age, $path . DS, true);
			} else {
				if ($cutoff === true || filemtime($path) < $cutoff) {
					unlink($path);
				} else {
					$fileCount++;
				}
			}
		}
		if ($deleteOnEmpty && $fileCount == 0) {
			rmdir($dir);
		}
		closedir($handle);
		return $fileCount;
	}
	
	//Finds the full path of the cached minified file
	private function getCacheFile($files, $type) {
		$cacheFilepath = $this->getCacheFilepath($files, $type);
		$lastModified = $this->getLastModified($files, $type);
		if ($this->forceOverwrite || !is_file($cacheFilepath) || filemtime($cacheFilepath) < $lastModified) {
		//	debug(filemtime($cacheFilepath) . ' :: ' . $lastModified);
			$this->buildCacheFile($cacheFilepath, $files, $type);
		}
		return $this->getCacheFilepath($files, $type, true);
	}
	
	//Finds full path of a Cake asset
	private function getPath($file, $type) {
		$oFile = $file;
		list($plugin, $file) = pluginSplit($oFile);
		if (!empty($plugin) && !preg_match('/^[A-Z]/', $plugin)) {
			$plugin = null;
			$file = $oFile;
		}
		$root = empty($plugin) ? WWW_ROOT : $this->_getPluginDir($plugin) . 'webroot' . DS;
		$filepath = $root . $type . DS . $file;
		if (substr($filepath, -1 * strlen($type)) != $type) {
			$filepath .= ".$type";
		}
		return $filepath;
	}
	
	//Finds the full path of where the cached file will be stored
	private function getCacheFilepath($files, $type, $forWeb = false) {
		return $this->getCacheDir($type, $forWeb, $this->getFilename($files, $type));
	}

	
	//Finds a hashed value for the cached file
	private function getFilename($files, $type) {
		return md5(implode('|', $files)) . '.' . $type;
	}	
	
	//Finds the most recent last time a group of files was modified
	private function getLastModified($files, $type) {
		$lastModified = 0;
		foreach ($files as $file) {
			$path = $this->getPath($file, $type);
			if (is_file($path)) {
				if (($filemtime = filemtime($path)) > $lastModified) {
					$lastModified = $filemtime;
				}
			}
		}
		return $lastModified;
	}
	
	//Creates a cached file including all files
	private function buildCacheFile($filename, $files, $type) {
		$filename = trim($filename);
		if (!($dirname = dirname($filename))) {
			throw new Exception("Invalid directory");
		}
		$file = substr($filename, strlen($dirname) + 1);
		if (!is_dir($dirname)) {
			if (!mkdir($dirname, 0755, true)) {
				throw new Exception("Could not create directory: $dirname");
			}
		}

		if (!($fp = fopen($filename, 'w'))) {
			throw new Exception("Could not open file: $filename");
		}

		if ($type == 'js') {
			$PhpClosure = new PhpClosure();
		}

		$fileHeader = '';
		$fileContent = '';
		foreach ($files as $file) {
			$path = $this->getPath($file, $type);
			if (!empty($PhpClosure)) {
				$PhpClosure->add($path);
			} else {
				if (is_file($path)) {
					$content = file_get_contents($path);

					//Strip comments
					$content = preg_replace('!/\*.*?\*/!s', '', $content);
					
					if (preg_match_all('/@import[^;]+;/', $content, $matches)) {
						foreach ($matches[0] as $match) {
							$fileHeader .= $match;
						}
						$content = str_replace($matches[0], '', $content);
					}
					if (!empty($fileContent)) {
						$fileContent .= "\n";
					}
					$fileContent .= "/*$file*/\n";
					$fileContent .= $content;
				}	
			}
		}
		if (!empty($PhpClosure)) {
			fwrite($fp, $PhpClosure->compile());
		} else {
			fwrite($fp, $fileHeader . $fileContent);
		}
		fclose($fp);
	}
	
	private function _getPluginDir($plugin = null) {
		if (empty($plugin)) {
			$plugin = self::PLUGIN_NAME;
		}
		return APP. 'Plugin' . DS . $plugin . DS;
	}
}