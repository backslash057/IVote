// Test de fumée : vérifie que l'app se construit sans erreur.
//
// Aucun appel réseau n'est effectué pendant le test (le FutureBuilder
// reste en attente jusqu'au premier pompage).

import 'package:flutter_test/flutter_test.dart';

import 'package:ivote/main.dart';

void main() {
  testWidgets('IVote se construit', (WidgetTester tester) async {
    await tester.pumpWidget(const MyApp());
    await tester.pump();

    expect(find.text('IVote'), findsOneWidget);
    expect(find.text('Vote & Paiement Mobile Money'), findsOneWidget);
  });
}