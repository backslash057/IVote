import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/campaign_service.dart';
import '../theme.dart';

class CreateCampaignPage extends StatefulWidget {
  const CreateCampaignPage({super.key});

  @override
  State<CreateCampaignPage> createState() => _CreateCampaignPageState();
}

class _CreateCampaignPageState extends State<CreateCampaignPage> {
  final CampaignService _service = CampaignService();
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _priceController = TextEditingController(text: '100');
  DateTime? _closingDate;
  bool _loading = false;

  @override
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    _priceController.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _closingDate ?? now.add(const Duration(days: 7)),
      firstDate: now,
      lastDate: now.add(const Duration(days: 365)),
    );
    if (picked != null) setState(() => _closingDate = picked);
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _loading = true);
    try {
      final price = int.parse(_priceController.text.trim());
      final date = _closingDate == null
          ? null
          : '${_closingDate!.year}-${_closingDate!.month.toString().padLeft(2, '0')}-${_closingDate!.day.toString().padLeft(2, '0')} 23:59:59';
      await _service.createCampaign(
        title: _titleController.text.trim(),
        description: _descriptionController.text.trim(),
        pricePerVote: price,
        dateCloture: date,
      );
      if (!mounted) return;
      Navigator.of(context).pop();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Campagne créée (brouillon). Publiez-la depuis le tableau de bord.')),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message)),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Nouvelle campagne')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextFormField(
                controller: _titleController,
                decoration: const InputDecoration(
                  labelText: 'Titre',
                  hintText: 'Ex. Miss & Master UY1 2026',
                ),
                validator: (v) =>
                    v == null || v.trim().isEmpty ? 'Titre requis' : null,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _descriptionController,
                maxLines: 4,
                decoration: const InputDecoration(
                  labelText: 'Description',
                  hintText: 'Présentez votre scrutin et ses règles.',
                ),
                validator: (v) =>
                    v == null || v.trim().isEmpty ? 'Description requise' : null,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _priceController,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(
                  labelText: 'Prix par vote (FCFA)',
                  prefixIcon: Icon(Icons.monetization_on_outlined),
                ),
                validator: (v) {
                  final p = int.tryParse(v ?? '');
                  if (p == null || p <= 0) return 'Prix invalide';
                  return null;
                },
              ),
              const SizedBox(height: 16),
              InkWell(
                borderRadius: BorderRadius.circular(12),
                onTap: _pickDate,
                child: InputDecorator(
                  decoration: const InputDecoration(
                    labelText: 'Date de clôture (optionnelle)',
                    prefixIcon: Icon(Icons.event_outlined),
                  ),
                  child: Text(
                    _closingDate == null
                        ? 'Non définie'
                        : '${_closingDate!.day}/${_closingDate!.month}/${_closingDate!.year}',
                    style: TextStyle(
                      color:
                          _closingDate == null ? AppColors.textSecondary : Colors.white,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 24),
              ElevatedButton(
                onPressed: _loading ? null : _submit,
                child: _loading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Text('Créer la campagne'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}