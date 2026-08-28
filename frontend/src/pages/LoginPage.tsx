import { LoginView } from '../components/LoginView';
import { useNavigate } from 'react-router-dom';
import type { UserSession } from '../types';
import { useIVote } from '../state/IVoteContext';

export function LoginPage() {
  const navigate = useNavigate();
  const { setUserSession, showToast } = useIVote();

  const handleLoginSuccess = (session: UserSession) => {
    setUserSession(session);
    navigate(session.role === 'superadmin' ? '/superadmin' : '/organizer');
  };

  return <LoginView onLoginSuccess={handleLoginSuccess} onBackToPublic={() => navigate('/')} onShowToast={showToast} />;
}
