<?php /* $name, $value */ ?>
<div class="editor" data-editor>
  <div class="editor__bar" role="toolbar" aria-label="Formattazione">
    <button type="button" data-cmd="formatBlock" data-arg="p" title="Paragrafo">¶</button>
    <button type="button" data-cmd="formatBlock" data-arg="h2" title="Titolo">H2</button>
    <button type="button" data-cmd="formatBlock" data-arg="h3" title="Sottotitolo">H3</button>
    <button type="button" data-cmd="bold" title="Grassetto"><b>B</b></button>
    <button type="button" data-cmd="italic" title="Corsivo"><i>I</i></button>
    <button type="button" data-cmd="insertUnorderedList" title="Elenco">• Elenco</button>
    <button type="button" data-cmd="insertOrderedList" title="Elenco numerato">1. Elenco</button>
    <button type="button" data-cmd="formatBlock" data-arg="blockquote" title="Citazione">“ ”</button>
    <button type="button" data-cmd="link" title="Link">Link</button>
    <button type="button" data-cmd="unlink" title="Togli link">Togli link</button>
    <button type="button" data-cmd="image" title="Immagine dalla libreria">Immagine</button>
    <button type="button" data-cmd="gallery" title="Galleria: più immagini dalla libreria, si ingrandiscono al clic">Galleria</button>
    <button type="button" data-cmd="doc" title="Documento (si aggiorna da solo in tutte le lingue)">Documento</button>
    <button type="button" data-cmd="video" title="Video YouTube">Video</button>
    <button type="button" data-cmd="html" class="editor__html" aria-pressed="false">HTML</button>
  </div>
  <div class="editor__area prose" contenteditable="true"><?= $value ?></div>
  <textarea name="<?= e($name) ?>" class="editor__src" hidden><?= e($value) ?></textarea>
</div>
