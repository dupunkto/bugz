<main class="container" style="flex-direction: column; padding-top: 1rem;">
  <div class="issue-filters">
    <a href="?is=open"
       class="<?= $filter_status == 'open' ? 'selected' : '' ?>">
      open (<?= \core\countIssues($project['id'], 'open', $filter_type) ?>)
    </a>
    <a href="?is=closed"
       class="<?= $filter_status == 'closed' ? 'selected' : '' ?>">
      closed (<?= \core\countIssues($project['id'], 'closed', $filter_type) ?>)
    </a>
  </div>

  <ul class="issue-list">
    <?php foreach($issues as $issue): ?>
      <li>
        <a href="/<?= esc_attr($namespace) ?>/<?= esc_attr($project_name) ?>/<?= $issue['number'] ?>">
          <span class="dot dot-<?= esc_attr($issue['status']) ?>" title="<?= esc_attr($issue['status']) ?>"></span>
          <span class="title"><span class="number">#<?= $issue['number'] ?></span> <?= esc_inner($issue['title']) ?></span>
        </a>
      </li>
    <?php endforeach ?>

    <?php if(empty($issues)): ?>
      <p class="placeholder">No <?= esc_inner($filter_type) ?>s.</p>
    <?php endif ?>
  </ul>
</main>
