import 'package:flutter/material.dart';
import '../../services/auth_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/ivote_logo.dart';
import 'signup_page.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  bool _isLoading = false;
  String? _errorMessage;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final res = await AuthService().login(
      _emailController.text.trim(),
      _passwordController.text,
    );

    if (!mounted) return;

    setState(() => _isLoading = false);

    if (res['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Row(
            children: [
              const Icon(Icons.check_circle_rounded, color: Colors.white, size: 20),
              const SizedBox(width: 8),
              Text(res['message'] ?? 'Connecté avec succès'),
            ],
          ),
          backgroundColor: AppColors.greenAccent,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      );
      Navigator.of(context).pop(true);
    } else {
      setState(() {
        _errorMessage = res['message'] ?? 'Email ou mot de passe incorrect.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            // Custom Top Bar (Retour + Theme Toggle)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  TextButton.icon(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: Icon(Icons.arrow_back_rounded, size: 18, color: theme.colorScheme.onSurfaceVariant),
                    label: Text(
                      'Retour',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ),
                  IconButton(
                    onPressed: () => ThemeController.toggleTheme(),
                    icon: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: theme.colorScheme.outline),
                      ),
                      child: Icon(
                        ThemeController.isDarkMode
                            ? Icons.wb_sunny_outlined
                            : Icons.nightlight_round_outlined,
                        size: 18,
                        color: theme.colorScheme.onSurface,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Formulaire Centré Verticalement
            Expanded(
              child: Center(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(24.0),
                  child: Container(
                    padding: const EdgeInsets.all(28.0),
                    decoration: BoxDecoration(
                      color: theme.colorScheme.surfaceContainerHighest,
                      borderRadius: BorderRadius.circular(28),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.04),
                          blurRadius: 20,
                          offset: const Offset(0, 8),
                        )
                      ],
                    ),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          // Logo IVote
                          const Center(child: IvLogo(size: 64)),
                          const SizedBox(height: 16),
                          Text(
                            'Espace Organisateur',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.w800,
                              color: theme.colorScheme.onSurface,
                              letterSpacing: -0.5,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            'Connectez-vous pour piloter vos campagnes et retraits',
                            textAlign: TextAlign.center,
                            style: TextStyle(fontSize: 12, color: theme.colorScheme.onSurfaceVariant),
                          ),
                          const SizedBox(height: 24),

                          // Message d'erreur
                          if (_errorMessage != null) ...[
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: AppColors.redAccent.withOpacity(0.1),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: AppColors.redAccent.withOpacity(0.3)),
                              ),
                              child: Text(
                                _errorMessage!,
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  color: AppColors.redAccent,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                            const SizedBox(height: 16),
                          ],

                          // Email
                          Text(
                            'Adresse e-mail',
                            style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface),
                          ),
                          const SizedBox(height: 6),
                          TextFormField(
                            controller: _emailController,
                            keyboardType: TextInputType.emailAddress,
                            style: TextStyle(fontSize: 13, color: theme.colorScheme.onSurface),
                            decoration: InputDecoration(
                              filled: true,
                              fillColor: theme.colorScheme.surface,
                              border: InputBorder.none,
                              enabledBorder: InputBorder.none,
                              focusedBorder: InputBorder.none,
                              prefixIcon: Icon(Icons.alternate_email_rounded, size: 18, color: theme.colorScheme.onSurfaceVariant),
                              hintText: 'nom@domaine.com',
                              hintStyle: TextStyle(fontSize: 13, color: theme.colorScheme.onSurfaceVariant.withOpacity(0.5)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                            ),
                            validator: (v) => (v == null || !v.contains('@')) ? 'Email invalide' : null,
                          ),
                          const SizedBox(height: 16),

                          // Mot de passe
                          Text(
                            'Mot de passe',
                            style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface),
                          ),
                          const SizedBox(height: 6),
                          TextFormField(
                            controller: _passwordController,
                            obscureText: true,
                            style: TextStyle(fontSize: 13, color: theme.colorScheme.onSurface),
                            decoration: InputDecoration(
                              filled: true,
                              fillColor: theme.colorScheme.surface,
                              border: InputBorder.none,
                              enabledBorder: InputBorder.none,
                              focusedBorder: InputBorder.none,
                              prefixIcon: Icon(Icons.lock_outline_rounded, size: 18, color: theme.colorScheme.onSurfaceVariant),
                              hintText: '••••••••••••',
                              hintStyle: TextStyle(fontSize: 13, color: theme.colorScheme.onSurfaceVariant.withOpacity(0.5)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                            ),
                            validator: (v) => (v == null || v.length < 4) ? 'Mot de passe trop court' : null,
                          ),
                          const SizedBox(height: 24),

                          // Bouton Se Connecter
                          SizedBox(
                            height: 46,
                            child: ElevatedButton(
                              onPressed: _isLoading ? null : _handleLogin,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: theme.colorScheme.primary,
                                foregroundColor: Colors.white,
                                elevation: 0,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                              ),
                              child: _isLoading
                                  ? const SizedBox(
                                      width: 20,
                                      height: 20,
                                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                    )
                                  : const Row(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        Icon(Icons.login_rounded, size: 18),
                                        SizedBox(width: 8),
                                        Text('Se Connecter', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                                      ],
                                    ),
                            ),
                          ),
                          const SizedBox(height: 20),

                          // Footer vers Inscription
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                'Nouvel organisateur ?',
                                style: TextStyle(fontSize: 12, color: theme.colorScheme.onSurfaceVariant),
                              ),
                              TextButton(
                                onPressed: () {
                                  Navigator.of(context).pushReplacement(
                                    MaterialPageRoute(builder: (_) => const SignupPage()),
                                  );
                                },
                                child: Text(
                                  'Créer un compte',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                    color: theme.colorScheme.primary,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}