import 'package:flutter/material.dart';

/// Logo IVote (coche blanche sur squircle bleu).
///
/// [size] est la hauteur/largeur de la marque en pixels logiques.
/// [withWordmark] ajoute le texte « IVote » à droite de la marque.
class IvLogo extends StatelessWidget {
  const IvLogo({
    super.key,
    this.size = 96,
    this.withWordmark = false,
    this.wordmarkColor,
  });

  final double size;
  final bool withWordmark;
  final Color? wordmarkColor;

  @override
  Widget build(BuildContext context) {
    final mark = Image.asset(
      'assets/logo/ivote_logo.png',
      width: size,
      height: size,
      filterQuality: FilterQuality.high,
    );

    if (!withWordmark) {
      return mark;
    }

    final color = wordmarkColor ?? Theme.of(context).colorScheme.onSurface;
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        mark,
        SizedBox(width: size * 0.18),
        Text(
          'IVote',
          style: TextStyle(
            fontSize: size * 0.42,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.5,
            color: color,
          ),
        ),
      ],
    );
  }
}
