<?php if (!empty($revisions)): ?>
  <details class="revs">
    <summary>Versioni precedenti (<?= count($revisions) ?>)</summary>
    <ul class="rows">
      <?php foreach (array_slice($revisions, 0, 15) as $rv): ?>
        <li><span class="small"><?= e(substr($rv['at'], 0, 16)) ?><?= $rv['user_email'] ? ' · ' . e(strtok($rv['user_email'], '@')) : '' ?></span>
          <button class="btn btn--small" name="restore" value="<?= (int)$rv['id'] ?>" type="submit" formnovalidate data-confirm="Ripristinare questa versione? Quella attuale resta tra le versioni precedenti.">Ripristina</button></li>
      <?php endforeach; ?>
    </ul>
  </details>
<?php endif; ?>
