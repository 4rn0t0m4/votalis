// Participants connectés : une connexion par utilisateur virtuel, puis pages du vote rapide. Cible : p95 < 1 s.
// Le compte est celui du jeu de démonstration (développement seulement). La limitation de débit de la
// connexion (10 par minute) impose de se connecter une seule fois par VU et de rester sous 10 VUs.
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  scenarios: {
    votants: { executor: 'constant-vus', vus: 8, duration: '60s' },
  },
  thresholds: {
    'http_req_duration{page:vote-rapide}': ['p(95)<1000'],
    http_req_failed: ['rate<0.01'],
  },
};

const BASE = __ENV.BASE_URL || 'http://web';
const USER = __ENV.LOGIN || 'participante@example.test';
const PASSWORD = __ENV.PASSWORD || 'mot-de-passe-de-test-123';

export default function () {
  if (__ITER === 0) {
    const login = http.get(`${BASE}/connexion`);
    const token = login.html().find('input[name=_token]').attr('value');
    const auth = http.post(`${BASE}/connexion`, { _token: token, identifier: USER, password: PASSWORD }, { redirects: 0 });
    check(auth, { 'connexion redirige': (r) => r.status === 302 });
  }
  for (let i = 0; i < 5; i++) {
    const page = http.get(`${BASE}/vote-rapide`, { tags: { page: 'vote-rapide' } });
    check(page, { 'vote rapide 200': (r) => r.status === 200 });
    sleep(1);
  }
}
