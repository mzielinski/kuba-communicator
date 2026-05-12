// ============================================
// ALARM — audio generation and playback
// ============================================
import {showToast, updateStatus} from './utils.js';
import {loadAlarmDevicePreference, saveAlarmDevicePreference} from './api.js';

/** Convert an AudioBuffer to a WAV Blob */
function audioBufferToWav(buf) {
    const numCh = buf.numberOfChannels;
    const sr = buf.sampleRate;
    const blockAlign = numCh * 2;                   // 16-bit
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

/** Generate a 6-second beeping alarm sound as a WAV Blob */
function generateAlarmAudioBlob() {
    return new Promise((resolve, reject) => {
        try {
            const AC = window.AudioContext || window.webkitAudioContext;
            const ctx = new AC();
            const offline = new OfflineAudioContext(1, ctx.sampleRate * 6, ctx.sampleRate);
            const osc = offline.createOscillator();
            const gain = offline.createGain();
            osc.connect(gain);
            gain.connect(offline.destination);

            let t = 0, high = true;
            const BEEP = 0.2, PAUSE = 0.1, TOTAL = 6;
            while (t < TOTAL) {
                osc.frequency.setValueAtTime(high ? 900 : 750, t);
                gain.gain.setValueAtTime(0.6, t);
                gain.gain.setValueAtTime(0.01, t + BEEP);
                t += BEEP + PAUSE;
                high = !high;
            }
            gain.gain.setValueAtTime(0.0, TOTAL);
            osc.start(0);
            osc.stop(TOTAL);

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

    let blob;
    try {
        blob = await generateAlarmAudioBlob();
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
