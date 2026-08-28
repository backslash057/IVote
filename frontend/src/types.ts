export type AppView = 'public' | 'login' | 'organizer' | 'superadmin';

export type UserRole = 'voter' | 'organizer' | 'superadmin';

export interface UserSession {
  isAuthenticated: boolean;
  role: UserRole;
  name: string;
  email: string;
  organizationName?: string;
  organizerId?: string;
  avatar: string;
}

export interface Candidate {
  id: string;
  name: string;
  category: string; // Dynamic category name (e.g., 'Miss', 'Master', 'Prix du Public', etc.)
  number: number;
  faculty: string;
  age: number;
  bio: string;
  votes: number;
  percentage: number;
  imageUrl: string;
  quote?: string;
  instagram?: string;
  rank?: number;
}

export interface VotePackage {
  id: string;
  votes: number;
  priceFCFA: number;
  discountPercent: number;
  popular?: boolean;
  bestValue?: boolean;
  label: string;
}

export interface Campaign {
  id: string;
  title: string;
  subtitle: string;
  organization: string;
  organizerId: string;
  category: string;
  categories: string[]; // List of dynamic categories created by the organizer (e.g. ['Miss', 'Master', 'Prix Spécial'])
  bannerUrl: string;
  startDate: string;
  endDate: string;
  status: 'active' | 'scheduled' | 'ended' | 'blocked';
  totalVotes: number;
  totalRevenueFCFA: number;
  pricePerVoteFCFA: number;
  candidates: Candidate[];
  allowPublicStats?: boolean;
}

export interface Transaction {
  id: string;
  transactionRef: string;
  voterName: string;
  voterPhone: string;
  candidateId: string;
  candidateName: string;
  candidateCategory: string;
  candidateAvatar: string;
  votesCount: number;
  amountFCFA: number;
  paymentMethod: 'orange_money' | 'mtn_momo' | 'bank_transfer' | 'credit_card';
  status: 'completed' | 'pending' | 'failed';
  timestamp: string;
  message?: string;
}

export interface PayoutRequest {
  id: string;
  organizerId: string;
  organizerName: string;
  organizerEmail: string;
  organizerAvatar: string;
  campaignId: string;
  campaignTitle: string;
  requestedAmountFCFA: number;
  commissionFeeFCFA: number;
  netPayoutFCFA: number;
  paymentMethod: 'mtn_momo' | 'orange_money' | 'bank_transfer';
  walletNumber: string;
  status: 'pending' | 'approved' | 'rejected';
  requestedAt: string;
  processedAt?: string;
  rejectionReason?: string;
}

export interface OrganizerAccount {
  id: string;
  name: string;
  organization: string;
  email: string;
  phone: string;
  status: 'active' | 'suspended';
  campaignsCount: number;
  totalRaisedFCFA: number;
  joinedDate: string;
  avatar: string;
}

export interface FraudAlert {
  id: string;
  severity: 'high' | 'medium' | 'low';
  campaignTitle: string;
  candidateName: string;
  description: string;
  detectedAt: string;
  status: 'open' | 'investigated' | 'resolved';
  ipOrPhone: string;
}

export interface PlatformStats {
  totalPlatformVolumeFCFA: number;
  totalVotesCast: number;
  totalOrganizers: number;
  activeCampaignsCount: number;
  globalCommissionPercent: number;
  totalCommissionEarnedFCFA: number;
  avgOrderValueFCFA: number;
}

export interface ToastMessage {
  id: string;
  type: 'success' | 'info' | 'warning' | 'error';
  title: string;
  description: string;
}
