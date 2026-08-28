import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { CandidateBioModal } from '../components/CandidateBioModal';
import { PublicVotingView } from '../components/PublicVotingView';
import { ShareModal } from '../components/ShareModal';
import { VoteModal } from '../components/VoteModal';
import type { Candidate } from '../types';
import { useIVote } from '../state/IVoteContext';

export function PublicCampaignPage() {
  const { id } = useParams();
  const { campaigns, handleVoteSuccess, showToast } = useIVote();
  const campaign = campaigns.find((item) => item.id === id);
  const [votingCandidate, setVotingCandidate] = useState<Candidate | null>(null);
  const [bioCandidate, setBioCandidate] = useState<Candidate | null>(null);
  const [shareCandidate, setShareCandidate] = useState<Candidate | null>(null);

  if (!campaign) {
    return (
      <div className="flex min-h-screen items-center justify-center px-4 pt-16 text-center">
        <div>
          <p className="text-xs font-semibold uppercase tracking-wider text-emerald-400">Campagne introuvable</p>
          <h1 className="mt-3 font-display text-3xl font-bold text-white">Cette campagne n'existe pas.</h1>
          <Link to="/" className="mt-6 inline-flex rounded-xl bg-emerald-600 px-5 py-3 text-xs font-bold text-white hover:bg-emerald-500">Retour à l'accueil</Link>
        </div>
      </div>
    );
  }

  return (
    <>
      <PublicVotingView
        campaign={campaign}
        onOpenVote={setVotingCandidate}
        onOpenBio={setBioCandidate}
        onOpenShare={setShareCandidate}
      />
      <VoteModal
        candidate={votingCandidate}
        isOpen={!!votingCandidate}
        onClose={() => setVotingCandidate(null)}
        onVoteSuccess={(candidateId, votesCount, amountFCFA, voterName, voterPhone, paymentMethod, message) => handleVoteSuccess(campaign.id, candidateId, votesCount, amountFCFA, voterName, voterPhone, paymentMethod, message)}
      />
      <CandidateBioModal
        candidate={bioCandidate}
        isOpen={!!bioCandidate}
        onClose={() => setBioCandidate(null)}
        onOpenVote={(candidate) => {
          setBioCandidate(null);
          setVotingCandidate(candidate);
        }}
        onShare={(candidate) => {
          setBioCandidate(null);
          setShareCandidate(candidate);
        }}
      />
      <ShareModal
        candidate={shareCandidate}
        campaignId={campaign.id}
        campaignTitle={campaign.title}
        isOpen={!!shareCandidate}
        onClose={() => setShareCandidate(null)}
        onShowToast={showToast}
      />
    </>
  );
}
