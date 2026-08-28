import React from 'react';
import { Sparkles } from 'lucide-react';
import { Link } from 'react-router-dom';

export const Navbar: React.FC = () => {
  return (
    <header className="fixed top-0 left-0 right-0 z-40 bg-slate-950/90 backdrop-blur-xl border-b border-slate-800 transition-all">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        
        {/* Left: Brand Identity */}
        <div className="flex items-center gap-3 sm:gap-6">
          <Link
            to="/"
            className="flex items-center gap-2.5 text-left group cursor-pointer"
          >
            <div className="w-8 h-8 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-600/30 group-hover:scale-105 transition-transform">
              <Sparkles className="w-4 h-4" />
            </div>
            <div>
              <span className="text-base font-bold font-display tracking-tight text-white flex items-center gap-1.5">
                IVote
              </span>
              <span className="text-[10px] text-slate-400 hidden sm:block">Vote & Paiement Mobile Money</span>
            </div>
          </Link>

        </div>

        {/* Right Area: Clean spacing */}
        <div className="flex items-center gap-2">
          {/* Header right intentionally clean */}
        </div>
      </div>
    </header>
  );
};
