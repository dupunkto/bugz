<?php
// Public rendering engine.

require_once __DIR__ . "/core/core.php";

$alnum_pattern = '([a-zA-Z0-9_\-\.]+)';
$ns_pattern = "^/~?{$alnum_pattern}";

switch (true) {
  case $path == '/':
    $page = 'listing';
    break;

  case $path == '/robots.txt' and UNLISTED:
    header("Content-Type: text/plain");
    echo "User-agent: *\n";
    echo "Disallow: /\n";
    exit;

  case route("^/login$"):
    if(\auth\is_authenticated()) { header("Location: /"); exit; }
    isset($_GET['code']) ? \auth\handle_callback() : \auth\initiate_session();
    exit;

  case route("^/api/issue$") && $method == 'GET':
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    $project = \core\getProject(@$_GET['repo'], @$_GET['project']);
    if(!$project) { http_response_code(404); echo '{"error":"not found"}'; exit; }

    $issue = \core\getIssue($project['id'], (int)@$_GET['number']);
    if(!$issue) { http_response_code(404); echo '{"error":"not found"}'; exit; }

    echo json_encode(['status' => $issue['status']]);
    exit;

  case route("^/api/commit$") && $method == 'POST':
    if(!SECRET || @$_SERVER['HTTP_AUTHORIZATION'] != 'Bearer ' . SECRET) {
      http_response_code(401);
      echo "Unauthorized.";
      exit;
    }

    $repo = \core\getRepo(@$_POST['namespace'], @$_POST['repo'])
      ?: \core\createRepo(@$_POST['namespace'], @$_POST['repo']);

    foreach(\core\parseActionRefs(@$_POST['message']) as $ref) {
      $project = \core\getProject($ref['namespace'], $ref['project']);
      if(!$project) continue;

      $issue = \core\getIssue($project['id'], $ref['number']);
      if(!$issue) continue;

      $status = ($ref['status'] && $issue['status'] !== $ref['status']) ? $ref['status'] : null;
      \store\exec_query(
        'INSERT INTO issue_commits (issue_id, author, rev, repo_id, status) VALUES (?, ?, ?, ?, ?)',
        [$issue['id'], @$_POST['author'], @$_POST['rev'], $repo['id'], $status]
      ) or die("Failed to link commit.");
    }

    exit;

  case route("^/api/project$") && $method == 'POST':
    if (!SECRET || @$_SERVER['HTTP_AUTHORIZATION'] != 'Bearer ' . SECRET) {
      http_response_code(401);
      echo "Unauthorized.";
      exit;
    }

    $namespace = trim(@$_POST['namespace']);
    $project_name = trim(@$_POST['project_name']);
    $description = trim(@$_POST['description']) ?: null;

    if (!$namespace || !$project_name) {
      http_response_code(400);
      echo "Missing namespace or project_name.";
      exit;
    }

    if (\core\getProject($namespace, $project_name)) {
      http_response_code(409);
      echo "Project already exists.";
      exit;
    }

    \core\createProject($namespace, $project_name, $description);
    http_response_code(201);
    echo BUGZ_URL . "/{$namespace}/{$project_name}\n";
    exit;

  case route("^/new$"):
    \auth\require_authenticated();

    if($method == 'POST') {
      $namespace = trim(@$_POST['namespace']);
      $project_name = trim(@$_POST['project_name']);
      $description = trim(@$_POST['description']) ?: null;

      if($namespace && $project_name) {
        if(\core\getProject($namespace, $project_name)) {
          $form_error = "A project with that name already exists in that namespace.";
        } else {
          \core\createProject($namespace, $project_name, $description);
          header("Location: /{$namespace}/{$project_name}");
          exit;
        }
      }
    }

    $page = 'new-project';
    break;

  // Redirect bare namespace URLs to home
  case route("{$ns_pattern}/?$"):
    header("Location: /");
    exit;

  case scope("{$ns_pattern}/{$alnum_pattern}"):
    $namespace = $params[1];
    $project_name = $params[2];

    if($project = \core\getProject($namespace, $project_name)) {
      switch (true) {
        case route("^/tasks/new$"):
          \auth\require_authenticated();

          if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $title = trim(@$_POST['title']);
            $body = trim(@$_POST['body']);

            if($title) {
              $issue = \core\createIssue($project['id'], \auth\current_user(), 'task', $title, $body);
              \core\createIssueRefs($issue['id'], \auth\current_user(), $body);
              header("Location: /{$namespace}/{$project_name}/{$issue['number']}");
              exit;
            }
          }

          $type = 'task';
          $page = 'new-issue';
          break;

        case route("^/bugs/new$"):
          \auth\require_authenticated();

          if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $title = trim(@$_POST['title']);
            $body = trim(@$_POST['body']);

            if($title) {
              $issue = \core\createIssue($project['id'], \auth\current_user(), 'bug', $title, $body);
              \core\createIssueRefs($issue['id'], \auth\current_user(), $body);
              header("Location: /{$namespace}/{$project_name}/{$issue['number']}");
              exit;
            }
          }

          $type = 'bug';
          $page = 'new-issue';
          break;

        case route("^/tasks/?$"):
          $filter_status = in_array(@$_GET['is'], ['open', 'closed']) ? $_GET['is'] : 'open';
          $filter_type = 'task';
          $issues = \core\listIssues($project['id'], $filter_status, $filter_type);
          $page = 'issues';
          break;

        case route("^/bugs/?$"):
          $filter_status = in_array(@$_GET['is'], ['open', 'closed']) ? $_GET['is'] : 'open';
          $filter_type = 'bug';
          $issues = \core\listIssues($project['id'], $filter_status, $filter_type);
          $page = 'issues';
          break;

        case route("^/(\d+)$"):
          $issue_number = (int)$params[1];
          $issue = \core\getIssue($project['id'], $issue_number);

          if(!$issue) { $page = '404'; break; }

          if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $author = \auth\is_authenticated()
              ? \auth\current_user()
              : trim(@$_POST['author']);
            $body = trim(@$_POST['body']) ?: null;
            $status = null;

            if(\auth\is_authenticated() && isset($_POST['status'])) {
              $valid = array_merge(['open'], \core\CLOSED_STATUSES);
              $status = in_array($_POST['status'], $valid) ? $_POST['status'] : null;
            }

            $captcha = array_unique($_POST['captcha'] ?? []); sort($captcha);
            $ok = \auth\is_authenticated() || $captcha == ['bike-0', 'bike-1', 'bike-2'];
            
            if($author && ($body || $status) && $ok) {
              \core\addLogEntry($issue['id'], $author, $body, $status);
              if($body) \core\createIssueRefs($issue['id'], $author, $body);
            }

            header("Location: /{$namespace}/{$project_name}/{$issue_number}");
            exit;
          }

          $page = 'issue';
          break;

        case route("^/?$"):
          $page = 'project';
          break;
      }

      if(isset($page)) break;
    }

  default:
    http_response_code(404);
    $page = '404';
    break;
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title><?= SITE_TITLE ?></title>
    <?php if(UNLISTED): ?>
      <meta name="robots" content="noindex, nofollow" />
    <?php endif ?>
    <style>
      <?php include __DIR__ . "/partials/main.css" ?>
    </style>
  </head>
  <body>
    <?php if(isset($project)) include __DIR__ . "/partials/header.php" ?>
    <?php include __DIR__ . "/partials/{$page}.php" ?>
  </body>
</html>
