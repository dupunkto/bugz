<?php
  $on_tasks = @$filter_type == 'task' || ($page == 'issue' && @$issue['type'] == 'task');
  $on_bugs = @$filter_type == 'bug' || ($page == 'issue' && @$issue['type'] == 'bug');
?>
<header>
  <div class="container">
    <h1>
      <a href="/<?= esc_attr($namespace) ?>">~<?= esc_inner($namespace) ?></a>/<?= esc_inner($project_name) ?>
    </h1>
    <nav>
      <a href="/<?= esc_attr($namespace) ?>/<?= esc_attr($project_name) ?>"
         class="<?= $page == 'project' ? 'selected' : '' ?>">overview</a>
      <a href="/<?= esc_attr($namespace) ?>/<?= esc_attr($project_name) ?>/tasks"
         class="<?= $on_tasks ? 'selected' : '' ?>">tasks</a>
      <a href="/<?= esc_attr($namespace) ?>/<?= esc_attr($project_name) ?>/bugs"
         class="<?= $on_bugs ? 'selected' : '' ?>">bugs</a>
      <?php if(\auth\is_authenticated()): ?>
        <span class="user"><?= esc_inner(\auth\current_user()) ?></span>
      <?php else: ?>
        <a class="login" href="/login">login &rarr;</a>
      <?php endif ?>
    </nav>
  </div>
  <div class="line">
    <p class="container description">
      <?= esc_inner(@$project['description']) ?>

      <?php if(\auth\is_authenticated()): ?>
        <a class="new" href="/<?= esc_attr($namespace) ?>/<?= esc_attr($project_name) ?>/bugs/new">new bug</a>
        <a class="new" href="/<?= esc_attr($namespace) ?>/<?= esc_attr($project_name) ?>/tasks/new">new task</a>
      <?php else: ?>
        <a class="new" href="mailto:<?= esc_attr(ISSUES_EMAIL) ?>">new bug</a>
      <?php endif ?>
    </p>
  </div>
</header>
