<?php
  $recent_issues = array_slice(\core\listIssues($project['id'], 'open'), 0, 3);
  $recent_activity = \core\listRecentlyActiveIssues($project['id']);
  $milestones = \core\listMilestones($project['id'], upcoming: true);
  $repos = \core\listLinkedRepos($project['id']);
?>
<aside class="container summary">
  <section class="issues">
    <h2>
      Open issues
      <small>
        <?= \core\countIssues($project['id'], 'open') ?> open &middot;
        <?= \core\countIssues($project['id'], 'closed') ?> closed
      </small>
    </h2>

    <ul>
      <?php foreach($recent_issues as $issue): ?>
        <li>
          <a href="/<?= esc_attr($namespace) ?>/<?= esc_attr($project_name) ?>/<?= $issue['number'] ?>">
            <span class="title"><span class="number dot-<?= esc_attr($issue['status']) ?>" title="<?= esc_attr($issue['status']) ?>">#<?= $issue['number'] ?></span> <?= esc_inner($issue['title']) ?></span>
            <span class="badges">
              <span class="badge badge-<?= esc_attr($issue['type']) ?>"><?= esc_inner($issue['type']) ?></span>
            </span>
          </a>
        </li>
      <?php endforeach ?>
    </ul>

    <?php if(empty($recent_issues)): ?>
      <p class="placeholder">No open issues.</p>
    <?php endif ?>
  </section>

  <section class="milestones">
    <h2>Milestones</h2>

    <?php if(empty($milestones)): ?>
      <p class="placeholder">No upcoming milestones.</p>
    <?php endif ?>

    <?php foreach($milestones as $ms): ?>
      <h3><?= esc_inner($ms['name']) ?></h3>
      <?php if($ms['description']): ?>
        <p><?= esc_inner($ms['description']) ?></p>
      <?php endif ?>
    <?php endforeach ?>
  </section>

  <section class="repos">
    <h2>Linked repos</h2>

    <?php if(empty($repos)): ?>
      <p class="placeholder">No linked repositories.</p>
    <?php endif ?>

    <?php foreach($repos as $repo): ?>
      <p>
        <a href="<?= esc_attr(\core\repoURL($repo['namespace'], $repo['repo_name'])) ?>">
          ~<?= esc_inner($repo['namespace']) ?>/<?= esc_inner($repo['repo_name']) ?>
        </a>
      </p>
    <?php endforeach ?>
  </section>
  <section class="recent-activity">
    <h2>Recent activity</h2>

    <ul>
      <?php foreach($recent_activity as $issue): ?>
        <li>
          <a href="/<?= esc_attr($namespace) ?>/<?= esc_attr($project_name) ?>/<?= $issue['number'] ?>">
            <span class="title"><span class="number dot-<?= esc_attr($issue['status']) ?>" title="<?= esc_attr($issue['status']) ?>">#<?= $issue['number'] ?></span> <?= esc_inner($issue['title']) ?></span>
            <span class="badges">
              <span class="badge badge-<?= esc_attr($issue['type']) ?>"><?= esc_inner($issue['type']) ?></span>
            </span>
          </a>
        </li>
      <?php endforeach ?>
    </ul>

    <?php if(empty($recent_activity)): ?>
      <p class="placeholder">No recent activity.</p>
    <?php endif ?>
  </section>
</aside>
