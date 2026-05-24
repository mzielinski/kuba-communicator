# Kuba – Urządzenie Komunikacyjne

A web-based **Augmentative and Alternative Communication (AAC) device** designed to help individuals with communication
difficulties express themselves using categorised word/phrase buttons. The application runs entirely in the browser,
backed by a lightweight PHP server.

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Project Structure](#project-structure)
- [Requirements](#requirements)
- [Installation & Setup](#installation--setup)
- [User Accounts & Registration](#user-accounts--registration)
- [Roles & Statuses](#roles--statuses)
- [Data Files](#data-files)
- [Configuration](#configuration)
- [API Reference](#api-reference)
- [Development](#development)

---

## Overview

When a user clicks (or dwells over) a word button the application simultaneously:

1. **Speaks** the word aloud via the browser's Web Speech API.
2. **Copies** the word to the clipboard.
3. **Sends** the word to a configured Telegram recipient (if enabled).

A special **alarm button** plays a 6-second beeping alert to call for assistance.

---

## Features

### 🗣️ Text-to-Speech

- Words are spoken aloud using the browser's built-in `SpeechSynthesis` API.
- Language is set to `pl-PL` (Polish) or `en-GB` (English); falls back to the browser default.
- Emoji characters are automatically stripped before speech.

### 📂 Categories & Words

- Words are grouped into **categories** displayed as tiles on the main grid.
- Categories can be **normal** or **expandable** (opens a full-screen word grid).
- Category order, display size, and expand mode are all configurable.
- Each word can have a custom **colour** and **font size**.

### ✏️ Word & Category Management

Accessed via the ⚙️ button in the header. The management modal has four tabs:

| Tab                   | What you can do                                                                 |
|-----------------------|---------------------------------------------------------------------------------|
| **Manage Categories** | Add, rename, resize, reorder, toggle expand mode, or delete categories          |
| **Manage Words**      | Add, edit (text, colour, font size), reorder, or delete words within a category |
| **Global Words**      | Add words that appear across multiple categories with configurable scope        |
| **Settings**          | Configure language, alarm, dwell time, dark mode, and Telegram integration      |

### 👤 User Accounts & Self-Registration

- Users can **register** on the login page with a valid email and password.
- A **confirmation email** is sent; after clicking the link the admin is notified.
- The **admin approves** new accounts (via email link or admin panel).
- Pending users see clear status messages when attempting to log in.

### 👥 Admin User Management Panel

- The **username button** (👤) in the header opens the user management panel.
- **Admins** see all users: email, role, status, creation date, last update.
- Admins can **Approve** accounts (WAITING_FOR_APPROVAL → ACTIVE).
- Admins can **Delete** any user account.
- Regular users see only their own account and can delete it (self-service).

### 👁️ Demo Mode

- A **"Try demo (no account)"** link on the login page auto-logs in a read-only demo session.
- Demo users can explore the full UI but **cannot save** any changes.
- A yellow banner is shown in demo mode with a link to create an account.

### 🚨 Alarm

- Configurable sound profiles (high, mid, low, siren), duration and audio output device.

### 🖱️ Dwell Time

- Hover-to-click for eye-tracking or switch-access hardware.

### 📱 Telegram Integration

- Forward every word to a Telegram chat; multiple named recipients supported.

### 🌙 Dark Mode & 🌐 Language

- Toggle dark mode; select Polish or English — saved per user.

---

## Project Structure

```
kuba-komunikacja/
├── index.php                # Main PHP router — serves public/ files and routes API calls
├── .htaccess                # Blocks direct access to src/ and data/; routes all requests to index.php
├── dev-server.sh            # Quick-start helper for local PHP dev server
│
├── public/                  # All front-end assets (served to the browser)
│   ├── index.html           # Main application shell
│   ├── login.html           # Login + registration page
│   ├── styles.css           # All CSS styles
│   └── js/
│       ├── app.js               # Bootstrap / entry point
│       ├── state.js             # Shared mutable state (categories, preferences, role…)
│       ├── api.js               # All fetch calls to the PHP backend
│       ├── auth.js              # Session check & logout button
│       ├── userManagement.js    # User management modal (admin panel + self-service)
│       ├── renderer.js          # Render category grid & expanded view
│       ├── wordActions.js       # Word-click pipeline (speak → copy → Telegram)
│       ├── speech.js            # Web Speech API wrapper
│       ├── alarm.js             # Alarm sound generation (multiple profiles)
│       ├── dwell.js             # Dwell-time (hover-to-click) behaviour
│       ├── i18n.js              # Internationalization (Polish / English)
│       ├── translations.js      # Translation strings (pl / en)
│       ├── settingsManagement.js # Settings modal
│       └── utils.js             # Shared utilities (toast, clipboard, confirm dialog…)
│
├── src/                     # PHP backend (not directly accessible — routed via index.php)
│   ├── api/
│   │   ├── words-handler.php        # Load / save words.json
│   │   ├── preferences-handler.php  # Load / save preferences.json, Telegram config
│   │   ├── file-handler.php         # Generic file-get helper
│   │   ├── users.php                # User management API (register, approve, delete, list)
│   │   └── notifications-telegram.php  # Telegram message-sending backend
│   ├── auth/
│   │   ├── login.php                # Auth endpoints (login / logout / check-session / demo-login)
│   │   └── confirm-email.php        # Email confirmation & admin approval handler (HTML page)
│   └── core/
│       ├── auth.php                 # Session / authentication helpers (role-aware)
│       ├── credentials.php          # Thread-safe credentials.json CRUD (flock)
│       ├── i18n.php                 # Server-side translations helper
│       └── mailer.php               # Email sending helper (reads .env)
│
└── data/                    # Runtime data (not directly accessible)
    ├── credentials.json         # User accounts (bcrypt passwords, roles, statuses)
    ├── templates/
    │   ├── words.json               # Predefined words template (copied on account activation)
    │   ├── global-words.json        # Predefined global words template
    │   └── emails/
    │       ├── admin-notification.html  # Email template: admin approval request
    │       └── approved-email.html      # Email template: account approved notification
    ├── admin/
    │   ├── words.json
    │   ├── global-words.json
    │   └── preferences.json
    ├── demo/
    │   ├── words.json
    │   └── preferences.json
    └── <derived-from-email>/    # Created automatically on account activation
        ├── words.json
        ├── global-words.json
        └── preferences.json
```

### URL Routing

All HTTP requests are handled by `index.php` (enforced via `.htaccess`):

| Incoming URL                  | Served from                                               |
|-------------------------------|-----------------------------------------------------------|
| `/`                           | `public/index.html`                                       |
| `/login.html`                 | `public/login.html`                                       |
| `/js/*.js`                    | `public/js/`                                              |
| `/login.php`                  | `src/auth/login.php`                                      |
| `/user.php`                   | `src/api/users.php`                                       |
| `/api.php`                    | `src/api/` (words, prefs, etc.)                           |
| `/src/auth/confirm-email.php` | `src/auth/confirm-email.php` (whitelisted in `.htaccess`) |

---

## Requirements

- **PHP 7.4+** with `curl` extension (for Telegram) and `random_bytes` support.
- PHP must be able to send email via `mail()` (configure sendmail or SMTP relay in `php.ini`).
- A modern browser with ES Modules, Web Speech API, and Web Audio API support.

---

## Installation & Setup

### 1. Clone the repository

```bash
git clone <repo-url> kuba-komunikacja
cd kuba-komunikacja
```

### 2. Configure `.env` (app config)

Create `.env` at the **project root**:

```dotenv
# Admin email — receives registration approval requests
ADMIN_EMAIL=admin@yourdomain.com

# Public URL of the application — used in email confirmation links
APP_URL=https://your-app-url.com

# Email sender settings
SMTP_FROM=noreply@yourdomain.com
SMTP_FROM_NAME=Kuba Communication System
```

### 3. Configure `data/.env` (Telegram config)

```dotenv
TELEGRAM_BOT_TOKEN=<your-telegram-bot-token>
```

### 4. Update existing user emails in `credentials.json`

Edit `data/credentials.json` and set real email addresses for the existing accounts:

```json
{
  "users": [
    {
      "email": "your-real-admin@email.com",
      "role": "ADMIN",
      "status": "ACTIVE",
      "data_dir": "admin"
    }
  ]
}
```

> **Note:** The `email` field is the login identifier. The `data_dir` field maps each user to their data directory under
`data/`.

### 5. Set up predefined words template (optional)

Edit `data/templates/words.json` to define the word categories that new users get when they register with "predefined
words" enabled.

### 6. Start the development server

```bash
bash dev-server.sh
```

The app will be available at **http://localhost:8000**.

---

## User Accounts & Registration

### Self-registration flow

1. User clicks **"Create new account"** on the login page.
2. Fills in: email, password (min 8 chars), app language, optional predefined words.
3. Account is created with status `WAITING_FOR_CONFIRMATION`.
4. User receives a **confirmation email** — must click the link to confirm their email address.
5. On confirmation, status changes to `WAITING_FOR_APPROVAL` and the **admin receives an email** with a one-click
   approval link.
6. Admin clicks the approval link (or approves via the admin panel) → status becomes `ACTIVE`, user's data directory is
   created (with optional predefined words copied in).
7. User receives a **"Your account has been approved"** email and can now log in.

### Email templates

Outgoing emails use HTML templates stored in `data/templates/emails/`:

| Template                  | Sent to | Trigger                           |
|---------------------------|---------|-----------------------------------|
| `admin-notification.html` | Admin   | User confirms their email address |
| `approved-email.html`     | User    | Admin approves the account        |

### Admin user management

Access by clicking the **👤 username button** in the top-right header:

- **Admins:** see all users with status, dates, and action buttons (Approve / Delete).
- **Regular users:** see only their own account with a "Delete account" option.

### Demo account

The demo account (role `DEMO`) is a read-only session accessible via the **"Try demo"** link on the login page. No
changes can be saved.

---

## Roles & Statuses

### Roles (set manually in `credentials.json`)

| Role    | Description                                          |
|---------|------------------------------------------------------|
| `ADMIN` | Full access; can approve/delete users                |
| `USER`  | Normal user; can manage their own words and settings |
| `DEMO`  | Read-only access; used for the demo session          |

### Account Statuses

| Status                     | Meaning                                                    |
|----------------------------|------------------------------------------------------------|
| `WAITING_FOR_CONFIRMATION` | Registered; awaiting email confirmation from the user      |
| `WAITING_FOR_APPROVAL`     | Email confirmed; awaiting admin approval                   |
| `ACTIVE`                   | Fully active; can log in                                   |
| `DELETED`                  | Soft-deleted; user cannot log in; data files are preserved |

---

## Data Files

### `credentials.json` schema (per user)

```json
{
  "email": "user@example.com",
  "password": "$2y$12$...",
  "role": "USER",
  "status": "ACTIVE",
  "language": "pl",
  "predefined_words": false,
  "data_dir": "user_at_example_com",
  "confirmation_token": null,
  "admin_approval_token": null,
  "created_at": "2026-05-17T12:00:00+00:00",
  "updated_at": "2026-05-17T12:00:00+00:00"
}
```

> Concurrent writes to `credentials.json` are protected by `flock()` exclusive locks.

---

## Configuration

### Application (`data/.env`)

```dotenv
TELEGRAM_BOT_TOKEN=123456789:AABBcc...
ADMIN_EMAIL=admin@yourdomain.com
APP_URL=http://localhost:8000
SMTP_FROM=noreply@yourdomain.com
SMTP_FROM_NAME=Kuba Communication System
```

---

## API Reference

All API endpoints are accessed via their **public URLs** (routed internally by `index.php`).

### `login.php` → `src/auth/login.php`

| Method | `action` param  | Description                                        |
|--------|-----------------|----------------------------------------------------|
| `POST` | *(none)*        | Login — body: `{ email, password, lang }`          |
| `POST` | `logout`        | Destroy session                                    |
| `GET`  | `check-session` | Returns `{ loggedIn, user: { email, role, … } }`   |
| `GET`  | `demo-login`    | Auto-login as demo user, redirects to `index.html` |

### `user.php` → `src/api/users.php`

| Method | `action` param     | Auth       | Description                                    |
|--------|--------------------|------------|------------------------------------------------|
| `POST` | `register`         | Public     | Register new account; sends confirmation email |
| `GET`  | `list-users`       | Required   | Admin: all users; User: own record             |
| `POST` | `approve-user`     | Admin only | Approve a user — body: `{ email }`             |
| `POST` | `delete-user`      | Required   | Delete user — body: `{ email }` (own or admin) |
| `GET`  | `get-current-user` | Required   | Returns current user's profile                 |

### `src/auth/confirm-email.php` (direct access, whitelisted in `.htaccess`)

| Method | `action` param | `token` param | Description                                      |
|--------|----------------|---------------|--------------------------------------------------|
| `GET`  | `confirm`      | ✅ required    | Confirm user's email (link sent to user's inbox) |
| `GET`  | `approve`      | ✅ required    | Approve account (link sent to admin's inbox)     |

### `api.php` → `src/api/` (words, preferences, Telegram)

| Method | `action` param         | Auth (write blocked for DEMO) | Description            |
|--------|------------------------|-------------------------------|------------------------|
| `GET`  | `load-words`           | Required                      | Load words.json        |
| `GET`  | `load-preferences`     | Optional                      | Load preferences.json  |
| `GET`  | `load-global-words`    | Required                      | Load global-words.json |
| `POST` | `save`                 | Required + write              | Save categories        |
| `POST` | `save-global-words`    | Required + write              | Save global words      |
| `POST` | `save-preferences`     | Required + write              | Save preferences       |
| `POST` | `save-telegram-config` | Required + write              | Save Telegram settings |

---

## Development

```bash
# Start the PHP dev server
bash dev-server.sh

# The app is available at http://localhost:8000
```

The front-end uses **ES Modules** — no build step is required. Edit files under `public/js/` and refresh the browser.

PHP source files live under `src/` and are never served directly to the browser — all access goes through `index.php`.
