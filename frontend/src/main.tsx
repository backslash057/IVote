import {StrictMode} from 'react';
import {createRoot} from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './App.tsx';
import './index.css';
import { IVoteProvider } from './state/IVoteContext';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <BrowserRouter>
      <IVoteProvider>
        <App />
      </IVoteProvider>
    </BrowserRouter>
  </StrictMode>,
);
