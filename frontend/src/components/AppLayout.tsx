import { Outlet } from 'react-router-dom';
import { Navbar } from './Navbar';
import { ToastContainer } from './ToastContainer';
import { useIVote } from '../state/IVoteContext';

export function AppLayout() {
  const { toasts, dismissToast } = useIVote();

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 selection:bg-emerald-500/30 selection:text-emerald-200 relative overflow-x-hidden">
      <Navbar />
      <main className="relative">
        <Outlet />
      </main>
      <ToastContainer toasts={toasts} onDismiss={dismissToast} />
    </div>
  );
}
