/// Remise appliquée en fonction du volume (identique au backend).
double discountFor(int votes) {
  if (votes >= 50) return 0.8;
  if (votes >= 20) return 0.85;
  if (votes >= 10) return 0.9;
  return 1.0;
}

/// Prix en FCFA pour `votes` à `pricePerVote` FCFA chacun, remise comprise.
int votePrice(int votes, int pricePerVote) =>
    (votes * pricePerVote * discountFor(votes)).round();

String formatFcfa(int amount) {
  final digits = amount.toString();
  return '${digits.replaceAllMapped(
    RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
    (Match m) => '${m[1]} ',
  )} FCFA';
}

String formatCount(int value) {
  return value.toString().replaceAllMapped(
    RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
    (Match m) => '${m[1]} ',
  );
}