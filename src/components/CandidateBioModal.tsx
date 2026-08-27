import React from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { X, Award, Sparkles, BookOpen, Quote, Instagram, Heart, Check, Share2, Zap } from 'lucide-react';
import { Candidate } from '../types';
import { formatFCFA } from '../utils/helpers';

interface CandidateBioModalProps {
  candidate: Candidate | null;
  isOpen: boolean;
  onClose: () => void;
  onOpenVote: (candidate: Candidate) => void;
  onShare: (candidate: Candidate) => void;
}

export const CandidateBioModal: React.FC<CandidateBioModalProps> = ({
  candidate,
  isOpen,
  onClose,
  onOpenVote,
  onShare,
}) => {
  if (!candidate) return null;

  return (
    <AnimatePresence>
      {isOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={onClose}
            className="fixed inset-0 bg-slate-950/80 backdrop-blur-md"
          />

          <motion.div
            initial={{ scale: 0.9, opacity: 0, y: 20 }}
            animate={{ scale: 1, opacity: 1, y: 0 }}
            exit={{ scale: 0.9, opacity: 0, y: 20 }}
            transition={{ type: 'spring', damping: 25, stiffness: 300 }}
            className="relative w-full max-w-lg rounded-3xl bg-slate-900 border border-slate-700/60 shadow-2xl p-6 z-10 text-slate-100 overflow-hidden"
          >
            {/* Background Glow */}
            <div className="absolute top-0 right-0 w-48 h-48 bg-emerald-600/15 rounded-full blur-3xl pointer-events-none" />

            <button
              onClick={onClose}
              className="absolute top-4 right-4 z-20 w-8 h-8 rounded-full bg-slate-800/80 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition-colors cursor-pointer"
            >
              <X className="w-4 h-4" />
            </button>

            {/* Candidate Header */}
            <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4 pb-5 border-b border-slate-800">
              <div className="relative">
                <img
                  src={candidate.imageUrl}
                  alt={candidate.name}
                  className="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl object-cover ring-2 ring-emerald-500/40 shadow-xl"
                />
                <span className="absolute -top-2 -left-2 bg-emerald-600 text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full shadow-md">
                  N° {candidate.number}
                </span>
              </div>

              <div className="text-center sm:text-left flex-1">
                <div className="flex items-center justify-center sm:justify-start gap-2 mb-1">
                  <span className="px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    {candidate.category}
                  </span>
                  {candidate.rank && candidate.rank <= 3 && (
                    <span className="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/30">
                      Rang #{candidate.rank}
                    </span>
                  )}
                </div>

                <h3 className="text-2xl font-bold font-display text-white">
                  {candidate.name}
                </h3>

                <p className="text-xs text-slate-400 mt-1 flex items-center justify-center sm:justify-start gap-1">
                  <BookOpen className="w-3.5 h-3.5 text-emerald-400" />
                  {candidate.faculty} • {candidate.age} ans
                </p>

                {candidate.instagram && (
                  <div className="flex items-center justify-center sm:justify-start gap-1 text-xs text-slate-400 mt-1.5">
                    <Instagram className="w-3.5 h-3.5 text-pink-400" />
                    <span>{candidate.instagram}</span>
                  </div>
                )}
              </div>
            </div>

            {/* Candidate Content */}
            <div className="py-5 space-y-4 text-xs sm:text-sm">
              {/* Quote */}
              {candidate.quote && (
                <div className="p-3.5 rounded-2xl bg-emerald-950/40 border border-emerald-500/20 text-emerald-200 italic flex items-start gap-2.5">
                  <Quote className="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
                  <p className="leading-relaxed">{candidate.quote}</p>
                </div>
              )}

              {/* Bio Details */}
              <div>
                <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                  Biographie & Vision du Candidat
                </h4>
                <p className="text-slate-300 leading-relaxed">
                  {candidate.bio}
                </p>
              </div>

              {/* Vote Stats */}
              <div className="grid grid-cols-2 gap-3 p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800">
                <div>
                  <div className="text-[11px] text-slate-400">Total Suffrages Recueillis</div>
                  <div className="text-xl font-bold text-white font-mono-numeric">
                    {candidate.votes.toLocaleString()}
                  </div>
                </div>
                <div>
                  <div className="text-[11px] text-slate-400">Part des Votes</div>
                  <div className="text-xl font-bold text-emerald-400 font-mono-numeric">
                    {candidate.percentage}%
                  </div>
                </div>
              </div>
            </div>

            {/* Actions Footer */}
            <div className="flex items-center gap-3 pt-2">
              <button
                onClick={() => {
                  onClose();
                  onShare(candidate);
                }}
                className="p-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors cursor-pointer"
                title="Partager le profil"
              >
                <Share2 className="w-5 h-5" />
              </button>

              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={() => {
                  onClose();
                  onOpenVote(candidate);
                }}
                className="flex-1 py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30 cursor-pointer"
              >
                <Zap className="w-4 h-4" />
                Voter pour {candidate.name.split(' ')[0]} Maintenant
              </motion.button>
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
};
