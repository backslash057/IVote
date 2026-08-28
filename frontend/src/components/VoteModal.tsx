import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  X, Check, ShieldCheck, Zap, Smartphone, CreditCard, 
  Sparkles, Award, ArrowRight, Loader2, CheckCircle2, 
  HeartHandshake, ChevronRight, Lock
} from 'lucide-react';
import type { Candidate, VotePackage } from '../types';
import { DEFAULT_VOTE_PACKAGES } from '../mockData';
import { formatFCFA, triggerConfetti, triggerSuccessSound } from '../utils/helpers';

interface VoteModalProps {
  candidate: Candidate | null;
  isOpen: boolean;
  onClose: () => void;
  onVoteSuccess: (candidateId: string, votesCount: number, amountFCFA: number, voterName: string, voterPhone: string, paymentMethod: any, message?: string) => void;
}

type PaymentProvider = 'mtn_momo' | 'orange_money';

export const VoteModal: React.FC<VoteModalProps> = ({
  candidate,
  isOpen,
  onClose,
  onVoteSuccess,
}) => {
  const [selectedPackage, setSelectedPackage] = useState<VotePackage>(DEFAULT_VOTE_PACKAGES[2]); // Default 10 votes
  const [customVotes, setCustomVotes] = useState<number>(10);
  const [isCustom, setIsCustom] = useState<boolean>(false);
  const [selectedPayment, setSelectedPayment] = useState<PaymentProvider>('mtn_momo');
  
  // Voter Details Form
  const [voterName, setVoterName] = useState('');
  const [voterPhone, setVoterPhone] = useState('');
  const [cheerMessage, setCheerMessage] = useState('');
  const [validationError, setValidationError] = useState<string | null>(null);
  
  // Checkout flow step: 'select' | 'processing_ussd' | 'success'
  const [flowStep, setFlowStep] = useState<'select' | 'processing_ussd' | 'success'>('select');
  const [ussdTimer, setUssdTimer] = useState(15);
  const [isSubmitting, setIsSubmitting] = useState(false);

  if (!candidate) return null;

  const currentVotesCount = isCustom ? customVotes : selectedPackage.votes;
  const currentPriceFCFA = isCustom
    ? Math.round(customVotes * 100 * (customVotes >= 50 ? 0.8 : customVotes >= 20 ? 0.85 : customVotes >= 10 ? 0.9 : 1))
    : selectedPackage.priceFCFA;

  const handleStartPayment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!voterPhone || voterPhone.trim().length < 8) {
      setValidationError('Veuillez renseigner un numéro Mobile Money valide (ex: 699 00 11 22).');
      return;
    }
    setValidationError(null);

    setIsSubmitting(true);
    // Move to USSD simulation screen
    setTimeout(() => {
      setIsSubmitting(false);
      setFlowStep('processing_ussd');
      setUssdTimer(15);
    }, 700);
  };

  const handleSimulatePinApproved = () => {
    setFlowStep('success');
    triggerConfetti();
    triggerSuccessSound();
    
    // Notify parent to record state
    onVoteSuccess(
      candidate.id,
      currentVotesCount,
      currentPriceFCFA,
      voterName.trim() || 'Votant IVote',
      voterPhone,
      selectedPayment,
      cheerMessage.trim() || undefined
    );
  };

  const handleResetAndClose = () => {
    setFlowStep('select');
    onClose();
  };

  const paymentProviders = [
    {
      id: 'mtn_momo' as PaymentProvider,
      name: 'MTN Mobile Money',
      code: '*126#',
      badge: 'Instantané USSD',
      bgClass: 'bg-amber-500/10 border-amber-500/30 text-amber-300 hover:border-amber-400',
      activeClass: 'border-amber-400 bg-amber-500/20 ring-2 ring-amber-500/40',
      iconBg: 'bg-amber-400 text-slate-950 font-bold',
      tag: 'MTN',
    },
    {
      id: 'orange_money' as PaymentProvider,
      name: 'Orange Money',
      code: '#150#',
      badge: '0% Frais',
      bgClass: 'bg-orange-500/10 border-orange-500/30 text-orange-300 hover:border-orange-400',
      activeClass: 'border-orange-400 bg-orange-500/20 ring-2 ring-orange-500/40',
      iconBg: 'bg-orange-500 text-white font-bold',
      tag: 'OM',
    },
  ];

  return (
    <AnimatePresence>
      {isOpen && (
        <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={handleResetAndClose}
            className="fixed inset-0 bg-slate-950/85 backdrop-blur-md"
          />

          {/* Modal / Bottom Sheet */}
          <motion.div
            initial={{ y: '100%', opacity: 0, scale: 0.95 }}
            animate={{ y: 0, opacity: 1, scale: 1 }}
            exit={{ y: '100%', opacity: 0, scale: 0.95 }}
            transition={{ type: 'spring', damping: 28, stiffness: 300 }}
            className="relative w-full max-w-xl max-h-[92vh] overflow-y-auto rounded-t-3xl sm:rounded-3xl bg-slate-900 border border-slate-700/60 shadow-2xl shadow-emerald-950/60 p-5 sm:p-7 z-10 text-slate-100"
          >
            {/* Drag Pill for Mobile */}
            <div className="w-12 h-1.5 bg-slate-700 rounded-full mx-auto mb-4 sm:hidden" />

            {/* Header with Candidate Badge */}
            <div className="flex items-center justify-between pb-4 border-b border-slate-800">
              <div className="flex items-center gap-3">
                <div className="relative">
                  <img
                    src={candidate.imageUrl}
                    alt={candidate.name}
                    className="w-12 h-12 rounded-full object-cover ring-2 ring-emerald-500/40 shadow-md"
                  />
                  <span className="absolute -bottom-1 -right-1 bg-emerald-600 text-white text-[10px] font-bold px-1.5 py-0.2 rounded-full border border-slate-900">
                    #{candidate.number}
                  </span>
                </div>
                <div>
                  <div className="text-xs text-emerald-400 font-medium tracking-wide uppercase flex items-center gap-1">
                    <Sparkles className="w-3 h-3" /> Espace Vote Public Sécurisé
                  </div>
                  <h3 className="text-base sm:text-lg font-bold font-display text-white">
                    Voter pour {candidate.name}
                  </h3>
                </div>
              </div>

              <button
                onClick={handleResetAndClose}
                className="w-8 h-8 rounded-full bg-slate-800/80 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition-colors cursor-pointer"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Flow Step 1: Package & Details Selection */}
            {flowStep === 'select' && (
              <form onSubmit={handleStartPayment} className="mt-5 space-y-6">
                {/* 1. Vote Packages */}
                <div>
                  <div className="flex items-center justify-between mb-3">
                    <label className="text-xs font-semibold uppercase tracking-wider text-slate-300 flex items-center gap-1.5">
                      <Award className="w-3.5 h-3.5 text-emerald-400" />
                      1. Choisissez votre Pack de Votes
                    </label>
                    <button
                      type="button"
                      onClick={() => setIsCustom(!isCustom)}
                      className="text-xs text-emerald-400 hover:text-emerald-300 underline font-medium cursor-pointer"
                    >
                      {isCustom ? 'Packs Recommandés' : 'Personnaliser le nombre'}
                    </button>
                  </div>

                  {!isCustom ? (
                    <div className="grid grid-cols-3 sm:grid-cols-3 gap-2 sm:gap-2.5">
                      {DEFAULT_VOTE_PACKAGES.map((pkg) => {
                        const isSelected = selectedPackage.id === pkg.id;
                        return (
                          <button
                            key={pkg.id}
                            type="button"
                            onClick={() => setSelectedPackage(pkg)}
                            className={`relative p-2.5 sm:p-3 rounded-xl border text-left transition-all cursor-pointer ${
                              isSelected
                                ? 'bg-emerald-600/20 border-emerald-500 ring-2 ring-emerald-500/30'
                                : 'bg-slate-800/50 border-slate-700/60 hover:border-slate-600 hover:bg-slate-800'
                            }`}
                          >
                            {pkg.popular && (
                              <span className="absolute -top-2.5 right-2 bg-amber-500 text-slate-950 text-[9px] font-bold px-1.5 py-0.5 rounded-full uppercase tracking-wider shadow-sm">
                                Populaire
                              </span>
                            )}
                            {pkg.bestValue && (
                              <span className="absolute -top-2.5 right-2 bg-emerald-500 text-slate-950 text-[9px] font-bold px-1.5 py-0.5 rounded-full uppercase tracking-wider shadow-sm">
                                Top Offre
                              </span>
                            )}

                            <div className="flex items-baseline justify-between mb-1">
                              <span className="text-base sm:text-lg font-extrabold text-white font-mono-numeric">
                                {pkg.votes}
                              </span>
                              <span className="text-[10px] text-slate-400 font-medium">
                                {pkg.votes === 1 ? 'Vote' : 'Votes'}
                              </span>
                            </div>

                            <div className="text-xs font-bold text-emerald-300 font-mono-numeric">
                              {formatFCFA(pkg.priceFCFA)}
                            </div>

                            {pkg.discountPercent > 0 && (
                              <div className="text-[10px] text-emerald-400 font-semibold mt-0.5">
                                Économisez {pkg.discountPercent}%
                              </div>
                            )}
                          </button>
                        );
                      })}
                    </div>
                  ) : (
                    <div className="p-4 rounded-xl bg-slate-800/60 border border-slate-700 space-y-3">
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-slate-300">Quantité personnalisée</span>
                        <span className="text-xl font-bold text-emerald-400 font-mono-numeric">
                          {customVotes} Votes
                        </span>
                      </div>
                      <input
                        type="range"
                        min={1}
                        max={500}
                        step={5}
                        value={customVotes}
                        onChange={(e) => setCustomVotes(parseInt(e.target.value))}
                        className="w-full h-2 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-emerald-500"
                      />
                      <div className="flex justify-between text-xs text-slate-400">
                        <span>1 Vote (100 FCFA)</span>
                        <span>250 Votes</span>
                        <span>500 Votes</span>
                      </div>
                    </div>
                  )}
                </div>

                {/* 2. Payment Methods */}
                <div>
                  <label className="text-xs font-semibold uppercase tracking-wider text-slate-300 flex items-center gap-1.5 mb-2.5">
                    <Smartphone className="w-3.5 h-3.5 text-emerald-400" />
                    2. Paiement Direct Mobile Money (Sans Frais Cachés)
                  </label>
                  <div className="grid grid-cols-2 gap-2">
                    {paymentProviders.map((prov) => {
                      const isSelected = selectedPayment === prov.id;
                      return (
                        <button
                          key={prov.id}
                          type="button"
                          onClick={() => setSelectedPayment(prov.id)}
                          className={`flex items-center gap-2.5 p-2.5 rounded-xl border text-left transition-all cursor-pointer ${
                            isSelected ? prov.activeClass : prov.bgClass
                          }`}
                        >
                          <div
                            className={`w-7 h-7 rounded-lg flex items-center justify-center text-[10px] shrink-0 ${prov.iconBg}`}
                          >
                            {prov.tag}
                          </div>
                          <div className="min-w-0">
                            <div className="text-xs font-bold text-slate-100 truncate">
                              {prov.name}
                            </div>
                            <div className="text-[10px] text-slate-400 font-mono">
                              {prov.code}
                            </div>
                          </div>
                        </button>
                      );
                    })}
                  </div>
                </div>

                {/* 3. Voter Details */}
                <div className="space-y-3 pt-1">
                  <label className="text-xs font-semibold uppercase tracking-wider text-slate-300 flex items-center gap-1.5">
                    <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
                    3. Numéro Mobile Money pour la validation
                  </label>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                      <input
                        type="text"
                        placeholder="Votre Nom ou Pseudo (optionnel)"
                        value={voterName}
                        onChange={(e) => setVoterName(e.target.value)}
                        className="w-full px-3.5 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors"
                      />
                    </div>
                    <div>
                      <input
                        type="tel"
                        required
                        placeholder="Numéro Mobile Money (ex: 699 00 11 22)"
                        value={voterPhone}
                        onChange={(e) => setVoterPhone(e.target.value)}
                        className="w-full px-3.5 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors"
                      />
                    </div>
                  </div>
                  <input
                    type="text"
                    placeholder="Message d'encouragement au candidat (optionnel)"
                    value={cheerMessage}
                    onChange={(e) => setCheerMessage(e.target.value)}
                    className="w-full px-3.5 py-2 rounded-xl bg-slate-800/50 border border-slate-700/80 text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                  />
                  {validationError && (
                    <div className="p-2.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
                      <span className="w-1.5 h-1.5 rounded-full bg-rose-400" />
                      <span>{validationError}</span>
                    </div>
                  )}
                </div>

                {/* Order Summary & Submit Button */}
                <div className="pt-3 border-t border-slate-800">
                  <div className="flex items-center justify-between mb-3 text-xs sm:text-sm">
                    <span className="text-slate-400">Total à payer</span>
                    <span className="text-lg sm:text-xl font-bold font-mono-numeric text-emerald-400">
                      {formatFCFA(currentPriceFCFA)}
                    </span>
                  </div>

                  <motion.button
                    whileHover={{ scale: 1.01 }}
                    whileTap={{ scale: 0.98 }}
                    type="submit"
                    disabled={isSubmitting}
                    className="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm sm:text-base flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30 cursor-pointer disabled:opacity-50"
                  >
                    {isSubmitting ? (
                      <>
                        <Loader2 className="w-4 h-4 animate-spin" />
                        Génération de la session Mobile Money...
                      </>
                    ) : (
                      <>
                        <Zap className="w-4 h-4 text-amber-300" />
                        Payer {formatFCFA(currentPriceFCFA)} & Valider {currentVotesCount} Votes
                      </>
                    )}
                  </motion.button>

                  <div className="flex items-center justify-center gap-2 mt-2 text-[11px] text-slate-400">
                    <Lock className="w-3 h-3 text-emerald-400" />
                    Transaction chiffrée SSL 256-bit • Prise en compte immédiate sur le classement
                  </div>
                </div>
              </form>
            )}

            {/* Flow Step 2: USSD Push Notification Simulation */}
            {flowStep === 'processing_ussd' && (
              <div className="py-8 text-center space-y-6">
                <div className="relative w-20 h-20 mx-auto">
                  <div className="absolute inset-0 rounded-full border-4 border-emerald-500/20 animate-ping" />
                  <div className="relative w-20 h-20 rounded-full bg-emerald-600/20 border-2 border-emerald-500 flex items-center justify-center shadow-xl shadow-emerald-500/20">
                    <Smartphone className="w-8 h-8 text-emerald-400 animate-bounce" />
                  </div>
                </div>

                <div>
                  <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-300 text-xs font-semibold mb-2">
                    <Loader2 className="w-3.5 h-3.5 animate-spin" /> Invite USSD Envoyée
                  </div>
                  <h4 className="text-xl font-bold font-display text-white">
                    Consultez l'écran de votre téléphone !
                  </h4>
                  <p className="text-xs sm:text-sm text-slate-300 max-w-sm mx-auto mt-1.5">
                    Une notification de débit a été envoyée au <strong className="text-white">{voterPhone}</strong>. Veuillez saisir votre code secret PIN Mobile Money pour confirmer <strong className="text-emerald-400">{formatFCFA(currentPriceFCFA)}</strong>.
                  </p>
                </div>

                {/* Simulated Phone Notification Mock */}
                <div className="max-w-xs mx-auto p-3.5 rounded-2xl bg-slate-950 border border-slate-700/80 shadow-inner text-left space-y-1.5">
                  <div className="flex items-center justify-between text-[10px] text-slate-400">
                    <span className="font-semibold text-emerald-400 uppercase">
                      POPUP {selectedPayment.toUpperCase()}
                    </span>
                    <span>À l'instant</span>
                  </div>
                  <div className="text-xs font-mono text-slate-200">
                    Autoriser le débit IVote de {formatFCFA(currentPriceFCFA)} pour {currentVotesCount} votes ?
                  </div>
                  <div className="text-[11px] text-amber-400 font-mono">
                    Entrez votre code secret PIN...
                  </div>
                </div>

                {/* Quick Simulation Action Button */}
                <div className="space-y-2 pt-2">
                  <motion.button
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                    onClick={handleSimulatePinApproved}
                    className="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30 cursor-pointer"
                  >
                    <CheckCircle2 className="w-4 h-4" />
                    Simuler PIN Validé (Confirmer les Votes)
                  </motion.button>
                  <button
                    type="button"
                    onClick={() => setFlowStep('select')}
                    className="text-xs text-slate-400 hover:text-slate-200 cursor-pointer"
                  >
                    Modifier le numéro ou le mode de paiement
                  </button>
                </div>
              </div>
            )}

            {/* Flow Step 3: Success Screen */}
            {flowStep === 'success' && (
              <div className="py-6 text-center space-y-5">
                <motion.div
                  initial={{ scale: 0 }}
                  animate={{ scale: 1 }}
                  transition={{ type: 'spring', stiffness: 300, damping: 20 }}
                  className="w-16 h-16 rounded-full bg-emerald-500/20 border-2 border-emerald-500 mx-auto flex items-center justify-center shadow-xl shadow-emerald-500/30"
                >
                  <Check className="w-8 h-8 text-emerald-400" />
                </motion.div>

                <div>
                  <span className="text-xs font-semibold uppercase tracking-wider text-emerald-400">
                    Paiement Validé & Enregistré
                  </span>
                  <h4 className="text-2xl font-bold font-display text-white mt-1">
                    Merci {voterName || 'Cher Votant'} !
                  </h4>
                  <p className="text-sm text-slate-300 mt-1">
                    Vous avez crédité avec succès <strong className="text-emerald-400">+{currentVotesCount} votes</strong> pour <strong className="text-white">{candidate.name}</strong>.
                  </p>
                </div>

                {/* Digital Receipt Card */}
                <div className="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 text-left space-y-2 text-xs">
                  <div className="flex justify-between text-slate-400">
                    <span>Référence Transaction</span>
                    <span className="font-mono text-slate-200">IVT-{Math.floor(10000 + Math.random() * 90000)}</span>
                  </div>
                  <div className="flex justify-between text-slate-400">
                    <span>Candidat</span>
                    <span className="font-semibold text-white">{candidate.name}</span>
                  </div>
                  <div className="flex justify-between text-slate-400">
                    <span>Suffrages Ajoutés</span>
                    <span className="font-bold text-emerald-400">+{currentVotesCount} Votes</span>
                  </div>
                  <div className="flex justify-between text-slate-400 border-t border-slate-800/80 pt-2 font-bold">
                    <span className="text-slate-300">Montant Débité</span>
                    <span className="text-emerald-400 font-mono text-sm">{formatFCFA(currentPriceFCFA)}</span>
                  </div>
                </div>

                <motion.button
                  whileHover={{ scale: 1.02 }}
                  whileTap={{ scale: 0.98 }}
                  onClick={handleResetAndClose}
                  className="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm cursor-pointer shadow-lg shadow-emerald-600/30"
                >
                  Retourner au Classement en Direct
                </motion.button>
              </div>
            )}
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
};
