import { Link } from 'react-router-dom';
import { ArrowRight, ShieldCheck, Sparkles, Vote } from 'lucide-react';
import { Footer } from '../components/Footer';
import { CampaignCard } from '../components/CampaignCard';
import { useIVote } from '../state/IVoteContext';

export function HomePage() {
  const { campaigns } = useIVote();
  const sortedCampaigns = [...campaigns].sort((a, b) => b.totalVotes - a.totalVotes);

  return (
    <div className="min-h-screen pb-16 pt-24">
      <section className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 px-6 py-12 shadow-2xl sm:px-10 lg:px-16 lg:py-16">
          <div className="absolute right-0 top-0 h-full w-1/2 bg-emerald-500/5" />
          <div className="relative max-w-2xl">
            <div className="mb-5 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-400">
              <Sparkles className="h-4 w-4" /> Le vote qui vous rassemble
            </div>
            <h1 className="font-display text-4xl font-bold leading-tight text-white sm:text-6xl">Découvrez les campagnes qui font vibrer le public.</h1>
            <p className="mt-5 max-w-xl text-sm leading-relaxed text-slate-300 sm:text-base">Votez pour vos favoris, soutenez les talents émergents et suivez les résultats en temps réel sur IVote.</p>
            <div className="mt-8 flex flex-wrap gap-3">
              <a href="#campaigns" className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-xs font-bold text-white shadow-lg shadow-emerald-600/20 transition-colors hover:bg-emerald-500">Explorer les campagnes <ArrowRight className="h-4 w-4" /></a>
              <Link to="/login" className="inline-flex items-center gap-2 rounded-xl border border-slate-700 px-5 py-3 text-xs font-bold text-slate-200 transition-colors hover:border-emerald-500 hover:text-white">Organiser une campagne</Link>
            </div>
          </div>
        </div>
      </section>

      <section id="campaigns" className="mx-auto max-w-7xl px-4 pt-16 sm:px-6 lg:px-8">
        <div className="mb-8 flex items-end justify-between gap-4">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wider text-emerald-400">À l'affiche</p>
            <h2 className="mt-2 font-display text-3xl font-bold text-white">Campagnes populaires</h2>
            <p className="mt-2 text-sm text-slate-400">Les votes les plus suivis du moment.</p>
          </div>
          <span className="hidden items-center gap-2 text-xs text-slate-500 sm:flex"><Vote className="h-4 w-4 text-emerald-400" /> Classement en direct</span>
        </div>
        {sortedCampaigns.length > 0 ? (
          <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            {sortedCampaigns.map((campaign) => <CampaignCard key={campaign.id} campaign={campaign} />)}
          </div>
        ) : (
          <div className="border-y border-slate-800 py-16 text-center text-sm text-slate-400">Aucune campagne disponible pour le moment.</div>
        )}
      </section>

      <section className="mx-auto max-w-7xl px-4 pt-16 sm:px-6 lg:px-8">
        <div className="flex flex-col items-start justify-between gap-6 rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:flex-row sm:items-center sm:p-8">
          <div className="flex items-start gap-4">
            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600/15 text-emerald-400"><ShieldCheck className="h-5 w-5" /></div>
            <div><h2 className="text-base font-bold text-white">Vous organisez un concours ou une élection ?</h2><p className="mt-1 max-w-xl text-xs leading-relaxed text-slate-400">Créez votre campagne de vote monétisée et suivez les paiements en direct.</p></div>
          </div>
          <Link to="/login" className="inline-flex shrink-0 items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-xs font-bold text-white transition-colors hover:bg-emerald-500">Espace organisateur <ArrowRight className="h-4 w-4" /></Link>
        </div>
      </section>
      <Footer />
    </div>
  );
}
