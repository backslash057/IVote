import React from 'react';
import { 
  Sparkles, ChevronDown
} from 'lucide-react';
import { AppView, Campaign, UserSession } from '../types';

interface NavbarProps {
  currentView: AppView;
  userSession: UserSession;
  campaigns: Campaign[];
  activeCampaignId: string;
  onSelectCampaign: (id: string) => void;
  onNavigate: (view: AppView) => void;
  onLogout?: () => void;
  isSimulatingLive?: boolean;
  onToggleSimulation?: () => void;
  totalVotesCount?: number;
}

export const Navbar: React.FC<NavbarProps> = ({
  currentView,
  userSession,
  campaigns,
  activeCampaignId,
  onSelectCampaign,
  onNavigate,
}) => {
  const activeCampaign = campaigns.find((c) => c.id === activeCampaignId) || campaigns[0];

  return (
    <header className="fixed top-0 left-0 right-0 z-40 bg-slate-950/90 backdrop-blur-xl border-b border-slate-800 transition-all">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        
        {/* Left: Brand Identity & Active Campaign Selector */}
        <div className="flex items-center gap-3 sm:gap-6">
          <button
            onClick={() => onNavigate('public')}
            className="flex items-center gap-2.5 text-left group cursor-pointer"
          >
            <div className="w-8 h-8 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-600/30 group-hover:scale-105 transition-transform">
              <Sparkles className="w-4 h-4" />
            </div>
            <div>
              <span className="text-base font-bold font-display tracking-tight text-white flex items-center gap-1.5">
                IVote <span className="text-[10px] uppercase font-mono px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">Official</span>
              </span>
              <span className="text-[10px] text-slate-400 hidden sm:block">Vote & Paiement Mobile Money</span>
            </div>
          </button>

          {/* Campaign Selector Dropdown (Public view & Organizer view) */}
          {campaigns.length > 1 && currentView !== 'login' && (
            <div className="relative hidden md:flex items-center">
              <select
                value={activeCampaignId}
                onChange={(e) => onSelectCampaign(e.target.value)}
                aria-label="Sélectionner une campagne de vote"
                className="text-xs font-semibold bg-slate-900 text-slate-200 border border-slate-800 rounded-xl px-3 py-1.5 pr-8 appearance-none focus:outline-none focus:border-emerald-500 cursor-pointer hover:border-slate-700"
              >
                {campaigns.map((camp) => (
                  <option key={camp.id} value={camp.id}>
                    {camp.title} ({camp.status === 'active' ? 'En cours' : camp.status})
                  </option>
                ))}
              </select>
              <ChevronDown className="w-3.5 h-3.5 text-slate-400 absolute right-2.5 pointer-events-none" />
            </div>
          )}
        </div>

        {/* Right Area: Clean spacing */}
        <div className="flex items-center gap-2">
          {/* Header right intentionally clean */}
        </div>
      </div>
    </header>
  );
};
