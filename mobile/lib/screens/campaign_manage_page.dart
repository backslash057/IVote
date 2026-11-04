import 'package:flutter/material.dart';
import '../services/campaign_service.dart';
import '../theme/app_theme.dart';
import 'campaign_detail_page.dart';

class CampaignManagePage extends StatefulWidget {
  final int campaignId;

  const CampaignManagePage({super.key, required this.campaignId});

  @override
  State<CampaignManagePage> createState() => _CampaignManagePageState();
}

class _CampaignManagePageState extends State<CampaignManagePage> {
  final CampaignService _service = CampaignService();
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _load();
  }

  void _load() {
    setState(() {
      _future = _service.fetchCampaignDashboard(widget.campaignId);
    });
  }

  String _formatNumber(Object? value) {
    final number =
        value is num ? value : num.tryParse(value?.toString() ?? '') ?? 0;
    return number.toString().replaceAllMapped(
          RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
          (Match m) => '${m[1]} ',
        );
  }

  // --- ACTIONS DE STATUT ---
  Future<void> _handleStatusAction(String action) async {
    try {
      final res =
          await _service.updateCampaignStatus(widget.campaignId, action);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['message'] ?? 'Statut mis à jour.'),
          backgroundColor: AppColors.greenAccent,
          behavior: SnackBarBehavior.floating,
        ),
      );
      _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString().replaceAll('Exception: ', '')),
          backgroundColor: AppColors.redAccent,
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  // --- MODALE MODIFICATION CAMPAGNE ---
  void _showEditCampaignModal(Map<String, dynamic> campaign) {
    final titleCtrl = TextEditingController(text: campaign['title'] ?? '');
    final descCtrl = TextEditingController(text: campaign['description'] ?? '');
    final priceCtrl = TextEditingController(
        text: campaign['price_per_vote']?.toString() ?? '100');
    final dateCtrl =
        TextEditingController(text: campaign['date_cloture'] ?? '');
    bool isSaving = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) {
          final theme = Theme.of(ctx);

          return Container(
            padding: EdgeInsets.only(
              left: 24,
              right: 24,
              top: 24,
              bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
            ),
            decoration: BoxDecoration(
              color: theme.colorScheme.surface,
              borderRadius:
                  const BorderRadius.vertical(top: Radius.circular(28)),
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: theme.colorScheme.outline,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Modifier la campagne',
                    style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        color: theme.colorScheme.onSurface),
                  ),
                  const SizedBox(height: 16),
                  Text('Titre',
                      style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: theme.colorScheme.onSurface)),
                  const SizedBox(height: 4),
                  TextField(
                    controller: titleCtrl,
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: theme.colorScheme.surfaceContainerHighest,
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text('Description',
                      style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: theme.colorScheme.onSurface)),
                  const SizedBox(height: 4),
                  TextField(
                    controller: descCtrl,
                    maxLines: 3,
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: theme.colorScheme.surfaceContainerHighest,
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text('Prix par vote (FCFA)',
                      style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: theme.colorScheme.onSurface)),
                  const SizedBox(height: 4),
                  TextField(
                    controller: priceCtrl,
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: theme.colorScheme.surfaceContainerHighest,
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text('Date de clôture (ex: 2026-12-31 23:59:59)',
                      style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: theme.colorScheme.onSurface)),
                  const SizedBox(height: 4),
                  TextField(
                    controller: dateCtrl,
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: theme.colorScheme.surfaceContainerHighest,
                      border: InputBorder.none,
                      hintText: 'Optionnel',
                      contentPadding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 20),
                  SizedBox(
                    height: 44,
                    child: ElevatedButton(
                      onPressed: isSaving
                          ? null
                          : () async {
                              setModalState(() => isSaving = true);
                              try {
                                await _service.updateCampaign(
                                  id: widget.campaignId,
                                  title: titleCtrl.text.trim(),
                                  description: descCtrl.text.trim(),
                                  pricePerVote:
                                      int.tryParse(priceCtrl.text) ?? 100,
                                  dateCloture: dateCtrl.text.trim().isEmpty
                                      ? null
                                      : dateCtrl.text.trim(),
                                );
                                if (!ctx.mounted) return;
                                Navigator.of(ctx).pop();
                                ScaffoldMessenger.of(ctx).showSnackBar(
                                  const SnackBar(
                                      content: Text(
                                          'Campagne modifiée avec succès.')),
                                );
                                _load();
                              } catch (e) {
                                setModalState(() => isSaving = false);
                                if (!ctx.mounted) return;
                                ScaffoldMessenger.of(ctx).showSnackBar(
                                  SnackBar(
                                      content: Text(e
                                          .toString()
                                          .replaceAll('Exception: ', ''))),
                                );
                              }
                            },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: theme.colorScheme.primary,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12)),
                      ),
                      child: isSaving
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                  strokeWidth: 2, color: Colors.white))
                          : const Text('Enregistrer',
                              style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  // --- MODALE AJOUT DE CANDIDAT ---
  void _showAddCandidateModal(List<dynamic> categories) {
    final nameCtrl = TextEditingController();
    final numberCtrl = TextEditingController();
    final ageCtrl = TextEditingController();
    final themeCtrl = TextEditingController();
    final descCtrl = TextEditingController();
    final newCatCtrl = TextEditingController();

    int? selectedCatId = categories.isNotEmpty
        ? int.tryParse(categories.first['category_id'].toString())
        : null;
    bool isNewCategory = categories.isEmpty;
    bool isSaving = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) {
          final theme = Theme.of(ctx);

          return Container(
            padding: EdgeInsets.only(
              left: 24,
              right: 24,
              top: 24,
              bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
            ),
            decoration: BoxDecoration(
              color: theme.colorScheme.surface,
              borderRadius:
                  const BorderRadius.vertical(top: Radius.circular(28)),
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: theme.colorScheme.outline,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Ajouter un Candidat',
                    style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        color: theme.colorScheme.onSurface),
                  ),
                  const SizedBox(height: 16),

                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Nom complet *',
                                style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: theme.colorScheme.onSurface)),
                            const SizedBox(height: 4),
                            TextField(
                              controller: nameCtrl,
                              decoration: InputDecoration(
                                filled: true,
                                fillColor:
                                    theme.colorScheme.surfaceContainerHighest,
                                border: InputBorder.none,
                                contentPadding: const EdgeInsets.symmetric(
                                    horizontal: 12, vertical: 10),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      SizedBox(
                        width: 90,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('N° Dossard *',
                                style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: theme.colorScheme.onSurface)),
                            const SizedBox(height: 4),
                            TextField(
                              controller: numberCtrl,
                              keyboardType: TextInputType.number,
                              decoration: InputDecoration(
                                filled: true,
                                fillColor:
                                    theme.colorScheme.surfaceContainerHighest,
                                border: InputBorder.none,
                                contentPadding: const EdgeInsets.symmetric(
                                    horizontal: 12, vertical: 10),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Âge',
                                style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: theme.colorScheme.onSurface)),
                            const SizedBox(height: 4),
                            TextField(
                              controller: ageCtrl,
                              keyboardType: TextInputType.number,
                              decoration: InputDecoration(
                                filled: true,
                                fillColor:
                                    theme.colorScheme.surfaceContainerHighest,
                                border: InputBorder.none,
                                contentPadding: const EdgeInsets.symmetric(
                                    horizontal: 12, vertical: 10),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Thème / Slogan',
                                style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: theme.colorScheme.onSurface)),
                            const SizedBox(height: 4),
                            TextField(
                              controller: themeCtrl,
                              decoration: InputDecoration(
                                filled: true,
                                fillColor:
                                    theme.colorScheme.surfaceContainerHighest,
                                border: InputBorder.none,
                                contentPadding: const EdgeInsets.symmetric(
                                    horizontal: 12, vertical: 10),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Catégorie
                  Text('Catégorie',
                      style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: theme.colorScheme.onSurface)),
                  const SizedBox(height: 4),
                  if (categories.isNotEmpty && !isNewCategory) ...[
                    DropdownButtonFormField<int>(
                      value: selectedCatId,
                      items: categories.map((cat) {
                        return DropdownMenuItem<int>(
                          value: int.tryParse(cat['category_id'].toString()),
                          child: Text(cat['name'] as String,
                              style: const TextStyle(fontSize: 13)),
                        );
                      }).toList(),
                      onChanged: (val) =>
                          setModalState(() => selectedCatId = val),
                      decoration: InputDecoration(
                        filled: true,
                        fillColor: theme.colorScheme.surfaceContainerHighest,
                        border: InputBorder.none,
                        contentPadding: const EdgeInsets.symmetric(
                            horizontal: 12, vertical: 10),
                      ),
                    ),
                    TextButton(
                      onPressed: () =>
                          setModalState(() => isNewCategory = true),
                      child: const Text('+ Créer une nouvelle catégorie',
                          style: TextStyle(fontSize: 11)),
                    ),
                  ] else ...[
                    TextField(
                      controller: newCatCtrl,
                      decoration: InputDecoration(
                        filled: true,
                        fillColor: theme.colorScheme.surfaceContainerHighest,
                        border: InputBorder.none,
                        hintText: 'Nom de la nouvelle catégorie',
                        contentPadding: const EdgeInsets.symmetric(
                            horizontal: 12, vertical: 10),
                      ),
                    ),
                    if (categories.isNotEmpty)
                      TextButton(
                        onPressed: () =>
                            setModalState(() => isNewCategory = false),
                        child: const Text('Choisir une catégorie existante',
                            style: TextStyle(fontSize: 11)),
                      ),
                  ],
                  const SizedBox(height: 20),

                  SizedBox(
                    height: 44,
                    child: ElevatedButton(
                      onPressed: isSaving
                          ? null
                          : () async {
                              final name = nameCtrl.text.trim();
                              final number =
                                  int.tryParse(numberCtrl.text.trim()) ?? 0;
                              if (name.isEmpty || number <= 0) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                      content: Text(
                                          'Nom et numéro de dossard requis.')),
                                );
                                return;
                              }

                              setModalState(() => isSaving = true);
                              try {
                                await _service.addCandidate(
                                  campaignId: widget.campaignId,
                                  name: name,
                                  candidateNumber: number,
                                  age: int.tryParse(ageCtrl.text.trim()),
                                  theme: themeCtrl.text.trim(),
                                  description: descCtrl.text.trim(),
                                  categoryId:
                                      isNewCategory ? null : selectedCatId,
                                  newCategoryName: isNewCategory
                                      ? newCatCtrl.text.trim()
                                      : null,
                                );
                                if (!ctx.mounted) return;
                                Navigator.of(ctx).pop();
                                ScaffoldMessenger.of(ctx).showSnackBar(
                                  const SnackBar(
                                    content:
                                        Text('Candidat ajouté avec succès !'),
                                    backgroundColor: AppColors.greenAccent,
                                  ),
                                );
                                _load();
                              } catch (e) {
                                setModalState(() => isSaving = false);
                                if (!ctx.mounted) return;
                                ScaffoldMessenger.of(ctx).showSnackBar(
                                  SnackBar(
                                      content: Text(e
                                          .toString()
                                          .replaceAll('Exception: ', ''))),
                                );
                              }
                            },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: theme.colorScheme.primary,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12)),
                      ),
                      child: isSaving
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                  strokeWidth: 2, color: Colors.white))
                          : const Text('Enregistrer le Candidat',
                              style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Future<void> _editCandidate(
      Map<String, dynamic> candidate, List<dynamic> categories) async {
    final nameController =
        TextEditingController(text: candidate['name']?.toString() ?? '');
    final numberController = TextEditingController(
        text: candidate['candidate_number']?.toString() ?? '');
    final ageController =
        TextEditingController(text: candidate['age']?.toString() ?? '');
    final themeController =
        TextEditingController(text: candidate['theme']?.toString() ?? '');
    final descriptionController =
        TextEditingController(text: candidate['description']?.toString() ?? '');
    final bioController =
        TextEditingController(text: candidate['bio']?.toString() ?? '');
    int? categoryId =
        int.tryParse(candidate['category_id']?.toString() ?? '') ??
            (categories.isNotEmpty
                ? int.tryParse(categories.first['category_id'].toString())
                : null);

    final saved = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (dialogContext, setDialogState) => AlertDialog(
          title: const Text('Modifier le candidat'),
          content: SingleChildScrollView(
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              TextField(
                  controller: nameController,
                  decoration: const InputDecoration(labelText: 'Nom')),
              TextField(
                  controller: numberController,
                  keyboardType: TextInputType.number,
                  decoration:
                      const InputDecoration(labelText: 'Numéro de candidat')),
              TextField(
                  controller: ageController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Âge')),
              TextField(
                  controller: themeController,
                  decoration: const InputDecoration(labelText: 'Thème')),
              TextField(
                  controller: descriptionController,
                  decoration: const InputDecoration(labelText: 'Description')),
              TextField(
                  controller: bioController,
                  decoration: const InputDecoration(labelText: 'Biographie')),
              if (categories.isNotEmpty)
                DropdownButtonFormField<int>(
                  value: categoryId,
                  decoration: const InputDecoration(labelText: 'Catégorie'),
                  items: categories
                      .map((item) => DropdownMenuItem<int>(
                          value:
                              int.tryParse(item['category_id'].toString()) ?? 0,
                          child: Text(item['name'] ?? '')))
                      .toList(),
                  onChanged: (value) =>
                      setDialogState(() => categoryId = value),
                ),
            ]),
          ),
          actions: [
            TextButton(
                onPressed: () => Navigator.pop(dialogContext, false),
                child: const Text('Annuler')),
            FilledButton(
                onPressed: () => Navigator.pop(dialogContext, true),
                child: const Text('Enregistrer')),
          ],
        ),
      ),
    );
    if (saved != true) return;
    try {
      await _service.updateCandidate(
        candidateId: (candidate['candidate_id'] as num).toInt(),
        name: nameController.text.trim(),
        candidateNumber: int.tryParse(numberController.text) ?? 0,
        categoryId: categoryId,
        age: int.tryParse(ageController.text),
        theme: themeController.text.trim(),
        description: descriptionController.text.trim(),
        bio: bioController.text.trim(),
      );
      _load();
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(error.toString().replaceFirst('Exception: ', ''))));
    }
  }

  Future<void> _manageCategories(
      List<dynamic> categories, List<dynamic> candidates) async {
    final newCategoryController = TextEditingController();
    await showDialog<void>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Catégories du brouillon'),
        content: SizedBox(
          width: 420,
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Row(children: [
              Expanded(
                  child: TextField(
                      controller: newCategoryController,
                      decoration: const InputDecoration(
                          hintText: 'Nouvelle catégorie'))),
              IconButton(
                  icon: const Icon(Icons.add),
                  onPressed: () async {
                    final name = newCategoryController.text.trim();
                    if (name.isEmpty) return;
                    try {
                      await _service.addCategory(widget.campaignId, name);
                      newCategoryController.clear();
                      if (context.mounted && dialogContext.mounted) {
                        Navigator.pop(dialogContext);
                        _load();
                      }
                    } catch (error) {
                      if (mounted && dialogContext.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                            content: Text(error
                                .toString()
                                .replaceFirst('Exception: ', ''))));
                      }
                    }
                  })
            ]),
            const Divider(),
            ...categories.map((category) {
              final categoryId =
                  int.tryParse(category['category_id'].toString()) ?? 0;
              final count = candidates
                  .where((candidate) =>
                      int.tryParse(
                          candidate['category_id']?.toString() ?? '') ==
                      categoryId)
                  .length;
              return ListTile(
                dense: true,
                title: Text(category['name'] ?? ''),
                subtitle: Text('$count candidat(s)'),
                trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                  IconButton(
                      icon: const Icon(Icons.edit_outlined),
                      tooltip: 'Renommer',
                      onPressed: () async {
                        final controller =
                            TextEditingController(text: category['name'] ?? '');
                        final name = await showDialog<String>(
                            context: dialogContext,
                            builder: (context) => AlertDialog(
                                    title: const Text('Renommer la catégorie'),
                                    content: TextField(controller: controller),
                                    actions: [
                                      TextButton(
                                          onPressed: () =>
                                              Navigator.pop(context),
                                          child: const Text('Annuler')),
                                      FilledButton(
                                          onPressed: () => Navigator.pop(
                                              context, controller.text.trim()),
                                          child: const Text('Enregistrer'))
                                    ]));
                        if (name != null && name.isNotEmpty) {
                          await _service.updateCategory(categoryId, name);
                          if (mounted && dialogContext.mounted) {
                            Navigator.pop(dialogContext);
                            _load();
                          }
                        }
                      }),
                  if (count == 0)
                    IconButton(
                        icon: const Icon(Icons.delete_outline,
                            color: AppColors.redAccent),
                        tooltip: 'Supprimer',
                        onPressed: () async {
                          await _service.deleteCategory(categoryId);
                          if (dialogContext.mounted) {
                            Navigator.pop(dialogContext);
                            _load();
                          }
                        }),
                ]),
              );
            }),
          ]),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text('Fermer'))
        ],
      ),
    );
    newCategoryController.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Gestion de Campagne',
          style: TextStyle(
              fontWeight: FontWeight.w800,
              fontSize: 16,
              color: theme.colorScheme.onSurface),
        ),
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.error_outline_rounded,
                        color: AppColors.redAccent, size: 40),
                    const SizedBox(height: 12),
                    Text('${snapshot.error}'.replaceAll('Exception: ', ''),
                        textAlign: TextAlign.center),
                    const SizedBox(height: 16),
                    ElevatedButton(
                        onPressed: _load, child: const Text('Réessayer')),
                  ],
                ),
              ),
            );
          }

          final data = snapshot.data!;
          final campaign = data['campaign'] as Map<String, dynamic>;
          final categories = (data['categories'] as List<dynamic>?) ?? [];
          final candidates = (data['candidates'] as List<dynamic>?) ?? [];
          final stats = data['stats'] as Map<String, dynamic>;

          final computedStatus = campaign['computed_status'] ?? 'live';
          final isLive = computedStatus == 'live';
          final isDraft = computedStatus == 'draft';
          final isSuspended = computedStatus == 'suspended';

          return RefreshIndicator(
            onRefresh: () async => _load(),
            child: ListView(
              padding: const EdgeInsets.all(16.0),
              children: [
                // 1. Panneau Principal de la Campagne
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.surfaceContainerHighest,
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(color: theme.colorScheme.outline),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: theme.colorScheme.surface,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              isLive
                                  ? '● En Direct'
                                  : (isDraft
                                      ? 'Brouillon'
                                      : (isSuspended
                                          ? 'Suspendue'
                                          : 'Clôturée')),
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: isLive
                                    ? AppColors.greenAccent
                                    : (isDraft
                                        ? AppColors.orangeAccent
                                        : (isSuspended
                                            ? AppColors.redAccent
                                            : theme
                                                .colorScheme.onSurfaceVariant)),
                              ),
                            ),
                          ),
                          Row(
                            children: [
                              if (isDraft)
                                IconButton(
                                  onPressed: () =>
                                      _showEditCampaignModal(campaign),
                                  icon:
                                      const Icon(Icons.edit_outlined, size: 18),
                                  tooltip: 'Modifier le brouillon',
                                ),
                              if (!isSuspended)
                                IconButton(
                                  onPressed: () {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(
                                        builder: (_) => CampaignDetailPage(
                                            campaignId: widget.campaignId),
                                      ),
                                    );
                                  },
                                  icon: const Icon(Icons.open_in_new_rounded,
                                      size: 18),
                                  tooltip: 'Page publique',
                                ),
                            ],
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        campaign['title'] ?? '',
                        style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                            color: theme.colorScheme.onSurface),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        campaign['description'] ?? '',
                        style: TextStyle(
                            fontSize: 12,
                            color: theme.colorScheme.onSurfaceVariant),
                      ),
                      if (isSuspended &&
                          (campaign['suspension_reason'] ?? '')
                              .toString()
                              .isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Text(
                            'Motif de suspension : ${campaign['suspension_reason']}',
                            style: const TextStyle(
                                fontSize: 11, color: AppColors.redAccent)),
                      ],
                      const SizedBox(height: 16),

                      // Actions de statut
                      Row(
                        children: [
                          if (isDraft)
                            Expanded(
                              child: ElevatedButton.icon(
                                onPressed: () => _handleStatusAction('publish'),
                                icon: const Icon(Icons.rocket_launch_rounded,
                                    size: 16),
                                label: const Text('Publier la Campagne',
                                    style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold)),
                                style: ElevatedButton.styleFrom(
                                    backgroundColor: AppColors.greenAccent,
                                    foregroundColor: Colors.white),
                              ),
                            ),
                          if (isLive)
                            Expanded(
                              child: ElevatedButton.icon(
                                onPressed: () => _handleStatusAction('close'),
                                icon: const Icon(Icons.lock_clock_rounded,
                                    size: 16),
                                label: const Text('Clôturer les Votes',
                                    style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold)),
                                style: ElevatedButton.styleFrom(
                                    backgroundColor: AppColors.redAccent,
                                    foregroundColor: Colors.white),
                              ),
                            ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                // 2. Statistiques Rapides
                Row(
                  children: [
                    Expanded(
                      child: _buildMiniStat(theme, 'TOTAL VOTES',
                          _formatNumber(stats['totalVotes'] ?? 0)),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildMiniStat(theme, 'REVENU BRUT',
                          '${_formatNumber(stats['totalRevenue'] ?? 0)} F'),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildMiniStat(
                          theme, 'CANDIDATS', '${candidates.length}'),
                    ),
                  ],
                ),
                const SizedBox(height: 24),

                // 3. Section Candidats
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Candidats (${candidates.length})',
                      style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: theme.colorScheme.onSurface),
                    ),
                    if (isDraft) ...[
                      OutlinedButton.icon(
                        onPressed: () =>
                            _manageCategories(categories, candidates),
                        icon: const Icon(Icons.category_outlined, size: 16),
                        label: const Text('Catégories',
                            style: TextStyle(fontSize: 12)),
                      ),
                      ElevatedButton.icon(
                        onPressed: () => _showAddCandidateModal(categories),
                        icon: const Icon(Icons.add_rounded, size: 16),
                        label: const Text('Ajouter',
                            style: TextStyle(
                                fontSize: 12, fontWeight: FontWeight.bold)),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: theme.colorScheme.primary,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(
                              horizontal: 12, vertical: 6),
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10)),
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 12),

                if (candidates.isEmpty)
                  Container(
                    padding: const EdgeInsets.all(28),
                    decoration: BoxDecoration(
                      color: theme.colorScheme.surfaceContainerHighest,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Center(
                      child: Text(
                        'Aucun candidat pour le moment. Cliquez sur "Ajouter" pour commencer.',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                            fontSize: 12,
                            color: theme.colorScheme.onSurfaceVariant),
                      ),
                    ),
                  )
                else
                  ...candidates.map((cand) => Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.surface,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: theme.colorScheme.outline),
                        ),
                        child: Row(
                          children: [
                            CircleAvatar(
                              radius: 20,
                              backgroundColor:
                                  theme.colorScheme.surfaceContainerHighest,
                              child: Text('#${cand['candidate_number']}',
                                  style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 12)),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(cand['name'] ?? '',
                                      style: TextStyle(
                                          fontSize: 14,
                                          fontWeight: FontWeight.bold,
                                          color: theme.colorScheme.onSurface)),
                                  Text(
                                      '${cand['category_name'] ?? 'Général'} • ${cand['total_votes']} votes',
                                      style: TextStyle(
                                          fontSize: 11,
                                          color: theme
                                              .colorScheme.onSurfaceVariant)),
                                ],
                              ),
                            ),
                            Text(
                              '${_formatNumber(cand['candidate_revenue'] ?? 0)} F',
                              style: const TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w800,
                                  color: AppColors.greenAccent),
                            ),
                            if (isDraft)
                              PopupMenuButton<String>(
                                tooltip: 'Actions candidat',
                                onSelected: (action) async {
                                  final candidate =
                                      Map<String, dynamic>.from(cand);
                                  if (action == 'edit') {
                                    await _editCandidate(candidate, categories);
                                  } else {
                                    final confirmed = await showDialog<bool>(
                                      context: context,
                                      builder: (dialogContext) => AlertDialog(
                                        title: const Text(
                                            'Supprimer ce candidat ?'),
                                        content: Text(
                                            '« ${candidate['name']} » sera retiré du brouillon.'),
                                        actions: [
                                          TextButton(
                                              onPressed: () => Navigator.pop(
                                                  dialogContext, false),
                                              child: const Text('Annuler')),
                                          FilledButton(
                                              onPressed: () => Navigator.pop(
                                                  dialogContext, true),
                                              child: const Text('Supprimer')),
                                        ],
                                      ),
                                    );
                                    if (confirmed == true) {
                                      await _service.deleteCandidate(
                                          (candidate['candidate_id'] as num)
                                              .toInt());
                                      _load();
                                    }
                                  }
                                },
                                itemBuilder: (context) => const [
                                  PopupMenuItem(
                                      value: 'edit', child: Text('Modifier')),
                                  PopupMenuItem(
                                      value: 'delete',
                                      child: Text('Supprimer')),
                                ],
                              ),
                          ],
                        ),
                      )),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildMiniStat(ThemeData theme, String label, String value) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: theme.colorScheme.outline),
      ),
      child: Column(
        children: [
          Text(value,
              style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                  color: theme.colorScheme.onSurface)),
          const SizedBox(height: 2),
          Text(label,
              style: TextStyle(
                  fontSize: 8,
                  fontWeight: FontWeight.bold,
                  color: theme.colorScheme.onSurfaceVariant)),
        ],
      ),
    );
  }
}
