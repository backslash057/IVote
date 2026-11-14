#!/usr/bin/env python3
"""Génère le logo IVote et toutes les icônes plateformes.

Concept : coche blanche (lisible comme un « V ») sur un squircle à dégradé bleu.

Le script est la source de vérité : relancez-le après toute retouche du dessin
pour régénérer l'ensemble des assets (Android, iOS, web, in-app, splash).

Dépendances : Pillow + numpy (déjà présents sur la machine de dev).
Usage : python3 tooling/generate_logo.py
"""

from __future__ import annotations

import os

import numpy as np
from PIL import Image, ImageDraw

# ---------------------------------------------------------------------------
#  Chemins
# ---------------------------------------------------------------------------
HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)  # mobile/

RES = os.path.join(ROOT, "android", "app", "src", "main", "res")
IOS = os.path.join(ROOT, "ios", "Runner", "Assets.xcassets", "AppIcon.appiconset")
WEB = os.path.join(ROOT, "web")
ASSETS = os.path.join(ROOT, "assets", "logo")

# ---------------------------------------------------------------------------
#  Charte
# ---------------------------------------------------------------------------
BLUE_TOP = (0x26, 0x68, 0xFC)
BLUE_BOTTOM = (0x15, 0x50, 0xD4)
WHITE = (0xFF, 0xFF, 0xFF)

SS = 4                # facteur de sur-échantillonnage (anti-aliasing)
CORNER_FRAC = 0.225   # rayon des coins du squircle
BOX_MAIN = 0.86       # encombrement du dessin (icône pleine)
BOX_SAFE = 0.62       # encombrement réduit (safe zone adaptative / maskable)

# Coche dans un repère 100x100 (p1 -> p2 -> p3), épaisseur 12.
CHECK = [(24, 52), (42, 70), (78, 30)]
STROKE = 12


# ---------------------------------------------------------------------------
#  Primitives
# ---------------------------------------------------------------------------
def gradient(size: int, c1, c2) -> Image.Image:
    """Dégradé diagonal c1 (haut-gauche) -> c2 (bas-droite)."""
    x = np.linspace(0.0, 1.0, size, dtype=np.float32)
    xx, yy = np.meshgrid(x, x)
    t = np.clip((xx + yy) / 2.0, 0.0, 1.0)[..., None]
    a = np.array(c1, dtype=np.float32)
    b = np.array(c2, dtype=np.float32)
    arr = a + (b - a) * t
    return Image.fromarray(arr.astype(np.uint8), "RGB")


def _draw_check(draw: ImageDraw.ImageDraw, canvas: int, box_frac: float) -> None:
    scale = box_frac * canvas / 100.0
    off = (canvas - 100.0 * scale) / 2.0

    def m(p):
        return (off + p[0] * scale, off + p[1] * scale)

    pts = [m(p) for p in CHECK]
    width = max(1, int(round(STROKE * scale)))
    draw.line(pts, fill=WHITE + (255,), width=width, joint="curve")
    r = width / 2.0
    for x, y in (pts[0], pts[-1]):
        draw.ellipse([x - r, y - r, x + r, y + r], fill=WHITE + (255,))


def icon(px: int, *, rounded: bool, box_frac: float, alpha: bool = True) -> Image.Image:
    """Icône complète : fond dégradé (squircle ou plein) + coche blanche."""
    canvas = px * SS
    img = gradient(canvas, BLUE_TOP, BLUE_BOTTOM).convert("RGBA")
    if rounded:
        mask = Image.new("L", (canvas, canvas), 0)
        ImageDraw.Draw(mask).rounded_rectangle(
            [0, 0, canvas - 1, canvas - 1],
            radius=int(round(CORNER_FRAC * canvas)),
            fill=255,
        )
        img.putalpha(mask)
    _draw_check(ImageDraw.Draw(img), canvas, box_frac)
    img = img.resize((px, px), Image.LANCZOS)
    return img if alpha else img.convert("RGB")


def foreground(px: int, box_frac: float = BOX_SAFE) -> Image.Image:
    """Calque avant (coche blanche) transparent pour l'icône adaptative."""
    canvas = px * SS
    img = Image.new("RGBA", (canvas, canvas), (0, 0, 0, 0))
    _draw_check(ImageDraw.Draw(img), canvas, box_frac)
    return img.resize((px, px), Image.LANCZOS)


def save(img: Image.Image, path: str) -> None:
    os.makedirs(os.path.dirname(path), exist_ok=True)
    img.save(path)
    print("  ", os.path.relpath(path, ROOT))


# ---------------------------------------------------------------------------
#  Génération
# ---------------------------------------------------------------------------
def main() -> None:
    print("Android — icônes héritées :")
    densities = {"mdpi": 48, "hdpi": 72, "xhdpi": 96, "xxhdpi": 144, "xxxhdpi": 192}
    for d, px in densities.items():
        save(icon(px, rounded=True, box_frac=BOX_MAIN),
             os.path.join(RES, f"mipmap-{d}", "ic_launcher.png"))

    print("Android — icône adaptative (avant-plan) :")
    fg = {"mdpi": 108, "hdpi": 162, "xhdpi": 216, "xxhdpi": 324, "xxxhdpi": 432}
    for d, px in fg.items():
        save(foreground(px), os.path.join(RES, f"mipmap-{d}", "ic_launcher_foreground.png"))

    print("iOS :")
    ios_sizes = {
        "Icon-App-20x20@1x.png": 20, "Icon-App-20x20@2x.png": 40, "Icon-App-20x20@3x.png": 60,
        "Icon-App-29x29@1x.png": 29, "Icon-App-29x29@2x.png": 58, "Icon-App-29x29@3x.png": 87,
        "Icon-App-40x40@1x.png": 40, "Icon-App-40x40@2x.png": 80, "Icon-App-40x40@3x.png": 120,
        "Icon-App-60x60@2x.png": 120, "Icon-App-60x60@3x.png": 180,
        "Icon-App-76x76@1x.png": 76, "Icon-App-76x76@2x.png": 152,
        "Icon-App-83.5x83.5@2x.png": 167, "Icon-App-1024x1024@1x.png": 1024,
    }
    for name, px in ios_sizes.items():
        save(icon(px, rounded=False, box_frac=BOX_MAIN, alpha=False),
             os.path.join(IOS, name))

    print("Web :")
    save(icon(32, rounded=True, box_frac=BOX_MAIN), os.path.join(WEB, "favicon.png"))
    save(icon(192, rounded=True, box_frac=BOX_MAIN), os.path.join(WEB, "icons", "Icon-192.png"))
    save(icon(512, rounded=True, box_frac=BOX_MAIN), os.path.join(WEB, "icons", "Icon-512.png"))
    save(icon(192, rounded=False, box_frac=BOX_SAFE), os.path.join(WEB, "icons", "Icon-maskable-192.png"))
    save(icon(512, rounded=False, box_frac=BOX_SAFE), os.path.join(WEB, "icons", "Icon-maskable-512.png"))
    save(icon(180, rounded=False, box_frac=BOX_MAIN, alpha=False), os.path.join(WEB, "apple-touch-icon.png"))

    print("In-app :")
    save(icon(512, rounded=True, box_frac=BOX_MAIN), os.path.join(ASSETS, "ivote_logo.png"))

    print("Splash Android :")
    save(icon(160, rounded=True, box_frac=BOX_MAIN), os.path.join(RES, "drawable", "launch_image.png"))

    print("\nTerminé.")


if __name__ == "__main__":
    main()
