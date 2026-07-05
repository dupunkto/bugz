<header class="epic">
  <div class="container">
    <h1><?= SITE_TITLE ?></h1>
    <nav>
      <span class="user"><?= esc_inner(\auth\current_user()) ?></span>
    </nav>
  </div>
</header>

<main class="container project-new">
  <h2>New project</h2>

  <?php if(isset($form_error)): ?>
    <p class="placeholder"><?= esc_inner($form_error) ?></p>
  <?php endif ?>

  <form class="new-form" method="post" action="">
    <div class="split">
      <label>
        Namespace
        <input type="text" name="namespace" required autofocus value="<?= esc_attr(@$_POST['namespace']) ?>" />
      </label>

      <label>
        Project name
        <input type="text" name="project_name" required value="<?= esc_attr(@$_POST['project_name']) ?>" />
      </label>
    </div>

    <label>
      Description
      <input type="text" name="description" value="<?= esc_attr(@$_POST['description']) ?>" />
    </label>

    <button type="submit">create project</button>
  </form>
</main>
