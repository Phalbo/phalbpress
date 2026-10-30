<header class="head"><h1>Eventi</h1></header>
<section class="panel">
  <form method="post" class="row"><?= Admin::csrfField() ?>
    <label class="field grow">Titolo<input type="text" name="title" required></label>
    <label class="field">Giorno<input type="date" name="day" required></label>
    <button class="btn btn--primary">Aggiungi</button></form>
  <ul class="rows mt"><?php foreach ($rows as $r): ?><li><span><?= e($r['title']) ?></span><span class="muted"><?= e($r['day']) ?></span></li><?php endforeach; ?></ul>
</section>
