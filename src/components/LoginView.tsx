import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  ShieldCheck, Lock, Mail, Building, ArrowLeft, CheckCircle2, 
  Sparkles, UserCheck, ArrowRight, Smartphone, Eye, EyeOff, ShieldAlert
} from 'lucide-react';
import { UserRole, UserSession } from '../types';
import { INITIAL_ORGANIZERS } from '../mockData';

interface LoginViewProps {
  initialRole?: 'organizer' | 'superadmin';
  onLoginSuccess: (session: UserSession) => void;
  onBackToPublic: () => void;
  onShowToast: (title: string, desc: string, type?: 'success' | 'info' | 'warning' | 'error') => void;
}

export const LoginView: React.FC<LoginViewProps> = ({
  initialRole = 'organizer',
  onLoginSuccess,
  onBackToPublic,
  onShowToast,
}) => {
  const [activeTab, setActiveTab] = useState<'organizer' | 'superadmin'>(initialRole);
  const [isRegisterMode, setIsRegisterMode] = useState<boolean>(false);
  const [showPassword, setShowPassword] = useState<boolean>(false);

  React.useEffect(() => {
    if (initialRole) {
      setActiveTab(initialRole);
    }
  }, [initialRole]);

  // Form Fields
  const [email, setEmail] = useState<string>('contact@uy1-events.cm');
  const [password, setPassword] = useState<string>('••••••••••••');
  const [name, setName] = useState<string>('Pr. Marcelle Ebongue');
  const [organization, setOrganization] = useState<string>('Comité Miss & Master UY1');
  const [phone, setPhone] = useState<string>('+237 677 00 22 11');

  // Quick 1-Click Demo Login
  const handleQuickDemoLogin = (role: 'organizer' | 'superadmin') => {
    if (role === 'organizer') {
      const demoOrg = INITIAL_ORGANIZERS[0];
      const session: UserSession = {
        isAuthenticated: true,
        role: 'organizer',
        name: demoOrg.name,
        email: demoOrg.email,
        organizationName: demoOrg.organization,
        organizerId: demoOrg.id,
        avatar: demoOrg.avatar,
      };
      onLoginSuccess(session);
      onShowToast(
        'Connexion Organisateur Réussie !',
        `Bienvenue sur votre tableau de bord, ${demoOrg.name} (${demoOrg.organization}).`
      );
    } else {
      const session: UserSession = {
        isAuthenticated: true,
        role: 'superadmin',
        name: 'Direction Générale IVote',
        email: 'superadmin@ivote.africa',
        organizationName: 'IVote Global Africa SAS',
        avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
      };
      onLoginSuccess(session);
      onShowToast(
        'Session Super Admin Activée',
        'Accès complet aux opérations de gouvernance et déblocage de fonds.',
        'info'
      );
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (activeTab === 'organizer') {
      const session: UserSession = {
        isAuthenticated: true,
        role: 'organizer',
        name: name || 'Organisateur IVote',
        email: email || 'organizer@ivote.africa',
        organizationName: organization || 'Mon Organisation',
        organizerId: 'org-1',
        avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
      };
      onLoginSuccess(session);
      onShowToast(
        isRegisterMode ? 'Compte Organisateur Créé !' : 'Connexion Réussie !',
        `Bienvenue dans votre espace de gestion d'événements, ${session.name}.`
      );
    } else {
      const session: UserSession = {
        isAuthenticated: true,
        role: 'superadmin',
        name: 'Direction Générale IVote',
        email: email || 'superadmin@ivote.africa',
        organizationName: 'IVote SAS',
        avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
      };
      onLoginSuccess(session);
      onShowToast('Connexion Super Admin Réussie', 'Session d\'administration globale active.');
    }
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center px-4 py-12 relative overflow-hidden">
      {/* Background glow effects with refined Emerald & Amber tones */}
      <div className="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-emerald-500/10 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute bottom-0 right-10 w-[500px] h-[300px] bg-amber-500/10 rounded-full blur-3xl pointer-events-none" />

      {/* Return to Public Voting */}
      <div className="w-full max-w-md mb-6 flex items-center justify-between z-10">
        <button
          onClick={onBackToPublic}
          className="flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-emerald-400 transition-colors cursor-pointer bg-slate-900/80 px-3.5 py-2 rounded-xl border border-slate-800 hover:border-emerald-500/40"
        >
          <ArrowLeft className="w-4 h-4" />
          Retour à la page de vote (Grand Public)
        </button>

        <span className="text-[11px] font-mono text-emerald-400 flex items-center gap-1.5">
          <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
          Portail Sécurisé SSL
        </span>
      </div>

      {/* Main Authentication Card */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.35 }}
        className="w-full max-w-md bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl relative z-10"
      >
        {/* Brand Header */}
        <div className="text-center mb-6">
          <div className="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/25 mb-3">
            <Sparkles className="w-6 h-6" />
          </div>
          <h1 className="text-2xl font-bold font-display text-white tracking-tight">
            Espace d'Administration <span className="text-emerald-400">IVote</span>
          </h1>
          <p className="text-xs text-slate-400 mt-1">
            Plateforme sécurisée de gestion des votes monétisés & retraits Mobile Money
          </p>
        </div>

        {/* Role Switcher Tabs */}
        <div className="flex p-1 rounded-2xl bg-slate-950 border border-slate-800 mb-6">
          <button
            type="button"
            onClick={() => {
              setActiveTab('organizer');
              setEmail('contact@uy1-events.cm');
            }}
            className={`flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'organizer'
                ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30'
                : 'text-slate-400 hover:text-slate-200'
            }`}
          >
            <Building className="w-4 h-4" />
            Organisateur (Client)
          </button>
          <button
            type="button"
            onClick={() => {
              setActiveTab('superadmin');
              setIsRegisterMode(false);
              setEmail('superadmin@ivote.africa');
            }}
            className={`flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'superadmin'
                ? 'bg-amber-600 text-white shadow-md shadow-amber-600/30'
                : 'text-slate-400 hover:text-slate-200'
            }`}
          >
            <ShieldCheck className="w-4 h-4" />
            Super Admin (IVote)
          </button>
        </div>

        {/* Quick Demo 1-Click Banner */}
        <div className="p-3.5 rounded-2xl bg-emerald-950/40 border border-emerald-500/30 mb-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2 text-xs font-bold text-emerald-300">
              <UserCheck className="w-4 h-4 text-emerald-400" />
              <span>Mode Démo 1-Clic</span>
            </div>
            <span className="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-semibold uppercase">
              Instantané
            </span>
          </div>
          <p className="text-[11px] text-slate-300 mt-1 mb-2.5">
            {activeTab === 'organizer'
              ? 'Accédez directement au tableau de bord du Comité Miss & Master UY1.'
              : 'Accédez au panneau de contrôle de supervision générale de la plateforme.'}
          </p>
          <button
            type="button"
            onClick={() => handleQuickDemoLogin(activeTab)}
            className={`w-full py-2.5 rounded-xl text-xs font-extrabold flex items-center justify-center gap-2 shadow-lg transition-transform active:scale-[0.98] cursor-pointer ${
              activeTab === 'organizer'
                ? 'bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black shadow-emerald-500/20'
                : 'bg-amber-500 hover:bg-amber-400 text-slate-950 font-black shadow-amber-500/20'
            }`}
          >
            <span>Connexion Démo {activeTab === 'organizer' ? 'Organisateur' : 'Super Admin'}</span>
            <ArrowRight className="w-4 h-4" />
          </button>
        </div>

        <div className="relative flex items-center justify-center my-4">
          <div className="border-t border-slate-800 w-full" />
          <span className="bg-slate-900 px-3 text-[11px] uppercase tracking-wider text-slate-500 font-semibold absolute">
            Ou formulaire manuel
          </span>
        </div>

        {/* Credentials Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          {activeTab === 'organizer' && isRegisterMode && (
            <>
              <div>
                <label className="block text-xs font-semibold text-slate-400 mb-1">
                  Nom du Responsable
                </label>
                <input
                  type="text"
                  required
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="Ex: Pr. Marcelle Ebongue"
                  className="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-slate-400 mb-1">
                  Nom de l'Organisation / Comité
                </label>
                <input
                  type="text"
                  required
                  value={organization}
                  onChange={(e) => setOrganization(e.target.value)}
                  placeholder="Ex: Comité Miss & Master UY1"
                  className="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-slate-400 mb-1">
                  Numéro de Téléphone (Mobile Money)
                </label>
                <input
                  type="text"
                  required
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  placeholder="+237 6XX XX XX XX"
                  className="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                />
              </div>
            </>
          )}

          <div>
            <label className="block text-xs font-semibold text-slate-400 mb-1">
              Adresse Email Professionnelle
            </label>
            <div className="relative">
              <Mail className="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder={activeTab === 'organizer' ? 'contact@uy1-events.cm' : 'superadmin@ivote.africa'}
                className="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-400 mb-1">
              Mot de passe
            </label>
            <div className="relative">
              <Lock className="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Votre mot de passe sécurisé"
                className="w-full pl-10 pr-10 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300"
              >
                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>
          </div>

          <button
            type="submit"
            className="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 transition-all cursor-pointer flex items-center justify-center gap-2 mt-2"
          >
            <Lock className="w-4 h-4" />
            {isRegisterMode ? 'Créer mon Espace Organisateur' : 'Se Connecter'}
          </button>
        </form>

        {/* Toggle Register / Login for Organizers */}
        {activeTab === 'organizer' && (
          <div className="text-center mt-4 pt-4 border-t border-slate-800/80">
            <button
              type="button"
              onClick={() => setIsRegisterMode(!isRegisterMode)}
              className="text-xs text-slate-400 hover:text-emerald-400 transition-colors cursor-pointer"
            >
              {isRegisterMode
                ? 'Vous avez déjà un compte ? Connectez-vous'
                : 'Nouveau créateur d\'événement ? Créez un compte organisateur'}
            </button>
          </div>
        )}
      </motion.div>
    </div>
  );
};
