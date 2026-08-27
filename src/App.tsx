import React, { useState, useEffect, useCallback } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  AppView, 
  Campaign, 
  Candidate, 
  FraudAlert, 
  OrganizerAccount, 
  PayoutRequest, 
  PlatformStats, 
  ToastMessage, 
  Transaction, 
  UserSession 
} from './types';
import { 
  INITIAL_CAMPAIGNS, 
  INITIAL_PAYOUTS, 
  INITIAL_PLATFORM_STATS, 
  INITIAL_TRANSACTIONS,
  INITIAL_ORGANIZERS,
  INITIAL_FRAUD_ALERTS
} from './mockData';
import { Navbar } from './components/Navbar';
import { PublicVotingView } from './components/PublicVotingView';
import { OrganizerDashboardView } from './components/OrganizerDashboardView';
import { SuperAdminView } from './components/SuperAdminView';
import { LoginView } from './components/LoginView';
import { VoteModal } from './components/VoteModal';
import { CandidateBioModal } from './components/CandidateBioModal';
import { ShareModal } from './components/ShareModal';
import { ToastContainer } from './components/ToastContainer';
import { formatFCFA } from './utils/helpers';

export default function App() {
  // Navigation View State
  const [currentView, setCurrentView] = useState<AppView>('public');
  const [loginTargetRole, setLoginTargetRole] = useState<'organizer' | 'superadmin'>('organizer');

  // User Authentication State
  const [userSession, setUserSession] = useState<UserSession>({
    isAuthenticated: false,
    role: 'voter',
    name: 'Visiteur Grand Public',
    email: '',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
  });

  // Core Data States
  const [campaigns, setCampaigns] = useState<Campaign[]>(INITIAL_CAMPAIGNS);
  const [activeCampaignId, setActiveCampaignId] = useState<string>(INITIAL_CAMPAIGNS[0].id);
  const [transactions, setTransactions] = useState<Transaction[]>(INITIAL_TRANSACTIONS);
  const [payouts, setPayouts] = useState<PayoutRequest[]>(INITIAL_PAYOUTS);
  const [organizers, setOrganizers] = useState<OrganizerAccount[]>(INITIAL_ORGANIZERS);
  const [fraudAlerts, setFraudAlerts] = useState<FraudAlert[]>(INITIAL_FRAUD_ALERTS);
  const [platformStats, setPlatformStats] = useState<PlatformStats>(INITIAL_PLATFORM_STATS);

  // Modals
  const [votingCandidate, setVotingCandidate] = useState<Candidate | null>(null);
  const [bioCandidate, setBioCandidate] = useState<Candidate | null>(null);
  const [shareCandidate, setShareCandidate] = useState<Candidate | null>(null);
  const [isShareModalOpen, setIsShareModalOpen] = useState<boolean>(false);

  // Notifications Toast State
  const [toasts, setToasts] = useState<ToastMessage[]>([]);

  // Real-time Traffic Simulator Toggle
  const [isSimulatingLive, setIsSimulatingLive] = useState<boolean>(true);

  // Active Campaign Reference
  const activeCampaign = campaigns.find((c) => c.id === activeCampaignId) || campaigns[0];

  // Show Toast Helper
  const showToast = useCallback((title: string, description: string, type: ToastMessage['type'] = 'success') => {
    const id = `toast-${Date.now()}-${Math.random().toString(36).substring(2, 6)}`;
    const newToast: ToastMessage = { id, title, description, type };
    setToasts((prev) => [...prev.slice(-4), newToast]);

    setTimeout(() => {
      setToasts((prev) => prev.filter((t) => t.id !== id));
    }, 4500);
  }, []);

  const handleDismissToast = (id: string) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  };

  // Navigation Guard / Router Handler
  const handleNavigate = (view: AppView, targetRole?: 'organizer' | 'superadmin') => {
    if (view === 'public') {
      setCurrentView('public');
      return;
    }

    if (view === 'login') {
      if (targetRole) {
        setLoginTargetRole(targetRole);
      }
      setCurrentView('login');
      return;
    }

    if (view === 'organizer') {
      if (!userSession.isAuthenticated) {
        setLoginTargetRole('organizer');
        setCurrentView('login');
        showToast(
          'Authentification Requise',
          'Veuillez vous connecter à votre compte organisateur pour accéder au tableau de bord.',
          'info'
        );
        return;
      }
      setCurrentView('organizer');
      return;
    }

    if (view === 'superadmin') {
      if (!userSession.isAuthenticated) {
        setLoginTargetRole('superadmin');
        setCurrentView('login');
        showToast(
          'Authentification Super Admin Requise',
          'Accès restreint aux administrateurs de la plateforme IVote.',
          'warning'
        );
        return;
      }
      if (userSession.role !== 'superadmin') {
        showToast(
          'Accès Non Autorisé',
          'Votre compte organisateur ne possède pas les privilèges Super Admin.',
          'error'
        );
        return;
      }
      setCurrentView('superadmin');
      return;
    }
  };

  // Login Success Handler
  const handleLoginSuccess = (session: UserSession) => {
    setUserSession(session);
    if (session.role === 'superadmin') {
      setCurrentView('superadmin');
    } else {
      setCurrentView('organizer');
    }
  };

  // Logout Handler
  const handleLogout = () => {
    setUserSession({
      isAuthenticated: false,
      role: 'voter',
      name: 'Visiteur Grand Public',
      email: '',
      avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
    });
    setCurrentView('public');
    showToast(
      'Déconnexion Réussie',
      'Vous êtes désormais déconnecté de votre espace administratif.',
      'info'
    );
  };

  // 1. Process a Paid Vote
  const handleVoteSuccess = (
    candidateId: string,
    votesCount: number,
    amountFCFA: number,
    voterName: string,
    voterPhone: string,
    paymentMethod: any,
    message?: string
  ) => {
    // 1. Update Candidate & Campaign in state
    setCampaigns((prevCampaigns) =>
      prevCampaigns.map((camp) => {
        if (camp.id !== activeCampaign.id) return camp;

        const updatedCandidates = camp.candidates.map((c) => {
          if (c.id === candidateId) {
            return {
              ...c,
              votes: c.votes + votesCount,
            };
          }
          return c;
        });

        // Recalculate totals & percentages
        const newTotalVotes = updatedCandidates.reduce((sum, c) => sum + c.votes, 0);
        const candidatesWithPercentages = updatedCandidates.map((c) => ({
          ...c,
          percentage: Number(((c.votes / (newTotalVotes || 1)) * 100).toFixed(1)),
        }));

        // Sort to update ranks
        const sortedByVotes = [...candidatesWithPercentages].sort((a, b) => b.votes - a.votes);
        const rankedCandidates = candidatesWithPercentages.map((c) => {
          const rank = sortedByVotes.findIndex((s) => s.id === c.id) + 1;
          return { ...c, rank };
        });

        return {
          ...camp,
          totalVotes: newTotalVotes,
          totalRevenueFCFA: camp.totalRevenueFCFA + amountFCFA,
          candidates: rankedCandidates,
        };
      })
    );

    const targetCandidate = activeCampaign.candidates.find((c) => c.id === candidateId);

    // 2. Add to Transactions
    const newTx: Transaction = {
      id: `tx-${Date.now()}`,
      transactionRef: `IVT-${Math.floor(10000 + Math.random() * 90000)}`,
      voterName,
      voterPhone,
      candidateId,
      candidateName: targetCandidate ? targetCandidate.name : 'Candidat',
      candidateCategory: targetCandidate?.category || 'Concours',
      candidateAvatar: targetCandidate?.imageUrl || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150',
      votesCount,
      amountFCFA,
      paymentMethod,
      status: 'completed',
      timestamp: 'À l\'instant',
      message,
    };
    setTransactions((prev) => [newTx, ...prev]);

    // 3. Update Platform Stats
    const feeEarned = Math.round(amountFCFA * (platformStats.globalCommissionPercent / 100));
    setPlatformStats((prev) => ({
      ...prev,
      totalPlatformVolumeFCFA: prev.totalPlatformVolumeFCFA + amountFCFA,
      totalVotesCast: prev.totalVotesCast + votesCount,
      totalCommissionEarnedFCFA: prev.totalCommissionEarnedFCFA + feeEarned,
    }));

    showToast(
      'Vote Confirmé & Enregistré !',
      `+${votesCount} votes enregistrés pour ${targetCandidate?.name || 'le candidat'} via ${paymentMethod.replace('_', ' ').toUpperCase()}.`
    );
  };

  // 2. Create Campaign Handler
  const handleCreateCampaign = (newCamp: Campaign) => {
    setCampaigns((prev) => [newCamp, ...prev]);
    setActiveCampaignId(newCamp.id);
    setPlatformStats((prev) => ({
      ...prev,
      activeCampaignsCount: prev.activeCampaignsCount + 1,
    }));
    showToast(
      'Campagne Publiée avec Succès !',
      `"${newCamp.title}" est maintenant active et prête à recevoir les votes du public.`
    );
  };

  // 3. Request Organizer Payout
  const handleRequestPayout = (newPayout: PayoutRequest) => {
    setPayouts((prev) => [newPayout, ...prev]);
    showToast(
      'Demande de Retrait Enregistrée !',
      `Un virement de ${formatFCFA(newPayout.netPayoutFCFA)} a été soumis pour autorisation Super Admin.`
    );
  };

  // 4. Approve Payout (Super Admin)
  const handleApprovePayout = (payoutId: string) => {
    setPayouts((prev) =>
      prev.map((p) => {
        if (p.id === payoutId) {
          return {
            ...p,
            status: 'approved',
            processedAt: 'Validé à l\'instant par Super Admin',
          };
        }
        return p;
      })
    );
  };

  // 5. Reject Payout (Super Admin)
  const handleRejectPayout = (payoutId: string, reason: string) => {
    setPayouts((prev) =>
      prev.map((p) => {
        if (p.id === payoutId) {
          return {
            ...p,
            status: 'rejected',
            rejectionReason: reason,
            processedAt: 'Rejeté à l\'instant',
          };
        }
        return p;
      })
    );
  };

  // 6. Update Platform Commission (Super Admin)
  const handleUpdateCommission = (newPercent: number) => {
    setPlatformStats((prev) => ({
      ...prev,
      globalCommissionPercent: newPercent,
      totalCommissionEarnedFCFA: Math.round(prev.totalPlatformVolumeFCFA * (newPercent / 100)),
    }));
  };

  // 7. Toggle Organizer Status (Super Admin)
  const handleToggleOrganizerStatus = (organizerId: string) => {
    setOrganizers((prev) =>
      prev.map((org) => {
        if (org.id === organizerId) {
          const nextStatus = org.status === 'active' ? 'suspended' : 'active';
          showToast(
            nextStatus === 'suspended' ? 'Organisateur Suspendu' : 'Organisateur Réactivé',
            `Le compte de ${org.name} est maintenant ${nextStatus === 'suspended' ? 'suspendu' : 'actif'}.`,
            nextStatus === 'suspended' ? 'warning' : 'success'
          );
          return { ...org, status: nextStatus };
        }
        return org;
      })
    );
  };

  // 8. Toggle Campaign Status (Super Admin)
  const handleToggleCampaignStatus = (campaignId: string) => {
    setCampaigns((prev) =>
      prev.map((camp) => {
        if (camp.id === campaignId) {
          const nextStatus = camp.status === 'active' ? 'blocked' : 'active';
          showToast(
            nextStatus === 'blocked' ? 'Campagne Suspendue' : 'Campagne Réactivée',
            `La campagne "${camp.title}" est désormais ${nextStatus === 'blocked' ? 'bloquée' : 'en ligne'}.`,
            nextStatus === 'blocked' ? 'warning' : 'success'
          );
          return { ...camp, status: nextStatus };
        }
        return camp;
      })
    );
  };

  // 9. Dismiss Fraud Alert (Super Admin)
  const handleDismissFraudAlert = (alertId: string) => {
    setFraudAlerts((prev) => prev.filter((a) => a.id !== alertId));
  };

  // 10. Live Traffic Simulation Loop
  useEffect(() => {
    if (!isSimulatingLive) return;

    const interval = setInterval(() => {
      const currentCamp = campaigns.find((c) => c.id === activeCampaignId);
      if (!currentCamp || currentCamp.candidates.length === 0) return;

      const randomIndex = Math.floor(Math.random() * currentCamp.candidates.length);
      const candidate = currentCamp.candidates[randomIndex];

      const simulatedPackages = [
        { votes: 5, amount: 450, method: 'orange_money' as const, name: 'Armand Nguema' },
        { votes: 10, amount: 900, method: 'mtn_momo' as const, name: 'Sonia Kamdem' },
        { votes: 25, amount: 2125, method: 'orange_money' as const, name: 'Kader Traoré' },
        { votes: 50, amount: 4000, method: 'mtn_momo' as const, name: 'Supporter Anonyme' },
      ];
      const pack = simulatedPackages[Math.floor(Math.random() * simulatedPackages.length)];

      handleVoteSuccess(
        candidate.id,
        pack.votes,
        pack.amount,
        pack.name,
        `+237 6${Math.floor(50 + Math.random() * 49)} ${Math.floor(10 + Math.random() * 89)} ${Math.floor(10 + Math.random() * 89)} 00`,
        pack.method
      );
    }, 14000);

    return () => clearInterval(interval);
  }, [isSimulatingLive, activeCampaignId, campaigns]);

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 selection:bg-emerald-500/30 selection:text-emerald-200 relative overflow-x-hidden">
      {/* Top Header Navbar */}
      <Navbar
        currentView={currentView}
        userSession={userSession}
        campaigns={campaigns}
        activeCampaignId={activeCampaignId}
        onSelectCampaign={(id) => setActiveCampaignId(id)}
        onNavigate={(view) => handleNavigate(view)}
        onLogout={handleLogout}
        isSimulatingLive={isSimulatingLive}
        onToggleSimulation={() => {
          setIsSimulatingLive(!isSimulatingLive);
          showToast(
            isSimulatingLive ? 'Simulation en Pause' : 'Simulation en Direct Activée',
            isSimulatingLive
              ? 'Le flux simulé des votes entrants a été mis en pause.'
              : 'Génération de votes simulés en direct via Mobile Money.',
            'info'
          );
        }}
        totalVotesCount={activeCampaign.totalVotes}
      />

      {/* Main View Router with Framer Motion Transitions */}
      <main className="relative">
        <AnimatePresence mode="wait">
          {/* 1. PUBLIC VOTING VIEW (LE GRAND PUBLIC / VOTANT) */}
          {currentView === 'public' && (
            <motion.div
              key="public"
              initial={{ opacity: 0, y: 15 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -15 }}
              transition={{ duration: 0.35, ease: 'easeOut' }}
            >
              <PublicVotingView
                campaign={activeCampaign}
                onOpenVote={(cand) => setVotingCandidate(cand)}
                onOpenBio={(cand) => setBioCandidate(cand)}
                onOpenShare={(cand) => {
                  setShareCandidate(cand);
                  setIsShareModalOpen(true);
                }}
                onNavigateToLogin={(target) => handleNavigate('login', target)}
              />
            </motion.div>
          )}

          {/* 2. LOGIN PORTAL (ORGANISATEUR OU SUPER ADMIN) */}
          {currentView === 'login' && (
            <motion.div
              key="login"
              initial={{ opacity: 0, scale: 0.98 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.98 }}
              transition={{ duration: 0.3, ease: 'easeOut' }}
            >
              <LoginView
                initialRole={loginTargetRole}
                onLoginSuccess={handleLoginSuccess}
                onBackToPublic={() => setCurrentView('public')}
                onShowToast={showToast}
              />
            </motion.div>
          )}

          {/* 3. ORGANIZER DASHBOARD (ORGANISATEUR CONNECTÉ) */}
          {currentView === 'organizer' && (
            <motion.div
              key="organizer"
              initial={{ opacity: 0, y: 15 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -15 }}
              transition={{ duration: 0.35, ease: 'easeOut' }}
            >
              <OrganizerDashboardView
                campaigns={campaigns}
                activeCampaign={activeCampaign}
                transactions={transactions}
                payoutRequests={payouts}
                userSession={userSession}
                onSelectCampaign={(c) => setActiveCampaignId(c.id)}
                onCreateCampaign={handleCreateCampaign}
                onRequestPayout={handleRequestPayout}
                onShowToast={showToast}
                onNavigateToPublic={() => setCurrentView('public')}
              />
            </motion.div>
          )}

          {/* 4. SUPER ADMIN DASHBOARD (DIRECTION IVOTE CONNECTÉE) */}
          {currentView === 'superadmin' && (
            <motion.div
              key="superadmin"
              initial={{ opacity: 0, y: 15 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -15 }}
              transition={{ duration: 0.35, ease: 'easeOut' }}
            >
              <SuperAdminView
                stats={platformStats}
                payouts={payouts}
                organizers={organizers}
                campaigns={campaigns}
                fraudAlerts={fraudAlerts}
                onApprovePayout={handleApprovePayout}
                onRejectPayout={handleRejectPayout}
                onUpdateCommission={handleUpdateCommission}
                onToggleOrganizerStatus={handleToggleOrganizerStatus}
                onToggleCampaignStatus={handleToggleCampaignStatus}
                onDismissFraudAlert={handleDismissFraudAlert}
                onShowToast={showToast}
              />
            </motion.div>
          )}
        </AnimatePresence>
      </main>

      {/* Global Modals */}
      {/* 1. Vote Checkout Modal / USSD Bottom Sheet */}
      <VoteModal
        candidate={votingCandidate}
        isOpen={!!votingCandidate}
        onClose={() => setVotingCandidate(null)}
        onVoteSuccess={handleVoteSuccess}
      />

      {/* 2. Candidate Bio Modal */}
      <CandidateBioModal
        candidate={bioCandidate}
        isOpen={!!bioCandidate}
        onClose={() => setBioCandidate(null)}
        onOpenVote={(c) => {
          setBioCandidate(null);
          setVotingCandidate(c);
        }}
        onShare={(c) => {
          setBioCandidate(null);
          setShareCandidate(c);
          setIsShareModalOpen(true);
        }}
      />

      {/* 3. Share & QR Modal */}
      <ShareModal
        candidate={shareCandidate}
        campaignTitle={activeCampaign.title}
        isOpen={isShareModalOpen}
        onClose={() => {
          setIsShareModalOpen(false);
          setShareCandidate(null);
        }}
        onShowToast={showToast}
      />

      {/* 4. Global Toast Notifications Container */}
      <ToastContainer toasts={toasts} onDismiss={handleDismissToast} />
    </div>
  );
}
