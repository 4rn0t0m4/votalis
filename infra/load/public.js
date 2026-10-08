// Lecture publique : accueil, thème, fiche, journal. Cible MVP (CDC 11) : pages publiques p95 < 1 s.
// Exécution : make load (k6 dans un conteneur, sur le réseau du compose, cible http://web).
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  scenarios: {
    lecteurs: { executor: 'ramping-vus', startVUs: 5, stages: [{ duration: '30s', target: 50 }, { duration: '60s', target: 50 }, { duration: '15s', target: 0 }] },
  },
  thresholds: {
    http_req_duration: ['p(95)<1000'],
    http_req_failed: ['rate<0.01'],
  },
};

const BASE = __ENV.BASE_URL || 'http://web';
const THEME = __ENV.THEME_SLUG || 'economie';
const PROPOSAL = __ENV.PROPOSAL_ID || '1';

export default function () {
  const pages = [`${BASE}/`, `${BASE}/themes/${THEME}`, `${BASE}/themes/${THEME}?classement=debattues`, `${BASE}/propositions/${PROPOSAL}`, `${BASE}/journal-de-moderation`, `${BASE}/arbitrages`];
  const res = http.get(pages[Math.floor(Math.random() * pages.length)], { redirects: 3 });
  check(res, { 'statut 200': (r) => r.status === 200 });
  sleep(Math.random() * 2 + 0.5);
}
