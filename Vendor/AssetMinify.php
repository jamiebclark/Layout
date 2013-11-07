<?php
require_once('PhpClosure.php');

class AssetMinify {
	public $forceOverwrite = false;
	
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
				if (!empty($minFiles)) {
					$return[] = $this->getDstFile($minFiles, $type);
				}
				$return[] = $file;
				$minFiles = array();
			}
		}
		if (!empty($minFiles)) {
			$return[] = $this->getDstFile($minFiles, $type);
		}	
		return $return;
	}

	public function getCacheDir($type, $forWeb = true, $filename = null) {
		if ($forWeb) {
			//$dir = 'Layout.min/';
			$dir = "Layout./$type-min/";
		} else {
			$dir = APP . 'Plugin' . DS . 'Layout' . DS . 'webroot' . DS . $type . DS . 'min' . DS;
		}
		if (!empty($filename)) {
			$dir .= $filename;
		}
		return $dir;	
	}
	
	//Finds the full path of the cached minified file
	private function getDstFile($files, $type) {
		$dstFilename = $this->getDstPath($files, $type);
		$lastModified = $this->getLastModified($files, $type);
		if ($this->forceOverwrite || !is_file($dstFilename) || filemtime($dstFilename) < $lastModified) {
			$this->buildDstFile($dstFilename, $files, $type);
		}
		return $this->getDstPath($files, $type, true);
	}
	
	//Finds full path of a Cake asset
	private function getPath($file, $type) {
		list($plugin, $file) = pluginSplit($file);
		$root = empty($plugin) ? WWW_ROOT : APP . 'Plugin' . DS . $plugin . DS . 'webroot' . DS;
		return $root . $type . DS . $file . '.' . $type;
	}
	
	//Finds the full path of where the cached file will be stored
	private function getDstPath($files, $type, $forWeb = false) {
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
	private function buildDstFile($filename, $files, $type) {
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
					$content = preg_replace('@/\*.+\*/@s', '', $content);
					
					if (preg_match_all('/@import[^;]+;/', $content, $matches)) {
						foreach ($matches[0] as $match) {
							$fileHeader .= $match;
						}
						$content = str_replace($matches[0], '', $content);
					}
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
}