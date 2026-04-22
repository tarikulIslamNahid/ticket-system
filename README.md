# Ticket Management System

Laravel 13 + Vue 3 ticket platform with AI categorization, realtime replies, and email notifications.

## Prerequisites

- **PHP 8.3+**
- **Composer 2.x**
- **Node.js 22+** and **npm 10+**
- **MySQL 8**
- A **Google AI Studio** API key (free) — [aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey)
- A **Twilio SendGrid** API key with a verified single sender

---

## Setup

### 1. Clone and install

```bash
git clone <repo-url> ticket-system
cd ticket-system

composer install
npm install
```

### 2. Create the environment file

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Create the database

```sql
CREATE DATABASE ticket_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Fill the required keys in `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ticket_system
DB_USERNAME=root
DB_PASSWORD=

GEMINI_API_KEY=YOUR_GEMINI_KEY
GEMINI_MODEL=gemini-2.5-flash

MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=YOUR_SENDGRID_KEY
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@your-verified-sender.com
MAIL_FROM_NAME="Ticket System"

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8081
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

QUEUE_CONNECTION=database
```

> **Notes:**
>
> - `MAIL_USERNAME` is the literal string `apikey` (SendGrid convention).
> - `MAIL_FROM_ADDRESS` must be a verified Single Sender in SendGrid.
> - If `REVERB_PORT=8081` is already in use on Windows, change it and rebuild (`npm run build`).

### 5. Migrate and seed

```bash
php artisan migrate --seed
```

This creates the admin user and 10 demo tickets.

### 6. Build frontend assets

```bash
npm run build
```

---

## Run the Application

Open **four terminals** and run one command in each:

```bash
# Terminal 1 — Laravel HTTP server
php artisan serve

# Terminal 2 — Vite dev server (hot reload)
npm run dev

# Terminal 3 — WebSocket server (realtime + typing)
php artisan reverb:start --port=8081

# Terminal 4 — Queue worker (email delivery)
php artisan queue:work
```

All four are needed for the full experience:

- Skip terminal 3 → realtime and typing indicators stop working.
- Skip terminal 4 → emails queue up but are never delivered.

Open [http://127.0.0.1:8000](http://127.0.0.1:8000) in your browser.

---

## How to Use

### Demo credentials

```text
Admin URL:  http://127.0.0.1:8000/login
Email:      tarikulislamnahid15@gmail.com
Password:   password
```

### Customer side

#### Submit a ticket via form

1. Visit [http://127.0.0.1:8000/contact](http://127.0.0.1:8000/contact).
2. Fill in name, email (or phone), subject, message.
3. Click **Submit ticket**.
4. You are redirected to `/ticket/{token}` — your private tracking page.
5. A confirmation email is sent to the address you provided (requires `queue:work`).

#### Submit a ticket via chat

1. On any public page, click the floating chat button in the bottom-right corner.
2. Fill in the form (name, email/phone, first message) and hit **Start chat**.
3. The widget switches to a conversation view; the session is saved in `localStorage`.
4. Close the tab and reopen `/contact` later — the widget restores your chat automatically.

#### Track and reply to a ticket

1. Open the `/ticket/{token}` link (from the confirmation email or the chat widget).
2. New admin replies appear in real time.
3. Type a reply in the form at the bottom and click **Send reply** — the admin sees it instantly.
4. When the admin closes the ticket, the reply form is replaced by a "ticket closed" message.

### Admin side

#### Log in

Go to `/login` and sign in with the demo credentials above.

#### View all tickets

1. Click **Tickets** in the sidebar.
2. Use the search box (ticket number, name, email, subject) or the status dropdown to filter.
3. Click any row to open the ticket.

#### Reply to a ticket

1. On the ticket detail page, read the conversation thread.
2. If Gemini is configured, an AI-suggested reply appears in the right sidebar and above the reply box.
3. Click **Use AI suggestion** to paste the draft into the textarea — edit as needed.
4. Click **Send reply**. The customer receives an email and sees the reply live if the ticket page is open.
5. Start typing — the customer's window shows "Support is typing…" while you compose.

#### Change ticket status

- On the ticket detail page, use the **Close ticket** / **Reopen ticket** button in the Details card on the right.
- The customer's window reflects the new status instantly; the reply form hides when closed.

---

## Tip: Quick Realtime Demo

Open two windows side by side:

1. **Window A:** admin ticket detail at `/admin/tickets/{id}`
2. **Window B:** public view at `/ticket/{public_token}` for the same ticket

Type in Window A — Window B shows a typing indicator. Send a reply — it appears in Window B instantly. Close the ticket — Window B's reply form disappears.

---

## Troubleshooting

**Emails are not arriving.**

- Confirm `php artisan queue:work`  
- Verify `MAIL_FROM_ADDRESS` is added under **Settings → Sender Authentication** in SendGrid.

**Typing indicator and live replies are not working.**

- Confirm `php artisan reverb:start --port=8081` is running (Terminal 3).
- Check the browser console for WebSocket errors.
- Verify `VITE_REVERB_*` values match `REVERB_*` and re-run `npm run build` after changes.

**Reverb won't start — "Failed to listen on tcp://0.0.0.0:8081".**

- Another process is on that port. Run `netstat -ano | findstr ":8081"` (Windows) or `lsof -i :8081` (macOS/Linux), pick an unused port, update `REVERB_PORT` and re-run `npm run build`.

**AI is always returning "other".**

- `GEMINI_API_KEY` is missing or invalid — the app silently falls back to a keyword classifier. Set a valid key in `.env` and run `php artisan config:clear`.
