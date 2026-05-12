# KUBA - Eye-Tracker AAC Application 👁️

**Komunikacyjne Urządzenie dla Osób ze Spowolnionym Mówiem** _(AAC Device for Polish speakers)_

Complete MVP for eye-tracker-friendly word communication with:
- ✅ Large, high-contrast button layout
- ✅ Polish text-to-speech
- ✅ Automatic clipboard copy
- ✅ Category-based word organization
- ✅ Word management UI (add/remove words on the fly)
- ✅ Custom word colors
- ✅ Auto-generated word IDs from text
- ✅ Desktop-optimized for Windows/macOS
- ✅ Single-page responsive layout

---

## 📁 Project Structure

```
kuba-list-do-kopiowania/
├── index.html           # Main UI
├── styles.css           # Eye-tracker-friendly styling
├── app.js               # JavaScript application logic
├── words.json           # Vocabulary configuration (REQUIRED)
├── api.php              # Backend API for saving word changes
└── README.md            # This file
```

---

## 🚀 Quick Start

### 1. **Development (Local Testing)**

Use Python's built-in server (macOS/Linux/Windows):

```bash
cd /Users/maciezie/Projects/maciezie/kuba/kuba-list-do-kopiowania
python3 -m http.server 8000
```

Then open: **http://localhost:8000**

### 2. **Production Deployment**

Deploy to your web server (Apache, Nginx, etc.):
1. Copy all files to your web server directory
2. Ensure `words.json` is accessible
3. Configure `backend.php` with WhatsApp credentials (see below)

---

## 📝 Configuration

### A. Word List (`words.json`)

Words are organized by categories. Format:

```json
{
  "categories": {
    "Potrzeba": {
      "order": 1,
      "size": "large",
      "cols": 2,
      "rows": 2,
      "words": [
        {
          "id": "help_me",
          "text": "Pomóż mi",
          "color": "#ff6b6b"
        },
        {
          "id": "pee",
          "text": "Siku"
        }
      ]
    },
    "Stan": {
      "order": 2,
      "size": "large",
      "cols": 2,
      "rows": 2,
      "words": [
        {
          "id": "pain",
          "text": "Boli",
          "color": "#ff6b6b"
        }
      ]
    }
  }
}
```

**Category Config Fields:**
- `order`: Display order (1 = first)
- `size`: Layout size - `"small"`, `"medium"`, or `"large"`
- `cols`: Grid columns (for layout)
- `rows`: Grid rows (for layout)

**Word Fields:**
- `id`: Unique identifier (auto-generated from text, internal use only)
- `text`: Display text (spoken & copied to clipboard)
- `color`: (Optional) Custom button color (e.g., `"#ff6b6b"` for red)

**Auto-Generated IDs:**
- IDs are created automatically from word text
- Example: "Pomóż mi" → `pomóż_mi`
- Converted to lowercase, spaces → underscores, special chars removed
- You do NOT need to manually set IDs

### B. Word Management UI

Click the **⚙️** (gear icon) in the header to open the word management modal.

**Features:**
- **Add Word Tab:** Add new words to any category at specific positions
- **View Words Tab:** See all words organized by category with delete buttons
- Changes are automatically saved to `words.json`

**Add Word Process:**
1. Select a category
2. Select position in that category ("At end" or before a specific word)
3. Enter word text
4. (Optional) Enable custom color
5. Click "Dodaj słowo" (Add Word)

**Auto-ID Generation:**
- No need to enter an ID manually
- ID is automatically generated from the word text
- Example: "Pomóż mi" → `pomóż_mi`

---

## 🎨 Customization Guide

### Change Language

Edit `app.js` line ~102:
```javascript
utterance.lang = "pl-PL"; // Change to "en-US", "de-DE", etc.
```

### Change Voice Speed

Edit `app.js` line ~103:
```javascript
utterance.rate = 0.9; // 0.5 = very slow, 1 = normal, 2 = fast
```

### Change Color Scheme

Edit `styles.css`:
```css
/* Change primary gradient color */
.container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Change button colors */
.word-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

### Add Custom Categories

1. Add category to `words.json`:
   ```json
   "Nova Kategoria": {
     "order": 6,
     "size": "medium",
     "cols": 2,
     "rows": 1,
     "words": [
       {
         "id": "custom_word",
         "text": "Custom Word"
       }
     ]
   }
   ```

---

## 🔊 Text-to-Speech (Web Speech API)

The app uses native **Web Speech API** with Polish (`pl-PL`) support.

### Supported Browsers:
- ✅ Chrome/Chromium (Windows, macOS, Linux)
- ✅ Edge (Windows, macOS)
- ✅ Safari (macOS 14.5+)
- ⚠️ Firefox (limited voice options)

### Troubleshooting Speech:
1. Check system speech settings
2. Install Polish language pack on Windows/macOS
3. Try different browser
4. Test at: https://www.google.com/search?q=web+speech+api+demo

---

## 🎵 Audio Device Selection for Alarm

The app has **one audio device selector** (🔊) for selecting where the alarm plays.

### How It Works:
- Click the 🔊 selector in the header to see all available audio devices
- Select which device the alarm should play on (Speaker, Built-in, etc.)
- **Your choice is automatically saved** and restored on next visit
- Alarm will play on the selected device when you click 🚨

### Important Notes:
- **Words (speech)** always play on system's default device (browser limitation)
- **Alarm can be routed to any device** you select
- On macOS: When you plug in Bluetooth headphones, they become default for audio
- If headphones are default, select "Built-in Speaker" (or another device) for alarm
- Alarm will ignore your system default and use YOUR selected device

### Example Setup:
1. Plug in Bluetooth headphones
2. Select "Built-in Speaker" in 🔊 selector
3. Click word → audio plays on headphones (system default)
4. Click alarm 🚨 → loud alarm plays on speaker (your selection)
5. Caregiver hears alarm on speaker! ✅



---

## 🚨 Alarm Feature

The **Alarm button** (🚨) in the header alerts the caregiver when the user needs immediate assistance.

### How It Works:
1. Click the **🚨 Alarm button** in the top right
2. A loud, distinctive alarm tone plays for 6 seconds
3. **The alarm plays on the device you selected in 🔊 Alarm Device selector**
4. The caregiver hears the alarm immediately

### Key Design:
- ✅ **Two separate selectors** - alarm independent of words device
- ✅ User can wear headphones for private word communication
- ✅ Alarm always audible to caregiver on selected speaker
- ✅ Loud and distinctive (900Hz/750Hz alternating tones, 6 seconds)
- ✅ Works on Windows, macOS, and Linux

### Typical Scenario:

**Setup:**
- User wearing Bluetooth headphones
- 🎧 Words → "AirPods" (for private communication)
- 🔊 Alarm → "MacBook Pro Speaker" (for caregiver alert)

**What Happens:**
1. User needs help, clicks 🚨
2. **LOUD ALARM plays from laptop speaker** → Everyone in room hears it
3. Caregiver immediately knows something is wrong and comes to help
4. User can continue using headphones for word communication

### Use Cases:
- User needs immediate help and caregiver must know instantly
- Communication across room without eye contact
- Emergency alert that's always audible
- Caregiver doesn't need to watch screen constantly

---

## 📋 Input Methods

### Mouse/Trackpad
Works immediately - standard click on button

### Eye Tracker (Tobii)
- Eye tracker emulates mouse clicks
- App receives normal click events
- **No special configuration needed**

### Eye Gaze Dwell-to-Click
If your eye tracker supports dwell timing:
1. Configure dwell duration in eye tracker settings (e.g., 500ms)
2. App automatically responds to click event

### Input Methods

### Input Methods

### Keyboard (Fallback)
- **Tab** = move focus between buttons
- **Space/Enter** = click selected button

### Eye-Tracking / Dwell Time (NEW!)
- Simply look at (or hover over) a button for 2 seconds
- Button automatically activates (simulating a click)
- Visual feedback: the button fades to white as you look at it
- When you look away and back - timer resets
- **Configurable:** Press ⚙️ → "Ustawienia" tab to change dwell time (0.5s - 5.0s)
- Standard mouse clicks work normally too

### How Dwell Time Works:
1. **Look at button** → Count down starts (visual feedback shown)
2. **Keep looking** → After X seconds, button activates
3. **Look away** → Timer stops and resets
4. **Look back** → Timer starts again from zero
5. **Safety:** Button won't re-trigger for 500ms after activation

### Benefits:
- ✅ Perfect for eye-tracker systems
- ✅ No need to click for users with movement difficulties
- ✅ Configurable delay - adjust to user comfort
- ✅ Standard clicks still work

## 🚨 Alarm Feature

The **Alarm button** (🚨) in the header alerts the caregiver that the user needs assistance.

### How It Works:
1. Click the **🚨 Alarm button** in the top right
2. A loud, distinctive alarm tone plays for 6 seconds
3. **IMPORTANT: The alarm ALWAYS plays on the system's default speaker/audio device**
4. The caregiver hears the alarm even if the user is wearing headphones

### Key Design:
- ✅ **User can wear headphones** for private word communication (words via headphones)
- ✅ **Alarm always plays on speaker** so caregiver knows user needs help
- ✅ Alarm is LOUD and distinctive (900Hz/750Hz alternating tones)
- ✅ Works on Windows, macOS, and Linux
- ✅ Generated dynamically using Web Audio API

### Use Cases:
- User needs immediate help and caregiver needs to know
- Quick communication across room
- Emergency alert independent of user's device selection
- Caregiver is busy but needs to hear if user calls for help

### How Caregiver Knows User Needs Help:

**Scenario:**
- User is wearing headphones and listening to speech output
- User clicks 🚨 Alarm button
- **LOUD ALARM plays on room speaker** → Caregiver hears it immediately
- Caregiver comes to help

**This solves the problem:** Caregiver doesn't have to stare at screen. When they hear alarm on speaker, they know something is wrong.


### Testing Locally

```bash
# Start server
python3 -m http.server 8000

# Open in browser
open http://localhost:8000
```

### Browser Developer Tools

Press **F12** to open console and check for errors.

### Why One Selector, Not Two?

**Web Speech API Limitation:**
- Words use Web Speech API (browser feature)
- Web Speech API does NOT support device routing
- Words must use the system's default audio device
- This is a browser limitation, cannot be worked around

**Alarm Solution:**
- Alarm uses Web Audio API + `<audio>` element  
- Web Audio supports `setSinkId()` for device routing
- Alarm can play on any device you select
- This gives users flexibility while respecting browser limits

---

## 🐛 Troubleshooting

### Words still play on system default even if I want different device?
**This is expected and cannot be changed.**

Web Speech API (browser feature) does NOT support device routing. Words must use the system's default audio device. This is a limitation of the browser, not the app.

**If you want words on headphones:**
- Set headphones/Bluetooth as system default in System Preferences → Sound
- Select "Built-in Speaker" for alarm in 🔊 selector
- Result: Words on headphones, alarm on speaker ✅

### Alarm not routing to selected device?
1. Reload the page and try again
2. Check browser console (F12) for error messages
3. Try different browser (Chrome vs Safari vs Edge)
4. Device IDs are unique - plugging/unplugging devices changes them

### Cannot see all devices?
1. Browser must ask for microphone permission
2. If you denied it, reload the page and click "Allow"
3. This is required for full device enumeration on macOS/Windows

### Device ID changes between localhost and PHP server?
Device IDs are **unique identifiers** that change when:
- Audio configuration changes  
- You plug/unplug devices
- Browser context changes

This is normal. The app automatically detects which devices are available.

### Words not loading?
- Check browser console (F12)
- Verify `words.json` exists in same directory
- Check JSON syntax at https://jsonlint.com

### Speech not working?
- Enable system text-to-speech (Windows Settings → Speech)
- Install Polish language pack
- Try different browser (Chrome recommended)
- Check browser permissions

### Word management changes not saving?
- Check browser console (F12) for errors
- Verify `api.php` is in the correct directory
- Ensure `words.json` has write permissions (644 or 755)
- Check PHP is enabled on your server

### Audio devices not showing on Windows?
1. **Check Browser Permissions:**
   - Open browser Settings
   - Go to Privacy & Security (or similar)
   - Find Microphone or Media permissions
   - Make sure it's set to "Ask" (not "Block")
   
2. **Grant Permission When Asked:**
   - When you click the speaker icon (🔊), browser will show a permission dialog
   - Click "Allow" to grant microphone access
   - This allows the app to enumerate available audio devices

3. **Check Windows Settings:**
   - Settings → Privacy & Security → Microphone
   - Toggle "Microphone" switch ON
   - Toggle "Microphone access" for your browser ON

4. **Restart Browser:**
   - Close the browser completely
   - Open it again and reload the page
   - Try clicking the speaker icon again

5. **Try Different Browser:**
   - Try Chrome instead of Edge (or vice versa)
   - Chrome usually handles audio device enumeration better
   
**Note:** On Windows, unlike macOS, you may not see a permission dialog immediately. The app tries to access devices quietly first, and only asks for permission if needed.

### Speech always plays from same device on macOS (even after selecting different one)?
This is a **browser limitation**, not an app bug:
- Web Speech API (text-to-speech) in web browsers does NOT support directing output to a specific audio device
- Speech output is controlled by macOS system settings, not by the browser
- **Solution:** Change the default audio device in macOS:
  1. Click the **Apple menu** → **System Settings** (or System Preferences)
  2. Go to **Sound**
  3. Select **Output** tab
  4. Choose your desired device (Headphones, Speakers, etc.)
  5. Speech in the app will now play through that device

**Note:** This is a platform/browser limitation. The app can enumerate devices for informational purposes, but cannot actually control speech output routing.

### Buttons too small/large?
- Use custom colors in `words.json` to emphasize important words
- Or adjust CSS font sizes in `styles.css`

---

## 📱 Browser Requirements

- **Minimum:** Modern browser with Web Speech API support
- **Recommended:** Chrome/Edge on Windows with system speech enabled
- **Tested:** Windows 10+, macOS 11+
- **Resolution:** 1920x1080 or higher recommended for button clarity

---

## ♿ Accessibility Features

- Large, high-contrast buttons (WCAG AAA compliant)
- Keyboard navigation support (Tab + Enter)
- Screen reader compatible
- No time-based interactions (no timeouts for button press)
- Clear status feedback (toast notifications)

---

## 📊 Performance Notes

- App loads instantly (no build step needed)
- Speech synthesis loads on first use
- All processing happens in browser (no server latency)
- WhatsApp fallback works without backend

---

## 🔐 Security

### Current Setup (Development)
- No authentication required
- Localhost safe
- Backend accepts requests from any origin

### For Production:
1. Add CORS validation in `api.php`
2. Implement API key authentication for word management
3. Use HTTPS only
4. Validate all word data inputs
5. Rate limit API calls
6. Use environment variables for any future API keys
7. Make `words.json` read-only except for `api.php` script

---

## 📝 License & Credits

Built for accessibility. Feel free to customize and extend for specific user needs.

**Questions? Issues?** Check browser console (F12) for error messages.

---

## 🎯 Next Steps

1. ✅ Test locally with `python3 -m http.server 8000`
2. ✅ Customize `words.json` with your vocabulary
3. ✅ Test speech with different browsers
4. ✅ Use word management UI to add/remove words
5. ⏳ Deploy to production PHP server
6. ⏳ (Optional) Add WhatsApp integration in the future

**Ready to use!** 🚀

