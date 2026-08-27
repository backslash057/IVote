import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Sparkles, Trophy, Clock, Search, 
  Share2, ArrowRight, ShieldCheck, 
  CheckCircle2, AlertCircle, Smartphone, Award,
  ExternalLink, Zap, Check, Filter, Tag, Layers
} from 'lucide-react';
import { Campaign, Candidate } from '../types';
import { formatFCFA } from '../utils/helpers';
import { Footer } from './Footer';

interface PublicVotingViewProps {
  campaign: Campaign;
  onOpenVote: (candidate: Candidate) => void;
  onOpenBio: (candidate: Candidate) => void;
  onOpenShare: (candidate: Candidate) => void;
  onOpenQRScanner?: () => void;
  onNavigateToLogin?: (targetRole?: 'organizer' | 'superadmin') => void;
}

export const PublicVotingView: React.FC<PublicVotingViewProps> = ({
  campaign,
  onOpenVote,
  onOpenBio,
  onOpenShare,
  onOpenQRScanner,
  onNavigateToLogin,
}) => {
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [timeLeft, setTimeLeft] = useState({ days: 7, hours: 14, mins: 32, secs: 45 });

  // Countdown timer simulation
  useEffect(() => {
    const timer = setInterval(() => {
      setTimeLeft((prev) => {
        if (prev.secs > 0) return { ...prev, secs: prev.secs - 1 };
        if (prev.mins > 0) return { ...prev, mins: 59, secs: 59 };
        if (prev.hours > 0) return { ...prev, hours: prev.hours - 1, mins: 59, secs: 59 };
        if (prev.days > 0) return { ...prev, days: prev.days - 1, hours: 23, mins: 59, secs: 59 };
        return prev;
      });
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  // Dynamic categories from campaign or fallback
  const availableCategories = campaign.categories && campaign.categories.length > 0
    ? campaign.categories
    : Array.from(new Set(campaign.candidates.map((c) => c.category)));

  // Categories to display based on selected filter
  const displayedCategories = selectedCategory === 'all'
    ? availableCategories
    : availableCategories.filter(
        (cat) => cat.toLowerCase() === selectedCategory.toLowerCase()
      );

  // Helper to filter candidates within a specific category
  const getCategoryCandidates = (categoryName: string) => {
    return campaign.candidates.filter((cand) => {
      const matchesCat = cand.category.toLowerCase() === categoryName.toLowerCase();
      const matchesSearch =
        searchQuery.trim() === '' ||
        cand.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        cand.faculty.toLowerCase().includes(searchQuery.toLowerCase()) ||
        cand.number.toString().includes(searchQuery.trim());
      return matchesCat && matchesSearch;
    });
  };

  // Check if any candidate exists across all displayed categories for search state
  const totalMatchingCandidates = displayedCategories.reduce(
    (sum, cat) => sum + getCategoryCandidates(cat).length,
    0
  );

  return (
    <div className="min-h-screen pb-28 pt-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
      
      {/* 1. HERO BANNER & CAMPAIGN COUNTDOWN - Generous top margin & visible background image */}
      <section className="relative rounded-3xl overflow-hidden border border-slate-800 bg-slate-900 shadow-2xl mb-12 min-h-[480px] sm:min-h-[540px] flex flex-col justify-between">
        {/* Background Image with increased visibility */}
        <div className="absolute inset-0 z-0">
          <img
            src={campaign.bannerUrl}
            alt={campaign.title}
            className="w-full h-full object-cover object-center opacity-45 filter saturate-125"
          />
          {/* Solid color overlay for readability without gradients */}
          <div className="absolute inset-0 bg-slate-950/60" />
        </div>

        {/* Top spacer / margin to reveal and emphasize the background banner */}
        <div className="relative z-10 p-6 sm:p-8 flex items-center justify-end">
          <span className="text-xs text-slate-300 font-mono bg-slate-900/90 px-3 py-1.5 rounded-full border border-slate-700 backdrop-blur-md">
            Prix unitaire : <strong className="text-emerald-400">{campaign.pricePerVoteFCFA} FCFA / vote</strong>
          </span>
        </div>

        {/* Content container with generous top margin for background visibility */}
        <div className="relative z-10 p-6 sm:p-10 lg:p-12 mt-28 sm:mt-36 lg:mt-44 bg-slate-950/80 backdrop-blur-md border-t border-slate-800/80 flex flex-col lg:flex-row lg:items-end justify-between gap-8">
          <div className="max-w-2xl space-y-3">
            <h1 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold font-display text-white tracking-tight leading-tight">
              {campaign.title}
            </h1>

            <p className="text-sm sm:text-base text-slate-300 leading-relaxed font-normal">
              {campaign.subtitle}
            </p>

            <div className="flex flex-wrap items-center gap-4 text-xs text-slate-300 pt-2">
              <div className="flex items-center gap-1.5 font-medium">
                <span className="text-slate-400">Organisé par :</span>
                <span className="text-emerald-400 font-bold">{campaign.organization}</span>
              </div>
              <div className="flex items-center gap-1.5">
                <ShieldCheck className="w-4 h-4 text-emerald-400" />
                <span>Paiements instantanés via <strong>Orange Money & MTN MoMo</strong></span>
              </div>
            </div>
          </div>

          {/* Right Column: Live Countdown Box & Key Stats */}
          <div className="flex flex-col sm:flex-row lg:flex-col gap-4 shrink-0">
            {/* Countdown Clock */}
            <div className="p-4 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
              <div className="flex items-center justify-between gap-2 text-xs font-semibold text-slate-400 mb-2">
                <span className="flex items-center gap-1 text-amber-400">
                  <Clock className="w-3.5 h-3.5" /> Clôture des votes dans
                </span>
              </div>

              <div className="grid grid-cols-4 gap-2 text-center font-mono">
                <div className="p-2 rounded-xl bg-slate-950 border border-slate-800">
                  <div className="text-xl font-bold text-white">{timeLeft.days}</div>
                  <div className="text-[9px] uppercase text-slate-400">Jours</div>
                </div>
                <div className="p-2 rounded-xl bg-slate-950 border border-slate-800">
                  <div className="text-xl font-bold text-white">{String(timeLeft.hours).padStart(2, '0')}</div>
                  <div className="text-[9px] uppercase text-slate-400">Heures</div>
                </div>
                <div className="p-2 rounded-xl bg-slate-950 border border-slate-800">
                  <div className="text-xl font-bold text-white">{String(timeLeft.mins).padStart(2, '0')}</div>
                  <div className="text-[9px] uppercase text-slate-400">Mins</div>
                </div>
                <div className="p-2 rounded-xl bg-slate-950 border border-slate-800">
                  <div className="text-xl font-bold text-emerald-400">{String(timeLeft.secs).padStart(2, '0')}</div>
                  <div className="text-[9px] uppercase text-slate-400">Secs</div>
                </div>
              </div>
            </div>

            {/* Total Votes Placed counter */}
            <div className="p-3.5 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between gap-4">
              <div>
                <div className="text-[11px] font-semibold text-slate-400">Total Votes Enregistrés</div>
                <div className="text-xl font-extrabold font-mono text-emerald-400">
                  {campaign.totalVotes.toLocaleString()} votes
                </div>
              </div>
              <div className="w-9 h-9 rounded-xl bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                <Zap className="w-5 h-5" />
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 2. DYNAMIC CATEGORIES TABS & SEARCH CONTROLS */}
      <section className="mb-10 space-y-4">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          
          {/* Dynamic Categories Tab Bar */}
          <div className="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-none">
            <button
              onClick={() => setSelectedCategory('all')}
              className={`px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer ${
                selectedCategory === 'all'
                  ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30'
                  : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
              }`}
            >
              Toutes les Catégories ({campaign.candidates.length})
            </button>

            {availableCategories.map((cat) => {
              const count = campaign.candidates.filter(
                (c) => c.category.toLowerCase() === cat.toLowerCase()
              ).length;

              return (
                <button
                  key={cat}
                  onClick={() => setSelectedCategory(cat)}
                  className={`px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer ${
                    selectedCategory.toLowerCase() === cat.toLowerCase()
                      ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30'
                      : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
                  }`}
                >
                  Catégorie : {cat} {count > 0 ? `(${count})` : ''}
                </button>
              );
            })}
          </div>

          {/* Candidate Search Bar */}
          <div className="relative w-full md:w-80">
            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Rechercher par nom, filière, n°..."
              className="w-full pl-9 pr-4 py-2.5 rounded-2xl bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors"
            />
          </div>
        </div>
      </section>

      {/* 3. CANDIDATES SEPARATED MANUALLY BY CATEGORY SECTIONS */}
      <section className="space-y-12">
        {displayedCategories.map((categoryName) => {
          const categoryCandidates = getCategoryCandidates(categoryName);
          if (categoryCandidates.length === 0 && searchQuery.trim() !== '') {
            return null; // Skip category section if search yields zero results in it
          }

          return (
            <div 
              key={categoryName} 
              className="p-6 sm:p-8 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-lg space-y-6"
            >
              {/* Category Header Separator */}
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-2xl bg-emerald-600/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400 font-bold">
                    <Award className="w-5 h-5" />
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="text-[11px] font-mono uppercase tracking-wider text-emerald-400 font-semibold">
                        Sélection Officielle
                      </span>
                    </div>
                    <h2 className="text-xl sm:text-2xl font-bold font-display text-white">
                      Catégorie : {categoryName}
                    </h2>
                  </div>
                </div>

                <div className="flex items-center gap-2">
                  <span className="px-3 py-1 rounded-full bg-slate-800 text-slate-300 text-xs font-semibold">
                    {categoryCandidates.length} Candidat{categoryCandidates.length > 1 ? 's' : ''}
                  </span>
                </div>
              </div>

              {/* Candidates Grid for this Category */}
              {categoryCandidates.length > 0 ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                  {categoryCandidates.map((candidate) => (
                    <motion.div
                      key={candidate.id}
                      initial={{ opacity: 0, y: 15 }}
                      animate={{ opacity: 1, y: 0 }}
                      className="group relative rounded-3xl bg-slate-900 border border-slate-800 hover:border-emerald-500/40 shadow-xl overflow-hidden transition-all duration-300 flex flex-col"
                    >
                      {/* Candidate Poster Header */}
                      <div className="relative h-64 sm:h-72 w-full overflow-hidden bg-slate-950">
                        <img
                          src={candidate.imageUrl}
                          alt={candidate.name}
                          className="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500"
                        />
                        {/* Solid bottom shade for text readability */}
                        <div className="absolute inset-x-0 bottom-0 h-24 bg-slate-950/80" />

                        {/* Candidate Number Badge */}
                        <div className="absolute top-3 left-3 px-3 py-1 rounded-full bg-slate-950/90 border border-slate-700 text-xs font-bold text-white shadow-md flex items-center gap-1.5">
                          <span className="w-2 h-2 rounded-full bg-emerald-400" />
                          <span>N° {candidate.number}</span>
                        </div>

                        {/* Category Pill */}
                        <div className="absolute top-3 right-3 px-2.5 py-1 rounded-full bg-emerald-600/30 border border-emerald-500/50 text-[11px] font-bold text-emerald-300 backdrop-blur-sm">
                          {candidate.category}
                        </div>
                      </div>

                      {/* Candidate Info Body */}
                      <div className="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                          <div className="flex items-center justify-between">
                            <h3 className="text-lg font-bold font-display text-white group-hover:text-emerald-400 transition-colors">
                              {candidate.name}
                            </h3>
                            <span className="text-xs text-slate-400 font-medium">
                              {candidate.age} ans
                            </span>
                          </div>

                          <p className="text-xs text-slate-400 font-medium mt-0.5">
                            {candidate.faculty}
                          </p>

                          {candidate.quote && (
                            <p className="text-xs text-slate-300 italic mt-2.5 line-clamp-2 bg-slate-950 p-2.5 rounded-xl border border-slate-800">
                              « {candidate.quote} »
                            </p>
                          )}
                        </div>

                        {/* Vote Statistics */}
                        <div className="space-y-1.5 pt-2 border-t border-slate-800">
                          <div className="flex items-center justify-between text-xs">
                            <span className="text-slate-400 font-medium">Suffrages obtenus</span>
                            <span className="font-mono font-bold text-emerald-400">
                              {candidate.votes.toLocaleString()} votes ({candidate.percentage}%)
                            </span>
                          </div>

                          {/* Progress Bar (Single solid color) */}
                          <div className="w-full h-2 rounded-full bg-slate-950 overflow-hidden border border-slate-800">
                            <div
                              className="h-full bg-emerald-500 rounded-full transition-all duration-500"
                              style={{ width: `${Math.min(candidate.percentage, 100)}%` }}
                            />
                          </div>
                        </div>

                        {/* Actions: Voter, Bio, Partager */}
                        <div className="pt-2 flex items-center gap-2">
                          {/* Primary Vote Button */}
                          <button
                            onClick={() => onOpenVote(candidate)}
                            className="flex-1 py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                          >
                            <Zap className="w-3.5 h-3.5" />
                            <span>Voter (Dès {campaign.pricePerVoteFCFA} F)</span>
                          </button>

                          {/* Bio Modal Trigger */}
                          <button
                            onClick={() => onOpenBio(candidate)}
                            title="Lire la biographie"
                            className="p-2.5 rounded-xl bg-slate-950 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 transition-colors cursor-pointer"
                          >
                            <ExternalLink className="w-4 h-4" />
                          </button>

                          {/* Share Modal Trigger */}
                          <button
                            onClick={() => onOpenShare(candidate)}
                            title="Partager le lien & QR Code"
                            className="p-2.5 rounded-xl bg-slate-950 hover:bg-slate-800 text-slate-300 hover:text-emerald-400 border border-slate-800 transition-colors cursor-pointer"
                          >
                            <Share2 className="w-4 h-4" />
                          </button>
                        </div>
                      </div>
                    </motion.div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 text-xs text-slate-500 bg-slate-950/60 rounded-2xl border border-slate-800">
                  Aucun candidat enregistré dans cette catégorie.
                </div>
              )}
            </div>
          );
        })}

        {/* Global Empty State if Search matches nothing in any category */}
        {totalMatchingCandidates === 0 && (
          <div className="text-center py-16 px-4 bg-slate-900 rounded-3xl border border-slate-800">
            <AlertCircle className="w-10 h-10 text-slate-500 mx-auto mb-3" />
            <h4 className="text-base font-bold text-white">Aucun candidat ne correspond à votre recherche</h4>
            <p className="text-xs text-slate-400 mt-1">
              Essayez de modifier votre mot-clé ou de sélectionner une autre catégorie.
            </p>
            <button
              onClick={() => {
                setSelectedCategory('all');
                setSearchQuery('');
              }}
              className="mt-4 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold cursor-pointer"
            >
              Réinitialiser les filtres
            </button>
          </div>
        )}
      </section>

      {/* 4. HOW IT WORKS FOOTER FOR VOTERS */}
      <section className="mt-16 p-6 sm:p-8 rounded-3xl bg-slate-900 border border-slate-800">
        <div className="text-center max-w-xl mx-auto mb-8">
          <h3 className="text-lg font-bold font-display text-white">
            Comment voter en 3 étapes simples ?
          </h3>
          <p className="text-xs text-slate-400 mt-1">
            Aucun compte requis. Paiement direct et instantané via votre compte Mobile Money.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
            <div className="w-8 h-8 rounded-xl bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-bold text-sm">
              1
            </div>
            <h4 className="text-sm font-bold text-white">Choisissez votre Candidat</h4>
            <p className="text-xs text-slate-400 leading-relaxed">
              Consultez le profil de votre candidat favori et sélectionnez le pack de votes de votre choix (1x, 5x, 10x, 50x...).
            </p>
          </div>

          <div className="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
            <div className="w-8 h-8 rounded-xl bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-bold text-sm">
              2
            </div>
            <h4 className="text-sm font-bold text-white">Validez par Mobile Money</h4>
            <p className="text-xs text-slate-400 leading-relaxed">
              Renseignez votre numéro Orange Money ou MTN MoMo et confirmez le push USSD sur votre téléphone avec votre code secret.
            </p>
          </div>

          <div className="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
            <div className="w-8 h-8 rounded-xl bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-bold text-sm">
              3
            </div>
            <h4 className="text-sm font-bold text-white">Suffrages Comptabilisés</h4>
            <p className="text-xs text-slate-400 leading-relaxed">
              Vos votes sont instantanément crédités au compteur officiel avec reçu numérique téléchargeable et partageable.
            </p>
          </div>
        </div>
      </section>

      {/* Organizer & Administrator Callout */}
      {onNavigateToLogin && (
        <section className="mt-8">
          <div className="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-emerald-600/20 text-emerald-400 flex items-center justify-center shrink-0">
                <ShieldCheck className="w-5 h-5" />
              </div>
              <div>
                <h4 className="text-sm font-bold text-white">Vous organisez un concours ou une élection ?</h4>
                <p className="text-xs text-slate-400">
                  Créez votre campagne de vote monétisée, suivez les paiements en direct et effectuez des retraits Mobile Money.
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2 w-full sm:w-auto shrink-0">
              <button
                onClick={() => onNavigateToLogin('organizer')}
                className="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition-all shadow-md shadow-emerald-600/30 cursor-pointer text-center"
              >
                Espace Organisateur
              </button>
            </div>
          </div>
        </section>
      )}

      {/* 5. ARITeD Official Technology Footer */}
      <Footer onNavigateToLogin={onNavigateToLogin} />
    </div>
  );
};
