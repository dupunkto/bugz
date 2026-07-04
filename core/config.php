<?php
// Basic configuration for getting Bugz up-and-running.

define('UNLISTED', true);

define('SITE_TITLE', getenv("SITE_TITLE") ?: "{du}punkto issue tracker");

define('GITZ_URL', rtrim(getenv("GITZ_URL") ?: "https://git.dupunkto.org", "/"));
define('BUGZ_URL', rtrim(getenv("BUGZ_URL") ?: "https://issues.dupunkto.org", "/"));

define('SECRET', getenv("SECRET"));
define('ISSUES_EMAIL', getenv("ISSUES_EMAIL") ?: 'issues@dupunkto.org');

define('NYM_ENDPOINT', rtrim(getenv("NYM_ENDPOINT") ?: 'https://nym.dupunkto.org', '/'));
define('CLIENT_ID', BUGZ_URL);
define('REDIRECT_URI', BUGZ_URL . '/login');

// Disable error logging in production environments
ini_set('display_errors', "0");
ini_set('display_startup_errors', "0");
error_reporting(0);
