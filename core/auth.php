<?php

namespace auth;

session_start() or die("Failed to start session");

function is_authenticated() {
  // The development server can't handle concurrent requests,
  // so the CURL request below will fail. Therefore, I decided
  // to turn authenticated of when running locally.
  return !!@$_SESSION['current_user'] or is_builtin();
}

function current_user() {
  // Comment the following line out to test the logged-out UX.
  if(is_builtin()) return 'Linux Torvalds <linus@dupunkto.org>';

  $user = @$_SESSION['current_user'];
  if(!$user) return null;

  $name = @$user['displayname'];
  $email = @$user['email'];

  return $email ? "{$name} <{$email}>" : $name;
}

function require_authenticated() {
  if(!is_authenticated()) initiate_session();
}

function initiate_session() {
  $redirect = @$_SERVER['REQUEST_URI'] ?: '/';

  $_SESSION['state'] = bin2hex(random_bytes(16));
  $_SESSION['code_verifier'] = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
  $_SESSION['redirect_after'] = $redirect;

  $code_challenge = rtrim(
    strtr(base64_encode(hash('sha256', $_SESSION['code_verifier'], binary: true)), '+/', '-_'),
    '='
  );

  $params = http_build_query([
    'client_id' => CLIENT_ID,
    'redirect_uri' => REDIRECT_URI,
    'state' => $_SESSION['state'],
    'code_challenge' => $code_challenge,
    'code_challenge_method' => 'S256',
  ]);

  header('Location: ' . NYM_ENDPOINT . '?' . $params);
  exit;
}

function handle_callback() {
  $code = @$_GET['code'];
  $state = @$_GET['state'];
  $iss = @$_GET['iss'];
  $me = @$_GET['me'];

  if(!$code || !$state || !$iss) {
    http_response_code(400);
    die("Missing parameters.");
  }

  if($state !== @$_SESSION['state']) {
    http_response_code(401);
    die("State mismatch.");
  }

  if($iss !== NYM_ENDPOINT) {
    http_response_code(401);
    die("Issuer mismatch.");
  }

  $response = \http\post(NYM_ENDPOINT, [
    'grant_type' => 'authorization_code',
    'me' => $me,
    'code' => $code,
    'client_id' => CLIENT_ID,
    'redirect_uri' => REDIRECT_URI,
    'code_verifier' => @$_SESSION['code_verifier'],
  ], ['Accept: application/json']);

  if($response['state'] === 'failed' || $response['status'] >= 400) {
    http_response_code(401);
    die("Token verification failed.");
  }

  $body = json_decode($response['body'], associative: true);

  if(!isset($body['me'], $body['meta'])) {
    http_response_code(401);
    die("Incomplete token response.");
  }

  $redirect = @$_SESSION['redirect_after'] ?: '/';

  unset($_SESSION['state'], $_SESSION['code_verifier'], $_SESSION['redirect_after']);
  session_regenerate_id(true);

  $_SESSION['current_user'] = $body['meta'];

  header('Location: ' . $redirect);
  exit;
}

