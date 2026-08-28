import React from 'react';
import { Sparkles, Mail, Phone } from 'lucide-react';
import { Link } from 'react-router-dom';

export const Footer: React.FC = () => {
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
              Plateforme de vote électronique sécurisé et de monétisation pour concours, élections étudiantes et cérémonies de récompenses.
            </p>
          </div>

          {/* Col 2: Paiements & Sécurité */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-200">
              Paiements Supportés
            </h4>
            <ul className="space-y-2 text-xs">
              <li className="flex items-center gap-2 text-slate-300">
                <span className="w-2 h-2 rounded-full bg-orange-500" />
                Orange Money
              </li>
              <li className="flex items-center gap-2 text-slate-300">
                <span className="w-2 h-2 rounded-full bg-amber-400" />
                MTN Mobile Money
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
                <span>+237 656 50 55 98</span>
              </div>
            </div>
            <Link to="/login" className="inline-flex text-xs font-semibold text-emerald-400 hover:text-emerald-300">
              Espace organisateur
            </Link>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="pt-3 border-t border-slate-800/80 text-sm sm:text-left">
          <span>&copy; {currentYear} <strong>IVote</strong>. Tous droits réservés.</span>
        </div>
      </div>
    </footer>
  );
};
