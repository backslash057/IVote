import 'package:flutter/material.dart';
import '../services/campaign_service.dart';
import '../theme/app_theme.dart';

class CreateCampaignPage extends StatefulWidget {
  const CreateCampaignPage({super.key});

  @override
  State<CreateCampaignPage> createState() => _CreateCampaignPageState();
}

class _CreateCampaignPageState extends State<CreateCampaignPage> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _priceController = TextEditingController(text: '100');
  final _dateController = TextEditingController();
  bool _saving = false;

  @override
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    _priceController.dispose();
    _dateController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final result = await CampaignService().createCampaign(
        title: _titleController.text.trim(),
        description: _descriptionController.text.trim(),
        pricePerVote: int.parse(_priceController.text.trim()),
        dateCloture: _dateController.text.trim().isEmpty
            ? null
            : _dateController.text.trim(),
      );
      if (!mounted) return;
      Navigator.of(context).pop(result['campaign_id']);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
            content: Text(error.toString().replaceFirst('Exception: ', '')),
            backgroundColor: AppColors.redAccent),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: const Text('Nouvelle campagne')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Text('Créer un brouillon',
                style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.w800,
                    color: theme.colorScheme.onSurface)),
            const SizedBox(height: 6),
            Text(
                'Ajoutez ensuite les catégories et les candidats avant de publier.',
                style: TextStyle(color: theme.colorScheme.onSurfaceVariant)),
            const SizedBox(height: 24),
            TextFormField(
                controller: _titleController,
                decoration:
                    const InputDecoration(labelText: 'Nom de la campagne'),
                validator: (value) => value == null || value.trim().isEmpty
                    ? 'Le nom est obligatoire.'
                    : null),
            const SizedBox(height: 14),
            TextFormField(
                controller: _descriptionController,
                minLines: 4,
                maxLines: 6,
                decoration: const InputDecoration(labelText: 'Description'),
                validator: (value) => value == null || value.trim().isEmpty
                    ? 'La description est obligatoire.'
                    : null),
            const SizedBox(height: 14),
            TextFormField(
                controller: _priceController,
                keyboardType: TextInputType.number,
                decoration:
                    const InputDecoration(labelText: 'Prix par vote (FCFA)'),
                validator: (value) {
                  final price = int.tryParse(value ?? '');
                  return price == null || price < 50
                      ? 'Minimum : 50 FCFA.'
                      : null;
                }),
            const SizedBox(height: 14),
            TextFormField(
                controller: _dateController,
                decoration: const InputDecoration(
                    labelText: 'Clôture (YYYY-MM-DD HH:MM:SS)',
                    hintText: 'Optionnel')),
            const SizedBox(height: 28),
            SizedBox(
                height: 48,
                child: ElevatedButton.icon(
                    onPressed: _saving ? null : _submit,
                    icon: _saving
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(
                                strokeWidth: 2, color: Colors.white))
                        : const Icon(Icons.save_outlined),
                    label:
                        Text(_saving ? 'Création...' : 'Créer le brouillon'))),
          ],
        ),
      ),
    );
  }
}
