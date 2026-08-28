import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  ShieldCheck, DollarSign, TrendingUp, Sliders, CheckCircle2, 
  XCircle, AlertCircle, RefreshCw, Zap, Server, Activity, 
  Users, Building, Check, X, Sparkles, Filter, ChevronRight, Lock,
  ShieldAlert, UserX, UserCheck, Play, Pause, AlertTriangle
} from 'lucide-react';
import type { PayoutRequest, PlatformStats, OrganizerAccount, Campaign, FraudAlert } from '../types';
import { formatFCFA, triggerConfetti, triggerSuccessSound } from '../utils/helpers';

interface SuperAdminViewProps {
  stats: PlatformStats;
  payouts: PayoutRequest[];
  organizers: OrganizerAccount[];
  campaigns: Campaign[];
  fraudAlerts: FraudAlert[];
  onApprovePayout: (payoutId: string) => void;
  onRejectPayout: (payoutId: string, reason: string) => void;
  onUpdateCommission: (newPercent: number) => void;
  onToggleOrganizerStatus: (organizerId: string) => void;
  onToggleCampaignStatus: (campaignId: string) => void;
  onDismissFraudAlert: (alertId: string) => void;
  onShowToast: (title: string, desc: string, type?: 'success' | 'info' | 'warning' | 'error') => void;
}

type AdminTab = 'payouts' | 'organizers' | 'campaigns' | 'security' | 'commission';

export const SuperAdminView: React.FC<SuperAdminViewProps> = ({
  stats,
  payouts,
  organizers,
  campaigns,
  fraudAlerts,
  onApprovePayout,
  onRejectPayout,
  onUpdateCommission,
  onToggleOrganizerStatus,
  onToggleCampaignStatus,
  onDismissFraudAlert,
  onShowToast,
}) => {
  const [activeAdminTab, setActiveAdminTab] = useState<AdminTab>('payouts');
  const [commissionRate, setCommissionRate] = useState<number>(stats.globalCommissionPercent || 10);
  const [rejectingPayout, setRejectingPayout] = useState<PayoutRequest | null>(null);
  const [rejectReason, setRejectReason] = useState('Vérification d\'identité (KYC) incomplète');
  const [filterStatus, setFilterStatus] = useState<'all' | 'pending' | 'approved' | 'rejected'>('all');

  // Interactive commission earnings projection
  const projectedCommissionFCFA = Math.round(stats.totalPlatformVolumeFCFA * (commissionRate / 100));

  const handleSaveCommission = () => {
    onUpdateCommission(commissionRate);
    onShowToast(
      'Taux de Commission Mis à Jour !',
      `Le taux global IVote est désormais fixé à ${commissionRate}%. Il s'applique à tous les nouveaux retraits.`
    );
  };

  const handleApprove = (payout: PayoutRequest) => {
    triggerConfetti();
    triggerSuccessSound();
    onApprovePayout(payout.id);
    onShowToast(
      'Retrait Mobile Money Débloqué !',
      `Virement de ${formatFCFA(payout.netPayoutFCFA)} validé pour ${payout.organizerName} via ${payout.paymentMethod.replace('_', ' ').toUpperCase()}.`
    );
  };

  const handleConfirmReject = () => {
    if (!rejectingPayout) return;
    onRejectPayout(rejectingPayout.id, rejectReason);
    onShowToast(
      'Demande de Retrait Rejetée',
      `L'organisateur ${rejectingPayout.organizerName} a été notifié du motif de rejet.`,
      'warning'
    );
    setRejectingPayout(null);
  };

  const filteredPayouts = payouts.filter((p) => {
    if (filterStatus === 'all') return true;
    return p.status === filterStatus;
  });

  const pendingPayoutsCount = payouts.filter((p) => p.status === 'pending').length;

  return (
    <div className="min-h-screen pb-28 pt-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
      
      {/* 1. TOP HEADER */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
        <div>
          <div className="flex items-center gap-2 text-xs font-semibold text-amber-400 uppercase tracking-wider mb-1">
            <ShieldCheck className="w-4 h-4 text-amber-400" />
            <span>Direction Générale IVote SAS • Panneau Super Administrateur</span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-bold font-display text-white">
            Supervision Globale & Gouvernance Financière
          </h1>
          <p className="text-xs sm:text-sm text-slate-400 mt-0.5">
            Validation des retraits Mobile Money, modération des campagnes, gestion des fraudes & commissions.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <span className="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-semibold">
            <span className="w-2 h-2 rounded-full bg-emerald-400 animate-ping" />
            Passerelles Télécoms : Opérationnelles à 100%
          </span>
        </div>
      </div>

      {/* 2. HIGH-LEVEL PLATFORM STATS */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 my-6">
        {/* Total Platform Volume */}
        <motion.div
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl"
        >
          <div className="flex items-center justify-between text-slate-400 text-xs font-semibold uppercase tracking-wider">
            <span>Volume Total Encaissé</span>
            <div className="w-9 h-9 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
              <DollarSign className="w-5 h-5" />
            </div>
          </div>
          <div className="text-2xl font-bold font-mono-numeric text-white mt-2">
            {formatFCFA(stats.totalPlatformVolumeFCFA)}
          </div>
          <div className="text-xs text-slate-400 mt-1">
            Sur {stats.totalVotesCast.toLocaleString()} votes vérifiés
          </div>
        </motion.div>

        {/* IVote Commission Earned */}
        <motion.div
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.05 }}
          className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl"
        >
          <div className="flex items-center justify-between text-slate-400 text-xs font-semibold uppercase tracking-wider">
            <span>Commissions IVote Encaissées</span>
            <div className="w-9 h-9 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400">
              <TrendingUp className="w-5 h-5" />
            </div>
          </div>
          <div className="text-2xl font-bold font-mono-numeric text-amber-400 mt-2">
            {formatFCFA(stats.totalCommissionEarnedFCFA)}
          </div>
          <div className="text-xs text-slate-400 mt-1">
            Taux moyen appliqué : <strong className="text-white">{stats.globalCommissionPercent}%</strong>
          </div>
        </motion.div>

        {/* Active Organizers */}
        <motion.div
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl"
        >
          <div className="flex items-center justify-between text-slate-400 text-xs font-semibold uppercase tracking-wider">
            <span>Organisateurs Actifs</span>
            <div className="w-9 h-9 rounded-xl bg-teal-500/15 border border-teal-500/30 flex items-center justify-center text-teal-400">
              <Users className="w-5 h-5" />
            </div>
          </div>
          <div className="text-2xl font-bold font-mono-numeric text-white mt-2">
            {organizers.length} Organisations
          </div>
          <div className="text-xs text-slate-400 mt-1">
            {campaigns.length} Campagnes hébergées
          </div>
        </motion.div>

        {/* Pending Payout Queue */}
        <motion.div
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.15 }}
          className="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl"
        >
          <div className="flex items-center justify-between text-slate-400 text-xs font-semibold uppercase tracking-wider">
            <span>Retraits en Attente</span>
            <div className="w-9 h-9 rounded-xl bg-rose-500/15 border border-rose-500/30 flex items-center justify-center text-rose-400">
              <AlertCircle className="w-5 h-5" />
            </div>
          </div>
          <div className="text-2xl font-bold font-mono-numeric text-rose-400 mt-2">
            {pendingPayoutsCount} Demandes
          </div>
          <div className="text-xs text-slate-400 mt-1">
            En attente d'autorisation Super Admin
          </div>
        </motion.div>
      </div>

      {/* 3. TABS NAVIGATION FOR SUPER ADMIN */}
      <div className="flex items-center gap-2 border-b border-slate-800 mb-6 overflow-x-auto pb-1 scrollbar-none">
        {[
          { id: 'payouts' as AdminTab, label: `Retraits & Décaissements (${pendingPayoutsCount})`, icon: <DollarSign className="w-4 h-4" /> },
          { id: 'organizers' as AdminTab, label: `Gestion Organisateurs (${organizers.length})`, icon: <Building className="w-4 h-4" /> },
          { id: 'campaigns' as AdminTab, label: `Modération Campagnes (${campaigns.length})`, icon: <Sparkles className="w-4 h-4" /> },
          { id: 'security' as AdminTab, label: `Sécurité & Fraudes (${fraudAlerts.length})`, icon: <ShieldAlert className="w-4 h-4" /> },
          { id: 'commission' as AdminTab, label: 'Paramètres Commission & Nœuds', icon: <Sliders className="w-4 h-4" /> },
        ].map((tab) => {
          const isActive = activeAdminTab === tab.id;
          return (
            <button
              key={tab.id}
              onClick={() => setActiveAdminTab(tab.id)}
              className={`relative flex items-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-bold transition-all cursor-pointer whitespace-nowrap ${
                isActive ? 'text-white' : 'text-slate-400 hover:text-slate-200'
              }`}
            >
              {tab.icon}
              <span>{tab.label}</span>
              {isActive && (
                <motion.div
                  layoutId="adminTabUnderline"
                  className="absolute bottom-0 left-0 right-0 h-0.5 bg-amber-500 shadow-sm shadow-amber-500"
                />
              )}
            </button>
          );
        })}
      </div>

      {/* TAB 1: RETRAITS MOBILE MONEY DES ORGANISATEURS */}
      {activeAdminTab === 'payouts' && (
        <div className="rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden space-y-4 p-4 sm:p-6">
          <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div>
              <h3 className="text-base font-bold font-display text-white flex items-center gap-2">
                <DollarSign className="w-5 h-5 text-emerald-400" />
                Guichet d'Autorisation des Décaissements Mobile Money
              </h3>
              <p className="text-xs text-slate-400">
                Validez ou rejetez les demandes de retraits des organisateurs après vérification
              </p>
            </div>

            {/* Status Filter */}
            <div className="flex items-center p-1 rounded-xl bg-slate-950 border border-slate-800">
              {(['all', 'pending', 'approved', 'rejected'] as const).map((st) => (
                <button
                  key={st}
                  onClick={() => setFilterStatus(st)}
                  className={`px-3 py-1.5 rounded-lg text-xs font-bold capitalize transition-colors cursor-pointer ${
                    filterStatus === st
                      ? 'bg-amber-600 text-white'
                      : 'text-slate-400 hover:text-white'
                  }`}
                >
                  {st === 'all' ? 'Tous' : st === 'pending' ? 'En Attente' : st === 'approved' ? 'Validés' : 'Rejetés'}
                </button>
              ))}
            </div>
          </div>

          <div className="overflow-x-auto rounded-2xl border border-slate-800">
            <table className="w-full text-left text-xs sm:text-sm">
              <thead className="bg-slate-950/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800 font-semibold">
                <tr>
                  <th className="py-3.5 px-4">Organisateur / Événement</th>
                  <th className="py-3.5 px-4">Canal Mobile Money</th>
                  <th className="py-3.5 px-4 text-right">Montant Brut</th>
                  <th className="py-3.5 px-4 text-right">Commission IVote</th>
                  <th className="py-3.5 px-4 text-right">Net à Verser</th>
                  <th className="py-3.5 px-4 text-center">Statut</th>
                  <th className="py-3.5 px-4 text-right">Actions Décisionnelles</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60 text-slate-200">
                {filteredPayouts.map((payout) => (
                  <tr key={payout.id} className="hover:bg-slate-800/40 transition-colors">
                    <td className="py-3.5 px-4">
                      <div className="font-bold text-white">{payout.organizerName}</div>
                      <div className="text-[11px] text-emerald-400">{payout.campaignTitle}</div>
                      <div className="text-[10px] text-slate-500 font-mono">{payout.requestedAt}</div>
                    </td>
                    <td className="py-3.5 px-4">
                      <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-800 text-slate-300 border border-slate-700">
                        {payout.paymentMethod.replace('_', ' ')} • {payout.walletNumber}
                      </span>
                    </td>
                    <td className="py-3.5 px-4 text-right font-mono font-semibold text-slate-300">
                      {formatFCFA(payout.requestedAmountFCFA)}
                    </td>
                    <td className="py-3.5 px-4 text-right font-mono font-semibold text-amber-400">
                      -{formatFCFA(payout.commissionFeeFCFA)}
                    </td>
                    <td className="py-3.5 px-4 text-right font-mono font-extrabold text-emerald-400 text-sm">
                      {formatFCFA(payout.netPayoutFCFA)}
                    </td>
                    <td className="py-3.5 px-4 text-center">
                      {payout.status === 'pending' ? (
                        <span className="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30">
                          En attente
                        </span>
                      ) : payout.status === 'approved' ? (
                        <span className="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                          Validé & Versé
                        </span>
                      ) : (
                        <span className="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-500/20 text-rose-300 border border-rose-500/30">
                          Rejeté
                        </span>
                      )}
                    </td>
                    <td className="py-3.5 px-4 text-right">
                      {payout.status === 'pending' ? (
                        <div className="flex items-center justify-end gap-2">
                          <motion.button
                            whileHover={{ scale: 1.05 }}
                            whileTap={{ scale: 0.95 }}
                            onClick={() => handleApprove(payout)}
                            className="flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition-all cursor-pointer"
                          >
                            <Check className="w-3.5 h-3.5" /> Débloquer Virement
                          </motion.button>

                          <motion.button
                            whileHover={{ scale: 1.05 }}
                            whileTap={{ scale: 0.95 }}
                            onClick={() => setRejectingPayout(payout)}
                            className="flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 text-xs font-bold transition-all cursor-pointer"
                          >
                            <X className="w-3.5 h-3.5" /> Rejeter
                          </motion.button>
                        </div>
                      ) : payout.status === 'approved' ? (
                        <span className="text-xs text-emerald-400 font-medium">
                          Traité & Clôturé
                        </span>
                      ) : (
                        <span className="text-[11px] text-rose-400" title={payout.rejectionReason}>
                          Rejeté : {payout.rejectionReason || 'Non conforme'}
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

      {/* TAB 2: GESTION DES ORGANISATEURS (CLIENTS) */}
      {activeAdminTab === 'organizers' && (
        <div className="rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden space-y-4 p-4 sm:p-6">
          <div>
            <h3 className="text-base font-bold font-display text-white flex items-center gap-2">
              <Building className="w-5 h-5 text-teal-400" />
              Répertoire des Comptes Organisateurs
            </h3>
            <p className="text-xs text-slate-400">
              Vérification KYC, blocage préventif et solde cumulé des organisateurs
            </p>
          </div>

          <div className="overflow-x-auto rounded-2xl border border-slate-800">
            <table className="w-full text-left text-xs sm:text-sm">
              <thead className="bg-slate-950/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800 font-semibold">
                <tr>
                  <th className="py-3.5 px-4">Organisateur</th>
                  <th className="py-3.5 px-4">Organisation / Contact</th>
                  <th className="py-3.5 px-4 text-right">Campagnes</th>
                  <th className="py-3.5 px-4 text-right">Total Récolté</th>
                  <th className="py-3.5 px-4 text-center">Statut du Compte</th>
                  <th className="py-3.5 px-4 text-right">Action Modération</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60 text-slate-200">
                {organizers.map((org) => (
                  <tr key={org.id} className="hover:bg-slate-800/40 transition-colors">
                    <td className="py-3.5 px-4 flex items-center gap-3">
                      <img
                        src={org.avatar}
                        alt={org.name}
                        className="w-9 h-9 rounded-full object-cover border border-slate-700"
                      />
                      <div>
                        <div className="font-bold text-white">{org.name}</div>
                        <div className="text-[11px] text-slate-400">{org.email}</div>
                      </div>
                    </td>
                    <td className="py-3.5 px-4">
                      <div className="font-semibold text-slate-200">{org.organization}</div>
                      <div className="text-[11px] text-slate-400 font-mono">{org.phone}</div>
                    </td>
                    <td className="py-3.5 px-4 text-right font-mono font-semibold text-white">
                      {org.campaignsCount}
                    </td>
                    <td className="py-3.5 px-4 text-right font-mono font-bold text-emerald-400">
                      {formatFCFA(org.totalRaisedFCFA)}
                    </td>
                    <td className="py-3.5 px-4 text-center">
                      {org.status === 'active' ? (
                        <span className="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                          Vérifié / Actif
                        </span>
                      ) : (
                        <span className="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-500/20 text-rose-300 border border-rose-500/30">
                          Suspendu
                        </span>
                      )}
                    </td>
                    <td className="py-3.5 px-4 text-right">
                      <button
                        onClick={() => onToggleOrganizerStatus(org.id)}
                        className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                          org.status === 'active'
                            ? 'bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30'
                            : 'bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30'
                        }`}
                      >
                        {org.status === 'active' ? 'Suspendre' : 'Réactiver'}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* TAB 3: MODÉRATION DES CAMPAGNES */}
      {activeAdminTab === 'campaigns' && (
        <div className="rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden space-y-4 p-4 sm:p-6">
          <div>
            <h3 className="text-base font-bold font-display text-white flex items-center gap-2">
              <Sparkles className="w-5 h-5 text-emerald-400" />
              Supervision des Campagnes Publiques
            </h3>
            <p className="text-xs text-slate-400">
              Contrôlez et modérez toutes les campagnes de vote actives sur la plateforme IVote
            </p>
          </div>

          <div className="overflow-x-auto rounded-2xl border border-slate-800">
            <table className="w-full text-left text-xs sm:text-sm">
              <thead className="bg-slate-950/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800 font-semibold">
                <tr>
                  <th className="py-3.5 px-4">Titre de la Campagne</th>
                  <th className="py-3.5 px-4">Organisation</th>
                  <th className="py-3.5 px-4 text-right">Candidats</th>
                  <th className="py-3.5 px-4 text-right">Total Votes</th>
                  <th className="py-3.5 px-4 text-right">Revenus Générés</th>
                  <th className="py-3.5 px-4 text-center">Statut</th>
                  <th className="py-3.5 px-4 text-right">Modération</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60 text-slate-200">
                {campaigns.map((camp) => (
                  <tr key={camp.id} className="hover:bg-slate-800/40 transition-colors">
                    <td className="py-3.5 px-4 font-bold text-white">
                      {camp.title}
                    </td>
                    <td className="py-3.5 px-4 text-slate-300">
                      {camp.organization}
                    </td>
                    <td className="py-3.5 px-4 text-right font-mono text-slate-200">
                      {camp.candidates.length}
                    </td>
                    <td className="py-3.5 px-4 text-right font-mono font-bold text-emerald-400">
                      {camp.totalVotes.toLocaleString()}
                    </td>
                    <td className="py-3.5 px-4 text-right font-mono font-bold text-white">
                      {formatFCFA(camp.totalRevenueFCFA)}
                    </td>
                    <td className="py-3.5 px-4 text-center">
                      <span className={`px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${
                        camp.status === 'active'
                          ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                          : 'bg-rose-500/20 text-rose-300 border border-rose-500/30'
                      }`}>
                        {camp.status === 'active' ? 'En Cours' : 'Suspendue'}
                      </span>
                    </td>
                    <td className="py-3.5 px-4 text-right">
                      <button
                        onClick={() => onToggleCampaignStatus(camp.id)}
                        className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                          camp.status === 'active'
                            ? 'bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30'
                            : 'bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30'
                        }`}
                      >
                        {camp.status === 'active' ? 'Suspendre Campagne' : 'Réactiver Campagne'}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* TAB 4: SÉCURITÉ & FRAUDES */}
      {activeAdminTab === 'security' && (
        <div className="rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden space-y-4 p-4 sm:p-6">
          <div>
            <h3 className="text-base font-bold font-display text-white flex items-center gap-2">
              <ShieldAlert className="w-5 h-5 text-rose-400" />
              Détection des Fraudes & Anomalies de Votes (Anti-Bot)
            </h3>
            <p className="text-xs text-slate-400">
              Analyse heuristique des tentatives de votes automatisés et d'usurpation USSD
            </p>
          </div>

          <div className="space-y-3">
            {fraudAlerts.map((alert) => (
              <div
                key={alert.id}
                className="p-4 rounded-2xl bg-slate-950/80 border border-rose-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
              >
                <div className="flex items-start gap-3">
                  <div className="p-2.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400 shrink-0 mt-0.5">
                    <AlertTriangle className="w-5 h-5" />
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="text-sm font-bold text-white">{alert.campaignTitle}</span>
                      <span className="text-[10px] px-2 py-0.5 rounded font-mono uppercase bg-rose-950 text-rose-300 border border-rose-800">
                        {alert.severity}
                      </span>
                    </div>
                    <p className="text-xs text-slate-300 mt-1">{alert.description}</p>
                    <div className="flex items-center gap-3 text-[11px] text-slate-400 font-mono mt-1.5">
                      <span>Cible : {alert.candidateName}</span>
                      <span>•</span>
                      <span>Origine : {alert.ipOrPhone}</span>
                      <span>•</span>
                      <span>Détecté : {alert.detectedAt}</span>
                    </div>
                  </div>
                </div>

                <div className="flex items-center gap-2 shrink-0">
                  <button
                    onClick={() => {
                      onDismissFraudAlert(alert.id);
                      onShowToast('Cible Bloquée', `L'IP / Numéro ${alert.ipOrPhone} a été neutralisé.`);
                    }}
                    className="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-md cursor-pointer transition-colors"
                  >
                    Bannir IP / Numéro
                  </button>
                  <button
                    onClick={() => onDismissFraudAlert(alert.id)}
                    className="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold cursor-pointer transition-colors"
                  >
                    Ignorer
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* TAB 5: COMMISSION & PARAMÈTRES */}
      {activeAdminTab === 'commission' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="lg:col-span-2 p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Sliders className="w-5 h-5 text-amber-400" />
                <h3 className="text-base font-bold font-display text-white">
                  Taux de Commission Global IVote
                </h3>
              </div>
              <span className="px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 font-mono text-sm font-bold border border-amber-500/30">
                {commissionRate}% Frais Plateforme
              </span>
            </div>

            <p className="text-xs sm:text-sm text-slate-400">
              Ajustez le prélèvement automatique appliqué lors des décaissements des organisateurs d'événements.
            </p>

            <div className="space-y-3 pt-2">
              <div className="flex items-center justify-between text-xs text-slate-300 font-semibold">
                <span>Standard (5%)</span>
                <span className="text-amber-400 font-bold font-mono text-base">{commissionRate}%</span>
                <span>Maximum (25%)</span>
              </div>
              <input
                type="range"
                min={5}
                max={25}
                step={0.5}
                value={commissionRate}
                onChange={(e) => setCommissionRate(parseFloat(e.target.value))}
                className="w-full h-2.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-amber-500"
              />
            </div>

            <div className="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
              <div>
                <span className="text-slate-400">Revenus IVote Projetés à {commissionRate}% :</span>
                <div className="text-lg font-bold font-mono text-emerald-400 mt-0.5">
                  {formatFCFA(projectedCommissionFCFA)}
                </div>
              </div>

              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={handleSaveCommission}
                className="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-lg shadow-amber-600/30 transition-all cursor-pointer flex items-center justify-center gap-1.5"
              >
                <Check className="w-3.5 h-3.5" /> Enregistrer le Taux
              </motion.button>
            </div>
          </div>

          {/* Carrier Gateways */}
          <div className="p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div className="flex items-center gap-2">
              <Activity className="w-5 h-5 text-emerald-400" />
              <h3 className="text-base font-bold font-display text-white">
                Passerelles Télécoms
              </h3>
            </div>

            <div className="space-y-3">
              {[
                { name: 'MTN MoMo Gateway', status: 'Optimal', latency: '84ms', uptime: '99.98%', badge: 'bg-amber-400' },
                { name: 'Orange Money Webhook', status: 'Optimal', latency: '112ms', uptime: '99.95%', badge: 'bg-orange-400' },
                { name: 'Wave Direct Settle', status: 'Actif', latency: '65ms', uptime: '100%', badge: 'bg-emerald-400' },
              ].map((node) => (
                <div key={node.name} className="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/80 flex items-center justify-between text-xs">
                  <div className="flex items-center gap-2.5">
                    <span className={`w-2 h-2 rounded-full ${node.badge} animate-pulse`} />
                    <div>
                      <div className="font-bold text-slate-200">{node.name}</div>
                      <div className="text-[10px] text-slate-500">Latence : {node.latency}</div>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="text-emerald-400 font-semibold">{node.status}</div>
                    <div className="text-[10px] text-slate-500 font-mono">{node.uptime}</div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* REJECT REASON MODAL */}
      <AnimatePresence>
        {rejectingPayout && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setRejectingPayout(null)}
              className="fixed inset-0 bg-slate-950/80 backdrop-blur-md"
            />

            <motion.div
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              className="relative w-full max-w-md rounded-3xl bg-slate-900 border border-slate-700/60 shadow-2xl p-6 z-10 text-slate-100 space-y-4"
            >
              <div className="flex items-center justify-between">
                <h4 className="text-base font-bold text-rose-400 flex items-center gap-2">
                  <XCircle className="w-5 h-5" /> Rejeter la Demande de Retrait
                </h4>
                <button
                  onClick={() => setRejectingPayout(null)}
                  className="text-slate-400 hover:text-white cursor-pointer"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              <p className="text-xs text-slate-300">
                Spécifiez le motif de rejet pour le retrait de{' '}
                <strong className="text-white">
                  {formatFCFA(rejectingPayout.requestedAmountFCFA)}
                </strong>{' '}
                demandé par <strong className="text-white">{rejectingPayout.organizerName}</strong>.
              </p>

              <div>
                <label className="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">
                  Motif du Rejet
                </label>
                <textarea
                  rows={3}
                  value={rejectReason}
                  onChange={(e) => setRejectReason(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-rose-500"
                />
              </div>

              <div className="flex items-center justify-end gap-2 pt-2">
                <button
                  onClick={() => setRejectingPayout(null)}
                  className="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold cursor-pointer"
                >
                  Annuler
                </button>
                <button
                  onClick={handleConfirmReject}
                  className="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-md cursor-pointer"
                >
                  Confirmer le Rejet
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </div>
  );
};
