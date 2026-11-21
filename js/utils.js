import { state } from './state.js';
import { i18n } from './config.js';

export const t = (key) => {
    const lang = state.gameState.language || 'pt';
    return i18n[lang] && i18n[lang][key] ? i18n[lang][key] : (i18n['pt'][key] || key);
};

export const showToast = (message, duration = 3000) => {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = 'bg-[#1a1a1b] border border-slate-700 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 animate-pop pointer-events-auto max-w-sm w-full mx-4';
    toast.innerHTML = `
        <div class="text-emerald-400 text-xl"><i class="fas fa-info-circle"></i></div>
        <div class="flex-1 font-bold text-sm leading-tight">${message}</div>
        <button class="text-slate-400 hover:text-white" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(toast);
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }
    }, duration);
};

let audioCtx = null;
export const playSound = (freq, type, duration, vol=0.1) => {
    try {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        if (audioCtx.state === 'suspended') audioCtx.resume();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = type;
        osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
        gain.gain.setValueAtTime(vol, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + duration);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + duration);
    } catch(e) {}
};

export const playClickSound = () => playSound(600, 'sine', 0.1, 0.05);
export const playWinSound = () => { playSound(400, 'sine', 0.1, 0.1); setTimeout(() => playSound(600, 'sine', 0.2, 0.1), 100); };
export const playLoseSound = () => { playSound(300, 'sawtooth', 0.3, 0.1); };

export const fireConfetti = () => {
    if (typeof confetti !== 'undefined') {
        var duration = 3000;
        var animationEnd = Date.now() + duration;
        var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 100 };
        function randomInRange(min, max) { return Math.random() * (max - min) + min; }
        var interval = setInterval(function() {
            var timeLeft = animationEnd - Date.now();
            if (timeLeft <= 0) { return clearInterval(interval); }
            var particleCount = 50 * (timeLeft / duration);
            confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } });
            confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } });
        }, 250);
    }
};

export const formatTime = (seconds) => {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    if (h > 0) return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
};

export const saveLocalState = () => {
    if (state.currentRoom) return; // Não sobrescrever o progresso do Desafio Diário Solo durante partidas Multiplayer
    localStorage.setItem(`termoForca_state_${state.currentMode}`, JSON.stringify({
        date: state.todayDate,
        gameState: state.gameState,
        hints: state.hintsRemaining
    }));
};

export const copyTextToClipboard = async (text) => {
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            // Fall through to legacy textarea method
        }
    }
    try {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.top = '-9999px';
        textArea.style.left = '-9999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(textArea);
        return ok;
    } catch (err) {
        return false;
    }
};

export const generateRandomName = () => {
    const adjectives = ['Lindo', 'Rapido', 'Maluco', 'Esperto', 'Selvagem', 'Brabo', 'Sortudo', 'Doido', 'Agil', 'Forte', 'Feliz', 'Triste', 'Zangado'];
    const nouns = ['Panda', 'Lobo', 'Tigre', 'Dragao', 'Ninja', 'Gato', 'Cachorro', 'Pato', 'Leao', 'Gaviao', 'Sapo', 'Pombo', 'Rato'];
    const num = Math.floor(Math.random() * 1000);
    const adj = adjectives[Math.floor(Math.random() * adjectives.length)];
    const noun = nouns[Math.floor(Math.random() * nouns.length)];
    return `${adj}_${noun}_${num}`;
};
