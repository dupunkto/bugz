<?php
// Basic configuration for getting Bugz up-and-running.

define('MAX_COMMITS', 5);
define('MAX_REPOS', 7);
define('UNLISTED', true);

// Disable error logging in prod environments
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);
