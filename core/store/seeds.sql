INSERT INTO repos (namespace, repo_name) VALUES ('dupunkto', 'gitz');
INSERT INTO repos (namespace, repo_name) VALUES ('dupunkto', 'bugz');

INSERT INTO projects (namespace, project_name, description) VALUES ('dupunkto', 'gitz', 'Awesome git frontend written in PHP.');
INSERT INTO projects (namespace, project_name, description) VALUES ('dupunkto', 'bugz', 'Awesome issue tracker written in PHP.');

INSERT INTO project_repos (project_id, repo_id) VALUES (1, 1);
INSERT INTO project_repos (project_id, repo_id) VALUES (2, 2);

-- dupunkto/gitz issues
INSERT INTO issues (project_id, number, type, title, body, author) VALUES
  (1, 1, 'task', 'Add syntax highlighting for common languages', 'Would be nice to support at least PHP, JS, Python, and shell in code blocks.', 'Robin <robin@dupunkto.org>'),
  (1, 2, 'bug',  'Clone URL wrong for repos with dots in the name', 'Repos like "my.project" produce a broken clone URL. The dot gets URL-encoded somewhere it shouldn''t.', 'Robin <robin@dupunkto.org>'),
  (1, 3, 'task', 'Show branch list on repo overview', '', 'Robin <robin@dupunkto.org>'),
  (1, 4, 'bug',  'Empty repos show a 500 instead of a placeholder', 'HEAD fails when there are no commits yet.', 'Robin <robin@dupunkto.org>');

-- commit references on gitz issues
INSERT INTO issue_commits (issue_id, author, rev, repo_id, linked_at) VALUES
  (2, 'Robin <robin@dupunkto.org>', 'a3f1c2e9b4d78601f5e3a2c1b0d9e8f7a6b5c4d3', 1, '2026-06-10 11:22:00'),
  (2, 'Robin <robin@dupunkto.org>', 'f7e2d1c0b9a8f7e6d5c4b3a2f1e0d9c8b7a6f5e4', 1, '2026-06-11 09:05:00');

-- close issue 2 (bug fixed) and issue 4 (duplicate of 2), after the commits
INSERT INTO issue_log (issue_id, author, status, posted_at) VALUES
  (2, 'Robin <robin@dupunkto.org>', 'completed', '2026-06-11 09:10:00'),
  (4, 'Robin <robin@dupunkto.org>', 'duplicate', '2026-06-01 14:00:00');

-- a commit referencing a bugz issue
INSERT INTO issue_commits (issue_id, author, rev, repo_id, linked_at) VALUES
  (5, 'Robin <robin@dupunkto.org>', 'c2a1b0f9e8d7c6b5a4f3e2d1c0b9a8f7e6d5c4b3', 2, '2026-06-20 16:45:00');

-- issue cross-references (bugz#1 body references gitz#1; bugz#3 references bugz#1)
INSERT INTO issue_refs (issue_id, source_issue_id, author, linked_at) VALUES
  (1, 5, 'Robin <robin@dupunkto.org>', '2026-06-15 10:00:00'),
  (5, 7, 'Robin <robin@dupunkto.org>', '2026-06-22 09:30:00');

-- dupunkto/bugz issues
INSERT INTO issues (project_id, number, type, title, body, author) VALUES
  (2, 1, 'task', 'Markdown support in issue bodies', 'Right now bodies are plain text. At least support bold, italic, inline code and fenced code blocks.', 'Robin <robin@dupunkto.org>'),
  (2, 2, 'task', 'Email notifications on new issues', 'Send an email to ISSUES_EMAIL whenever a new issue is filed.', 'Robin <robin@dupunkto.org>'),
  (2, 3, 'bug',  'Status dot missing on project summary page', 'The summary shows issue rows without the status dot that the issue list has.', 'Robin <robin@dupunkto.org>');
