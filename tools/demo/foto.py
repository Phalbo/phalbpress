"""Foto del sito dimostrativo: ritaglia ai formati del seed, stampa le misure in un angolo e salva WebP leggeri (+ -960).
uso: python3 tools/demo/foto.py <cartella-foto> [<uscita>]   (default: tools/demo/site/seed-media)
Le foto sono scelte per parola chiave nel nome del file: vedi MAPPA.
"""
import os, sys
from PIL import Image, ImageDraw, ImageFont, ImageOps

SRC = sys.argv[1]
OUT = sys.argv[2] if len(sys.argv) > 2 else os.path.join(os.path.dirname(__file__), 'site', 'seed-media')
FONT = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'

# nome nel seed: (parola chiave nel file, larghezza, altezza, punto da tenere 0-1 orizzontale, verticale)
MAPPA = {
    'hero-1': ('european-town-square', 2400, 1350, .5, .55),
    'hero-2': ('piazza-del-popolo', 2400, 1350, .5, .5),
    'fascia-1': ('group-holding-hands', 2400, 1000, .5, .45),
    'fascia-2': ('rolling-green-hills-and-distant', 2400, 1000, .5, .6),
    'news-1': ('corporate-team-collaborating', 1800, 1200, .5, .5),
    'news-2': ('confident-adult-male-instructor', 1800, 1200, .45, .45),
    'news-3': ('woman-working-on-laptop-at-home-near', 1800, 1200, .55, .5),
    'news-4': ('conference-attendees', 1800, 1200, .5, .5),
    'news-5': ('teamwork-putting-hands', 1800, 1200, .5, .5),
    'news-6': ('red-mug-on-desk', 1800, 1200, .5, .5),
    'storia-1': ('radiant-woman-in-a-red-coat', 1200, 1500, .52, .4),
    'storia-2': ('adult-education-man', 1200, 1500, .42, .45),
    'storia-3': ('woman-in-wheelchair', 1200, 1500, .25, .5),
    'galleria-1': ('office-celebration', 1800, 1200, .5, .5),
    'galleria-2': ('smiling-teacher', 1800, 1200, .55, .5),
    'galleria-3': ('people-walking-on-wooden', 1800, 1200, .5, .6),
    'galleria-4': ('sticky-note', 1200, 1500, .5, .5),
    'galleria-5': ('young-woman-works-on-laptop', 1200, 1500, .55, .5),
    'galleria-6': ('girls-looking-at-smartphone', 1500, 1500, .45, .5),
}


def label(im, text):
    d = ImageDraw.Draw(im, 'RGBA')
    size = max(18, im.width // 55)
    f = ImageFont.truetype(FONT, size)
    l, t, r, b = d.textbbox((0, 0), text, font=f)
    pad = size // 2
    x1, y1 = im.width - pad, im.height - pad
    x0, y0 = x1 - (r - l) - pad * 2, y1 - (b - t) - pad * 2
    d.rectangle([x0, y0, x1, y1], fill=(0, 0, 0, 150))
    d.text((x0 + pad - l, y0 + pad - t), text, font=f, fill=(255, 255, 255, 235))


os.makedirs(OUT, exist_ok=True)
files = os.listdir(SRC)
for name, (key, w, h, fx, fy) in MAPPA.items():
    src = next((f for f in files if key in f), None)
    if not src:
        print('manca una foto per', name, '(' + key + ')'); continue
    im = ImageOps.exif_transpose(Image.open(os.path.join(SRC, src))).convert('RGB')
    im = ImageOps.fit(im, (w, h), Image.LANCZOS, centering=(fx, fy))   # ritaglia e allarga se serve
    label(im, f'{w} × {h}')
    im.save(os.path.join(OUT, name + '.webp'), quality=62, method=6)
    small = im.resize((960, round(h * 960 / w)), Image.LANCZOS)
    small.save(os.path.join(OUT, name + '-960.webp'), quality=62, method=6)
    print(name, '<-', src[:60])
