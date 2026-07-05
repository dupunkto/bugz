<main class="container issue-new">
  <h2>New <?= esc_inner($type) ?></h2>
  <form class="new-form" method="post" action="">
    <input type="hidden" name="type" value="<?= esc_attr($type) ?>" />

    <label>
      Title
      <input type="text" name="title" required autofocus />
    </label>

    <label>
      Description
      <textarea name="body" rows="8"></textarea>
    </label>

    <button type="submit">open <?= esc_inner($type) ?></button>
  </form>
</main>
