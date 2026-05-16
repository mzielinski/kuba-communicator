// ============================================
// ALARM — audio generation and playback
// ============================================
import {showToast, updateStatus} from './utils.js';
import {loadAlarmDevicePreference, saveAlarmDevicePreference, loadAlarmTypePreference, saveAlarmTypePreference} from './api.js';

/**
 * Available alarm sound profiles.
 * Each entry: { label, freqA, freqB, beep?, pause?, oscType? }
 * Special type 'siren' uses a frequency sweep instead of alternating tones.
 */
export const ALARM_PROFILES = {
    'high':  { label: '🔔 Wysokie tony (900 / 750 Hz)',    freqA: 900, freqB: 750, beep: 0.2, pause: 0.1, oscType: 'square' },
    'mid':   { label: '🎵 Średnie tony (500 / 400 Hz)',    freqA: 500, freqB: 400, beep: 0.25, pause: 0.1, oscType: 'square' },
    'low':   { label: '🔊 Niskie tony (220 / 160 Hz)',     freqA: 220, freqB: 160, beep: 0.3, pause: 0.15, oscType: 'square' },
    'siren': { label: '🚨 Syrena (150 → 800 Hz sweep)',    type: 'siren' },
};

/** Convert an AudioBuffer to a WAV Blob */
function audioBufferToWav(buf) {
    const numCh = buf.numberOfChannels;
    const sr = buf.sampleRate;
    const blockAlign = numCh * 2;
    const dataLen = buf.length * blockAlign;
    const ab = new ArrayBuffer(44 + dataLen);
    const view = new DataView(ab);
    const ch = Array.from({length: numCh}, (_, i) => buf.getChannelData(i));

    const ws = (off, s) => {
        for (let i = 0; i < s.length; i++) view.setUint8(off + i, s.charCodeAt(i));
    };
    ws(0, 'RIFF');
    view.setUint32(4, 36 + dataLen, true);
    ws(8, 'WAVE');
    ws(12, 'fmt ');
    view.setUint32(16, 16, true);
    view.setUint16(20, 1, true);
    view.setUint16(22, numCh, true);
    view.setUint32(24, sr, true);
    view.setUint32(28, sr * blockAlign, true);
    view.setUint16(32, blockAlign, true);
    view.setUint16(34, 16, true);
    ws(36, 'data');
    view.setUint32(40, dataLen, true);

    let off = 44;
    for (let i = 0; i < buf.length; i++) {
        for (let j = 0; j < numCh; j++) {
            const s = Math.max(-1, Math.min(1, ch[j][i]));
            view.setInt16(off, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
            off += 2;
        }
    }
    return new Blob([ab], {type: 'audio/wav'});
}

/**
 * Generate alarm sound with configurable duration (useful for testing)
 * @param {string} profileKey - key from ALARM_PROFILES (default: 'high')
 * @param {number} duration - duration in seconds (default: 6)
 */
function generateAlarmAudioBlob(profileKey = 'high', duration = 6) {
    return new Promise((resolve, reject) => {
        try {
            const SAMPLE_RATE = 44100;
            const offline = new OfflineAudioContext(1, SAMPLE_RATE * duration, SAMPLE_RATE);
            const osc = offline.createOscillator();
            const gain = offline.createGain();
            osc.connect(gain);
            gain.connect(offline.destination);

            const profile = ALARM_PROFILES[profileKey] || ALARM_PROFILES['high'];

            if (profile.type === 'siren') {
                // Sweeping siren: low → high → low, repeated
                osc.type = 'sawtooth';
                const SWEEP = 0.75; // seconds per half-sweep
                let t = 0;
                while (t < duration) {
                    osc.frequency.setValueAtTime(150, t);
                    osc.frequency.linearRampToValueAtTime(800, t + SWEEP);
                    gain.gain.setValueAtTime(0.5, t);
                    t += SWEEP;
                    if (t < duration) {
                        osc.frequency.linearRampToValueAtTime(150, t + SWEEP);
                        gain.gain.setValueAtTime(0.5, t);
                        t += SWEEP;
                    }
                }
            } else {
                // Alternating two-tone beep pattern
                const {freqA, freqB, beep = 0.2, pause = 0.1, oscType = 'square'} = profile;
                osc.type = oscType;
                let t = 0, high = true;
                while (t < duration) {
                    osc.frequency.setValueAtTime(high ? freqA : freqB, t);
                    gain.gain.setValueAtTime(0.6, t);
                    gain.gain.setValueAtTime(0.01, t + beep);
                    t += beep + pause;
                    high = !high;
                }
            }

            gain.gain.setValueAtTime(0.0, duration);
            osc.start(0);
            osc.stop(duration);

            offline.startRendering().then(b => resolve(audioBufferToWav(b))).catch(reject);
        } catch (err) {
            reject(err);
        }
    });
}

/** Play the alarm on the configured audio output device */
export async function playAlarm() {
    const alarmAudio = document.getElementById('alarm-audio');
    const deviceSelect = document.getElementById('alarm-output-select');

    showToast('🚨 ALARM - Osoba potrzebuje wsparcia!', 'warning');
    updateStatus('ALARM!');

    // Load selected alarm type preference
    const alarmType = await loadAlarmTypePreference();

    let blob;
    try {
        blob = await generateAlarmAudioBlob(alarmType);
    } catch (err) {
        console.error('Failed to generate alarm audio:', err);
        showToast('Błąd generowania alarmu', 'error');
        updateStatus('Błąd');
        return;
    }

    const url = URL.createObjectURL(blob);
    alarmAudio.src = url;
    alarmAudio.volume = 1.0;

    const device = deviceSelect?.value;
    if (device && alarmAudio.setSinkId) {
        try {
            await alarmAudio.setSinkId(device);
            const name = deviceSelect.options[deviceSelect.selectedIndex].text;
            showToast(`Alarm na: ${name}`, 'success');
        } catch (err) {
            console.warn('Could not set alarm device:', err);
        }
    }

    try {
        await alarmAudio.play();
    } catch (err) {
        console.error('Failed to play alarm:', err);
        showToast('Błąd odtwarzania alarmu', 'error');
        updateStatus('Błąd');
        URL.revokeObjectURL(url);
        return;
    }

    await new Promise(resolve => {
        alarmAudio.onended = resolve;
        setTimeout(resolve, 7000);
    });

    updateStatus('Gotowe');
    showToast('Alarm wyłączony', 'info');
    URL.revokeObjectURL(url);
}

/** Test the currently selected alarm sound (shorter duration) */
async function testAlarmSound(profileKey = 'high') {
    const alarmAudio = document.getElementById('alarm-audio');

    let blob;
    try {
        blob = await generateAlarmAudioBlob(profileKey, 2); // 2-second test
    } catch (err) {
        console.error('Failed to generate test alarm:', err);
        showToast('Błąd generowania dźwięku testowego', 'error');
        return;
    }

    const url = URL.createObjectURL(blob);
    alarmAudio.src = url;
    alarmAudio.volume = 1.0;

    try {
        await alarmAudio.play();
    } catch (err) {
        console.error('Failed to play test alarm:', err);
        showToast('Błąd odtwarzania dźwięku', 'error');
        URL.revokeObjectURL(url);
        return;
    }

    await new Promise(resolve => {
        alarmAudio.onended = resolve;
        setTimeout(resolve, 3000);
    });

    URL.revokeObjectURL(url);
}

/**
 * Request microphone permission so the browser exposes all audio device labels,
 * then immediately stop the stream.
 */
export async function initializeAudioDevices() {
    if (!navigator.mediaDevices?.enumerateDevices) {
        showToast('Twoja przeglądarka nie obsługuje wyboru urządzenia audio', 'warning');
        return;
    }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({audio: true});
        stream.getTracks().forEach(t => t.stop());
        console.log('✅ Microphone permission granted');
    } catch (err) {
        console.warn('Microphone permission denied:', err);
        showToast('💡 Przyznaj dostęp do mikrofonu aby zobaczyć wszystkie urządzenia audio', 'warning');
    }
}

/** Populate #alarm-output-select with available audio outputs */
export async function initializeAlarmDeviceSelector(alarmSelect) {
    if (!navigator.mediaDevices?.enumerateDevices) return;

    alarmSelect.innerHTML = '<option value="">Domyślne</option>';
    const devices = await navigator.mediaDevices.enumerateDevices();
    const outputs = devices.filter(d => d.kind === 'audiooutput');

    outputs.forEach((d, i) => {
        const opt = document.createElement('option');
        opt.value = d.deviceId;
        opt.textContent = d.label || `Speaker ${i + 1}`;
        alarmSelect.appendChild(opt);
    });

    const saved = await loadAlarmDevicePreference();
    if (saved && outputs.some(d => d.deviceId === saved)) alarmSelect.value = saved;

    alarmSelect.addEventListener('change', (e) => {
        const name = e.target.options[e.target.selectedIndex].text;
        if (e.target.value) {
            showToast(`Alarm będzie na: ${name}`, 'info');
            saveAlarmDevicePreference(e.target.value);
        } else {
            showToast('Alarm na domyślnym urządzeniu', 'info');
            saveAlarmDevicePreference('');
        }
    });

    if (outputs.length === 0) showToast('❌ Brak dostępnych urządzeń audio', 'error');
    else if (outputs.length === 1) showToast('📱 Jedno urządzenie audio dostępne', 'info');
    else showToast(`✅ ${outputs.length} urządzeń audio dostępnych`, 'success');
}

/** Populate #alarm-type-select with available alarm profiles and wire up persistence */
export async function initializeAlarmTypeSelector(typeSelect) {
    if (!typeSelect) return;

    // Build options from ALARM_PROFILES
    typeSelect.innerHTML = '';
    Object.entries(ALARM_PROFILES).forEach(([key, profile]) => {
        const opt = document.createElement('option');
        opt.value = key;
        opt.textContent = profile.label;
        typeSelect.appendChild(opt);
    });

    // Load saved preference
    const saved = await loadAlarmTypePreference();
    if (saved && ALARM_PROFILES[saved]) typeSelect.value = saved;

    typeSelect.addEventListener('change', async (e) => {
        const key = e.target.value;
        await saveAlarmTypePreference(key);
        const label = ALARM_PROFILES[key]?.label || key;
        showToast(`🔔 Rodzaj alarmu: ${label}`, 'info');
    });

    // Wire up test button
    const testBtn = document.getElementById('test-alarm-sound-btn');
    if (testBtn) {
        testBtn.addEventListener('click', async () => {
            const selectedType = typeSelect.value || 'high';
            testBtn.disabled = true;
            testBtn.textContent = '🔄 Testowanie...';
            try {
                await testAlarmSound(selectedType);
            } catch (err) {
                console.error('Test failed:', err);
                showToast('Błąd podczas testowania alarmu', 'error');
            } finally {
                testBtn.disabled = false;
                testBtn.textContent = '🔊 Testuj';
            }
        });
    }
}
