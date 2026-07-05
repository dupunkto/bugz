<?php
  $log = \core\listIssueLog($issue['id']);
  $commits = \core\listIssueCommits($issue['id']);
  $refs = \core\listIssueRefs($issue['id']);

  $events = array_merge(
    array_map(fn($e) => $e + ['_type' => 'log', '_ts' => $e['posted_at']], $log),
    array_map(fn($e) => $e + ['_type' => 'commit', '_ts' => $e['linked_at']], $commits),
    array_map(fn($e) => $e + ['_type' => 'ref', '_ts' => $e['linked_at']], $refs)
  );
  usort($events, fn($a, $b) => strcmp($a['_ts'], $b['_ts']));
?>

<main class="container issue-detail">
  <h2>
    <span class="title">
      <span class="number dot-<?= esc_attr($issue['status']) ?>" title="<?= esc_attr($issue['status']) ?>">#<?= $issue['number'] ?></span>
      <?= esc_inner($issue['title']) ?>
    </span>
  </h2>

  <article class="comment">
    <header>
      <span class="author"><?= esc_inner(@$issue['author']) ?></span>
      <time><?= esc_inner(@$issue['created_at']) ?></time>
    </header>
    <?php if($issue['body']): ?>
      <?= \core\renderBody($issue['body']) ?>
    <?php else: ?>
      <p class="placeholder">No description provided.</p>
    <?php endif ?>
  </article>

  <?php foreach($events as $event): ?>
    <?php if($event['_type'] === 'ref'): ?>
      <div class="log-event">
        Referenced in
        <a href="/<?= esc_attr($event['source_namespace']) ?>/<?= esc_attr($event['source_project']) ?>/<?= $event['source_number'] ?>">
          <?= esc_inner($event['source_namespace']) ?>/<?= esc_inner($event['source_project']) ?>#<?= $event['source_number'] ?>
        </a>
        by <span class="author"><?= esc_inner($event['author']) ?></span>
        <time><?= esc_inner($event['linked_at']) ?></time>
      </div>
    <?php elseif($event['_type'] === 'commit'): ?>
      <div class="log-event">
        Referenced in
        <a href="<?= esc_attr(\core\commitURL($event['namespace'], $event['repo_name'], $event['rev'])) ?>">
          <?= esc_inner($event['namespace']) ?>/<?= esc_inner($event['repo_name']) ?>@<?= esc_inner(substr($event['rev'], 0, 7)) ?>
        </a>
        by <span class="author"><?= esc_inner($event['author']) ?></span>
        <time><?= esc_inner($event['linked_at']) ?></time>
      </div>
    <?php elseif($event['body'] && $event['status']): ?>
      <div class="log-event">
        <span class="author"><?= esc_inner($event['author']) ?></span> changed status to <span class="dot dot-<?= esc_attr($event['status']) ?>" title="<?= esc_attr($event['status']) ?>"></span><?= esc_inner($event['status']) ?>, with comment:
        <time><?= esc_inner($event['posted_at']) ?></time>
      </div>
      <article class="comment">
        <?= \core\renderBody($event['body']) ?>
      </article>
    <?php elseif($event['body']): ?>
      <article class="comment">
        <header>
          <span class="author"><?= esc_inner($event['author']) ?></span>
          <time><?= esc_inner($event['posted_at']) ?></time>
        </header>
        <?= \core\renderBody($event['body']) ?>
      </article>
    <?php elseif($event['status']): ?>
      <div class="log-event">
        <span class="author"><?= esc_inner($event['author']) ?></span> changed status to <span class="dot dot-<?= esc_attr($event['status']) ?>" title="<?= esc_attr($event['status']) ?>"></span><?= esc_inner($event['status']) ?>
        <time><?= esc_inner($event['posted_at']) ?></time>
      </div>
    <?php endif ?>
  <?php endforeach ?>

  <form class="comment-form" method="post" action="">
    <?php if(!\auth\is_authenticated()): ?>
      <label>
        Author
        <input type="text" name="author" required placeholder="Name &lt;email@example.com&gt;" />
      </label>
    <?php endif ?>
    <label>
      Comment
      <textarea name="body" rows="5" oninput="this.form.querySelector('.captcha')?.style.removeProperty('display')"></textarea>
    </label>
    <?php if(\auth\is_authenticated()): ?>
      <p class="comment-as">commenting as <strong><?= esc_inner(\auth\current_user()) ?></strong></p>    
    <?php endif; ?>
    <div class="form-actions">
      <?php if(\auth\is_authenticated()): ?>
        <div class="close-actions">
          <?php if($issue['status'] === 'open'): ?>
            <button name="status" value="completed">Close as completed</button>
            <button name="status" value="duplicate">Close as duplicate</button>
            <button name="status" value="not-planned">Close as not planned</button>
          <?php else: ?>
            <button name="status" value="open">Reopen</button>
          <?php endif ?>
        </div>
      <?php else: ?>
        <fieldset class="captcha" style="display:none">
          <?php
            $items = [
              ['emoji' => '🚲', 'value' => 'bike-0'],
              ['emoji' => '🚲', 'value' => 'bike-1'],
              ['emoji' => '🚲', 'value' => 'bike-2'],
              ['emoji' => '🚂', 'value' => 'train'],
              ['emoji' => '🚗', 'value' => 'car'],
            ];
            shuffle($items);
          ?>
          <legend>Click all bicycles</legend>
          <?php foreach($items as $item): ?>
            <label>
              <input type="checkbox" name="captcha[]" value="<?= esc_attr($item['value']) ?>">
              <?= $item['emoji'] ?>
            </label>
          <?php endforeach ?>
        </fieldset>
      <?php endif ?>
      <button type="submit">Comment</button>
    </div>
  </form>
</main>
