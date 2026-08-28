import { OrganizerDashboardView } from '../components/OrganizerDashboardView';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useIVote } from '../state/IVoteContext';

export function OrganizerPage() {
  const navigate = useNavigate();
  const { campaigns, transactions, payouts, userSession, handleCreateCampaign, handleRequestPayout, showToast } = useIVote();
  const [activeCampaignId, setActiveCampaignId] = useState(campaigns[0]?.id ?? '');
  const activeCampaign = campaigns.find((campaign) => campaign.id === activeCampaignId) ?? campaigns[0];

  if (!activeCampaign) return <div className="px-6 pt-24 text-slate-300">Aucune campagne disponible.</div>;

  return <OrganizerDashboardView
    campaigns={campaigns}
    activeCampaign={activeCampaign}
    transactions={transactions}
    payoutRequests={payouts}
    userSession={userSession}
    onSelectCampaign={(campaign) => setActiveCampaignId(campaign.id)}
    onCreateCampaign={(campaign) => { handleCreateCampaign(campaign); setActiveCampaignId(campaign.id); }}
    onRequestPayout={handleRequestPayout}
    onShowToast={showToast}
    onNavigateToPublic={() => navigate(`/campaign/${encodeURIComponent(activeCampaign.id)}`)}
  />;
}
