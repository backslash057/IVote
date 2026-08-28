import { SuperAdminView } from '../components/SuperAdminView';
import { useIVote } from '../state/IVoteContext';

export function SuperAdminPage() {
  const { platformStats, payouts, organizers, campaigns, fraudAlerts, handleApprovePayout, handleRejectPayout, handleUpdateCommission, handleToggleOrganizerStatus, handleToggleCampaignStatus, handleDismissFraudAlert, showToast } = useIVote();
  return <SuperAdminView
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
  />;
}
