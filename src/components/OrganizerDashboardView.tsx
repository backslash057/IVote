import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  DollarSign, TrendingUp, Users, Vote, Plus, Download, 
  Search, Filter, Smartphone, CheckCircle, Clock, 
  ArrowUpRight, Sparkles, Layers, BarChart3, ChevronRight,
  ShieldCheck, RefreshCw, ExternalLink, Award, FileText,
  QrCode, AlertCircle, CheckCircle2, XCircle, Tag
} from 'lucide-react';
import { Campaign, Candidate, Transaction, PayoutRequest, UserSession } from '../types';
import { formatFCFA, formatCompactNumber } from '../utils/helpers';
import { CreateCampaignModal } from './CreateCampaignModal';
import { RequestPayoutModal } from './RequestPayoutModal';
import { CommunicationKitModal } from './CommunicationKitModal';

interface OrganizerDashboardViewProps {
  campaigns: Campaign[];
  activeCampaign: Campaign;
  transactions: Transaction[];
  payoutRequests: PayoutRequest[];
  userSession: UserSession;
  onSelectCampaign: (campaign: Campaign) => void;
  onCreateCampaign: (campaign: Campaign) => void;
  onRequestPayout: (payout: PayoutRequest) => void;
  onShowToast: (title: string, desc: string, type?: 'success' | 'info' | 'warning' | 'error') => void;
  onNavigateToPublic: () => void;
}

type TabType = 'overview' | 'candidates' | 'transactions' | 'payouts';

export const OrganizerDashboardView: React.FC<OrganizerDashboardViewProps> = ({
  campaigns,
  activeCampaign,
  transactions,
  payoutRequests,
  userSession,
  onSelectCampaign,
  onCreateCampaign,
  onRequestPayout,
  onShowToast,
  onNavigateToPublic,
}) => {
  const [activeTab, setActiveTab] = useState<TabType>('overview');
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isPayoutModalOpen, setIsPayoutModalOpen] = useState(false);
  const [isCommKitOpen, setIsCommKitOpen] = useState(false);
  const [txSearch, setTxSearch] = useState('');
  const [paymentFilter, setPaymentFilter] = useState('all');

  // Calculate stats
  const totalRevenue = activeCampaign.totalRevenueFCFA;
  const totalVotes = activeCampaign.totalVotes;
  const ivotePlatformFee = Math.round(totalRevenue * 0.10); // 10% commission
  const availableBalance = Math.round(totalRevenue * 0.90); // 90% organizer share

  // Filter transactions for this campaign
  const campaignTransactions = transactions.filter(
    (tx) => tx.campaignId === activeCampaign.id || tx.candidateName
  );

  const filteredTransactions = campaignTransactions.filter((tx) => {
    const matchSearch =
      tx.voterName.toLowerCase().includes(txSearch.toLowerCase()) ||
      tx.candidateName.toLowerCase().includes(txSearch.toLowerCase()) ||
      tx.transactionRef.toLowerCase().includes(txSearch.toLowerCase());
    const matchPayment = paymentFilter === 'all' || tx.paymentMethod === paymentFilter;
    return matchSearch && matchPayment;
  });

  const handleExportCSV = () => {
    onShowToast(
      'Export Réussi !',
      `${filteredTransactions.length} transactions exportées au format CSV pour le reporting comptable.`
    );
  };

  // Mock weekly revenue trajectory
  const chartData = [
    { day: 'Lun', revenue: 420000, votes: 4200 },
    { day: 'Mar', revenue: 680000, votes: 6800 },
    { day: 'Mer', revenue: 950000, votes: 9500 },
    { day: 'Jeu', revenue: 820000, votes: 8200 },
    { day: 'Ven', revenue: 1240000, votes: 12400 },
    { day: 'Sam', revenue: 1850000, votes: 18500 },
    { day: 'Dim (Aujourd\'hui)', revenue: 2150000, votes: 21500 },
  ];
  const maxRevenue = Math.max(...chartData.map((d) => d.revenue));

  return (
    <div className="min-h-screen pb-28 pt-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
      
      {/* 1. TOP HEADER & ORGANIZER BANNER */}
      <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 pb-6 border-b border-slate-800">
        <div>
          <div className="flex items-center gap-2 text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-1">
            <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
            <span>Tableau de Bord Organisateur • {userSession.organizationName || activeCampaign.organization}</span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-bold font-display text-white">
            {activeCampaign.title}
          </h1>
          <div className="flex flex-wrap items-center gap-3 text-xs text-slate-400 mt-1">
            <span>Prix unitaire : <strong className="text-white">{activeCampaign.pricePerVoteFCFA} FCFA/vote</strong></span>
            <span>•</span>
            <span className="text-emerald-400 font-semibold flex items-center gap-1">
              <CheckCircle2 className="w-3.5 h-3.5" /> Votes Monétisés Ouverts
            </span>
          </div>
        </div>

        {/* Header Action CTAs */}
        <div className="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
          {/* Campaign Selector Dropdown */}
          <select
            value={activeCampaign.id}
            onChange={(e) => {
              const selected = campaigns.find((c) => c.id === e.target.value);
              if (selected) onSelectCampaign(selected);
            }}
            aria-label="Sélectionner une campagne active"
            className="px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs font-semibold text-white focus:outline-none focus:border-emerald-500 cursor-pointer shadow-sm"
          >
            {campaigns.map((camp) => (
              <option key={camp.id} value={camp.id}>
                {camp.title}
              </option>
            ))}
          </select>

          {/* QR Code & Kits Generator CTA */}
          <button
            onClick={() => setIsCommKitOpen(true)}
            className="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-emerald-300 border border-emerald-500/30 text-xs font-semibold transition-colors cursor-pointer"
          >
            <QrCode className="w-3.5 h-3.5 text-emerald-400" />
            <span>Kits & QR Codes</span>
          </button>

          {/* View Live Public Portal */}
          <button
            onClick={onNavigateToPublic}
            className="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 text-xs font-semibold border border-slate-800 transition-colors cursor-pointer"
          >
            <ExternalLink className="w-3.5 h-3.5 text-slate-400" />
            <span>Page Publique</span>
          </button>

          {/* Request Payout CTA */}
          <button
            onClick={() => setIsPayoutModalOpen(true)}
            className="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/40 text-xs font-bold transition-all cursor-pointer shadow-sm"
          >
            <DollarSign className="w-3.5 h-3.5" />
            <span>Retirer Fonds (Cashout)</span>
          </button>

          {/* Create Campaign CTA */}
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={() => setIsCreateModalOpen(true)}
            className="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-emerald-600/30 transition-all cursor-pointer"
          >
            <Plus className="w-4 h-4" />
            <span>Nouvelle Campagne</span>
          </motion.button>
        </div>
      </div>

      {/* 2. STATS CARDS SECTION (Finances personnelles de l'organisateur) */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 my-6">
        {/* Total Revenus Bruts */}
        <motion.div
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3, delay: 0.05 }}
          className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl backdrop-blur-md relative overflow-hidden"
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
              Total Revenus Générés
            </span>
            <div className="w-9 h-9 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
              <DollarSign className="w-5 h-5" />
            </div>
          </div>
          <div className="text-2xl font-bold font-mono-numeric text-white mt-2">
            {formatFCFA(totalRevenue)}
          </div>
          <div className="flex items-center gap-1.5 text-xs text-emerald-400 mt-1 font-medium">
            <TrendingUp className="w-3.5 h-3.5" />
            <span>+24.8% de croissance 24h</span>
          </div>
        </motion.div>

        {/* Part IVote (10%) */}
        <motion.div
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3, delay: 0.1 }}
          className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl backdrop-blur-md"
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
              Commission IVote (10%)
            </span>
            <div className="w-9 h-9 rounded-xl bg-slate-800 flex items-center justify-center text-slate-400">
              <ShieldCheck className="w-5 h-5" />
            </div>
          </div>
          <div className="text-2xl font-bold font-mono-numeric text-slate-300 mt-2">
            {formatFCFA(ivotePlatformFee)}
          </div>
          <div className="text-xs text-slate-400 mt-1">
            Frais plateforme & passerelles
          </div>
        </motion.div>

        {/* Solde Disponible (90%) */}
        <motion.div
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3, delay: 0.15 }}
          className="p-5 rounded-2xl bg-slate-900/80 border border-emerald-500/40 shadow-xl backdrop-blur-md relative"
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-emerald-400 font-bold">
              Solde Net Retirable (90%)
            </span>
            <div className="w-9 h-9 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400">
              <Smartphone className="w-5 h-5" />
            </div>
          </div>
          <div className="text-2xl font-bold font-mono-numeric text-emerald-400 mt-2">
            {formatFCFA(availableBalance)}
          </div>
          <div className="text-xs text-slate-300 mt-1">
            Retrait instantané Orange / MTN
          </div>
        </motion.div>

        {/* Candidats & Catégories */}
        <motion.div
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3, delay: 0.2 }}
          className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl backdrop-blur-md"
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
              Candidats & Catégories
            </span>
            <div className="w-9 h-9 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400">
              <Users className="w-5 h-5" />
            </div>
          </div>
          <div className="text-2xl font-bold font-mono-numeric text-white mt-2">
            {activeCampaign.candidates.length} Candidats
          </div>
          <div className="text-xs text-slate-400 mt-1">
            {activeCampaign.categories?.length || 3} catégories actives
          </div>
        </motion.div>
      </div>

      {/* 3. TABS NAVIGATION */}
      <div className="flex items-center gap-2 border-b border-slate-800 mb-6 overflow-x-auto pb-1 scrollbar-none">
        {[
          { id: 'overview' as TabType, label: 'Aperçu & Vélocité des Votes', icon: <BarChart3 className="w-4 h-4" /> },
          { id: 'candidates' as TabType, label: 'Classement des Candidats', icon: <Award className="w-4 h-4" /> },
          { id: 'transactions' as TabType, label: `Transactions Votants (${campaignTransactions.length})`, icon: <FileText className="w-4 h-4" /> },
          { id: 'payouts' as TabType, label: `Statut des Retraits (${payoutRequests.length})`, icon: <DollarSign className="w-4 h-4" /> },
        ].map((tab) => {
          const isActive = activeTab === tab.id;
          return (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`relative flex items-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-bold transition-all cursor-pointer whitespace-nowrap ${
                isActive ? 'text-white' : 'text-slate-400 hover:text-slate-200'
              }`}
            >
              {tab.icon}
              <span>{tab.label}</span>
              {isActive && (
                <motion.div
                  layoutId="organizerTabUnderline"
                  className="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-500 shadow-sm shadow-emerald-500"
                />
              )}
            </button>
          );
        })}
      </div>

      {/* TAB CONTENT 1: OVERVIEW */}
      {activeTab === 'overview' && (
        <div className="space-y-6">
          {/* Revenue Velocity Chart */}
          <div className="p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 mb-6">
              <div>
                <h3 className="text-base font-bold font-display text-white">
                  Évolution des Revenus & Vélocité des Votes (7 Derniers Jours)
                </h3>
                <p className="text-xs text-slate-400">
                  Flux monétaire en temps réel collecté par Mobile Money
                </p>
              </div>
              <span className="text-xs px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 font-semibold border border-emerald-500/30">
                Pic Horaire : 1 840 votes/h
              </span>
            </div>

            {/* Custom Interactive SVG Bar Chart */}
            <div className="space-y-2">
              <div className="grid grid-cols-7 gap-2 sm:gap-4 h-48 sm:h-56 items-end pt-4 pb-2 border-b border-slate-800">
                {chartData.map((item, index) => {
                  const heightPercent = (item.revenue / maxRevenue) * 100;
                  const isToday = index === chartData.length - 1;
                  return (
                    <div key={item.day} className="flex flex-col items-center gap-2 h-full justify-end group">
                      {/* Tooltip on hover */}
                      <div className="opacity-0 group-hover:opacity-100 transition-opacity text-[10px] sm:text-xs font-mono font-bold text-white bg-slate-950 px-2 py-1 rounded-md border border-slate-700 shadow-md pointer-events-none whitespace-nowrap mb-1">
                        {formatFCFA(item.revenue)} ({item.votes} votes)
                      </div>

                      {/* Animated Bar */}
                      <div className="w-full max-w-[48px] bg-slate-800 rounded-t-xl overflow-hidden relative flex flex-col justify-end">
                        <motion.div
                          initial={{ height: 0 }}
                          animate={{ height: `${heightPercent}%` }}
                          transition={{ duration: 0.6, delay: index * 0.08 }}
                          className={`w-full rounded-t-xl ${
                            isToday
                              ? 'bg-emerald-500 shadow-md shadow-emerald-500/30'
                              : 'bg-slate-700 group-hover:bg-emerald-600'
                          }`}
                        />
                      </div>

                      {/* Day Label */}
                      <span className={`text-[10px] sm:text-xs font-medium ${isToday ? 'text-emerald-400 font-bold' : 'text-slate-400'}`}>
                        {item.day.split(' ')[0]}
                      </span>
                    </div>
                  );
                })}
              </div>
              <div className="flex justify-between text-[11px] text-slate-500 pt-1">
                <span>0 FCFA</span>
                <span>Max : {formatFCFA(maxRevenue)}</span>
              </div>
            </div>
          </div>

          {/* Quick Communication Kit Banner */}
          <div className="p-5 rounded-3xl bg-emerald-950/30 border border-emerald-500/30 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400 shrink-0">
                <QrCode className="w-6 h-6" />
              </div>
              <div>
                <h4 className="text-sm font-bold text-white">
                  Kits de Communication & Affiches QR Code
                </h4>
                <p className="text-xs text-slate-300">
                  Téléchargez les affiches promotionnelles et codes QR officiels de vote pour chaque candidat.
                </p>
              </div>
            </div>
            <button
              onClick={() => setIsCommKitOpen(true)}
              className="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition-all cursor-pointer whitespace-nowrap"
            >
              Générer Kits Candidats
            </button>
          </div>
        </div>
      )}

      {/* TAB CONTENT 2: CANDIDATES LEADERBOARD */}
      {activeTab === 'candidates' && (
        <div className="rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden">
          <div className="p-5 border-b border-slate-800 flex items-center justify-between">
            <div>
              <h3 className="text-base font-bold font-display text-white">
                Statistiques & Performance des Candidats
              </h3>
              <p className="text-xs text-slate-400">
                Décompte officiel et répartition des recettes par candidat
              </p>
            </div>
            <button
              onClick={() => setIsCommKitOpen(true)}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-emerald-400 text-xs font-bold cursor-pointer"
            >
              <QrCode className="w-3.5 h-3.5" />
              <span>Kits QR Code</span>
            </button>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs sm:text-sm">
              <thead className="bg-slate-950/60 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800 font-semibold">
                <tr>
                  <th className="py-3.5 px-4">Rang & Candidat</th>
                  <th className="py-3.5 px-4">Catégorie</th>
                  <th className="py-3.5 px-4">Filière / Faculté</th>
                  <th className="py-3.5 px-4 text-right">Votes Reçus</th>
                  <th className="py-3.5 px-4 text-right">Recette Générée</th>
                  <th className="py-3.5 px-4">Part du Vote</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60 text-slate-200">
                {activeCampaign.candidates.map((cand, idx) => {
                  const estRevenue = cand.votes * activeCampaign.pricePerVoteFCFA;
                  return (
                    <tr key={cand.id} className="hover:bg-slate-800/40 transition-colors">
                      <td className="py-3.5 px-4 flex items-center gap-3">
                        <span className={`w-6 h-6 rounded-lg flex items-center justify-center font-bold text-xs ${
                          idx === 0
                            ? 'bg-amber-500 text-slate-950'
                            : idx === 1
                            ? 'bg-slate-300 text-slate-950'
                            : 'bg-slate-800 text-slate-400'
                        }`}>
                          #{idx + 1}
                        </span>
                        <img
                          src={cand.imageUrl}
                          alt={cand.name}
                          className="w-9 h-9 rounded-full object-cover ring-1 ring-emerald-500/30"
                        />
                        <div>
                          <div className="font-bold text-white">{cand.name}</div>
                          <div className="text-[11px] text-slate-400 font-mono">N° {cand.number}</div>
                        </div>
                      </td>
                      <td className="py-3.5 px-4">
                        <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-600/20 text-emerald-300 border border-emerald-500/30">
                          {cand.category}
                        </span>
                      </td>
                      <td className="py-3.5 px-4 text-slate-300">
                        {cand.faculty}
                      </td>
                      <td className="py-3.5 px-4 text-right font-mono font-bold text-white">
                        {cand.votes.toLocaleString()}
                      </td>
                      <td className="py-3.5 px-4 text-right font-mono font-bold text-emerald-400">
                        {formatFCFA(estRevenue)}
                      </td>
                      <td className="py-3.5 px-4">
                        <div className="flex items-center gap-2">
                          <div className="w-24 h-2 bg-slate-800 rounded-full overflow-hidden">
                            <div
                              className="h-full bg-emerald-500 rounded-full"
                              style={{ width: `${cand.percentage}%` }}
                            />
                          </div>
                          <span className="font-mono text-xs text-slate-400">{cand.percentage}%</span>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* TAB CONTENT 3: LIVE TRANSACTIONS DATA TABLE */}
      {activeTab === 'transactions' && (
        <div className="rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden space-y-4 p-4 sm:p-6">
          {/* Table Controls */}
          <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div className="flex items-center gap-2 flex-1 max-w-md">
              <div className="relative flex-1">
                <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  placeholder="Rechercher par nom de votant, téléphone, réf..."
                  value={txSearch}
                  onChange={(e) => setTxSearch(e.target.value)}
                  className="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                />
              </div>

              <select
                value={paymentFilter}
                onChange={(e) => setPaymentFilter(e.target.value)}
                aria-label="Filtrer par moyen de paiement"
                className="px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300 focus:outline-none focus:border-emerald-500 cursor-pointer"
              >
                <option value="all">Tous les Moyens</option>
                <option value="mtn_momo">MTN MoMo</option>
                <option value="orange_money">Orange Money</option>
                <option value="wave">Wave</option>
              </select>
            </div>

            <button
              onClick={handleExportCSV}
              className="flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition-all cursor-pointer"
            >
              <Download className="w-3.5 h-3.5 text-emerald-400" /> Exporter CSV
            </button>
          </div>

          {/* Transactions Table */}
          <div className="overflow-x-auto rounded-2xl border border-slate-800">
            <table className="w-full text-left text-xs sm:text-sm">
              <thead className="bg-slate-950/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800 font-semibold">
                <tr>
                  <th className="py-3.5 px-4">Réf. Transaction</th>
                  <th className="py-3.5 px-4">Votant</th>
                  <th className="py-3.5 px-4">Candidat Bénéficiaire</th>
                  <th className="py-3.5 px-4">Moyen de Paiement</th>
                  <th className="py-3.5 px-4 text-right">Votes</th>
                  <th className="py-3.5 px-4 text-right">Montant</th>
                  <th className="py-3.5 px-4">Statut & Date</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60 text-slate-200">
                {filteredTransactions.map((tx) => (
                  <tr key={tx.id} className="hover:bg-slate-800/40 transition-colors">
                    <td className="py-3.5 px-4 font-mono text-xs font-semibold text-emerald-300">
                      {tx.transactionRef}
                    </td>
                    <td className="py-3.5 px-4">
                      <div className="font-bold text-white">{tx.voterName}</div>
                      <div className="text-[11px] text-slate-400 font-mono">{tx.voterPhone}</div>
                    </td>
                    <td className="py-3.5 px-4">
                      <div className="flex items-center gap-2">
                        <img
                          src={tx.candidateAvatar}
                          alt={tx.candidateName}
                          className="w-7 h-7 rounded-full object-cover ring-1 ring-emerald-500/40"
                        />
                        <span className="font-medium text-slate-200">{tx.candidateName}</span>
                      </div>
                    </td>
                    <td className="py-3.5 px-4">
                      <span className="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-300 border border-slate-700">
                        {tx.paymentMethod.replace('_', ' ')}
                      </span>
                    </td>
                    <td className="py-3.5 px-4 text-right font-mono font-bold text-emerald-400">
                      +{tx.votesCount}
                    </td>
                    <td className="py-3.5 px-4 text-right font-mono font-bold text-white">
                      {formatFCFA(tx.amountFCFA)}
                    </td>
                    <td className="py-3.5 px-4">
                      <div className="flex items-center gap-1.5">
                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-400" />
                        <span className="text-xs text-slate-300">{tx.timestamp}</span>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* TAB CONTENT 4: PAYOUTS STATUS */}
      {activeTab === 'payouts' && (
        <div className="rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden space-y-4 p-4 sm:p-6">
          <div className="flex items-center justify-between pb-4 border-b border-slate-800">
            <div>
              <h3 className="text-base font-bold font-display text-white">
                Historique et Suivi des Demandes de Retrait (Cashout)
              </h3>
              <p className="text-xs text-slate-400">
                Vos demandes de versement vers votre compte Orange Money / MTN MoMo
              </p>
            </div>
            <button
              onClick={() => setIsPayoutModalOpen(true)}
              className="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition-all cursor-pointer"
            >
              <Plus className="w-4 h-4" /> Demander un Retrait
            </button>
          </div>

          <div className="overflow-x-auto rounded-2xl border border-slate-800">
            <table className="w-full text-left text-xs sm:text-sm">
              <thead className="bg-slate-950/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800 font-semibold">
                <tr>
                  <th className="py-3.5 px-4">Réf. Demande</th>
                  <th className="py-3.5 px-4">Montant Retiré</th>
                  <th className="py-3.5 px-4">Moyen de Réception</th>
                  <th className="py-3.5 px-4">Numéro / Titulaire</th>
                  <th className="py-3.5 px-4">Date de Demande</th>
                  <th className="py-3.5 px-4">Statut</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60 text-slate-200">
                {payoutRequests.map((req) => (
                  <tr key={req.id} className="hover:bg-slate-800/40 transition-colors">
                    <td className="py-3.5 px-4 font-mono text-xs font-semibold text-emerald-300">
                      {req.id}
                    </td>
                    <td className="py-3.5 px-4 font-mono font-bold text-white">
                      {formatFCFA(req.amountFCFA)}
                    </td>
                    <td className="py-3.5 px-4">
                      <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-800 text-slate-300 border border-slate-700">
                        {req.paymentMethod.replace('_', ' ')}
                      </span>
                    </td>
                    <td className="py-3.5 px-4">
                      <div className="font-semibold text-white">{req.accountHolder}</div>
                      <div className="text-[11px] text-slate-400 font-mono">{req.walletNumber}</div>
                    </td>
                    <td className="py-3.5 px-4 text-xs text-slate-300">
                      {req.requestedAt}
                    </td>
                    <td className="py-3.5 px-4">
                      {req.status === 'completed' ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 text-xs font-bold">
                          <CheckCircle2 className="w-3.5 h-3.5" /> Validé & Versé
                        </span>
                      ) : req.status === 'pending' ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-500/15 text-amber-300 border border-amber-500/30 text-xs font-bold">
                          <Clock className="w-3.5 h-3.5" /> En attente de validation
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-500/15 text-rose-400 border border-rose-500/30 text-xs font-bold">
                          <XCircle className="w-3.5 h-3.5" /> Rejeté
                        </span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* MODALS */}
      <CreateCampaignModal
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
        onCreateSuccess={onCreateCampaign}
        organizerName={userSession.organizationName || activeCampaign.organization}
        organizerId={userSession.organizerId || 'org-1'}
      />

      <RequestPayoutModal
        isOpen={isPayoutModalOpen}
        onClose={() => setIsPayoutModalOpen(false)}
        availableBalanceFCFA={availableBalance}
        campaignTitle={activeCampaign.title}
        onRequestPayout={onRequestPayout}
      />

      <CommunicationKitModal
        campaign={activeCampaign}
        isOpen={isCommKitOpen}
        onClose={() => setIsCommKitOpen(false)}
        onShowToast={onShowToast}
      />
    </div>
  );
};
