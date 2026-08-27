import React from 'react';
import { motion } from 'motion/react';
import { Sparkles, LayoutDashboard, ShieldCheck, Vote, Radio, Lock, LogOut, UserCheck } from 'lucide-react';
import { AppView, UserSession } from '../types';

interface FloatingNavProps {
  currentView: AppView;
  userSession: UserSession;
  onViewChange: (view: AppView) => void;
  onLogout: () => void;
  isSimulatingLive: boolean;
  onToggleSimulation: () => void;
  totalVotesCount: number;
}

export const FloatingNav: React.FC<FloatingNavProps> = ({
  currentView,
  userSession,
  onViewChange,
  onLogout,
  isSimulatingLive,
  onToggleSimulation,
}) => {
  return (
    <header className="fixed top-4 left-0 right-0 z-50 flex justify-center px-2 sm:px-4 pointer-events-none">
      <motion.div
        initial={{ y: -50, opacity: 0 }}
        animate={{ y: 0, opacity: 1 }}
        transition={{ type: 'spring', stiffness: 260, damping: 25 }}
        className="pointer-events-auto flex items-center gap-1 sm:gap-2 p-1.5 sm:p-2 rounded-full border border-white/10 shadow-2xl shadow-emerald-950/40 bg-slate-900/95 backdrop-blur-xl max-w-full overflow-x-auto scrollbar-none"
      >
        {/* Logo / Brand Pill */}
        <button
          onClick={() => onViewChange('public')}
          className="flex items-center gap-2 pl-2 sm:pl-3 pr-2 py-1 text-xs font-bold text-white tracking-wide border-r border-white/10 hover:opacity-90 transition-opacity cursor-pointer shrink-0"
        >
          <div className="w-6 h-6 rounded-lg bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-md shadow-emerald-500/30">
            <Sparkles className="w-3.5 h-3.5 text-slate-950" />
          </div>
          <span className="font-display font-bold text-sm tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent hidden md:inline">
            IVote
          </span>
        </button>

        {/* View Switcher Tabs */}
        <nav aria-label="Main Navigation" className="flex items-center gap-1 shrink-0">
          {/* 1. Vote Public (Toujours accessible) */}
          <button
            onClick={() => onViewChange('public')}
            className={`relative flex items-center gap-1.5 px-2.5 py-1.5 sm:px-3.5 sm:py-2 rounded-full text-xs font-medium transition-colors duration-200 cursor-pointer ${
              currentView === 'public' ? 'text-white' : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
            }`}
          >
            {currentView === 'public' && (
              <motion.div
                layoutId="activeNavTab"
                className="absolute inset-0 rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 border border-emerald-400/30 shadow-lg shadow-emerald-600/25"
                transition={{ type: 'spring', stiffness: 380, damping: 30 }}
              />
            )}
            <span className="relative z-10 flex items-center gap-1.5">
              <Vote className="w-3.5 h-3.5" />
              <span>Vote Public</span>
              <span
                className={`text-[9px] px-1.5 py-0.2 rounded-full font-bold uppercase tracking-wider hidden sm:inline ${
                  currentView === 'public'
                    ? 'bg-white/20 text-white'
                    : 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'
                }`}
              >
                Live
              </span>
            </span>
          </button>

          {/* 2. Espace Organisateur (Gated) */}
          <button
            onClick={() => onViewChange('organizer')}
            className={`relative flex items-center gap-1.5 px-2.5 py-1.5 sm:px-3.5 sm:py-2 rounded-full text-xs font-medium transition-colors duration-200 cursor-pointer ${
              currentView === 'organizer' ? 'text-white' : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
            }`}
          >
            {currentView === 'organizer' && (
              <motion.div
                layoutId="activeNavTab"
                className="absolute inset-0 rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 border border-emerald-400/30 shadow-lg shadow-emerald-600/25"
                transition={{ type: 'spring', stiffness: 380, damping: 30 }}
              />
            )}
            <span className="relative z-10 flex items-center gap-1.5">
              <LayoutDashboard className="w-3.5 h-3.5" />
              <span>Organisateur</span>
              {!userSession.isAuthenticated && (
                <Lock className="w-3 h-3 text-amber-400/80" />
              )}
            </span>
          </button>

          {/* 3. Super Admin (Gated) */}
          <button
            onClick={() => onViewChange('superadmin')}
            className={`relative flex items-center gap-1.5 px-2.5 py-1.5 sm:px-3.5 sm:py-2 rounded-full text-xs font-medium transition-colors duration-200 cursor-pointer ${
              currentView === 'superadmin' ? 'text-white' : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
            }`}
          >
            {currentView === 'superadmin' && (
              <motion.div
                layoutId="activeNavTab"
                className="absolute inset-0 rounded-full bg-gradient-to-r from-amber-600 to-yellow-600 border border-amber-400/30 shadow-lg shadow-amber-600/25"
                transition={{ type: 'spring', stiffness: 380, damping: 30 }}
              />
            )}
            <span className="relative z-10 flex items-center gap-1.5">
              <ShieldCheck className="w-3.5 h-3.5" />
              <span>Super Admin</span>
              {!userSession.isAuthenticated && (
                <Lock className="w-3 h-3 text-amber-400/80" />
              )}
            </span>
          </button>

          {/* 4. Bouton Connexion direct si déconnecté */}
          {!userSession.isAuthenticated ? (
            <button
              onClick={() => onViewChange('login')}
              className={`relative flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-full text-xs font-bold transition-all cursor-pointer ${
                currentView === 'login'
                  ? 'bg-emerald-500 text-slate-950 shadow-md shadow-emerald-500/20'
                  : 'bg-emerald-950/60 hover:bg-emerald-900/80 text-emerald-300 border border-emerald-500/40'
              }`}
            >
              <Lock className="w-3.5 h-3.5" />
              <span>Connexion</span>
            </button>
          ) : (
            /* User Info & Logout Button if logged in */
            <div className="flex items-center gap-1 pl-1 border-l border-white/10">
              <div className="flex items-center gap-1.5 px-2 py-1 rounded-full bg-white/5 text-[11px] text-slate-300">
                <img
                  src={userSession.avatar}
                  alt={userSession.name}
                  className="w-4 h-4 rounded-full object-cover"
                />
                <span className="max-w-[80px] truncate hidden sm:inline font-semibold">
                  {userSession.name.split(' ')[0]}
                </span>
                <span className={`px-1 py-0.2 rounded text-[9px] font-bold uppercase ${
                  userSession.role === 'superadmin' ? 'bg-amber-500/20 text-amber-300' : 'bg-emerald-500/20 text-emerald-300'
                }`}>
                  {userSession.role === 'superadmin' ? 'Admin' : 'Org'}
                </span>
              </div>
              <button
                onClick={onLogout}
                title="Se déconnecter"
                className="p-1.5 rounded-full hover:bg-rose-950/40 text-slate-400 hover:text-rose-400 transition-colors cursor-pointer"
              >
                <LogOut className="w-3.5 h-3.5" />
              </button>
            </div>
          )}
        </nav>

        {/* Live Simulator Toggle */}
        <div className="flex items-center pl-1 sm:pl-2 pr-1 border-l border-white/10 shrink-0">
          <button
            onClick={onToggleSimulation}
            title={isSimulatingLive ? 'Simulation des votes en direct ACTIVE' : 'Activer la simulation du flux de votes'}
            className={`flex items-center gap-1.5 px-2 py-1.5 sm:px-2.5 rounded-full text-xs font-medium transition-all cursor-pointer ${
              isSimulatingLive
                ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 shadow-sm shadow-emerald-500/20'
                : 'text-slate-400 hover:text-slate-200 hover:bg-white/5 border border-transparent'
            }`}
          >
            <Radio
              className={`w-3.5 h-3.5 ${
                isSimulatingLive ? 'text-emerald-400 animate-pulse' : 'text-slate-500'
              }`}
            />
            <span className="hidden lg:inline text-[11px]">
              {isSimulatingLive ? 'Live' : 'Simuler'}
            </span>
          </button>
        </div>
      </motion.div>
    </header>
  );
};
