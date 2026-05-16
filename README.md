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
- [User Accounts](#user-accounts)
- [Data Files](#data-files)
- [Configuration](#configuration)
- [API Reference](#api-reference)
- [Development](#development)

---

## Overview

When a user clicks (or dwells over) a word button the application simultaneously:

1. **Speaks** the word aloud via the browser's Web Speech API (Polish locale).
2. **Copies** the word to the clipboard.
3. **Sends** the word to a configured Telegram recipient (if enabled).

A special **alarm button** plays a 6-second beeping alert to call for assistance.

---

## Features

### 🗣️ Text-to-Speech

- Words are spoken aloud using the browser's built-in `SpeechSynthesis` API.
- Language is set to `pl-PL` (Polish); falls back to the browser default when a Polish voice is unavailable.
- Emoji characters are automatically stripped before speech.

### 📂 Categories & Words

- Words are grouped into **categories** displayed as tiles on the main grid.
- Categories can be **normal** (words shown directly inside the tile) or **expandable** (a single button that opens a
  full-screen word grid).
- Category order, display size (`small` / `medium` / `large`), and expand mode are all configurable.
- Each word can have a custom **colour** and **font size**.

### ✏️ Word & Category Management

Accessed via the ⚙️ button in the header. The management modal has four tabs:

| Tab                   | What you can do                                                                                              |
|-----------------------|--------------------------------------------------------------------------------------------------------------|
| **Manage Categories** | Add, rename, resize, reorder, toggle expand mode, or delete categories                                       |
| **Manage Words**      | Add, edit (text, colour, font size), reorder, or delete words within a category                              |
| **Global Words**      | Add words that appear across multiple categories with configurable scope (all categories or expandable only) |
| **Settings**          | Configure language, alarm (sound type, device, duration), dwell time, dark mode, and Telegram integration    |

Changes are applied to the live grid immediately; they are only **persisted** when you click **💾 Save changes**.

### 🚨 Alarm

- A dedicated alarm button (word id `alarm`) generates and plays a synthesised beeping sound.
- **Configurable sound profiles:**
    - **High tone** (900 / 750 Hz) — sharp, attention-grabbing pitch
    - **Mid tone** (500 / 400 Hz) — medium pitch
    - **Low tone** (220 / 160 Hz) — low, gentle pitch
    - **Siren** (150 → 800 Hz sweep) — sweeping frequency
- **Configurable duration:** 1–10 seconds (default: 6 seconds)
- The alarm is routed to a configurable audio output device (see Settings)
- All alarm settings are saved per user in preferences

### 🖱️ Dwell Time (Gaze / Pointer Input)

- When enabled, hovering over a button for a configurable duration (0.5 s – 5 s) **automatically activates** it — no
  click required.
- A visual overlay fills up as the dwell timer counts down.
- The dwell time and enabled state are saved per user in preferences.
- Useful for eye-tracking hardware or switch-access devices.

### 🌍 Global Words

Global words are words that automatically appear in multiple (or all) categories:

- **Scope options:**
    - **All categories** — word appears in every category (normal and expandable)
    - **Expand-only** — word appears only in expanded category views
- Each global word has a unique ID and custom color/size
- Useful for common phrases like "Yes", "No", or "Help" that should be accessible everywhere

### 📱 Telegram Integration

- Optionally forward every selected word/phrase to a Telegram chat.
- **Message format:** `[Category]: [Word/Phrase]` — includes the category to provide context.
- Multiple named recipients can be stored; one is selected as the active target.
- A test button lets you verify the connection from the Settings tab.
- Uses a Telegram Bot configured via an environment variable (see [Configuration](#configuration)).

### 🔊 Audio Device Selection

- Lists all available audio output devices (requires microphone permission to expose device labels).
- The chosen device is saved as a user preference and used for alarm playback.

### 🌙 Dark Mode

- Toggle dark mode in the Settings tab for comfortable use in low-light environments.
- The dark mode preference is saved per user.
- High contrast colours ensure readability in dark backgrounds.

### 🌐 Language Support

- Select between **Polish** and **English** in the Settings tab.
- All UI text, labels, hints, and toast notifications are translated.
- Language preference is saved per user.
- The app automatically uses the browser's Web Speech API for the selected language.

### 📜 Recently Clicked Messages

- A scrollable timeline above the main grid shows the **last 10 clicked words/phrases**.
- Each message displays:
    - The word text
    - The category it came from
    - The timestamp of the click
- Click any recent message to **replay** it (speak, copy, and optionally send to Telegram).
- Useful for reviewing the communication history during a session.

### 🔐 Authentication

- Session-based login with bcrypt-hashed passwords.
- Each user has an isolated data directory (`data/<username>/`).
- A utility script (`hash-passwords.php`) generates bcrypt hashes for new passwords.

---

## Project Structure

```
kuba-komunikacja/
├── index.html               # Main application shell
├── login.html               # Login page
├── styles.css               # All CSS styles
│
├── login.php                # Auth endpoints (login / logout / check-session)
├── api.php                  # REST API router (words, preferences, Telegram config)
├── backend.php              # Telegram message-sending backend
├── hash-passwords.php       # Utility: generate bcrypt password hashes
│
├── handlers/
│   ├── words-handler.php        # Load / save words.json
│   ├── preferences-handler.php  # Load / save preferences.json, Telegram config
│   └── file-handler.php         # Generic file-get helper
│
├── includes/
│   └── auth.php             # Session / authentication helpers
│
├── js/
│   ├── app.js               # Bootstrap / entry point
│   ├── state.js             # Shared mutable state (categories, preferences, recentMessages)
│   ├── api.js               # All fetch calls to the PHP backend
│   ├── auth.js              # Session check & logout button
│   ├── renderer.js          # Render category grid & expanded view, recent messages timeline
│   ├── wordActions.js       # Word-click pipeline (speak → copy → Telegram → track in history)
│   ├── speech.js            # Web Speech API wrapper
│   ├── alarm.js             # Alarm sound generation (multiple profiles), device selection
│   ├── dwell.js             # Dwell-time (hover-to-click) behaviour
│   ├── i18n.js              # Internationalization (Polish / English)
│   ├── settingsManagement.js # Settings modal — categories, words, global words, preferences
│   └── utils.js             # Shared utilities (toast, clipboard, ID generation…)
│
├── data/
│   ├── credentials.json     # User accounts (bcrypt-hashed passwords)
│   ├── .env                 # Secret environment variables (not committed)
│   ├── admin/
│   │   ├── words.json           # Admin's word list
│   │   └── preferences.json     # Admin's preferences
│   ├── demo/
│   │   ├── words.json
│   │   └── preferences.json
│   └── <username>/          # Created automatically on first login
│       ├── words.json
│       └── preferences.json
│
└── dev-server.sh            # Quick-start helper for local PHP dev server
```

---

## Requirements

- **PHP 7.4+** with the `curl` extension enabled (for Telegram).
- A modern browser with support for:
    - Web Speech API (`SpeechSynthesis`)
    - Web Audio API (`OfflineAudioContext`)
    - `navigator.mediaDevices` (for audio device enumeration)
    - ES Modules (`<script type="module">`)

---

## Installation & Setup

### 1. Clone the repository

```bash
git clone <repo-url> kuba-komunikacja
cd kuba-komunikacja
```

### 2. Create the `data/.env` file

This file holds secrets and is **never committed to version control**.

```dotenv
TELEGRAM_BOT_TOKEN=<your-telegram-bot-token>
```

### 3. Configure users

Edit `data/credentials.json`. Passwords must be bcrypt-hashed. Use the helper script to generate hashes:

```bash
php hash-passwords.php
```

Example `credentials.json`:

```json
{
  "users": [
    {
      "username": "admin",
      "password": "$2y$12$..."
    }
  ]
}
```

### 4. Initialize user data directories

Create a directory for each user and seed it with an initial `words.json` and `preferences.json`:

```bash
mkdir -p data/admin
cp data/demo/words.json data/admin/words.json
cp data/demo/preferences.json data/admin/preferences.json
```

### 5. Start the development server

```bash
bash dev-server.sh
```

The app will be available at **http://localhost:8000**.

> The helper script uses PHP's built-in web server. It also loads `data/.env` into the process environment so Telegram
> works locally.

---

## User Accounts

| Username | Description                |
|----------|----------------------------|
| `admin`  | Full administrative access |
| `demo`   | Demo/guest account         |

Each user's word list and preferences are stored independently under `data/<username>/`.

To add a new user:

1. Generate a bcrypt hash with `php hash-passwords.php`.
2. Add the entry to `data/credentials.json`.
3. Create `data/<username>/` with `words.json` and `preferences.json`.

---

## Data Files

### `words.json`

Defines all categories and their words.

```json
{
  "categories": {
    "Category Name": {
      "order": 1,
      "size": "large",
      "cols": 2,
      "rows": 2,
      "expand": false,
      "words": [
        {
          "id": "unique-id",
          "text": "Word Text",
          "color": "#667eea",
          "size": "30px"
        }
      ]
    }
  }
}
```

| Field           | Type                                 | Description                                                    |
|-----------------|--------------------------------------|----------------------------------------------------------------|
| `order`         | number                               | Display order (ascending)                                      |
| `size`          | `"small"` \| `"medium"` \| `"large"` | Visual tile size                                               |
| `cols`          | number                               | Grid columns for this category (affects layout)                |
| `rows`          | number                               | Grid rows for this category (affects layout)                   |
| `expand`        | boolean                              | If `true`, clicking the tile opens a full-screen word grid     |
| `words[].id`    | string                               | Unique identifier; use `"alarm"` to trigger the built-in alarm |
| `words[].text`  | string                               | Display text (can include emoji)                               |
| `words[].color` | string                               | CSS colour for the button background                           |
| `words[].size`  | string                               | CSS font size (e.g. `"30px"`)                                  |

### `global-words.json` (optional per user)

Stores global words that appear across multiple categories.

```json
{
  "words": [
    {
      "id": "yes-global",
      "text": "✅ Yes",
      "color": "#00be00",
      "size": "25px",
      "scope": "all"
    },
    {
      "id": "help-global",
      "text": "🆘 Help",
      "scope": "expand-only"
    }
  ]
}
```

| Field   | Type                       | Description                                                                             |
|---------|----------------------------|-----------------------------------------------------------------------------------------|
| `id`    | string                     | Unique identifier for the global word                                                   |
| `text`  | string                     | Display text (can include emoji)                                                        |
| `color` | string                     | CSS colour for the button background (optional)                                         |
| `size`  | string                     | CSS font size (optional, defaults to `20px` in expanded, `30px` in categories)          |
| `scope` | `"all"` \| `"expand-only"` | Where the word appears: `"all"` = every category, `"expand-only"` = expanded views only |

### `preferences.json`

Stores per-user settings.

```json
{
  "dwellTimeMs": 2000,
  "dwellEnabled": false,
  "selectedAlarmDeviceId": "",
  "alarmType": "high",
  "alarmDuration": 6,
  "darkModeEnabled": false,
  "language": "en",
  "telegramEnabled": false,
  "telegramSelectedChatId": "",
  "telegramChats": [
    {
      "id": "123456789",
      "name": "Jan"
    }
  ]
}
```

| Field                   | Type    | Description                                                |
|-------------------------|---------|------------------------------------------------------------|
| `dwellTimeMs`           | number  | Hover duration before auto-activation (500–5000 ms)        |
| `dwellEnabled`          | boolean | Enable/disable dwell time feature                          |
| `selectedAlarmDeviceId` | string  | Audio device ID for alarm playback; empty = system default |
| `alarmType`             | string  | Alarm sound profile: `high`, `mid`, `low`, or `siren`      |
| `alarmDuration`         | number  | Alarm duration in seconds (1–10)                           |
| `darkModeEnabled`       | boolean | Enable/disable dark mode                                   |
| `language`              | string  | UI language: `pl` (Polish) or `en` (English)               |
| `telegramEnabled`       | boolean | Enable/disable Telegram message forwarding                 |
| `telegramSelectedChatId` | string  | Active recipient's Chat ID                                 |
| `telegramChats[].id`    | string  | Chat ID of a Telegram recipient                            |
| `telegramChats[].name`  | string  | Display name for the recipient                             |

---

## Configuration

### Telegram Bot

1. Create a bot via [@BotFather](https://t.me/BotFather) and copy the token.
2. Store the token in `data/.env`:
   ```dotenv
   TELEGRAM_BOT_TOKEN=123456789:AABBcc...
   ```
3. Find a recipient's Chat ID by messaging [@userinfobot](https://t.me/userinfobot).
4. Add the recipient in the app's **Settings → Telegram** section.

### Alarm Sound

The alarm is a synthesised sound generated entirely in the browser — no audio files required.

**Features:**

- **Multiple sound profiles** — Choose between high, mid, low tones, or a sweeping siren
- **Configurable duration** — Set alarm length from 1–10 seconds
- **Device selection** — Route alarm to any available audio output device
- The chosen profile, duration, and device are saved as user preferences in **Settings → Alarm Settings**

---

## API Reference

All endpoints return JSON.

### `login.php`

| Method | `action` param  | Description                            |
|--------|-----------------|----------------------------------------|
| `POST` | *(none)*        | Login — body: `{ username, password }` |
| `POST` | `logout`        | Destroy session                        |
| `GET`  | `check-session` | Returns `{ loggedIn, user }`           |

### `api.php`

| Method | `action` param         | Description                                                    |
|--------|------------------------|----------------------------------------------------------------|
| `GET`  | `load-words`           | Load current user's `words.json`                               |
| `GET`  | `load-preferences`     | Load current user's `preferences.json`                         |
| `GET`  | `load-global-words`    | Load current user's global words                               |
| `POST` | `save`                 | Save categories and word list — body: `{ categories }`         |
| `POST` | `save-global-words`    | Save global words — body: `{ words }`                          |
| `POST` | `save-preferences`     | Save preferences — body: preference fields (dwell, alarm, etc) |
| `POST` | `save-telegram-config` | Save Telegram enabled state and selected chat                  |
| `POST` | `add-telegram-chat`    | Add a Telegram recipient — body: `{ name, chatId }`            |
| `POST` | `remove-telegram-chat` | Remove a recipient — body: `{ chatId }`                        |
| `POST` | `update-telegram-chat` | Rename a recipient — body: `{ chatId, name }`                  |

### `backend.php`

| Method | `action` (JSON body)       | Description                                  |
|--------|----------------------------|----------------------------------------------|
| `POST` | `send-telegram-message`    | Send a message — body: `{ message, chatId }` |
| `POST` | `test-telegram-connection` | Send a test message — body: `{ chatId }`     |

---

## Development

```bash
# Start the PHP dev server (auto-loads data/.env)
bash dev-server.sh

# Generate a bcrypt hash for a new password
php hash-passwords.php
```

The front-end uses **ES Modules** — no build step is required. Simply edit the files under `js/` and refresh the
browser.

Debug logs from `backend.php` are appended to `debug.log` (auto-rotated when it exceeds 1 MB).

