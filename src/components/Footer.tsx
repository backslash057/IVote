import React from 'react';
import { Sparkles, ShieldCheck, Smartphone, Lock, Heart, CheckCircle2, Globe, Mail, Phone } from 'lucide-react';

interface FooterProps {
  onNavigateToLogin?: (role?: 'organizer' | 'superadmin') => void;
}

export const Footer: React.FC<FooterProps> = ({ onNavigateToLogin }) => {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="mt-20 border-t border-slate-800/80 bg-slate-950 text-slate-400">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
          
          {/* Col 1: Brand & ARITeD Presentation */}
          <div className="space-y-4 md:col-span-2">
            <div className="flex items-center gap-2.5">
              <div className="w-8 h-8 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold shadow-md shadow-emerald-600/30">
                <Sparkles className="w-4 h-4" />
              </div>
              <span className="text-lg font-bold font-display text-white tracking-tight">
                IVote
              </span>
              <span className="text-[10px] font-mono uppercase px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                Propulsé par ARITeD
              </span>
            </div>

            <p className="text-xs text-slate-400 leading-relaxed max-w-md">
              Plateforme technologique de vote électronique sécurisé et de monétisation pour concours, élections étudiantes et cérémonies de récompenses en Afrique.
            </p>

            <div className="p-3.5 rounded-2xl bg-slate-900 border border-slate-800/80 text-xs space-y-1.5 max-w-md">
              <div className="flex items-center gap-2 text-slate-200 font-semibold">
                <span className="w-2 h-2 rounded-full bg-emerald-400" />
                <span>Ingénierie & Technologie <strong>ARITeD</strong></span>
              </div>
              <p className="text-[11px] text-slate-400 leading-normal">
                Développé et opéré sous la supervision technologique d'<strong>ARITeD</strong>, garantissant l'intégrité des suffrages et la conformité des flux Mobile Money.
              </p>
            </div>
          </div>

          {/* Col 2: Paiements & Sécurité */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-200">
              Paiements Supportés
            </h4>
            <ul className="space-y-2 text-xs">
              <li className="flex items-center gap-2 text-slate-300">
                <span className="w-2 h-2 rounded-full bg-orange-500" />
                Orange Money (#150#)
              </li>
              <li className="flex items-center gap-2 text-slate-300">
                <span className="w-2 h-2 rounded-full bg-amber-400" />
                MTN Mobile Money (*126#)
              </li>
              <li className="flex items-center gap-2 text-slate-400">
                <ShieldCheck className="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                Validation push USSD chiffrée
              </li>
              <li className="flex items-center gap-2 text-slate-400">
                <Lock className="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                Traçabilité & Reçus infalsifiables
              </li>
            </ul>
          </div>

          {/* Col 3: Assistance & Contact */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-200">
              Support & Organisation
            </h4>
            <div className="space-y-2 text-xs text-slate-400">
              <div className="flex items-center gap-2">
                <Mail className="w-3.5 h-3.5 text-slate-400" />
                <span>support@arited.org</span>
              </div>
              <div className="flex items-center gap-2">
                <Phone className="w-3.5 h-3.5 text-slate-400" />
                <span>+237 677 00 22 11 / 699 00 11 22</span>
              </div>
              <div className="pt-2">
                {onNavigateToLogin && (
                  <button
                    onClick={() => onNavigateToLogin('organizer')}
                    className="text-xs text-emerald-400 hover:text-emerald-300 font-semibold flex items-center gap-1 cursor-pointer transition-colors"
                  >
                    <span>Portail Organisateur &rarr;</span>
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="pt-8 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
          <div className="flex items-center gap-2 flex-wrap text-center sm:text-left">
            <span>&copy; {currentYear} <strong>IVote</strong>. Tous droits réservés.</span>
            <span className="hidden sm:inline text-slate-700">•</span>
            <span>Une réalisation propulsée par <strong>ARITeD</strong>.</span>
          </div>

          <div className="flex items-center gap-4 text-slate-400">
            <span className="hover:text-slate-300 transition-colors">Sécurité SSL 256-bit</span>
            <span>•</span>
            <span className="hover:text-slate-300 transition-colors">Conformité RGPD & Télécom</span>
          </div>
        </div>
      </div>
    </footer>
  );
};
