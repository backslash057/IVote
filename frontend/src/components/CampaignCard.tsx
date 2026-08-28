import { Link } from 'react-router-dom';
import { ArrowUpRight, Clock, Vote } from 'lucide-react';
import type { Campaign } from '../types';
import { formatCompactNumber, formatFCFA } from '../utils/helpers';

export function CampaignCard({ campaign }: { campaign: Campaign }) {
  return (
    <Link
      to={`/campaign/${encodeURIComponent(campaign.id)}`}
      className="group overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-xl transition-all hover:-translate-y-1 hover:border-emerald-500/50"
    >
      <div className="relative h-52 overflow-hidden bg-slate-950">
        <img src={campaign.bannerUrl} alt={campaign.title} className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
        <div className="absolute inset-0 bg-slate-950/45" />
        <span className="absolute left-4 top-4 rounded-full border border-white/20 bg-slate-950/80 px-3 py-1.5 text-[11px] font-semibold text-white">
          {campaign.status === 'active' ? 'Votes en cours' : campaign.status}
        </span>
        <ArrowUpRight className="absolute right-4 top-4 h-5 w-5 text-white transition-transform group-hover:translate-x-1 group-hover:-translate-y-1" />
      </div>
      <div className="space-y-4 p-5">
        <div>
          <p className="mb-1 text-[11px] font-semibold uppercase tracking-wider text-emerald-400">{campaign.category}</p>
          <h2 className="font-display text-xl font-bold text-white">{campaign.title}</h2>
          <p className="mt-2 line-clamp-2 text-xs leading-relaxed text-slate-400">{campaign.subtitle}</p>
        </div>
        <div className="flex items-center justify-between border-t border-slate-800 pt-4 text-xs">
          <span className="flex items-center gap-1.5 text-slate-400"><Vote className="h-4 w-4 text-emerald-400" /> {formatCompactNumber(campaign.totalVotes)} votes</span>
          <span className="flex items-center gap-1.5 text-slate-400"><Clock className="h-4 w-4 text-amber-400" /> {formatFCFA(campaign.pricePerVoteFCFA)}</span>
        </div>
      </div>
    </Link>
  );
}
