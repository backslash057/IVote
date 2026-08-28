import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  X, Plus, Trash2, ArrowRight, ArrowLeft, Sparkles, 
  Check, Calendar, DollarSign, Image, Users, Layers,
  Smartphone, Award, ShieldCheck, Tag
} from 'lucide-react';
import type { Campaign, Candidate } from '../types';
import { formatFCFA, triggerConfetti, triggerSuccessSound } from '../utils/helpers';

interface CreateCampaignModalProps {
  isOpen: boolean;
  onClose: () => void;
  onCreateSuccess: (campaign: Campaign) => void;
  organizerName?: string;
  organizerId?: string;
}

export const CreateCampaignModal: React.FC<CreateCampaignModalProps> = ({
  isOpen,
  onClose,
  onCreateSuccess,
  organizerName = 'Comité Miss & Master UY1',
  organizerId = 'org-1',
}) => {
  const [currentStep, setCurrentStep] = useState<1 | 2 | 3 | 4>(1);

  // Step 1: Campaign Details
  const [title, setTitle] = useState('');
  const [subtitle, setSubtitle] = useState('');
  const [organization, setOrganization] = useState(organizerName);
  const [categoryType, setCategoryType] = useState('Concours Universitaire & Élection');
  const [bannerUrl, setBannerUrl] = useState('https://images.unsplash.com/photo-1511578314322-379afb476865?w=1200&auto=format&fit=crop&q=80');
  const [endDate, setEndDate] = useState('2026-09-30');

  // Step 2: Custom Categories Manager (Requested by user)
  const [categoriesList, setCategoriesList] = useState<string[]>(['Miss', 'Master', 'Prix du Public']);
  const [newCategoryInput, setNewCategoryInput] = useState<string>('');

  // Candidates List
  const [candidates, setCandidates] = useState<Partial<Candidate>[]>([
    {
      id: 'temp-1',
      name: 'Elena Vance',
      category: 'Miss',
      number: 1,
      faculty: 'Faculté des Sciences',
      age: 21,
      bio: 'Présidente du club robotique et engagée dans l\'autonomisation des femmes.',
      votes: 0,
      percentage: 0,
      imageUrl: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=500&auto=format&fit=crop&q=80',
    },
    {
      id: 'temp-2',
      name: 'Malik Touré',
      category: 'Master',
      number: 2,
      faculty: 'Sciences Économiques & Gestion',
      age: 22,
      bio: 'Orateur de débat et porteur de projets agricoles durables.',
      votes: 0,
      percentage: 0,
      imageUrl: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&auto=format&fit=crop&q=80',
    },
  ]);

  // Step 3: Pricing & Payout
  const [pricePerVoteFCFA, setPricePerVoteFCFA] = useState<number>(100);
  const [payoutWalletNumber, setPayoutWalletNumber] = useState('+237 677 00 22 11');
  const [payoutMethod, setPayoutMethod] = useState<'mtn_momo' | 'orange_money' | 'wave'>('mtn_momo');

  if (!isOpen) return null;

  // Add Custom Category Tag
  const handleAddCategoryTag = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    const trimmed = newCategoryInput.trim();
    if (!trimmed) return;
    if (categoriesList.some((c) => c.toLowerCase() === trimmed.toLowerCase())) {
      setNewCategoryInput('');
      return;
    }
    setCategoriesList([...categoriesList, trimmed]);
    setNewCategoryInput('');
  };

  // Remove Category Tag
  const handleRemoveCategoryTag = (catToRemove: string) => {
    if (categoriesList.length <= 1) return;
    setCategoriesList(categoriesList.filter((c) => c !== catToRemove));
  };

  const handleAddCandidate = () => {
    const nextNumber = candidates.length + 1;
    const defaultCat = categoriesList[0] || 'Général';
    setCandidates([
      ...candidates,
      {
        id: `temp-${Date.now()}`,
        name: `Candidat #${nextNumber}`,
        category: defaultCat,
        number: nextNumber,
        faculty: 'Filière / Département',
        age: 20,
        bio: 'Présentation du candidat...',
        votes: 0,
        percentage: 0,
        imageUrl: 'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=500&auto=format&fit=crop&q=80',
      },
    ]);
  };

  const handleRemoveCandidate = (index: number) => {
    if (candidates.length <= 1) return;
    setCandidates(candidates.filter((_, i) => i !== index));
  };

  const handleCandidateChange = (index: number, field: keyof Candidate, value: any) => {
    const updated = [...candidates];
    updated[index] = { ...updated[index], [field]: value };
    setCandidates(updated);
  };

  const handleFinishCreate = () => {
    if (!title.trim()) {
      alert('Veuillez renseigner un titre pour la campagne.');
      setCurrentStep(1);
      return;
    }

    const newCampaign: Campaign = {
      id: `camp-${Date.now()}`,
      title: title.trim() || 'Nouvelle Campagne Officielle 2026',
      subtitle: subtitle.trim() || 'Élection et vote populaire en direct sur IVote Africa',
      organization: organization.trim() || organizerName,
      organizerId: organizerId,
      category: categoryType,
      categories: categoriesList,
      bannerUrl: bannerUrl,
      startDate: new Date().toISOString().split('T')[0],
      endDate: endDate,
      status: 'active',
      totalVotes: 0,
      totalRevenueFCFA: 0,
      pricePerVoteFCFA: Number(pricePerVoteFCFA) || 100,
      allowPublicStats: true,
      candidates: candidates.map((c, idx) => ({
        id: `cand-${Date.now()}-${idx}`,
        name: c.name || `Candidat #${idx + 1}`,
        category: c.category || categoriesList[0] || 'Général',
        number: idx + 1,
        faculty: c.faculty || 'Faculté / Département',
        age: c.age || 21,
        bio: c.bio || 'Candidat officiel de la compétition.',
        votes: 0,
        percentage: 0,
        imageUrl: c.imageUrl || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=500&auto=format&fit=crop&q=80',
        rank: idx + 1,
      })),
    };

    triggerConfetti();
    triggerSuccessSound();
    onCreateSuccess(newCampaign);
    onClose();
  };

  const stepTitles = [
    { num: 1, title: 'Détails & Bannière', icon: <Layers className="w-4 h-4" /> },
    { num: 2, title: 'Catégories & Candidats', icon: <Users className="w-4 h-4" /> },
    { num: 3, title: 'Prix du Vote & Retrait', icon: <DollarSign className="w-4 h-4" /> },
    { num: 4, title: 'Lancement Direct', icon: <Sparkles className="w-4 h-4" /> },
  ];

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
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
        className="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-3xl bg-slate-900 border border-slate-700/80 shadow-2xl p-5 sm:p-7 z-10 text-slate-100"
      >
        {/* Header */}
        <div className="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <span className="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1.5">
              <Sparkles className="w-3.5 h-3.5" /> Assistant Créateur de Campagne
            </span>
            <h3 className="text-lg sm:text-xl font-bold font-display text-white mt-0.5">
              Créer une Nouvelle Campagne de Vote
            </h3>
          </div>
          <button
            onClick={onClose}
            className="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition-colors cursor-pointer"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Stepper Header Pills */}
        <div className="flex items-center justify-between gap-1 sm:gap-2 my-5">
          {stepTitles.map((st) => {
            const isCurrent = currentStep === st.num;
            const isDone = currentStep > st.num;
            return (
              <button
                key={st.num}
                type="button"
                onClick={() => setCurrentStep(st.num as any)}
                className={`flex-1 flex items-center justify-center gap-1.5 py-2 px-1 sm:px-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                  isCurrent
                    ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30'
                    : isDone
                    ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'
                    : 'bg-slate-800/60 text-slate-400 border border-slate-800'
                }`}
              >
                <span className="shrink-0">{st.icon}</span>
                <span className="hidden xs:inline">
                  {st.num}. {st.title}
                </span>
              </button>
            );
          })}
        </div>

        {/* STEP 1: CAMPAIGN DETAILS */}
        {currentStep === 1 && (
          <div className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Titre de la Campagne *
              </label>
              <input
                type="text"
                placeholder="Ex: Élection Miss & Master UY1 2026"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                className="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Sous-titre / Description de l'événement
              </label>
              <input
                type="text"
                placeholder="Ex: Grande Finale Annuelle du Charisme et du Leadership Académique"
                value={subtitle}
                onChange={(e) => setSubtitle(e.target.value)}
                className="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                  Organisation Responsable
                </label>
                <input
                  type="text"
                  placeholder="Ex: Comité Miss & Master UY1"
                  value={organization}
                  onChange={(e) => setOrganization(e.target.value)}
                  className="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                  Type d'Événement
                </label>
                <select
                  value={categoryType}
                  onChange={(e) => setCategoryType(e.target.value)}
                  className="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                >
                  <option value="Concours Universitaire & Élection">Concours Universitaire & Élection</option>
                  <option value="Beauté, Mode & Pageantry">Beauté, Mode & Pageantry</option>
                  <option value="Musique & Récompenses">Musique & Récompenses</option>
                  <option value="Startups, Pitch & Innovation">Startups, Pitch & Innovation</option>
                  <option value="Sports & Athlétisme">Sports & Athlétisme</option>
                </select>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                  Date de Clôture des Votes
                </label>
                <input
                  type="date"
                  value={endDate}
                  onChange={(e) => setEndDate(e.target.value)}
                  className="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                  Image Bannière (URL HD)
                </label>
                <input
                  type="text"
                  value={bannerUrl}
                  onChange={(e) => setBannerUrl(e.target.value)}
                  className="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                />
              </div>
            </div>
          </div>
        )}

        {/* STEP 2: CUSTOM CATEGORIES & CANDIDATES LIST */}
        {currentStep === 2 && (
          <div className="space-y-6">
            
            {/* 1. Dynamic Custom Categories Creator Section */}
            <div className="p-4 rounded-2xl bg-slate-950 border border-emerald-500/30 space-y-3">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Tag className="w-4 h-4 text-emerald-400" />
                  <h4 className="text-xs font-bold text-emerald-300 uppercase tracking-wider">
                    Catégories Personnalisées de la Campagne
                  </h4>
                </div>
                <span className="text-[10px] text-slate-400">
                  {categoriesList.length} catégories définies
                </span>
              </div>
              <p className="text-xs text-slate-400">
                Créez vos propres catégories (ex: Miss, Master, Prix de l'Élégance, etc.). Le public pourra filtrer les candidats par catégorie.
              </p>

              {/* Tag Input Form */}
              <div className="flex items-center gap-2">
                <input
                  type="text"
                  placeholder="Ex: Prix Coup de Cœur, Meilleur Espoir..."
                  value={newCategoryInput}
                  onChange={(e) => setNewCategoryInput(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                      e.preventDefault();
                      handleAddCategoryTag();
                    }
                  }}
                  className="flex-1 px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                />
                <button
                  type="button"
                  onClick={() => handleAddCategoryTag()}
                  className="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1 cursor-pointer transition-colors"
                >
                  <Plus className="w-3.5 h-3.5" /> Ajouter
                </button>
              </div>

              {/* Tags Cloud */}
              <div className="flex flex-wrap gap-2 pt-1">
                {categoriesList.map((cat) => (
                  <span
                    key={cat}
                    className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs font-semibold"
                  >
                    <span>{cat}</span>
                    {categoriesList.length > 1 && (
                      <button
                        type="button"
                        onClick={() => handleRemoveCategoryTag(cat)}
                        className="hover:text-rose-400 transition-colors p-0.5 cursor-pointer"
                      >
                        <X className="w-3 h-3" />
                      </button>
                    )}
                  </span>
                ))}
              </div>
            </div>

            {/* 2. Contestants List */}
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs font-bold text-slate-300 uppercase tracking-wider">
                  Liste des Candidats ({candidates.length})
                </span>
                <button
                  type="button"
                  onClick={handleAddCandidate}
                  className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600/20 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-600 hover:text-white text-xs font-bold transition-all cursor-pointer"
                >
                  <Plus className="w-3.5 h-3.5" /> Ajouter un Candidat
                </button>
              </div>

              <div className="space-y-3 max-h-[300px] overflow-y-auto pr-1">
                {candidates.map((cand, idx) => (
                  <div
                    key={cand.id || idx}
                    className="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2.5"
                  >
                    <div className="flex items-center justify-between">
                      <span className="text-xs font-bold text-emerald-400">
                        Candidat N° {idx + 1}
                      </span>
                      {candidates.length > 1 && (
                        <button
                          type="button"
                          onClick={() => handleRemoveCandidate(idx)}
                          className="text-slate-500 hover:text-rose-400 transition-colors p-1 cursor-pointer"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      )}
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                      <input
                        type="text"
                        placeholder="Nom complet du candidat"
                        value={cand.name}
                        onChange={(e) => handleCandidateChange(idx, 'name', e.target.value)}
                        className="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
                      />
                      
                      {/* Dynamic Category Selector */}
                      <select
                        value={cand.category}
                        onChange={(e) => handleCandidateChange(idx, 'category', e.target.value)}
                        className="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
                      >
                        {categoriesList.map((c) => (
                          <option key={c} value={c}>
                            {c}
                          </option>
                        ))}
                      </select>

                      <input
                        type="text"
                        placeholder="Filière / Département"
                        value={cand.faculty}
                        onChange={(e) => handleCandidateChange(idx, 'faculty', e.target.value)}
                        className="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
                      />
                    </div>

                    <input
                      type="text"
                      placeholder="URL Photo (Lien Unsplash ou direct)"
                      value={cand.imageUrl}
                      onChange={(e) => handleCandidateChange(idx, 'imageUrl', e.target.value)}
                      className="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-300 focus:outline-none focus:border-emerald-500"
                    />
                  </div>
                ))}
              </div>
            </div>

          </div>
        )}

        {/* STEP 3: VOTE PRICING & PAYOUT */}
        {currentStep === 3 && (
          <div className="space-y-5">
            <div className="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-3">
              <label className="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                Prix Unitaire du Vote (FCFA)
              </label>
              <div className="flex items-center gap-3">
                <input
                  type="number"
                  min={50}
                  max={5000}
                  step={50}
                  value={pricePerVoteFCFA}
                  onChange={(e) => setPricePerVoteFCFA(Number(e.target.value))}
                  className="w-40 px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-base font-bold text-emerald-400 font-mono focus:outline-none focus:border-emerald-500"
                />
                <span className="text-xs text-slate-400">
                  Tarif standard recommandé : <strong>100 FCFA</strong> par vote
                </span>
              </div>
            </div>

            <div className="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-3">
              <label className="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                Portefeuille Mobile Money de l'Organisateur (Pour vos retraits)
              </label>
              <div className="grid grid-cols-3 gap-2">
                {[
                  { id: 'mtn_momo', label: 'MTN MoMo' },
                  { id: 'orange_money', label: 'Orange Money' },
                  { id: 'wave', label: 'Wave' },
                ].map((m) => (
                  <button
                    key={m.id}
                    type="button"
                    onClick={() => setPayoutMethod(m.id as any)}
                    className={`py-2 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                      payoutMethod === m.id
                        ? 'bg-emerald-600 text-white shadow'
                        : 'bg-slate-900 text-slate-400 border border-slate-800'
                    }`}
                  >
                    {m.label}
                  </button>
                ))}
              </div>

              <input
                type="text"
                placeholder="+237 6XX XX XX XX (Numéro Mobile Money Organisateur)"
                value={payoutWalletNumber}
                onChange={(e) => setPayoutWalletNumber(e.target.value)}
                className="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
              />
            </div>

            <div className="p-3.5 rounded-2xl bg-emerald-950/40 border border-emerald-500/30 text-xs text-emerald-300 space-y-1">
              <div className="font-bold flex items-center gap-1">
                <ShieldCheck className="w-4 h-4 text-emerald-400" /> Commission IVote : 10%
              </div>
              <div className="text-slate-300">
                Sur chaque vote collecté, vous conservez 90% en solde net immédiatement retirable.
              </div>
            </div>
          </div>
        )}

        {/* STEP 4: REVIEW & LAUNCH */}
        {currentStep === 4 && (
          <div className="space-y-4 text-center">
            <div className="w-14 h-14 rounded-2xl bg-emerald-500/20 border-2 border-emerald-500/40 text-emerald-400 mx-auto flex items-center justify-center shadow-xl shadow-emerald-500/20">
              <Sparkles className="w-7 h-7" />
            </div>

            <div>
              <h4 className="text-xl font-bold font-display text-white">
                Prêt pour la Publication de {title || 'Campagne'} !
              </h4>
              <p className="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                Votre portail de vote est prêt avec {candidates.length} candidats répartis sur {categoriesList.length} catégories, au prix de {formatFCFA(pricePerVoteFCFA)} par vote.
              </p>
            </div>

            {/* Summary Card */}
            <div className="p-4 rounded-2xl bg-slate-950/90 border border-slate-800 text-left text-xs space-y-2">
              <div className="flex justify-between text-slate-400">
                <span>Organisation</span>
                <span className="font-semibold text-white">{organization || organizerName}</span>
              </div>
              <div className="flex justify-between text-slate-400">
                <span>Catégories</span>
                <span className="font-semibold text-emerald-400">{categoriesList.join(', ')}</span>
              </div>
              <div className="flex justify-between text-slate-400">
                <span>Candidats Inscrits</span>
                <span className="font-semibold text-slate-200">{candidates.length} Candidats</span>
              </div>
              <div className="flex justify-between text-slate-400">
                <span>Date de Fin</span>
                <span className="text-slate-200">{endDate}</span>
              </div>
              <div className="flex justify-between text-slate-400 border-t border-slate-800 pt-2 font-bold">
                <span className="text-slate-300">Prix par Vote</span>
                <span className="text-emerald-400">{formatFCFA(pricePerVoteFCFA)}</span>
              </div>
            </div>
          </div>
        )}

        {/* Footer Buttons */}
        <div className="flex items-center justify-between pt-5 mt-5 border-t border-slate-800">
          {currentStep > 1 ? (
            <button
              type="button"
              onClick={() => setCurrentStep((currentStep - 1) as any)}
              className="flex items-center gap-1 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors cursor-pointer"
            >
              <ArrowLeft className="w-3.5 h-3.5" /> Précédent
            </button>
          ) : (
            <div />
          )}

          {currentStep < 4 ? (
            <button
              type="button"
              onClick={() => setCurrentStep((currentStep + 1) as any)}
              className="flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30 transition-all cursor-pointer"
            >
              Continuer <ArrowRight className="w-3.5 h-3.5" />
            </button>
          ) : (
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              type="button"
              onClick={handleFinishCreate}
              className="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold shadow-xl shadow-emerald-600/30 transition-all cursor-pointer"
            >
              <Sparkles className="w-4 h-4" /> Publier & Ouvrir le Vote
            </motion.button>
          )}
        </div>
      </motion.div>
    </div>
  );
};
