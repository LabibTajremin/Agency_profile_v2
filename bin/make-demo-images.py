#!/usr/bin/env python3
"""
Generate the demo images shipped in plugin/qe-edu-core/demo/images.

Every image is original abstract artwork drawn here (no photos, no third-party
logos), so the demo is free to redistribute. Deterministic: the same key
always draws the same picture. Run: python3 bin/make-demo-images.py
"""
import math
import os
import random
import sys

from PIL import Image, ImageDraw, ImageFilter, ImageFont

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, 'plugin', 'qe-edu-core', 'demo', 'images')
FONT_BOLD = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
FONT = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'

NAVY = (20, 3, 66)
INDIGO = (54, 61, 151)
VIOLET = (100, 64, 251)
CRIMSON = (164, 30, 52)

PALETTES = [
    [(20, 3, 66), (54, 61, 151), (100, 64, 251), (237, 237, 237)],
    [(14, 124, 134), (0, 90, 148), (20, 3, 66), (226, 239, 240)],
    [(164, 30, 52), (226, 87, 76), (232, 155, 18), (250, 238, 226)],
    [(15, 135, 93), (14, 124, 134), (184, 145, 58), (233, 244, 238)],
    [(194, 71, 122), (100, 64, 251), (54, 61, 151), (246, 234, 241)],
    [(47, 53, 66), (0, 90, 148), (184, 145, 58), (236, 238, 241)],
]


def rng(key):
    return random.Random(sum((i + 1) * ord(c) for i, c in enumerate(key)))


def gradient(size, top, bottom):
    w, h = size
    img = Image.new('RGB', size, top)
    draw = ImageDraw.Draw(img)
    for y in range(h):
        t = y / max(1, h - 1)
        draw.line([(0, y), (w, y)], fill=tuple(int(top[i] + (bottom[i] - top[i]) * t) for i in range(3)))
    return img


def art(key, size, palette=None):
    """Soft layered shapes over a gradient."""
    r = rng(key)
    pal = palette or PALETTES[r.randrange(len(PALETTES))]
    w, h = size
    img = gradient(size, pal[0], pal[1]).convert('RGBA')
    layer = Image.new('RGBA', size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    for _ in range(7):
        color = pal[r.randrange(len(pal))]
        rad = r.randint(int(min(w, h) * 0.15), int(min(w, h) * 0.6))
        cx, cy = r.randint(-rad // 2, w), r.randint(-rad // 2, h)
        draw.ellipse([cx - rad, cy - rad, cx + rad, cy + rad], fill=color + (r.randint(70, 150),))
    layer = layer.filter(ImageFilter.GaussianBlur(min(w, h) // 14))
    img = Image.alpha_composite(img, layer)
    lines = Image.new('RGBA', size, (0, 0, 0, 0))
    ld = ImageDraw.Draw(lines)
    for i in range(5):
        y = r.randint(0, h)
        amp = r.randint(10, h // 6)
        pts = [(x, y + amp * math.sin(x / (w / (2 + i)) + i)) for x in range(0, w + 10, 10)]
        ld.line(pts, fill=(255, 255, 255, 60), width=max(2, w // 300))
    img = Image.alpha_composite(img, lines)
    return img.convert('RGB')


def save_jpg(img, name):
    img.save(os.path.join(OUT, name + '.jpg'), 'JPEG', quality=70, optimize=True, progressive=True)


def font(size, bold=True):
    return ImageFont.truetype(FONT_BOLD if bold else FONT, size)


def wordmark(name, mark, color, size=(400, 160)):
    """Fictional partner logo: a simple mark plus "Name|Subtitle", on white."""
    img = Image.new('RGBA', size, (255, 255, 255, 255))
    d = ImageDraw.Draw(img)
    w, h = size
    s = int(h * 0.42)
    x0, y0 = 18, (h - s) // 2
    if mark == 'shield':
        d.polygon([(x0, y0), (x0 + s, y0), (x0 + s, y0 + s * 0.6), (x0 + s / 2, y0 + s), (x0, y0 + s * 0.6)], fill=color)
    elif mark == 'circle':
        d.ellipse([x0, y0, x0 + s, y0 + s], fill=color)
    elif mark == 'book':
        d.rectangle([x0, y0 + s * 0.15, x0 + s * 0.48, y0 + s], fill=color)
        d.rectangle([x0 + s * 0.52, y0 + s * 0.15, x0 + s, y0 + s], fill=color)
    else:
        d.polygon([(x0 + s / 2, y0), (x0 + s, y0 + s), (x0, y0 + s)], fill=color)
    first, _, second = name.partition('|')
    f1, f2 = font(24), font(16, False)
    d.text((x0 + s + 16, h / 2 - 26), first.strip(), font=f1, fill=(30, 30, 40))
    if second:
        d.text((x0 + s + 16, h / 2 + 4), second.strip().upper(), font=f2, fill=(90, 90, 100))
    return img


def avatar(initials, color, size=400):
    img = gradient((size, size), color, tuple(max(0, c - 60) for c in color))
    d = ImageDraw.Draw(img)
    f = font(int(size * 0.34))
    box = d.textbbox((0, 0), initials, font=f)
    d.text(((size - (box[2] - box[0])) / 2 - box[0], (size - (box[3] - box[1])) / 2 - box[1]), initials, font=f, fill=(255, 255, 255))
    return img


def logo(color, sub_color, name):
    """Stacked wordmark (about 5:1), legible at the logo widget's default width."""
    img = Image.new('RGBA', (820, 180), (0, 0, 0, 0))
    d = ImageDraw.Draw(img)
    d.rounded_rectangle([0, 30, 120, 150], radius=26, fill=CRIMSON)
    d.polygon([(28, 120), (60, 58), (92, 120)], fill=(255, 255, 255))
    d.text((148, 34), 'Brightway Education', font=font(50), fill=color)
    d.text((150, 104), 'G R O U P', font=font(36, False), fill=sub_color)
    img = img.crop(img.getbbox())
    img.save(os.path.join(OUT, name + '.png'), optimize=True)


def pdf():
    pages = []
    for i, title in enumerate(['Brightway Education Group', 'Our services', 'Global network']):
        page = art('pdf-%d' % i, (1240, 1754), PALETTES[0])
        d = ImageDraw.Draw(page)
        d.rectangle([80, 1300, 1160, 1650], fill=(255, 255, 255))
        d.text((120, 1360), title, font=font(64), fill=NAVY)
        d.text((120, 1460), 'Company profile 2026', font=font(36, False), fill=(60, 60, 70))
        pages.append(page)
    pages[0].save(os.path.join(OUT, 'company-profile.pdf'), save_all=True, append_images=pages[1:], resolution=150, quality=60)


DESTINATIONS = ['united-kingdom', 'united-states', 'canada', 'australia', 'ireland', 'germany', 'france', 'netherlands',
                'new-zealand', 'malaysia', 'uae', 'turkey', 'cyprus', 'malta', 'spain', 'italy']
PARTNERS = [
    ('Northbridge|University', 'shield', NAVY),
    ('Harbour|Institute of Technology', 'triangle', INDIGO),
    ('St Aldric|College', 'book', CRIMSON),
    ('Westmere|University', 'circle', (0, 90, 148)),
    ('Lakeside|Business School', 'triangle', (15, 135, 93)),
    ('Kingsgate|University', 'shield', (100, 64, 251)),
    ('Avalon|Global Academy', 'circle', (194, 71, 122)),
    ('Meridian|College London', 'book', (47, 53, 66)),
    ('Pacific Rim|University', 'triangle', (14, 124, 134)),
    ('Crestwood|Institute', 'shield', (184, 145, 58)),
    ('Bright Path|Advisors', 'circle', (226, 87, 76)),
    ('Global Study|Link', 'book', (232, 155, 18)),
]
PEOPLE = {'team-1': ('AR', NAVY), 'team-2': ('SK', INDIGO), 'team-3': ('ML', CRIMSON), 'team-4': ('JT', (15, 135, 93)),
          'student-1': ('NA', (100, 64, 251)), 'student-2': ('DO', (0, 90, 148)), 'student-3': ('LC', (194, 71, 122))}


def main():
    os.makedirs(OUT, exist_ok=True)
    for i in range(1, 4):
        save_jpg(art('hero-%d' % i, (600, 600)), 'hero-%d' % i)
    save_jpg(art('banner', (1600, 480), PALETTES[0]), 'banner')
    save_jpg(art('about', (900, 700), PALETTES[1]), 'about')
    for i, key in enumerate(['recruitment', 'pathways', 'preparation', 'campus']):
        save_jpg(art('service-' + key, (800, 560), PALETTES[i % len(PALETTES)]), 'service-' + key)
    for key in DESTINATIONS:
        save_jpg(art('dest-' + key, (600, 750)), 'destination-' + key)
    for i in range(1, 7):
        save_jpg(art('news-%d' % i, (800, 500)), 'news-%d' % i)
    for i in range(1, 4):
        save_jpg(art('event-%d' % i, (800, 500)), 'event-%d' % i)
    for i, (name, mark, color) in enumerate(PARTNERS, 1):
        wordmark(name, mark, color).convert('RGB').save(os.path.join(OUT, 'partner-%d.png' % i), optimize=True)
    for key, (initials, color) in PEOPLE.items():
        save_jpg(avatar(initials, color), key)
    logo(NAVY, CRIMSON, 'logo')
    logo((255, 255, 255), (255, 255, 255), 'logo-light')
    pdf()
    total = sum(os.path.getsize(os.path.join(OUT, f)) for f in os.listdir(OUT))
    print('%d files, %.0f KB' % (len(os.listdir(OUT)), total / 1024))


if __name__ == '__main__':
    sys.exit(main())
