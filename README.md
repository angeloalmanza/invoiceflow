# 🧾 InvoiceFlow

Applicazione web per **gestire clienti e fatture**: crei un cliente, compili una fattura con più righe e IVA, ne segui lo stato (bozza → inviata → pagata), scarichi il PDF e controlli sulla dashboard quanto hai fatturato, incassato e ancora da incassare.

Backend **Laravel** (API REST con token Sanctum) + frontend **Vue 3** (SPA). Interfaccia e PDF sono in italiano, importi in euro.

- **App online:** <https://app-invoiceflow.netlify.app/> (frontend su Netlify)
- **API in produzione:** `https://invoiceflow-api.onrender.com/api` (Render, piano che va in sospensione: la prima richiesta può metterci qualche decina di secondi, per questo il frontend mostra una schermata "Connecting to server..." all'avvio)

## Indice

- [Cosa fa](#-cosa-fa)
- [Stack](#-stack)
- [Avvio rapido](#-avvio-rapido)
- [Configurazione](#-configurazione)
- [API](#-api)
- [Modello dati](#-modello-dati)
- [Come funziona](#-come-funziona)
- [Test](#-test)
- [Struttura del repository](#-struttura-del-repository)
- [Deploy](#-deploy)
- [Limiti noti e cose da fare](#-limiti-noti-e-cose-da-fare)

## ✨ Cosa fa

| Sezione | Descrizione |
|---|---|
| **Registrazione e login** | Account con nome, email e password (minimo 8 caratteri). L'accesso restituisce un token che il frontend invia a ogni richiesta. Le rotte dell'app sono protette: senza login si viene rimandati a `/login`. |
| **Dashboard** | Totale fatturato, **incassato** (fatture pagate), **da incassare** (fatture inviate), numero di fatture per stato (bozze, inviate, pagate) e azioni rapide. |
| **Clienti** | Anagrafica con nome, email, telefono, indirizzo, città, paese e partita IVA. Elenco ordinato per nome con il numero di fatture di ciascun cliente, creazione e modifica in una finestra modale, eliminazione con conferma. |
| **Fatture** | Elenco ordinato per data con filtro per stato (tutte / bozza / inviata / pagata). |
| **Editor fattura** | Cliente, data, scadenza, note, **aliquota IVA** (22% di default) e un numero libero di righe (descrizione, quantità, prezzo unitario). Subtotale, IVA e totale si aggiornano mentre scrivi. |
| **Dettaglio fattura** | Righe, totali, cliente e un pulsante che fa avanzare lo stato: *Segna come Inviata* → *Segna come Pagata*. |
| **PDF** | Scarica `fattura-INV-0001.pdf` generato dal server (DomPDF) con i dati del cliente, la P.IVA, le righe e i totali. |

Numerazione automatica `INV-0001`, `INV-0002`, … indipendente per ogni utente. Ogni utente vede solo i propri clienti e le proprie fatture.

## 🧰 Stack

| Livello | Tecnologie |
|---|---|
| **Backend** | PHP 8.3+ (8.4 nel container), Laravel 13, Laravel Sanctum (token personali), barryvdh/laravel-dompdf |
| **Database** | SQLite in locale, PostgreSQL in produzione (Neon/Render: il container include `pdo_pgsql`) |
| **Frontend** | Vue 3.5 (Composition API), Vue Router 4, Pinia, Axios, Tailwind CSS 4, icone Lucide, Vite 8 |
| **Infrastruttura** | Docker (nginx + PHP-FPM + supervisord), Docker Compose per lo sviluppo, hosting statico per la SPA |

## 🚀 Avvio rapido

### Con Docker Compose

Requisiti: Docker.

```bash
git clone https://github.com/angeloalmanza/invoiceflow.git
cd invoiceflow
touch backend/database/database.sqlite   # file SQLite vuoto, le migrazioni lo riempiono
docker compose up --build
```

- Frontend: <http://localhost:5173>
- API: <http://localhost:8000/api>

Il container del backend applica le migrazioni a ogni avvio.

### Senza Docker

Requisiti: PHP ≥ 8.3 con le estensioni `pdo_sqlite`, `mbstring`, `xml`, `gd`; Composer; Node `^20.19` o `≥ 22.12`.

**Backend**

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed        # opzionale: crea test@example.com (password: password)
php artisan serve          # http://localhost:8000
```

**Frontend** (in un altro terminale)

```bash
cd frontend
npm install
npm run dev                # http://localhost:5173
```

In locale il frontend punta a `http://localhost:8000/api` se `VITE_API_URL` non è impostata. Poi apri la pagina, registra un account e inizia da *Clienti*.

Per provare l'app puoi usare il seeder oppure registrarti dalla schermata di registrazione.

## ⚙️ Configurazione

**Backend** (`backend/.env`, modello in `.env.example`)

| Variabile | Cosa fa |
|---|---|
| `APP_KEY` | Chiave dell'app (`php artisan key:generate`). Obbligatoria in produzione. |
| `DB_CONNECTION` | `sqlite` (default) oppure `pgsql`; con `pgsql` servono anche `DB_URL` o `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. |
| `FRONTEND_URL` | Unica origine ammessa dal CORS (default `http://localhost:5173`). In produzione va messo l'indirizzo del frontend. |
| `PORT` | Porta su cui nginx ascolta nel container (default `8080`). |

**Frontend**

| Variabile | Cosa fa |
|---|---|
| `VITE_API_URL` | Indirizzo base delle API, per esempio `https://invoiceflow-api.onrender.com/api`. Viene incorporata nella build. |

## 🔌 API

Tutte le risposte sono JSON. Le rotte protette richiedono `Authorization: Bearer <token>`.

| Metodo | Percorso | Descrizione |
|---|---|---|
| `POST` | `/api/auth/register` | Crea l'account (limite di 10 richieste al minuto su tutte le rotte `auth`). Campi: `name`, `email`, `password`, `password_confirmation`. Restituisce `user` e `token`. |
| `POST` | `/api/auth/login` | Campi: `email`, `password`. Restituisce `user` e `token`. |
| `POST` | `/api/auth/logout` | Revoca il token corrente. |
| `GET` | `/api/auth/me` | Utente autenticato. |
| `GET` `POST` | `/api/clients` | Elenco (con `invoices_count`) / creazione. |
| `GET` `PUT` `DELETE` | `/api/clients/{id}` | Dettaglio con fatture / modifica / eliminazione. |
| `GET` | `/api/invoices` | Elenco con cliente, dalla più recente. |
| `POST` | `/api/invoices` | Crea la fattura. Richiede `client_id`, `date`, `items[]` (`description`, `quantity`, `unit_price`); opzionali `due_date`, `notes`, `tax_rate`. Numero e totali sono calcolati dal server. |
| `GET` `PUT` `DELETE` | `/api/invoices/{id}` | Dettaglio con righe e cliente / aggiornamento (`status`, `due_date`, `notes`, `tax_rate`, `items`) / eliminazione. |
| `GET` | `/api/invoices/stats` | Totali (`total_invoiced`, `total_paid`, `total_pending`) e conteggi per stato. |
| `GET` | `/api/invoices/{id}/pdf` | Scarica il PDF. |
| `GET` | `/health` | Controllo di salute servito da nginx (risponde `ok`), non passa da Laravel. |

Gli stati validi sono `draft`, `sent`, `paid`. Se la validazione fallisce l'API risponde `422` con gli errori per campo; se una risorsa appartiene a un altro utente risponde `403`; oltre il limite di richieste sulle rotte `auth` risponde `429`.

## 🗂️ Modello dati

```
users ──< clients ──< invoices ──< invoice_items
  └────────────────────^
```

| Tabella | Campi principali |
|---|---|
| `users` | nome, email (unica), password con hash |
| `clients` | `user_id`, nome, email, telefono, indirizzo, città, paese, `vat_number` |
| `invoices` | `user_id`, `client_id`, `number` (unico per utente), `date`, `due_date`, `status` (`draft`/`sent`/`paid`), `notes`, `subtotal`, `tax_rate`, `tax_amount`, `total` |
| `invoice_items` | `invoice_id`, descrizione, quantità, `unit_price`, `total` |
| `personal_access_tokens` | token Sanctum |

Importi in `decimal(10,2)`. Le eliminazioni sono a cascata: cancellare un cliente cancella anche le sue fatture e le relative righe.

## 🔧 Come funziona

- **I totali li calcola il server.** Il frontend mostra un'anteprima mentre si compila, ma `subtotal`, `tax_amount` e `total` vengono ricalcolati dal backend dalle righe e dall'aliquota (IVA arrotondata a 2 decimali). Il client non può imporre un totale diverso.
- **Isolamento per utente.** Ogni controller controlla che la risorsa appartenga a `request()->user()` e restituisce `403` altrimenti; gli elenchi partono sempre dalle relazioni dell'utente. Anche il `client_id` di una fattura (in creazione e in modifica) deve appartenere all'utente, altrimenti la richiesta è respinta con `422`.
- **Numerazione.** Il numero è il massimo esistente dell'utente + 1, quindi non si ripete dopo un'eliminazione; l'unicità è per coppia `(user_id, number)`.
- **Modifica fattura.** Si possono cambiare cliente, date, note, stato, aliquota e righe; subtotale, IVA e totale vengono ricalcolati ogni volta che cambiano righe o aliquota.
- **Autenticazione a token.** Sanctum emette un token personale al login, valido 30 giorni. Il frontend lo salva in `localStorage`, lo aggiunge a ogni richiesta con un interceptor Axios e, su una risposta `401`, lo cancella e rimanda a `/login`. All'avvio il router recupera l'utente con `/auth/me` se esiste un token.
- **Cold start di Render.** `App.vue` mostra una schermata di caricamento finché il backend non risponde, così l'utente non vede errori mentre il servizio si risveglia.
- **Un solo container per il backend.** `docker/start.sh` mette in cache configurazione e rotte, esegue `migrate --force` e lancia `supervisord`, che tiene in piedi nginx e PHP-FPM.

## ✅ Test

```bash
cd backend
vendor/bin/phpunit      # oppure: composer test
```

20 test di funzionalità su database SQLite in memoria: registrazione, login e logout, rotte protette, CRUD dei clienti, calcolo dei totali lato server, numerazione dopo le eliminazioni e tra utenti diversi, cliente di un altro utente respinto, ricalcolo alla modifica di righe e aliquota, avanzamento di stato e statistiche, isolamento tra utenti (lettura, modifica, eliminazione, PDF) e generazione del PDF. La CI di GitHub Actions (`.github/workflows/ci.yml`) esegue i test del backend e la build del frontend a ogni push.

## 📁 Struttura del repository

```
invoiceflow/
├── docker-compose.yml        sviluppo: backend (8000) + frontend (5173)
├── .github/workflows/ci.yml  test backend + build frontend
├── backend/                  API Laravel
│   ├── app/
│   │   ├── Http/Controllers/ Auth, Client, Invoice
│   │   └── Models/           User, Client, Invoice, InvoiceItem
│   ├── routes/api.php        tutte le rotte dell'API
│   ├── database/migrations/  schema
│   ├── tests/Feature/        test di auth, clienti, fatture
│   ├── resources/views/invoices/pdf.blade.php   modello del PDF
│   ├── docker/               nginx.conf, supervisord.conf, start.sh
│   └── Dockerfile            immagine di produzione (PHP 8.4 + nginx)
└── frontend/                 SPA Vue
    ├── src/
    │   ├── views/            Login, Register, Dashboard, Clients, Invoices, InvoiceForm, InvoiceDetail
    │   ├── layouts/          AppLayout (barra laterale scura)
    │   ├── router/           rotte e guardie di autenticazione
    │   ├── stores/auth.js    sessione (Pinia)
    │   └── api/client.js     istanza Axios con token
    └── public/_redirects     fallback SPA per l'hosting statico
```

## 🌍 Deploy

- **Backend:** si costruisce con `backend/Dockerfile` (qualunque servizio che esegue container, per esempio Render). Variabili da impostare: `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `FRONTEND_URL`, `DB_CONNECTION=pgsql` con i dati del database, `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`. Il controllo di salute può puntare a `/health`.
- **Frontend:** `cd frontend && npm run build` produce `dist/`, da pubblicare su un hosting statico. `public/_redirects` (`/* /index.html 200`) riporta ogni percorso alla SPA. `VITE_API_URL` va impostata **prima** della build.

## 🚧 Limiti noti e cose da fare

Elenco onesto di ciò che il codice oggi non fa:

- **Elenchi senza paginazione**: fatture e clienti vengono restituiti per intero.
- **Token in `localStorage`**, quindi esposto a un eventuale XSS (scade dopo 30 giorni).
- **Nessuna verifica dell'email** alla registrazione e nessun recupero password.
- **Un solo formato**: valuta euro e testi in italiano sono fissi, non c'è multilingua né multivaluta.
- **Dati del mittente assenti nel PDF** a parte il nome utente: mancano ragione sociale, P.IVA e IBAN di chi emette la fattura.
- **Nessun test del frontend**: la CI ne verifica solo la build.
