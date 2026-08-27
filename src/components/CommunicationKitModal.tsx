import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  X, Download, QrCode, Share2, Copy, Check, 
  Smartphone, Sparkles, ExternalLink, Image, Printer, Trophy
} from 'lucide-react';
import { Campaign, Candidate } from '../types';

interface CommunicationKitModalProps {
  campaign: Campaign;
  isOpen: boolean;
  onClose: () => void;
  onShowToast: (title: string, desc: string, type?: 'success' | 'info' | 'warning' | 'error') => void;
}

export const CommunicationKitModal: React.FC<CommunicationKitModalProps> = ({
  campaign,
  isOpen,
  onClose,
  onShowToast,
}) => {
  const [selectedCandidate, setSelectedCandidate] = useState<Candidate>(
    campaign.candidates[0] || {
      id: 'cand-demo',
      name: 'Amina Diallo',
      category: 'Miss',
      number: 1,
      faculty: 'Informatique',
      age: 21,
      bio: '',
      votes: 14820,
      percentage: 34.2,
      imageUrl: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=600',
    }
  );

  const [copiedLink, setCopiedLink] = useState<boolean>(false);

  if (!isOpen) return null;

  const candidateVoteUrl = `https://ivote.africa/v/${campaign.id}/${selectedCandidate.number}`;
  const qrSvgUrl = `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=${encodeURIComponent(candidateVoteUrl)}&color=059669&bgcolor=ffffff`;

  const handleCopyLink = () => {
    navigator.clipboard.writeText(candidateVoteUrl);
    setCopiedLink(true);
    setTimeout(() => setCopiedLink(false), 2500);
    onShowToast('Lien copié !', `Le lien unique de vote pour ${selectedCandidate.name} est dans votre presse-papier.`);
  };

  const handleDownloadFlyer = () => {
    onShowToast(
      'Kit Visuel Téléchargé !',
      `L'affiche promotionnelle HD avec QR Code de ${selectedCandidate.name} a été exportée au format PNG.`
    );
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      {/* Backdrop */}
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        onClick={onClose}
        className="fixed inset-0 bg-slate-950/80 backdrop-blur-md"
      />

      {/* Modal Card */}
      <motion.div
        initial={{ scale: 0.9, opacity: 0 }}
        animate={{ scale: 1, opacity: 1 }}
        exit={{ scale: 0.9, opacity: 0 }}
        className="relative w-full max-w-3xl rounded-3xl bg-slate-900 border border-slate-700/80 shadow-2xl overflow-hidden z-10 text-slate-100 flex flex-col max-h-[90vh]"
      >
        {/* Header */}
        <div className="p-5 sm:p-6 border-b border-slate-800 flex items-center justify-between">
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
              <QrCode className="w-5 h-5" />
            </div>
            <div>
              <h3 className="text-base sm:text-lg font-bold font-display text-white">
                Générateur de Kit de Communication & QR Codes
              </h3>
              <p className="text-xs text-slate-400">
                Affiches, QR codes imprimables et liens directs de campagne
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 rounded-xl bg-slate-800 text-slate-400 hover:text-white cursor-pointer"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Content Body */}
        <div className="p-5 sm:p-6 overflow-y-auto space-y-6">
          {/* Candidate Selector */}
          <div>
            <label className="block text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">
              Sélectionnez un Candidat
            </label>
            <div className="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
              {campaign.candidates.map((cand) => (
                <button
                  key={cand.id}
                  onClick={() => setSelectedCandidate(cand)}
                  className={`flex items-center gap-2 px-3 py-2 rounded-2xl border text-xs font-semibold shrink-0 transition-all cursor-pointer ${
                    selectedCandidate.id === cand.id
                      ? 'bg-emerald-600/20 border-emerald-500 text-emerald-300 shadow-md'
                      : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white'
                  }`}
                >
                  <img
                    src={cand.imageUrl}
                    alt={cand.name}
                    className="w-6 h-6 rounded-full object-cover"
                  />
                  <span>N° {cand.number} - {cand.name}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Flyer Preview + Actions Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
            
            {/* Visual Poster / Flyer Mockup */}
            <div className="relative rounded-2xl overflow-hidden border border-emerald-500/40 bg-slate-950 p-5 shadow-xl text-center space-y-3">
              <div className="text-[10px] uppercase font-mono tracking-widest text-emerald-400 font-extrabold">
                {campaign.title}
              </div>

              <div className="relative w-28 h-28 mx-auto rounded-2xl overflow-hidden border-2 border-emerald-400 shadow-lg">
                <img
                  src={selectedCandidate.imageUrl}
                  alt={selectedCandidate.name}
                  className="w-full h-full object-cover"
                />
                <div className="absolute top-1 left-1 px-2 py-0.5 rounded bg-slate-950/90 text-[10px] font-bold text-white">
                  N° {selectedCandidate.number}
                </div>
              </div>

              <div>
                <h4 className="text-base font-bold text-white">{selectedCandidate.name}</h4>
                <div className="text-xs text-emerald-400 font-semibold">{selectedCandidate.category}</div>
              </div>

              {/* Printable QR in White Card */}
              <div className="p-3 bg-white rounded-xl inline-block shadow-inner mx-auto">
                <img
                  src={qrSvgUrl}
                  alt="QR Code"
                  className="w-28 h-28"
                />
              </div>

              <p className="text-[10px] text-slate-400 font-medium">
                Scannez avec votre appareil photo pour voter via Orange Money & MTN MoMo
              </p>
            </div>

            {/* Right details & download actions */}
            <div className="space-y-4">
              <div className="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
                <div className="text-xs font-semibold text-slate-400">Lien Direct de Vote :</div>
                <div className="flex items-center gap-2">
                  <input
                    type="text"
                    readOnly
                    value={candidateVoteUrl}
                    className="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono text-emerald-400 select-all"
                  />
                  <button
                    onClick={handleCopyLink}
                    className="p-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white cursor-pointer shrink-0 transition-colors"
                  >
                    {copiedLink ? <Check className="w-4 h-4" /> : <Copy className="w-4 h-4" />}
                  </button>
                </div>
              </div>

              <div className="space-y-2.5">
                <button
                  onClick={handleDownloadFlyer}
                  className="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2 cursor-pointer transition-all"
                >
                  <Download className="w-4 h-4" />
                  <span>Télécharger l'Affiche Promotionnelle (PNG HD)</span>
                </button>

                <button
                  onClick={() => {
                    const printWindow = window.open('', '_blank');
                    if (printWindow) {
                      printWindow.document.write(`
                        <html>
                          <head><title>QR Code - ${selectedCandidate.name}</title></head>
                          <body style="text-align:center; font-family:sans-serif; padding:40px;">
                            <h2>${campaign.title}</h2>
                            <h3>VOTEZ CANDIDAT N° ${selectedCandidate.number} : ${selectedCandidate.name}</h3>
                            <img src="${qrSvgUrl}" style="width:300px; height:300px; margin:20px 0;" />
                            <p>Scannez pour voter en direct via Orange Money & MTN MoMo sur ivote.africa</p>
                          </body>
                        </html>
                      `);
                      printWindow.document.close();
                      printWindow.print();
                    }
                  }}
                  className="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-xs font-semibold border border-slate-700 flex items-center justify-center gap-2 cursor-pointer transition-all"
                >
                  <Printer className="w-4 h-4" />
                  <span>Imprimer le QR Code Candidat (PDF/A4)</span>
                </button>
              </div>
            </div>

          </div>
        </div>

        {/* Footer */}
        <div className="p-4 sm:p-5 border-t border-slate-800 bg-slate-950/60 flex justify-end">
          <button
            onClick={onClose}
            className="px-5 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold hover:bg-slate-700 cursor-pointer"
          >
            Fermer
          </button>
        </div>
      </motion.div>
    </div>
  );
};
