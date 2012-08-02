<?php
// Don't remember how many times I wrote this framework from scratch 
// but always found some new metodology, just... enjoy it

// Having a strong error handler its to me the esential,
// one of my ideas was to have all the core in one place
// so i don't need to copy over and over again the same files
// well my case its I have all my apps in one domain, just
// its a big project splited in few modules so each one its
// independent of the rest, but can inherit layouts, stylesheet, 
// js from the default layout

// I need to define some constants
define('WSD_PATH'				, dirname(realpath(__FILE__)));
define('WSD_VER'				, '0.4');
define('T'						, microtime(TRUE));
define('M'						, memory_get_usage());
define('AJAX'					, strtolower(getenv('HTTP_X_REQUESTED_WITH'))==='xmlhttprequest'); # Some people don't trush this var I just want to have it, don't know if I will really use

// If isn't php 5.3 it will not work
if (PHP_VERSION < '5.3.0') die('wsd-php ' . WSD_VER . ' works only with PHP 5.3+, your current version are ' . PHP_VERSION);

// Creating the error handler function
function wsd_error_handler($errno, $errstr, $errfile, $errline)
{
	global $config;
	
	if (!(error_reporting() & $errno)) {
	    // Este código de error no está incluido en error_reporting
	    return;
	}

	switch ($errno) {
		case E_USER_ERROR:
		    $content = 	"<b>USER ERROR</b> [$errno] $errstr<br />\n".
		        		"  Fatal error on line $errline on file $errfile".
					    ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n".
		        		"Aborting...<br />\n";
		    break;
	
		case E_USER_WARNING:
		    $content = 	"<b>WARNING</b> [$errno] $errstr<br />\n".
		        		"  Fatal error on line $errline on file $errfile\n";
		    break;
	
		case E_USER_NOTICE:
		    $content = 	"<b>NOTICE</b> [$errno] $errstr<br />\n".
		        		"  Fatal error on line $errline on file $errfile\n";
		    break;
	
		default:
		    $content = 	"UNKNOWN ERROR: [$errno] $errstr<br />\n".
		        		"  Fatal error on line $errline on file $errfile\n";
		    break;
	}
	
	wsd_mailto($config['SITE_EMAIL'], $config['ADMIN_EMAIL'], '[error-handler] ' . $config['SITE_NAME'], $content);
	
	if ($errno == E_USER_ERROR) exit(1);

	/* No ejecutar el gestor de errores interno de PHP */
	return true;
} // wsd_error_handler()

// As the name reflect its a lazy init, so we preload the core of the framework
function lazyInit() {
	lazyGlob(WSD_PATH); # Get the WSD_PATH folder files, its not recursive
	lazyGlob(WSD_PATH . '/helpers'); # This will be the core helpers
} // lazyInit()

// Loads all php file from the specified directory
function lazyGlob($path = null) {
	if ($path == null) $path = WSD_PATH;
	
	foreach (glob($path . '/*.php') as $class) {
		if (!file_exists($class)) continue;
	    require_once $class;
	}
} // lazyInit()

// Simple mail to function
function wsd_mailto($from, $to, $subject, $content)
{
	if (is_array($to)){
		$err = 0;
		foreach ($to as $_to):
			if (!wsd_mailto($from, $_to, $subject, $content)) {
				++$err;
			}
		endforeach;
		
		if ($err > 0) return false;
		return true;
	} else {
		$headers = "From: $from\nContent-Type: text/html; charset=UTF-8";
		return mail($to, $subject, $content, $headers);
	}
} // wsd_mailto()

// Set the error handler function
set_error_handler('wsd_error_handler');

// Now we load all the stuff
