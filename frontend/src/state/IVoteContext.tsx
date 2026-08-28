import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import type {
  Campaign,
  FraudAlert,
  OrganizerAccount,
  PayoutRequest,
  PlatformStats,
  ToastMessage,
  Transaction,
  UserSession,
} from '../types';
import {
  INITIAL_CAMPAIGNS,
  INITIAL_FRAUD_ALERTS,
  INITIAL_ORGANIZERS,
  INITIAL_PAYOUTS,
  INITIAL_PLATFORM_STATS,
  INITIAL_TRANSACTIONS,
} from '../mockData';
import { formatFCFA } from '../utils/helpers';

type PaymentMethod = Transaction['paymentMethod'];
type ToastHandler = (title: string, description: string, type?: ToastMessage['type']) => void;

type VoteSuccessHandler = (
  campaignId: string,
  candidateId: string,
  votesCount: number,
  amountFCFA: number,
  voterName: string,
  voterPhone: string,
  paymentMethod: PaymentMethod,
  message?: string
) => void;

interface IVoteContextValue {
  campaigns: Campaign[];
  transactions: Transaction[];
  payouts: PayoutRequest[];
  organizers: OrganizerAccount[];
  fraudAlerts: FraudAlert[];
  platformStats: PlatformStats;
  userSession: UserSession;
  isSimulatingLive: boolean;
  toasts: ToastMessage[];
  showToast: ToastHandler;
  dismissToast: (id: string) => void;
  handleVoteSuccess: VoteSuccessHandler;
  handleCreateCampaign: (campaign: Campaign) => void;
  handleRequestPayout: (payout: PayoutRequest) => void;
  handleApprovePayout: (payoutId: string) => void;
  handleRejectPayout: (payoutId: string, reason: string) => void;
  handleUpdateCommission: (newPercent: number) => void;
  handleToggleOrganizerStatus: (organizerId: string) => void;
  handleToggleCampaignStatus: (campaignId: string) => void;
  handleDismissFraudAlert: (alertId: string) => void;
  setUserSession: (session: UserSession) => void;
  logout: () => void;
  toggleSimulation: () => void;
}

const IVoteContext = createContext<IVoteContextValue | null>(null);

const visitorSession: UserSession = {
  isAuthenticated: false,
  role: 'voter',
  name: 'Visiteur Grand Public',
  email: '',
  avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
};

export function IVoteProvider({ children }: { children: React.ReactNode }) {
  const [campaigns, setCampaigns] = useState(INITIAL_CAMPAIGNS);
  const [transactions, setTransactions] = useState(INITIAL_TRANSACTIONS);
  const [payouts, setPayouts] = useState(INITIAL_PAYOUTS);
  const [organizers, setOrganizers] = useState(INITIAL_ORGANIZERS);
  const [fraudAlerts, setFraudAlerts] = useState(INITIAL_FRAUD_ALERTS);
  const [platformStats, setPlatformStats] = useState(INITIAL_PLATFORM_STATS);
  const [userSession, setUserSession] = useState(visitorSession);
  const [toasts, setToasts] = useState<ToastMessage[]>([]);
  const [isSimulatingLive, setIsSimulatingLive] = useState(true);

  const showToast = useCallback<ToastHandler>((title, description, type = 'success') => {
    const id = `toast-${Date.now()}-${Math.random().toString(36).substring(2, 6)}`;
    setToasts((prev) => [...prev.slice(-4), { id, title, description, type }]);
    window.setTimeout(() => setToasts((prev) => prev.filter((toast) => toast.id !== id)), 4500);
  }, []);

  const dismissToast = useCallback((id: string) => {
    setToasts((prev) => prev.filter((toast) => toast.id !== id));
  }, []);

  const handleVoteSuccess: VoteSuccessHandler = useCallback((campaignId, candidateId, votesCount, amountFCFA, voterName, voterPhone, paymentMethod, message) => {
    let targetCandidateName = 'Candidat';
    let targetCandidateAvatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150';
    let targetCandidateCategory = 'Concours';

    setCampaigns((prevCampaigns) => prevCampaigns.map((campaign) => {
      if (campaign.id !== campaignId) return campaign;
      const targetCandidate = campaign.candidates.find((candidate) => candidate.id === candidateId);
      if (targetCandidate) {
        targetCandidateName = targetCandidate.name;
        targetCandidateAvatar = targetCandidate.imageUrl;
        targetCandidateCategory = targetCandidate.category;
      }

      const updatedCandidates = campaign.candidates.map((candidate) =>
        candidate.id === candidateId ? { ...candidate, votes: candidate.votes + votesCount } : candidate
      );
      const newTotalVotes = updatedCandidates.reduce((sum, candidate) => sum + candidate.votes, 0);
      const withPercentages = updatedCandidates.map((candidate) => ({
        ...candidate,
        percentage: Number(((candidate.votes / (newTotalVotes || 1)) * 100).toFixed(1)),
      }));
      const sortedByVotes = [...withPercentages].sort((a, b) => b.votes - a.votes);
      const rankedCandidates = withPercentages.map((candidate) => ({
        ...candidate,
        rank: sortedByVotes.findIndex((sorted) => sorted.id === candidate.id) + 1,
      }));

      return {
        ...campaign,
        totalVotes: newTotalVotes,
        totalRevenueFCFA: campaign.totalRevenueFCFA + amountFCFA,
        candidates: rankedCandidates,
      };
    }));

    setTransactions((prev) => [{
      id: `tx-${Date.now()}`,
      transactionRef: `IVT-${Math.floor(10000 + Math.random() * 90000)}`,
      voterName,
      voterPhone,
      candidateId,
      candidateName: targetCandidateName,
      candidateCategory: targetCandidateCategory,
      candidateAvatar: targetCandidateAvatar,
      votesCount,
      amountFCFA,
      paymentMethod,
      status: 'completed',
      timestamp: "À l'instant",
      message,
    }, ...prev]);

    setPlatformStats((prev) => {
      const feeEarned = Math.round(amountFCFA * (prev.globalCommissionPercent / 100));
      return {
        ...prev,
        totalPlatformVolumeFCFA: prev.totalPlatformVolumeFCFA + amountFCFA,
        totalVotesCast: prev.totalVotesCast + votesCount,
        totalCommissionEarnedFCFA: prev.totalCommissionEarnedFCFA + feeEarned,
      };
    });

    showToast(
      'Vote Confirmé & Enregistré !',
      `+${votesCount} votes enregistrés pour ${targetCandidateName} via ${paymentMethod.replace('_', ' ').toUpperCase()}.`
    );
  }, [showToast]);

  const handleCreateCampaign = useCallback((campaign: Campaign) => {
    setCampaigns((prev) => [campaign, ...prev]);
    setPlatformStats((prev) => ({ ...prev, activeCampaignsCount: prev.activeCampaignsCount + 1 }));
    showToast('Campagne Publiée avec Succès !', `"${campaign.title}" est maintenant active et prête à recevoir les votes du public.`);
  }, [showToast]);

  const handleRequestPayout = useCallback((payout: PayoutRequest) => {
    setPayouts((prev) => [payout, ...prev]);
    showToast('Demande de Retrait Enregistrée !', `Un virement de ${formatFCFA(payout.netPayoutFCFA)} a été soumis pour autorisation Super Admin.`);
  }, [showToast]);

  const handleApprovePayout = useCallback((payoutId: string) => {
    setPayouts((prev) => prev.map((payout) => payout.id === payoutId
      ? { ...payout, status: 'approved', processedAt: "Validé à l'instant par Super Admin" }
      : payout));
  }, []);

  const handleRejectPayout = useCallback((payoutId: string, reason: string) => {
    setPayouts((prev) => prev.map((payout) => payout.id === payoutId
      ? { ...payout, status: 'rejected', rejectionReason: reason, processedAt: "Rejeté à l'instant" }
      : payout));
  }, []);

  const handleUpdateCommission = useCallback((newPercent: number) => {
    setPlatformStats((prev) => ({
      ...prev,
      globalCommissionPercent: newPercent,
      totalCommissionEarnedFCFA: Math.round(prev.totalPlatformVolumeFCFA * (newPercent / 100)),
    }));
  }, []);

  const handleToggleOrganizerStatus = useCallback((organizerId: string) => {
    setOrganizers((prev) => prev.map((organizer) => {
      if (organizer.id !== organizerId) return organizer;
      const nextStatus = organizer.status === 'active' ? 'suspended' : 'active';
      showToast(
        nextStatus === 'suspended' ? 'Organisateur Suspendu' : 'Organisateur Réactivé',
        `Le compte de ${organizer.name} est maintenant ${nextStatus === 'suspended' ? 'suspendu' : 'actif'}.`,
        nextStatus === 'suspended' ? 'warning' : 'success'
      );
      return { ...organizer, status: nextStatus };
    }));
  }, [showToast]);

  const handleToggleCampaignStatus = useCallback((campaignId: string) => {
    setCampaigns((prev) => prev.map((campaign) => {
      if (campaign.id !== campaignId) return campaign;
      const nextStatus = campaign.status === 'active' ? 'blocked' : 'active';
      showToast(
        nextStatus === 'blocked' ? 'Campagne Suspendue' : 'Campagne Réactivée',
        `La campagne "${campaign.title}" est désormais ${nextStatus === 'blocked' ? 'bloquée' : 'en ligne'}.`,
        nextStatus === 'blocked' ? 'warning' : 'success'
      );
      return { ...campaign, status: nextStatus };
    }));
  }, [showToast]);

  const handleDismissFraudAlert = useCallback((alertId: string) => {
    setFraudAlerts((prev) => prev.filter((alert) => alert.id !== alertId));
  }, []);

  const toggleSimulation = useCallback(() => {
    setIsSimulatingLive((previous) => {
      const next = !previous;
      showToast(
        next ? 'Simulation en Direct Activée' : 'Simulation en Pause',
        next ? 'Génération de votes simulés en direct via Mobile Money.' : 'Le flux simulé des votes entrants a été mis en pause.',
        'info'
      );
      return next;
    });
  }, [showToast]);

  useEffect(() => {
    if (!isSimulatingLive) return;
    const interval = window.setInterval(() => {
      const campaign = campaigns.find((item) => item.candidates.length > 0 && item.status === 'active');
      if (!campaign) return;
      const candidate = campaign.candidates[Math.floor(Math.random() * campaign.candidates.length)];
      const packages = [
        { votes: 5, amount: 450, method: 'orange_money' as const, name: 'Armand Nguema' },
        { votes: 10, amount: 900, method: 'mtn_momo' as const, name: 'Sonia Kamdem' },
        { votes: 25, amount: 2125, method: 'orange_money' as const, name: 'Kader Traoré' },
        { votes: 50, amount: 4000, method: 'mtn_momo' as const, name: 'Supporter Anonyme' },
      ];
      const pack = packages[Math.floor(Math.random() * packages.length)];
      handleVoteSuccess(
        campaign.id,
        candidate.id,
        pack.votes,
        pack.amount,
        pack.name,
        `+237 6${Math.floor(50 + Math.random() * 49)} ${Math.floor(10 + Math.random() * 89)} ${Math.floor(10 + Math.random() * 89)} 00`,
        pack.method
      );
    }, 14000);
    return () => window.clearInterval(interval);
  }, [campaigns, handleVoteSuccess, isSimulatingLive]);

  const logout = useCallback(() => {
    setUserSession(visitorSession);
    showToast('Déconnexion Réussie', 'Vous êtes désormais déconnecté de votre espace administratif.', 'info');
  }, [showToast]);

  return (
    <IVoteContext.Provider value={{
      campaigns,
      transactions,
      payouts,
      organizers,
      fraudAlerts,
      platformStats,
      userSession,
      isSimulatingLive,
      toasts,
      showToast,
      dismissToast,
      handleVoteSuccess,
      handleCreateCampaign,
      handleRequestPayout,
      handleApprovePayout,
      handleRejectPayout,
      handleUpdateCommission,
      handleToggleOrganizerStatus,
      handleToggleCampaignStatus,
      handleDismissFraudAlert,
      setUserSession,
      logout,
      toggleSimulation,
    }}>
      {children}
    </IVoteContext.Provider>
  );
}

export function useIVote() {
  const context = useContext(IVoteContext);
  if (!context) throw new Error('useIVote must be used within IVoteProvider');
  return context;
}
