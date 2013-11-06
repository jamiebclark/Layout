<?php
header("Content-Type: $contentType");
header("X-Content-Type-Options: nosniff");

// No recompile needed, but see if we can send a 304 to the browser.
header("Last-Modified: $lastModified GMT"); 
header("Etag: $etag"); 

if ($isModified) { 
	// Read the cache file and send it to the client.
	echo file_get_contents($filepath);
} else {
	header("HTTP/1.1 304 Not Modified"); 
}
