import { state } from './state.js';
import { apiCall } from './api.js';
import { updateCountdown, updateLanguageUI } from './ui.js';
import { handleGuess, initMode } from './game.js';
import { searchRoomByCode } from './ui.js'; 
import { showToast } from './utils.js';
import { joinRoom } from './multiplayer.js';

let globalSettings = {};

const fetchSettings = async () => {
    try {
        const data = await apiCall('api/public_settings.php');
        globalSettings = data || {};
        window.FORCA_SETTINGS = globalSettings;
        
        if (globalSettings && globalSettings.donate_link) {
            const btnDonate = document.getElementById('btn-donate');
            if (btnDonate) {
                btnDonate.href = globalSettings.donate_link;
                btnDonate.classList.remove('hidden');
            }
        }

        // Inject Adsterra Social Bar / Global script if configured and provider is adsterra
        if (globalSettings.ad_provider === 'adsterra' && globalSettings.adsterra_social_bar && globalSettings.adsterra_social_bar.trim() !== '') {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = globalSettings.adsterra_social_bar;
            tempDiv.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                if (oldScript.innerHTML) newScript.innerHTML = oldScript.innerHTML;
                document.body.appendChild(newScript);
            });
        }

        // Preload Google H5 Games Ad Placement API if provider is adsense and adsense_code is configured
        if (globalSettings.ad_provider === 'adsense' && globalSettings.adsense_code && globalSettings.adsense_code.trim() !== '') {
            const pubId = globalSettings.adsense_code.trim();
            const script = document.createElement('script');
            script.async = true;
            script.src = `https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=${encodeURIComponent(pubId)}`;
            script.crossOrigin = 'anonymous';
            document.head.appendChild(script);
            window.adsbygoogle = window.adsbygoogle || [];
            window.adBreak = window.adConfig = function(o) { window.adsbygoogle.push(o); };
        }

        // Load Google Analytics (GA4) if configured in admin panel
        if (globalSettings.google_analytics_id && globalSettings.google_analytics_id.trim() !== '') {
            const gaId = globalSettings.google_analytics_id.trim();
            const gaScript = document.createElement('script');
            gaScript.async = true;
            gaScript.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(gaId)}`;
            document.head.appendChild(gaScript);
            window.dataLayer = window.dataLayer || [];
            window.gtag = function(){ window.dataLayer.push(arguments); };
            window.gtag('js', new Date());
            window.gtag('config', gaId);
        }
    } catch (e) {}
};

const loadSponsor = async () => {
    try {
        const data = await apiCall('api/sponsors.php', null);
        if (data && data.image_url) {
            const img = document.getElementById('sponsor-img');
            const link = document.getElementById('sponsor-link');
            const container = document.getElementById('sponsor-container');
            if (img && link && container) {
                img.src = data.image_url;
                link.href = data.target_link;
                container.classList.remove('hidden');
            }
        }
    } catch(e) {}
};

const initApp = () => {
    if (state.playerName) {
        const btnUser = document.getElementById('btn-user');
        if (btnUser) btnUser.classList.add('text-emerald-400');
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    const roomCode = urlParams.get('room');
    if (roomCode) {
        showToast("Entrando na sala...");
        document.getElementById('modes-modal').classList.add('hidden');
        joinRoom(roomCode);
    }
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (!document.getElementById('profile-modal').classList.contains('hidden')) {
                document.getElementById('profile-modal').classList.add('hidden');
            } else if (!document.getElementById('create-room-modal').classList.contains('hidden')) {
                document.getElementById('create-room-modal').classList.add('hidden');
                document.getElementById('modes-modal').classList.remove('hidden');
            } else if (!document.getElementById('public-rooms-modal').classList.contains('hidden')) {
                closePublicRooms(true);
            } else if (!document.getElementById('stats-modal').classList.contains('hidden')) {
                document.getElementById('stats-modal').classList.add('hidden');
            } else if (!document.getElementById('modes-modal').classList.contains('hidden')) {
                document.getElementById('modes-modal').classList.add('hidden');
            }
            return;
        }

        if (e.ctrlKey || e.altKey || e.metaKey) return;
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        const key = e.key.toUpperCase();
        if (/^[A-ZÇ]$/.test(key) && key.length === 1) {
            handleGuess(key);
        }
    });
};

setInterval(updateCountdown, 1000);
updateCountdown();

fetchSettings();
loadSponsor();
const hasRoomParam = new URLSearchParams(window.location.search).has('room');
if (!hasRoomParam) {
    initMode(1);
}
initApp();

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js').then(() => {
        console.log('Service Worker Registered');
    });
}

// --- EXPOSE FOR INLINE ONCLICK ---
import { 
    selectMode, openProfile, saveProfile, toggleRoomPrivacy,
    watchAdToCreateRoom, watchAdForWord, requestExtraWordUI, hostRequestNextMatch,
    closeRoom, toggleEditPermission, kickPlayer, approveJoinRequest, startRoom,
    closeEndgame, loadStats, shareResult, changeNameInLobby, createRoom,
    openPublicRooms, closePublicRooms
} from './ui.js';

window.selectMode = selectMode;
window.openProfile = openProfile;
window.saveProfile = saveProfile;
window.toggleRoomPrivacy = toggleRoomPrivacy;
window.watchAdToCreateRoom = watchAdToCreateRoom;
window.watchAdForWord = watchAdForWord;
window.requestExtraWordUI = requestExtraWordUI;
window.hostRequestNextMatch = hostRequestNextMatch;
window.closeRoom = closeRoom;
window.toggleEditPermission = toggleEditPermission;
window.kickPlayer = kickPlayer;
window.approveJoinRequest = approveJoinRequest;
window.startRoom = startRoom;
window.closeEndgame = closeEndgame;
window.loadStats = loadStats;
window.shareResult = shareResult;
window.changeNameInLobby = changeNameInLobby;
window.createRoom = createRoom;
window.initMode = initMode;
window.openPublicRooms = openPublicRooms;
window.closePublicRooms = closePublicRooms;
window.searchRoomByCode = searchRoomByCode;

import { cancelJoinRequest, setRoomFilter, startMultiplayerFlow, exitLobby, copyLobbyLink } from './multiplayer.js';
window.cancelJoinRequest = cancelJoinRequest;
window.setRoomFilter = setRoomFilter;
window.startMultiplayerFlow = startMultiplayerFlow;
window.exitLobby = exitLobby;
window.copyLobbyLink = copyLobbyLink;
