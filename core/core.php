<?php

namespace core;

define('STORE_VERSION', 0);

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/router.php";
require_once __DIR__ . "/neuro.php";
require_once __DIR__ . "/store.php";
require_once __DIR__ . "/auth.php";

// Projects

function listNamespaces() {
  $rows = \store\all('SELECT DISTINCT namespace FROM projects ORDER BY namespace');
  return array_map(fn($r) => $r['namespace'], $rows ?: []);
}

function listProjects($namespace = null) {
  if($namespace) {
    return \store\all('SELECT * FROM projects WHERE namespace = ? ORDER BY project_name', [$namespace]) ?: [];
  }
  return \store\all('SELECT * FROM projects ORDER BY namespace, project_name') ?: [];
}

function getProject($namespace, $name) {
  return \store\one('SELECT * FROM projects WHERE namespace = ? AND project_name = ?', [$namespace, $name]);
}

function createProject($namespace, $project_name, $description = null) {
  \store\exec_query(
    'INSERT INTO projects (namespace, project_name, description) VALUES (?, ?, ?)',
    [$namespace, $project_name, $description]
  ) or die("Failed to create project.");
  return getProject($namespace, $project_name);
}

// Repos

function listLinkedRepos($project_id) {
  return \store\all(
    'SELECT r.* FROM repos r JOIN project_repos pr ON pr.repo_id = r.id WHERE pr.project_id = ?',
    [$project_id]
  ) ?: [];
}

function getRepo($namespace, $repo_name) {
  return \store\one('SELECT * FROM repos WHERE namespace = ? AND repo_name = ?', [$namespace, $repo_name]);
}

// Issues

const CLOSED_STATUSES = ['completed', 'duplicate', 'not-planned'];

function listIssues($project_id, $status = null, $type = null) {
  $sql = 'SELECT * FROM issues_with_status WHERE project_id = ?';
  $params = [$project_id];

  if($status === 'open') {
    $sql .= " AND status = 'open'";
  } elseif($status === 'closed') {
    $sql .= " AND status != 'open'";
  }

  if($type) { $sql .= ' AND type = ?'; $params[] = $type; }

  $sql .= ' ORDER BY number DESC';
  return \store\all($sql, $params) ?: [];
}

function countIssues($project_id, $status, $type = null) {
  $type_clause = $type ? ' AND type = ?' : '';
  $type_param = $type ? [$type] : [];

  if($status === 'closed') {
    $row = \store\one(
      "SELECT COUNT(*) AS n FROM issues_with_status WHERE project_id = ? AND status != 'open'" . $type_clause,
      array_merge([$project_id], $type_param)
    );
  } else {
    $row = \store\one(
      'SELECT COUNT(*) AS n FROM issues_with_status WHERE project_id = ? AND status = ?' . $type_clause,
      array_merge([$project_id, $status], $type_param)
    );
  }
  return (int)($row['n'] ?? 0);
}

function listRecentlyActiveIssues($project_id, $limit = 5) {
  return \store\all(
    'SELECT i.*
     FROM issues_with_status i
     JOIN (
       SELECT issue_id, MAX(ts) AS last_activity
       FROM (
         SELECT issue_id, posted_at AS ts FROM issue_log
         UNION ALL
         SELECT issue_id, linked_at AS ts FROM issue_commits
         UNION ALL
         SELECT issue_id, linked_at AS ts FROM issue_refs
       ) all_activity
       GROUP BY issue_id
     ) a ON a.issue_id = i.id
     WHERE i.project_id = ?
     ORDER BY a.last_activity DESC
     LIMIT ?',
    [$project_id, $limit]
  ) ?: [];
}

function getIssue($project_id, $number) {
  return \store\one(
    'SELECT * FROM issues_with_status WHERE project_id = ? AND number = ?',
    [$project_id, $number]
  );
}

function listIssueLog($issue_id) {
  return \store\all(
    'SELECT * FROM issue_log WHERE issue_id = ? ORDER BY posted_at',
    [$issue_id]
  ) ?: [];
}

function listIssueCommits($issue_id) {
  return \store\all(
    'SELECT ic.*, r.namespace, r.repo_name
     FROM issue_commits ic
     JOIN repos r ON r.id = ic.repo_id
     WHERE ic.issue_id = ?
     ORDER BY ic.linked_at',
    [$issue_id]
  ) ?: [];
}

function listIssueRefs($issue_id) {
  return \store\all(
    'SELECT ir.*, i.number AS source_number, p.namespace AS source_namespace, p.project_name AS source_project
     FROM issue_refs ir
     JOIN issues i ON i.id = ir.source_issue_id
     JOIN projects p ON p.id = i.project_id
     WHERE ir.issue_id = ?
     ORDER BY ir.linked_at',
    [$issue_id]
  ) ?: [];
}

function nextIssueNumber($project_id) {
  $row = \store\one('SELECT MAX(number) AS n FROM issues WHERE project_id = ?', [$project_id]);
  return (int)($row['n'] ?? 0) + 1;
}

function createIssue($project_id, $author, $type, $title, $body) {
  $number = nextIssueNumber($project_id);
  \store\exec_query(
    'INSERT INTO issues (project_id, number, author, type, title, body) VALUES (?, ?, ?, ?, ?, ?)',
    [$project_id, $number, $author, $type, $title, $body]
  ) or die("Failed to create issue.");
  return getIssue($project_id, $number);
}

function createIssueRefs($source_issue_id, $author, $body) {
  if(!$body) return;
  foreach(parseRefs($body) as $ref) {
    $project = getProject($ref['namespace'], $ref['project']);
    if(!$project) continue;
    $issue = getIssue($project['id'], $ref['number']);
    if(!$issue || $issue['id'] == $source_issue_id) continue;
    \store\exec_query(
      'INSERT INTO issue_refs (issue_id, source_issue_id, author) VALUES (?, ?, ?)',
      [$issue['id'], $source_issue_id, $author]
    ) or die("Failed to create issue ref.");
  }
}

function addLogEntry($issue_id, $author, $body, $status = null) {
  \store\exec_query(
    'INSERT INTO issue_log (issue_id, author, body, status) VALUES (?, ?, ?, ?)',
    [$issue_id, $author, $body, $status]
  ) or die("Failed to add log entry.");
}

// Milestones

function listMilestones($project_id, $upcoming = false) {
  $sql = 'SELECT * FROM milestones WHERE project_id = ?';
  if($upcoming) $sql .= ' AND completed_at IS NULL';
  $sql .= ' ORDER BY name';
  return \store\all($sql, [$project_id]) ?: [];
}

// Hook

function parseRefs($message) {
  $refs = [];
  preg_match_all(
    '/~?([a-zA-Z0-9_\-\.]+)\/([a-zA-Z0-9_\-\.]+)#(\d+)/',
    $message, $matches, PREG_SET_ORDER
  );
  foreach($matches as $m) {
    $refs[] = ['namespace' => $m[1], 'project' => $m[2], 'number' => (int)$m[3]];
  }
  return $refs;
}

// Rendering

function repoURL($namespace, $repo) {
  return GITZ_URL . '/~' . rawurlencode($namespace) . '/' . rawurlencode($repo);
}

function commitURL($namespace, $repo, $hash) {
  return repoURL($namespace, $repo) . '/commit/' . $hash;
}

function renderBody($text) {
  $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
    'html_input' => 'escape',
    'allow_unsafe_links' => false,
  ]);

  $html = $converter->convert($text)->getContent();

  // ~ns/repo@hash -> commit link
  $html = preg_replace_callback(
    '/~?([a-zA-Z0-9_\-\.]+)\/([a-zA-Z0-9_\-\.]+)@([0-9a-f]{7,40})\b/',
    function($m) {
      $url = esc_attr(commitURL($m[1], $m[2], $m[3]));
      $label = esc_inner($m[1] . '/' . $m[2] . '@' . substr($m[3], 0, 7));
      return '<a href="' . $url . '">' . $label . '</a>';
    },
    $html
  );

  // ~ns/project#N -> status dot + issue link
  $html = preg_replace_callback(
    '/~?([a-zA-Z0-9_\-\.]+)\/([a-zA-Z0-9_\-\.]+)#(\d+)/',
    function($m) {
      $url = esc_attr('/' . $m[1] . '/' . $m[2] . '/' . $m[3]);
      $label = esc_inner($m[1] . '/' . $m[2] . '#' . $m[3]);
      $dot = '';
      $project = getProject($m[1], $m[2]);
      if($project) {
        $issue = getIssue($project['id'], (int)$m[3]);
        if($issue) {
          $s = esc_attr($issue['status']);
          $dot = '<span class="dot dot-' . $s . '" title="' . $s . '"></span>';
        }
      }
      return $dot . '<a href="' . $url . '">' . $label . '</a>';
    },
    $html
  );

  return $html;
}
