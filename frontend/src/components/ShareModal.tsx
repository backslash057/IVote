import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { X, Copy, Check, Link, Share2, MessageCircle } from 'lucide-react';
import type { Candidate } from '../types';

interface ShareModalProps {
  candidate: Candidate | null;
  campaignId: string;
  campaignTitle: string;
  isOpen: boolean;
  onClose: () => void;
  onShowToast: (title: string, desc: string) => void;
}

export const ShareModal: React.FC<ShareModalProps> = ({
  candidate,
  campaignId,
  campaignTitle,
  isOpen,
  onClose,
  onShowToast,
}) => {
  const [copied, setCopied] = useState(false);

  if (!isOpen) return null;

  const targetName = candidate ? candidate.name : campaignTitle;
  const shareUrl = typeof window !== 'undefined' 
    ? `${window.location.origin}/campaign/${encodeURIComponent(campaignId)}${candidate ? `?candidate=${encodeURIComponent(candidate.id)}` : ''}`
    : `/campaign/${encodeURIComponent(campaignId)}`;

  const handleCopy = () => {
    navigator.clipboard.writeText(shareUrl);
    setCopied(true);
    onShowToast('Link Copied!', `Direct voting link for ${targetName} is ready to paste.`);
    setTimeout(() => setCopied(false), 2500);
  };

  const handleWhatsApp = () => {
    const text = encodeURIComponent(
      `🌟 Vote for ${targetName} in the ${campaignTitle} on IVote! Cast your votes securely here: ${shareUrl}`
    );
    window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank');
  };

  const handleTwitter = () => {
    const text = encodeURIComponent(
      `Support ${targetName} in ${campaignTitle} #IVote2026: ${shareUrl}`
    );
    window.open(`https://twitter.com/intent/tweet?text=${text}`, '_blank');
  };

  return (
    <AnimatePresence>
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
          className="relative w-full max-w-md rounded-3xl bg-slate-900 border border-slate-700/60 shadow-2xl p-6 z-10 text-slate-100"
        >
          <button
            onClick={onClose}
            className="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition-colors cursor-pointer"
          >
            <X className="w-4 h-4" />
          </button>

          <div className="flex items-center gap-3 mb-4">
            <div className="w-10 h-10 rounded-2xl bg-violet-600/20 border border-violet-500/30 flex items-center justify-center text-violet-400">
              <Share2 className="w-5 h-5" />
            </div>
            <div>
              <h3 className="text-lg font-bold font-display text-white">
                Share & Boost Votes
              </h3>
              <p className="text-xs text-slate-400">
                {candidate ? `Promote Candidate #${candidate.number} ${candidate.name}` : campaignTitle}
              </p>
            </div>
          </div>

          {/* Quick Copy Link Box */}
          <div className="space-y-4">
            <div className="p-3 rounded-2xl bg-slate-950/80 border border-slate-800 flex items-center gap-2">
              <div className="text-xs text-slate-300 font-mono truncate flex-1">
                {shareUrl}
              </div>
              <motion.button
                whileTap={{ scale: 0.95 }}
                onClick={handleCopy}
                className={`px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all cursor-pointer ${
                  copied
                    ? 'bg-emerald-500 text-slate-950 shadow-md shadow-emerald-500/20'
                    : 'bg-violet-600 hover:bg-violet-500 text-white'
                }`}
              >
                {copied ? (
                  <>
                    <Check className="w-3.5 h-3.5" /> Copied
                  </>
                ) : (
                  <>
                    <Copy className="w-3.5 h-3.5" /> Copy
                  </>
                )}
              </motion.button>
            </div>

            {/* Social Share Buttons */}
            <div className="grid grid-cols-2 gap-2.5">
              <button
                onClick={handleWhatsApp}
                className="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl bg-emerald-600/15 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-600/25 text-xs font-semibold transition-all cursor-pointer"
              >
                <MessageCircle className="w-4 h-4 text-emerald-400" />
                WhatsApp Share
              </button>
              <button
                onClick={handleTwitter}
                className="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl bg-sky-600/15 border border-sky-500/30 text-sky-400 hover:bg-sky-600/25 text-xs font-semibold transition-all cursor-pointer"
              >
                <Link className="w-4 h-4 text-sky-400" />
                Post on X
              </button>
            </div>

            {/* QR Code Quick Mock */}
            <div className="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 text-center space-y-2">
              <div className="w-24 h-24 mx-auto rounded-xl bg-white p-2 shadow-md flex items-center justify-center">
                {/* SVG QR Code Simulation */}
                <svg viewBox="0 0 100 100" className="w-full h-full text-slate-950">
                  <rect width="100" height="100" fill="white" />
                  <rect x="10" y="10" width="30" height="30" fill="black" />
                  <rect x="16" y="16" width="18" height="18" fill="white" />
                  <rect x="20" y="20" width="10" height="10" fill="black" />

                  <rect x="60" y="10" width="30" height="30" fill="black" />
                  <rect x="66" y="16" width="18" height="18" fill="white" />
                  <rect x="70" y="20" width="10" height="10" fill="black" />

                  <rect x="10" y="60" width="30" height="30" fill="black" />
                  <rect x="16" y="66" width="18" height="18" fill="white" />
                  <rect x="20" y="70" width="10" height="10" fill="black" />

                  <rect x="50" y="50" width="10" height="10" fill="black" />
                  <rect x="70" y="60" width="20" height="10" fill="black" />
                  <rect x="50" y="70" width="10" height="20" fill="black" />
                  <rect x="80" y="80" width="10" height="10" fill="black" />
                </svg>
              </div>
              <div className="text-[11px] text-slate-400">
                Scan with phone camera for immediate voting poster access
              </div>
            </div>
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};
