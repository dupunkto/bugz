<header class="epic">
  <div class="container">
    <h1><?= SITE_TITLE ?></h1>
    <nav>
      <?php if(\auth\is_authenticated()): ?>
        <span class="user"><?= esc_inner(\auth\current_user()) ?></span>
      <?php else: ?>
        <a href="/login">login &rarr;</a>
      <?php endif ?>
    </nav>
  </div>
</header>

<?php $namespaces = \core\listNamespaces() ?>

<main class="container listing">
  <?php foreach($namespaces as $ns): ?>
    <section>
      <h2><?= esc_inner($ns) ?></h2>
      <ul>
        <?php foreach(\core\listProjects($ns) as $p): ?>
          <li>
            <a href="/<?= esc_attr($p['namespace']) ?>/<?= esc_attr($p['project_name']) ?>">
              <h3><?= esc_inner($p['project_name']) ?></h3>
              <p><?= esc_inner(@$p['description']) ?></p>
            </a>
          </li>
        <?php endforeach ?>
      </ul>
    </section>
  <?php endforeach ?>
</main>

<footer class="container">
  <span><?= count($namespaces) ?> projects</span>
  <span>
    Powered by <a href="//git.dupunkto.org/dupunkto/bugz">Bugz</a>, 
    a <a href="//dupunkto.org">{du}punkto</a> project.
  </span>
</footer>
