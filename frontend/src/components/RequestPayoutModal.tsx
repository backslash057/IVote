import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { X, ArrowRight, DollarSign, Smartphone, ShieldCheck, CheckCircle2, Loader2, Sparkles } from 'lucide-react';
import type { PayoutRequest } from '../types';
import { formatFCFA, triggerConfetti, triggerSuccessSound } from '../utils/helpers';

interface RequestPayoutModalProps {
  isOpen: boolean;
  onClose: () => void;
  availableBalanceFCFA: number;
  campaignTitle: string;
  onRequestPayout: (payout: PayoutRequest) => void;
}

export const RequestPayoutModal: React.FC<RequestPayoutModalProps> = ({
  isOpen,
  onClose,
  availableBalanceFCFA,
  campaignTitle,
  onRequestPayout,
}) => {
  const [requestedAmount, setRequestedAmount] = useState<number>(
    Math.min(availableBalanceFCFA, 500000)
  );
  const [paymentMethod, setPaymentMethod] = useState<'mtn_momo' | 'orange_money'>('mtn_momo');
  const [walletNumber, setWalletNumber] = useState('+237 677 00 22 11');
  const [organizerName, setOrganizerName] = useState('Comité d\'Organisation Miss & Master');
  const [organizerEmail, setOrganizerEmail] = useState('contact@uy1-events.cm');
  const [isSubmitting, setIsSubmitting] = useState(false);

  if (!isOpen) return null;

  const commissionFee = Math.round(requestedAmount * 0.1);
  const netPayout = requestedAmount - commissionFee;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (requestedAmount <= 0 || requestedAmount > availableBalanceFCFA) {
      alert('Montant de retrait invalide ou supérieur au solde disponible.');
      return;
    }

    setIsSubmitting(true);
    setTimeout(() => {
      setIsSubmitting(false);
      const newPayout: PayoutRequest = {
        id: `po-${Date.now()}`,
        organizerId: 'org-1',
        organizerName,
        organizerEmail,
        organizerAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
        campaignId: 'camp-1',
        campaignTitle,
        requestedAmountFCFA: requestedAmount,
        commissionFeeFCFA: commissionFee,
        netPayoutFCFA: netPayout,
        paymentMethod,
        walletNumber,
        status: 'pending',
        requestedAt: 'À l\'instant',
      };

      triggerConfetti();
      triggerSuccessSound();
      onRequestPayout(newPayout);
      onClose();
    }, 600);
  };

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          onClick={onClose}
          className="fixed inset-0 bg-slate-950/85 backdrop-blur-md"
        />

        <motion.div
          initial={{ scale: 0.92, opacity: 0, y: 20 }}
          animate={{ scale: 1, opacity: 1, y: 0 }}
          exit={{ scale: 0.92, opacity: 0, y: 20 }}
          transition={{ type: 'spring', damping: 26, stiffness: 320 }}
          className="relative w-full max-w-lg rounded-3xl bg-slate-900 border border-slate-700/60 shadow-2xl p-6 z-10 text-slate-100"
        >
          <div className="flex items-center justify-between pb-4 border-b border-slate-800">
            <div>
              <span className="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1">
                <DollarSign className="w-3.5 h-3.5" /> Décaissement Mobile Money
              </span>
              <h3 className="text-lg font-bold font-display text-white mt-0.5">
                Demande de Retrait des Fonds
              </h3>
            </div>
            <button
              onClick={onClose}
              className="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition-colors cursor-pointer"
            >
              <X className="w-4 h-4" />
            </button>
          </div>

          <form onSubmit={handleSubmit} className="mt-5 space-y-4">
            {/* Balance info */}
            <div className="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800 flex items-center justify-between">
              <span className="text-xs text-slate-400">Solde Disponible pour Retrait</span>
              <span className="text-base font-bold text-emerald-400 font-mono-numeric">
                {formatFCFA(availableBalanceFCFA)}
              </span>
            </div>

            {/* Requested Amount Slider / Input */}
            <div>
              <div className="flex justify-between text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                <span>Montant à Retirer (FCFA)</span>
                <span className="text-emerald-400 font-mono font-bold">
                  {formatFCFA(requestedAmount)}
                </span>
              </div>
              <input
                type="range"
                min={10000}
                max={availableBalanceFCFA || 100000}
                step={10000}
                value={requestedAmount}
                onChange={(e) => setRequestedAmount(Number(e.target.value))}
                className="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-500"
              />
            </div>

            {/* Payment Method */}
            <div>
              <label className="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                Compte de Réception Mobile Money
              </label>
              <div className="grid grid-cols-2 gap-2">
                {[
                  { id: 'mtn_momo', name: 'MTN MoMo' },
                  { id: 'orange_money', name: 'Orange Money' },
                ].map((item) => (
                  <button
                    key={item.id}
                    type="button"
                    onClick={() => setPaymentMethod(item.id as any)}
                    className={`py-2 px-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                      paymentMethod === item.id
                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30'
                        : 'bg-slate-800/80 text-slate-400 border border-slate-700 hover:text-white'
                    }`}
                  >
                    {item.name}
                  </button>
                ))}
              </div>
            </div>

            {/* Wallet & Account Info */}
            <div className="space-y-2">
              <label className="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                Numéro de Téléphone du Compte Marchand / Perso
              </label>
              <input
                type="text"
                required
                value={walletNumber}
                onChange={(e) => setWalletNumber(e.target.value)}
                placeholder="+237 6XX XX XX XX"
                className="w-full px-3.5 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
              />
            </div>

            {/* Financial Breakdown */}
            <div className="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 text-xs space-y-1.5">
              <div className="flex justify-between text-slate-400">
                <span>Montant Brut Demandé</span>
                <span>{formatFCFA(requestedAmount)}</span>
              </div>
              <div className="flex justify-between text-slate-400">
                <span>Commission Plateforme IVote (10%)</span>
                <span className="text-amber-400">-{formatFCFA(commissionFee)}</span>
              </div>
              <div className="flex justify-between text-slate-200 border-t border-slate-800 pt-1.5 font-bold">
                <span>Net Viré sur votre Compte</span>
                <span className="text-emerald-400 font-mono text-sm">{formatFCFA(netPayout)}</span>
              </div>
            </div>

            {/* Submit */}
            <div className="pt-2">
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                type="submit"
                disabled={isSubmitting}
                className="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30 cursor-pointer disabled:opacity-50"
              >
                {isSubmitting ? (
                  <Loader2 className="w-4 h-4 animate-spin" />
                ) : (
                  <>
                    <Sparkles className="w-4 h-4" /> Soumettre la Demande de Retrait
                  </>
                )}
              </motion.button>
            </div>
          </form>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};
