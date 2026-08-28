import { Link, Route, Routes } from 'react-router-dom';
import { AppLayout } from './components/AppLayout';
import { HomePage } from './pages/HomePage';
import { LoginPage } from './pages/LoginPage';
import { OrganizerPage } from './pages/OrganizerPage';
import { PublicCampaignPage } from './pages/PublicCampaignPage';
import { SuperAdminPage } from './pages/SuperAdminPage';

function NotFoundPage() {
  return (
    <div className="flex min-h-screen items-center justify-center px-4 pt-16 text-center text-slate-300">
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-emerald-400">404</p>
        <h1 className="mt-3 font-display text-3xl font-bold text-white">Page introuvable.</h1>
        <Link to="/" className="mt-6 inline-flex rounded-xl bg-emerald-600 px-5 py-3 text-xs font-bold text-white hover:bg-emerald-500">Retour à l'accueil</Link>
      </div>
    </div>
  );
}

export default function App() {
  return (
    <Routes>
      <Route element={<AppLayout />}>
        <Route path="/" element={<HomePage />} />
        <Route path="/campaign/:id" element={<PublicCampaignPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/organizer" element={<OrganizerPage />} />
        <Route path="/superadmin" element={<SuperAdminPage />} />
        <Route path="*" element={<NotFoundPage />} />
      </Route>
    </Routes>
  );
}
