CREATE TABLE `migrations` (
  `version` int(11) NOT NULL
);

CREATE TABLE `repos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namespace` varchar(255) NOT NULL,
  `repo_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namespace` varchar(255) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
);

CREATE TABLE `project_repos` (
  `project_id` int(11) NOT NULL,
  `repo_id` int(11) NOT NULL
);

CREATE TABLE `milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `completed_at` datetime DEFAULT NULL,
  `rev` varchar(255) DEFAULT NULL,
  `repo_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `number` int(11) NOT NULL DEFAULT 0,
  `type` varchar(20) NOT NULL DEFAULT 'task',
  `title` varchar(255) NOT NULL DEFAULT '',
  `body` text DEFAULT NULL,
  `author` varchar(255) NOT NULL DEFAULT '',
  `duplicate_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

CREATE TABLE `issue_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `author` varchar(255) NOT NULL DEFAULT '',
  `body` text DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `posted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

CREATE TABLE `issue_commits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `rev` varchar(40) NOT NULL,
  `repo_id` int(11) NOT NULL,
  `linked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

CREATE TABLE `issue_refs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `source_issue_id` int(11) NOT NULL,
  `author` varchar(255) NOT NULL DEFAULT '',
  `linked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

CREATE VIEW `issues_with_status` AS
  SELECT i.*, COALESCE(
    (SELECT l.status FROM issue_log l WHERE l.issue_id = i.id AND l.status IS NOT NULL ORDER BY l.id DESC LIMIT 1),
    'open'
  ) AS status
  FROM issues i;
