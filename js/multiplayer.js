import { state } from './state.js';
import { apiCall } from './api.js';
import { showToast, generateRandomName, copyTextToClipboard } from './utils.js';
import { initMode, resetGame, showEndgame, updateUI } from './game.js';
import { closePublicRooms, openCreateRoomSettings } from './ui.js';

export let loadedRooms = [];
export let pendingProfileDialogAction = null;
let isSyncing = false;

export const renderLiveRacePlayersHtml = (players, maxMistakes, roomStatus, isCompact = false) => {
    if (!Array.isArray(players) || players.length === 0) return '';
    return players.map((p, i) => {
        const isMe = p.uuid === state.uuid;
        const mistakes = parseInt(p.mistakes || 0);
        const maxM = parseInt(maxMistakes || 6);
        const livesLeft = Math.max(0, maxM - mistakes);
        const isWinner = p.finished == 1;
        const isEliminated = p.finished == -1;
        const isPlaying = p.finished == 0 && roomStatus === 'playing';

        // Rank badge
        let rankBadge = `<div class="w-6 h-6 rounded-lg bg-[#18181b] border border-slate-700/80 flex items-center justify-center text-[11px] font-black text-slate-400 shrink-0">#${i+1}</div>`;
        if (i === 0 && (p.score > 0 || isWinner)) {
            rankBadge = `<div class="w-6 h-6 rounded-lg bg-amber-500/20 border border-amber-500/50 flex items-center justify-center text-xs text-amber-400 shrink-0 shadow-[0_0_8px_rgba(245,158,11,0.25)]" title="1º Lugar"><i class="fas fa-crown"></i></div>`;
        } else if (i === 1 && p.score > 0) {
            rankBadge = `<div class="w-6 h-6 rounded-lg bg-slate-400/20 border border-slate-400/40 flex items-center justify-center text-xs text-slate-300 shrink-0" title="2º Lugar"><i class="fas fa-medal"></i></div>`;
        } else if (i === 2 && p.score > 0) {
            rankBadge = `<div class="w-6 h-6 rounded-lg bg-orange-700/20 border border-orange-600/40 flex items-center justify-center text-xs text-orange-400 shrink-0" title="3º Lugar"><i class="fas fa-medal"></i></div>`;
        }

        // Status pill
        let statusPill = '';
        if (isWinner) {
            statusPill = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/40"><i class="fas fa-trophy text-amber-400"></i> Acertou!</span>`;
        } else if (isEliminated) {
            statusPill = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/15 text-rose-400 border border-rose-500/30"><i class="fas fa-skull"></i> Enforcado</span>`;
        } else if (isPlaying) {
            const idle = parseInt(p.idle_seconds || 0);
            const timeLeft = Math.max(0, 120 - idle);
            const timerColor = timeLeft <= 20 ? 'text-rose-400 animate-pulse' : (timeLeft <= 45 ? 'text-amber-400' : 'text-slate-400');
            statusPill = `
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> Jogando
                    <span class="${timerColor} font-mono ml-0.5"><i class="fas fa-clock text-[9px]"></i> ${timeLeft}s</span>
                </span>
            `;
        } else {
            statusPill = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-400 border border-slate-700">Aguardando</span>`;
        }

        // Visual life hearts / shields bar
        const lifeSegments = Array.from({ length: maxM }).map((_, idx) => {
            const isAliveSegment = idx < livesLeft;
            return `<div class="h-1.5 flex-1 rounded-full transition-all duration-300 ${isWinner ? 'bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.5)]' : (isAliveSegment ? 'bg-emerald-500' : 'bg-rose-950/80 border border-rose-800/40')}"></div>`;
        }).join('');

        const cardBorder = isWinner
            ? 'border-emerald-500/50 bg-gradient-to-r from-emerald-950/30 to-[#222225]'
            : (isEliminated
                ? 'border-slate-800 bg-[#1a1a1c]/75 opacity-75'
                : (isMe ? 'border-indigo-500/50 bg-gradient-to-r from-indigo-950/40 to-[#252528]' : 'border-slate-700/80 bg-[#232326]'));

        return `
            <div class="p-2.5 rounded-xl border ${cardBorder} transition-all flex flex-col gap-1.5">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        ${rankBadge}
                        <img src="https://api.dicebear.com/9.x/adventurer/svg?seed=${encodeURIComponent(p.player_name)}" class="w-7 h-7 rounded-lg bg-slate-800 border border-slate-700 shrink-0">
                        <div class="flex flex-col min-w-0">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="font-bold text-xs ${isMe ? 'text-indigo-300' : 'text-white'} truncate">${p.player_name}</span>
                                ${isMe ? '<span class="text-[9px] font-black uppercase px-1.5 py-0.2 bg-indigo-500/20 text-indigo-300 rounded border border-indigo-500/30 shrink-0">Você</span>' : ''}
                                ${p.is_host ? '<i class="fas fa-star text-amber-400 text-[10px] shrink-0" title="Anfitrião"></i>' : (p.is_cohost ? '<i class="fas fa-shield-alt text-indigo-300 text-[10px] shrink-0" title="Co-Admin"></i>' : '')}
                            </div>
                            <div class="flex items-center gap-2 text-[11px]">
                                <span class="font-black text-emerald-400">${p.score || 0} pts</span>
                                <span class="text-slate-600">&bull;</span>
                                <span class="text-[10px] ${livesLeft <= 1 && !isWinner ? 'text-rose-400 font-bold' : 'text-slate-400'}">
                                    <i class="fas fa-heart ${livesLeft > 0 ? 'text-rose-500' : 'text-slate-600'} mr-0.5"></i> ${livesLeft}/${maxM} vidas
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="shrink-0">
                        ${statusPill}
                    </div>
                </div>
                <div class="flex items-center gap-1 w-full pt-0.5">
                    ${lifeSegments}
                </div>
            </div>
        `;
    }).join('');
};

export const copyLobbyLink = async () => {
    if (!state.currentRoom) return;
    const link = window.location.origin + window.location.pathname + '?room=' + state.currentRoom.code;
    const ok = await copyTextToClipboard(link);
    if (ok) {
        showToast("Link da sala copiado!");
    } else {
        showToast("Código da sala: " + state.currentRoom.code);
    }
};

export const setRoomFilter = (filter) => {
    window.currentRoomFilter = filter;
    
    ['all', 'public', 'private'].forEach(f => {
        const btn = document.getElementById('filter-room-' + f);
        if (btn) {
            if (f === filter) {
                btn.className = "flex-1 py-2 text-xs font-bold rounded-md bg-emerald-600 text-white transition-colors";
            } else {
                btn.className = "flex-1 py-2 text-xs font-bold rounded-md text-slate-400 hover:text-white transition-colors";
            }
        }
    });

    renderPublicRooms();
};

export const loadPublicRooms = async (isPolling = false) => {
    const list = document.getElementById('public-rooms-list');
    if (!isPolling && list) {
        list.innerHTML = '<div class="text-center text-slate-400 font-bold py-10 uppercase tracking-widest text-sm"><i class="fas fa-spinner fa-spin mr-2"></i> Buscando...</div>';
    }
    
    try {
        const data = await apiCall('api/room_list.php', { uuid: state.uuid });
        
        if (data && data.success) {
            loadedRooms = data.rooms;
            renderPublicRooms();
        } else if (list) {
            list.innerHTML = `<div class="text-center text-rose-500 font-bold py-10 uppercase">${data.error || 'Erro ao buscar salas.'}</div>`;
        }
    } catch (e) {
        if (list) list.innerHTML = '<div class="text-center text-rose-500 font-bold py-10 uppercase">Erro na conexão.</div>';
    }
};

export const renderPublicRooms = () => {
    const list = document.getElementById('public-rooms-list');
    if (!list) return;

    if (loadedRooms.length === 0) {
        list.innerHTML = '<div class="text-center text-slate-500 font-bold py-10 uppercase">Nenhuma sala disponível no momento.</div>';
        return;
    }

    let filter = window.currentRoomFilter || 'all';
    let filtered = loadedRooms;
    if (filter === 'public') filtered = loadedRooms.filter(r => r.is_private == 0);
    if (filter === 'private') filtered = loadedRooms.filter(r => r.is_private == 1);

    if (filtered.length === 0) {
        list.innerHTML = '<div class="text-center text-slate-500 font-bold py-10 uppercase">Nenhuma sala corresponde ao filtro.</div>';
        return;
    }

    list.innerHTML = filtered.map(room => {
        const isPrivate = room.is_private == 1;
        const isPending = state.pendingRoomRequest === room.code;
        const privacyBadge = isPrivate
            ? '<span class="text-xs bg-rose-600 w-6 h-6 flex items-center justify-center rounded text-white" title="Privada"><i class="fas fa-lock"></i></span>'
            : '<span class="text-xs bg-emerald-600 w-6 h-6 flex items-center justify-center rounded text-white" title="Pública"><i class="fas fa-globe"></i></span>';
        const pendingBadge = isPending 
            ? '<span class="text-amber-400 ml-1" title="Aguardando aprovação..."><i class="fas fa-hourglass-half fa-spin"></i></span>' 
            : '';
        const statusBadge = room.status === 'playing'
            ? '<span class="text-[10px] bg-indigo-600 px-1.5 h-6 flex items-center justify-center rounded font-bold text-white uppercase tracking-wider" title="Em Andamento"><i class="fas fa-play text-[8px] mr-1"></i> EM JOGO</span>'
            : (room.status === 'finished' ? '<span class="text-[10px] bg-slate-600 px-1.5 h-6 flex items-center justify-center rounded font-bold text-white uppercase tracking-wider" title="No Lobby"><i class="fas fa-coffee text-[8px] mr-1"></i> LOBBY</span>' : '');

        return `
            <div class="bg-[#2a2a2c] p-4 rounded-xl border border-slate-700 flex justify-between items-center gap-2 hover:border-indigo-500/50 transition-colors">
                <div class="min-w-0 flex-1">
                    <div class="font-black text-white text-lg flex items-center gap-2 truncate">
                        ${room.code} ${privacyBadge} ${statusBadge} ${pendingBadge}
                    </div>
                    <div class="text-xs text-slate-400 uppercase tracking-widest font-bold mt-1 truncate">
                        <i class="fas fa-users mr-1"></i>${room.current_players}/${room.max_players} JOGADOR(ES) &bull; <i class="fas fa-times-circle mr-1"></i>${room.max_mistakes || 6} TENT.
                    </div>
                </div>
                <button onclick="${isPending ? 'cancelJoinRequest()' : (room.is_host ? `window.adWatchedForRoom='${room.code}'; joinRoom('${room.code}')` : ((!isPrivate && room.current_players < room.max_players) ? `joinRoom('${room.code}')` : `requestJoinRoom('${room.code}')`))}" class="shrink-0 ${isPending ? 'bg-rose-600 hover:bg-rose-500 px-3' : 'bg-indigo-600 hover:bg-indigo-500 px-4'} text-white py-2 rounded-lg font-bold text-sm uppercase transition-colors">
                    ${isPending ? '<i class="fas fa-times"></i>' : (room.is_host ? 'GERENCIAR' : 'ENTRAR')}
                </button>
            </div>
        `;
    }).join('');
};

export const startMultiplayerFlow = async () => {
    const data = await apiCall('api/room_check.php', { uuid: state.uuid });
    
    if (data.success && data.has_room) {
        showToast("Recuperando sua sala ativa...");
        document.getElementById('modes-modal').classList.add('hidden');
        window.adWatchedForRoom = data.room_code;
        joinRoom(data.room_code);
    } else {
        document.getElementById('modes-modal').classList.add('hidden');
        openCreateRoomSettings();
    }
};

export const joinRoom = async (code) => {
    if (window.adWatchedForRoom !== code) {
        import('./ui.js').then(ui => {
            ui.simulateAd(() => {
                window.adWatchedForRoom = code;
                joinRoom(code);
            });
        });
        return;
    }

    const myName = state.playerName ? state.playerName : generateRandomName();
    
    const data = await apiCall('api/room_join.php', { room_code: code, uuid: state.uuid, name: myName });
    console.log("joinRoom API response:", data);
    
    if (data.success) {
        if (state.pollRequestInterval) clearInterval(state.pollRequestInterval);
        state.pendingRoomRequest = null;
        
        closePublicRooms(false);
        document.getElementById('modes-modal').classList.add('hidden');
        document.getElementById('endgame-modal').classList.add('hidden');
        
        const roomMode = parseInt(data.mode) || 1;
        state.currentMode = roomMode;
        const titles = {1: 'SOLO', 2: 'DUETO', 4: 'QUARTETO'};
        const titleMode = document.getElementById('title-mode');
        if (titleMode) titleMode.innerText = titles[roomMode] || 'SOLO';

        state.currentRoom = {
            code,
            is_host: data.is_host,
            status: data.status,
            mode: roomMode,
            max_mistakes: parseInt(data.max_mistakes) || 6,
            match_id: null
        };
        state.justJoined = true;
        if (data.max_mistakes) {
            state.gameState.maxMistakes = parseInt(data.max_mistakes);
        }
        
        document.getElementById('lobby-code').innerText = code;
        const link = window.location.origin + window.location.pathname + '?room=' + code;
        document.getElementById('lobby-qrcode').src = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(link);
        
        if (data.status === 'playing') {
            document.getElementById('lobby-modal').classList.add('hidden');
            document.getElementById('game-area').classList.remove('hidden');
            document.getElementById('keyboard').classList.remove('hidden');
            document.getElementById('multiplayer-hud-players-inline').classList.remove('hidden');
        } else {
            document.getElementById('lobby-modal').classList.remove('hidden');
            document.getElementById('game-area').classList.add('hidden');
            document.getElementById('keyboard').classList.add('hidden');
        }
        
        if (data.is_host && (data.status === 'waiting' || data.status === 'finished')) {
            document.getElementById('lobby-host-settings').classList.remove('hidden');
            document.getElementById('lobby-max-players').value = data.max_players || 4;
            document.getElementById('lobby-is-public').checked = (data.is_private == 0);
            document.getElementById('btn-start-room').classList.remove('hidden');
            if (data.status === 'finished') {
                document.getElementById('btn-start-room').innerHTML = '<i class="fas fa-play mr-2"></i> INICIAR NOVA PARTIDA';
            } else {
                document.getElementById('btn-start-room').innerHTML = '<i class="fas fa-play mr-2"></i> INICIAR PARTIDA';
            }
            document.getElementById('lobby-waiting-host').classList.add('hidden');
        } else if (!data.is_host && (data.status === 'waiting' || data.status === 'finished')) {
            document.getElementById('lobby-host-settings').classList.add('hidden');
            document.getElementById('btn-start-room').classList.add('hidden');
            document.getElementById('lobby-waiting-host').classList.remove('hidden');
            if (data.status === 'finished') {
                document.getElementById('lobby-waiting-host').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Aguardando o Host Iniciar Nova Partida...';
            } else {
                document.getElementById('lobby-waiting-host').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Aguardando o Host Iniciar...';
            }
        } else {
            document.getElementById('lobby-host-settings').classList.add('hidden');
            document.getElementById('btn-start-room').classList.add('hidden');
            document.getElementById('lobby-waiting-host').classList.add('hidden');
        }
        
        if (state.syncInterval) clearInterval(state.syncInterval);
        state.syncInterval = setInterval(() => syncRoom('ping'), 1000);
        syncRoom('ping');
    } else {
        if (data.error === 'Esta sala é privada e você não foi aceito.' || data.error === 'A sala está cheia.') {
            requestJoinRoom(code);
        } else {
            showToast(data.error);
            window.history.pushState({}, '', window.location.pathname);
        }
    }
};

export const syncRoom = async (action = 'update') => {
    if (!state.currentRoom) return;
    if (action === 'ping' && isSyncing) return;
    isSyncing = true;
    
    try {
    let payload = {
        room_code: state.currentRoom.code,
        uuid: state.uuid,
        action: action
    };

    if (action === 'update' && state.gameState) {
        payload.match_id = state.currentRoom.match_id || '';
        payload.mistakes = state.gameState.mistakes;
        payload.completed = state.gameState.boardState ? state.gameState.boardState.filter(b => b.completed).length : 0;
        payload.finished = state.gameState.isGameOver ? (state.gameState.boardState && state.gameState.boardState.every(b => b.completed) ? 1 : -1) : 0;
        if (state.gameState.scoreIncrement) {
            payload.score_increment = state.gameState.scoreIncrement;
            state.gameState.scoreIncrement = 0;
        }
    }

    const data = await apiCall('api/room_sync.php', payload);
    if (!data.success) {
        if (data.error) showToast(data.error);
        if (!data.keep_room) {
            exitLobby();
        }
        return;
    }

    const previousStatus = state.currentRoom.status;
    const previousMatchId = state.currentRoom.match_id;
    state.currentRoom.status = data.status;
    state.currentRoom.started_at = data.started_at;
    state.currentRoom.elapsed_seconds = typeof data.elapsed_seconds === 'number' ? data.elapsed_seconds : 0;
    state.currentRoom.active_players_count = typeof data.active_players_count === 'number' ? data.active_players_count : 0;
    state.currentRoom.last_sync_ts = Date.now();
    if (data.mode) {
        state.currentRoom.mode = parseInt(data.mode);
        state.currentMode = parseInt(data.mode);
    }
    state.amIHost = data.is_host;
    state.amICohost = data.is_cohost;
    
    if (data.max_mistakes) {
        state.currentRoom.max_mistakes = parseInt(data.max_mistakes);
        state.gameState.maxMistakes = parseInt(data.max_mistakes);
    }


    const playersDiv = document.getElementById('lobby-players');
    if (playersDiv && !document.getElementById('lobby-modal').classList.contains('hidden')) {
        const cCount = document.getElementById('lobby-count');
        if (cCount) cCount.innerText = data.players.length;
        
        playersDiv.innerHTML = data.players.map((p, i) => {
            const isMe = p.uuid === state.uuid;
            return `
                <div class="flex items-center justify-between p-2 rounded-lg bg-[#2a2a2c] border border-slate-700">
                    <div class="flex items-center gap-2 min-w-0">
                        <img src="https://api.dicebear.com/9.x/adventurer/svg?seed=${encodeURIComponent(p.player_name)}" class="w-8 h-8 rounded bg-slate-800 shrink-0">
                        <div class="flex flex-col truncate">
                            <span class="font-bold text-sm text-white truncate flex items-center gap-1">
                                ${p.player_name} 
                                ${p.is_host ? '<i class="fas fa-star text-indigo-400 text-xs" title="Dono da Sala"></i>' : (p.is_cohost ? '<i class="fas fa-star text-indigo-200 text-xs" title="Co-Admin"></i>' : '')}
                            </span>
                            <span class="text-xs text-emerald-400 font-bold flex items-center gap-1">
                                ${p.score} pts
                                ${i === 0 && p.score > 0 ? '<i class="fas fa-crown text-amber-400 text-xs" title="1º Lugar (Ouro)"></i>' : (i === 1 && p.score > 0 ? '<i class="fas fa-crown text-slate-300 text-xs" title="2º Lugar (Prata)"></i>' : (i === 2 && p.score > 0 ? '<i class="fas fa-crown text-orange-700 text-xs" title="3º Lugar (Bronze)"></i>' : ''))}
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-1 shrink-0 ml-2">
                        ${isMe ? `
                            <button onclick="changeNameInLobby()" class="text-xs bg-indigo-600 hover:bg-indigo-500 text-white px-2 py-1 rounded transition-colors" title="Mudar Apelido"><i class="fas fa-edit"></i></button>
                        ` : ''}
                        ${data.is_host && !p.is_host ? `
                            <button onclick="promotePlayer('${p.uuid}', ${p.is_cohost ? '0' : '1'})" class="text-xs ${p.is_cohost ? 'bg-slate-600 hover:bg-slate-500' : 'bg-amber-500 hover:bg-amber-400 text-black'} px-2 py-1 rounded transition-colors" title="${p.is_cohost ? 'Remover Co-Admin' : 'Promover a Co-Admin'}"><i class="fas ${p.is_cohost ? 'fa-user-minus' : 'fa-user-tie'}"></i></button>
                        ` : ''}
                        ${(data.is_host || state.amICohost) && !p.is_host && !p.is_cohost ? `
                            <button onclick="kickPlayer('${p.uuid}')" class="text-xs bg-rose-600 hover:bg-rose-500 text-white px-2 py-1 rounded transition-colors" title="Expulsar"><i class="fas fa-sign-out-alt"></i></button>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    if ((data.is_host || data.is_cohost) && data.requests !== undefined) {
        const reqContainer = document.getElementById('lobby-requests-container');
        const reqDiv = document.getElementById('lobby-requests');
        if (reqContainer && reqDiv) {
            reqContainer.classList.remove('hidden');
            if (data.requests.length === 0) {
                reqDiv.innerHTML = '<div class="text-sm text-slate-500 italic p-2 text-center">Nenhum pedido pendente.</div>';
            } else {
                reqDiv.innerHTML = data.requests.map(r => `
                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-800 border border-indigo-900">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <img src="https://api.dicebear.com/9.x/adventurer/svg?seed=${encodeURIComponent(r.player_name)}" class="w-7 h-7 rounded bg-slate-700 shrink-0">
                            <span class="font-bold text-sm text-white truncate">${r.player_name}</span>
                        </div>
                        <div class="flex gap-1 shrink-0 ml-2">
                            <button onclick="approveJoinRequest(${r.id}, 'accept')" class="bg-emerald-600 hover:bg-emerald-500 text-white w-7 h-7 rounded flex items-center justify-center transition-colors"><i class="fas fa-check"></i></button>
                            <button onclick="approveJoinRequest(${r.id}, 'reject')" class="bg-rose-600 hover:bg-rose-500 text-white w-7 h-7 rounded flex items-center justify-center transition-colors"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                `).join('');
            }
        }
    }

    // Dynamic update of Lobby & Endgame start/next match buttons based on room status and active players
    const hasActivePlayersInMatch = data.status === 'playing' && data.active_players_count > 0;
    const btnStartLobby = document.getElementById('btn-start-room');
    const lobbyWaitingEl = document.getElementById('lobby-waiting-host');
    const btnNextMatchEndgame = document.getElementById('btn-next-match-multi');

    if (data.is_host || data.is_cohost) {
        if (hasActivePlayersInMatch) {
            if (btnStartLobby) {
                btnStartLobby.disabled = true;
                btnStartLobby.classList.remove('hidden', 'bg-indigo-600', 'hover:bg-indigo-500');
                btnStartLobby.classList.add('bg-slate-700', 'text-slate-400', 'cursor-not-allowed');
                btnStartLobby.innerHTML = `<i class="fas fa-hourglass-half fa-spin mr-2"></i> AGUARDANDO ${data.active_players_count} JOGADOR(ES) TERMINAR(EM)...`;
            }
            if (btnNextMatchEndgame) {
                btnNextMatchEndgame.disabled = true;
                btnNextMatchEndgame.classList.remove('hidden', 'bg-indigo-600', 'hover:bg-indigo-500');
                btnNextMatchEndgame.classList.add('bg-slate-700', 'text-slate-400', 'cursor-not-allowed');
                btnNextMatchEndgame.innerHTML = `<i class="fas fa-hourglass-half fa-spin mr-2"></i> AGUARDANDO ${data.active_players_count} JOGADOR(ES)...`;
            }
        } else {
            if (btnStartLobby) {
                btnStartLobby.disabled = false;
                btnStartLobby.classList.remove('hidden', 'bg-slate-700', 'text-slate-400', 'cursor-not-allowed');
                btnStartLobby.classList.add('bg-indigo-600', 'hover:bg-indigo-500');
                btnStartLobby.innerHTML = data.status === 'finished'
                    ? '<i class="fas fa-play mr-2"></i> INICIAR NOVA PARTIDA'
                    : '<i class="fas fa-play mr-2"></i> INICIAR PARTIDA';
            }
            if (btnNextMatchEndgame && data.status === 'finished') {
                btnNextMatchEndgame.disabled = false;
                btnNextMatchEndgame.classList.remove('hidden', 'bg-slate-700', 'text-slate-400', 'cursor-not-allowed');
                btnNextMatchEndgame.classList.add('bg-indigo-600', 'hover:bg-indigo-500');
                btnNextMatchEndgame.innerHTML = '<i class="fas fa-forward mr-2"></i> PRÓXIMA PARTIDA (+ ANÚNCIO)';
            }
        }
        if (lobbyWaitingEl) lobbyWaitingEl.classList.add('hidden');
    } else {
        if (btnStartLobby) btnStartLobby.classList.add('hidden');
        if (btnNextMatchEndgame) btnNextMatchEndgame.classList.add('hidden');
        if (lobbyWaitingEl) {
            lobbyWaitingEl.classList.remove('hidden');
            if (hasActivePlayersInMatch) {
                lobbyWaitingEl.innerHTML = `<i class="fas fa-hourglass-half fa-spin mr-2"></i> Partida em andamento (${data.active_players_count} jogando)...`;
            } else if (data.status === 'finished') {
                lobbyWaitingEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Aguardando o Host Iniciar Nova Partida...';
            } else {
                lobbyWaitingEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Aguardando o Host Iniciar...';
            }
        }
    }

    // Check if the room's active words/round changed or haven't been loaded into the client board yet
    const serverWordsList = Array.isArray(data.words) ? data.words.map(w => w.word).filter(Boolean) : [];
    const clientWordsList = (state.gameState && Array.isArray(state.gameState.words)) ? state.gameState.words.map(w => w.word).filter(Boolean) : [];
    const wordsMismatch = !data.words_hidden && serverWordsList.length > 0 && JSON.stringify(serverWordsList) !== JSON.stringify(clientWordsList);
    const matchIdChanged = data.match_id && data.match_id !== previousMatchId;
    const becamePlaying = (previousStatus === 'waiting' || previousStatus === 'finished') && data.status === 'playing';

    if (data.status === 'playing' && (becamePlaying || state.justJoined || matchIdChanged || wordsMismatch)) {
        state.justJoined = false;
        state.currentRoom.match_id = data.match_id || null;
        if (data.words && data.words.length > 0) {
            state.gameState.words = data.words.map((w, index) => ({ id: 'multi_'+index, length: w.word.length, hint: w.hint, word: w.word }));
            resetGame();
            if (data.max_mistakes) state.gameState.maxMistakes = parseInt(data.max_mistakes);
            const guessResponse = await apiCall('api/guess.php', {
                mode: state.currentRoom.mode,
                room_code: state.currentRoom.code,
                guesses: [],
                override_words: state.gameState.words.map(w => w.word)
            });
            if (guessResponse && !guessResponse.error) {
                state.gameState.boardState = guessResponse.state;
                if (typeof window.updateUI === 'function') {
                    window.updateUI();
                } else if (typeof updateUI === 'function') {
                    updateUI();
                }
            }
        }
        
        document.getElementById('lobby-modal').classList.add('hidden');
        document.getElementById('multiplayer-hud-modal').classList.add('hidden');
        document.getElementById('game-area').classList.remove('hidden');
        document.getElementById('keyboard').classList.remove('hidden');
        document.getElementById('multiplayer-hud-players-inline').classList.remove('hidden');
        document.getElementById('btn-multiplayer-hud').classList.remove('hidden');
        document.getElementById('endgame-modal').classList.add('hidden');
        
        showToast("A partida começou!");
    } else if (previousStatus === 'playing' && data.status === 'finished') {
        state.currentRoom.match_id = data.match_id || state.currentRoom.match_id;
        // Alguém ganhou ou a partida acabou
        // Atualiza os botões do lobby (no background)
        if (data.is_host || data.is_cohost) {
            const btnStart = document.getElementById('btn-start-room');
            if (btnStart) {
                btnStart.innerHTML = '<i class="fas fa-play mr-2"></i> INICIAR PARTIDA';
                btnStart.classList.remove('hidden');
            }
            if (data.is_host) {
                const btnClose = document.getElementById('btn-close-room');
                if (btnClose) btnClose.classList.remove('hidden');
            }
        } else {
            document.getElementById('lobby-waiting-host').classList.remove('hidden');
            const btnStart = document.getElementById('btn-start-room');
            if (btnStart) btnStart.classList.add('hidden');
            const btnClose = document.getElementById('btn-close-room');
            if (btnClose) btnClose.classList.add('hidden');
        }

        // Se eu ainda estava jogando (vivo), mostra a tela de Fim da Corrida
        const me = data.players.find(p => p.uuid === state.uuid);
        const officialWords = (data.words && data.words.length > 0)
            ? data.words.map(w => w.word)
            : state.gameState.words.map(w => w.word);

        if (data.words && data.words.length > 0 && !data.words_hidden) {
            state.gameState.words = data.words.map((w, index) => ({ id: 'multi_'+index, length: w.word.length, hint: w.hint, word: w.word }));
        }

        if (me && me.finished == 0 && !state.gameState.isGameOver) {
            state.gameState.isGameOver = true;
            import('./game.js').then(g => {
                g.showEndgame({
                    is_win: false,
                    is_lose_race: true,
                    hide_words: false,
                    full_words: officialWords
                });
            });
        } else if (me && me.finished == -1) {
            // Eu já tinha perdido antes e a palavra estava oculta (Anti-Spoiler). Agora que todos terminaram, revela a palavra pra mim!
            import('./game.js').then(g => {
                // Preenche os slots não revelados do tabuleiro com a palavra final
                officialWords.forEach((word, bIdx) => {
                    for (let cIdx = 0; cIdx < word.length; cIdx++) {
                        const slot = document.getElementById(`slot-${bIdx}-${cIdx}`);
                        if (slot && (slot.innerText === '' || slot.innerText === '?')) {
                            slot.innerText = word[cIdx];
                            slot.classList.remove('text-amber-400', 'bg-amber-950/20', 'border-amber-500/40');
                            slot.classList.add('text-rose-400', 'bg-rose-950/20');
                        }
                    }
                });
                g.showEndgame({
                    is_win: false,
                    is_lose_race: false,
                    hide_words: false,
                    full_words: officialWords
                });
            });
        }
    }

    if (data.status === 'playing') {
        const me = data.players.find(p => p.uuid === state.uuid);
        if (me && me.finished == -1 && !state.gameState.isGameOver) {
            state.gameState.isGameOver = true;
            document.getElementById('game-area').classList.add('hidden');
            document.getElementById('keyboard').classList.add('hidden');
            document.getElementById('multiplayer-hud-players-inline').classList.add('hidden');
            document.getElementById('lobby-modal').classList.remove('hidden');
            showToast("Você foi removido da partida por inatividade.");
        }
    }

    if (data.status === 'playing' || data.status === 'finished') {
        state.currentRoom.players = data.players;
        const hudHtml = renderLiveRacePlayersHtml(data.players, data.max_mistakes, data.status);

        const inlineHud = document.getElementById('multiplayer-hud-players-inline');
        if (inlineHud) {
            const activeCount = typeof data.active_players_count === 'number' ? data.active_players_count : 0;
            const statusHeaderBadge = data.status === 'playing'
                ? `<span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> ${activeCount} jogando</span>`
                : `<span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">Rodada Encerrada</span>`;

            inlineHud.innerHTML = `
                <div class="flex items-center justify-between border-b border-slate-800 pb-2 mb-1">
                    <span class="text-xs font-black uppercase tracking-widest text-emerald-400"><i class="fas fa-trophy mr-1.5"></i>Corrida ao Vivo</span>
                    <div class="flex items-center gap-1.5">
                        ${statusHeaderBadge}
                        <span class="hidden sm:inline text-[10px] font-mono bg-slate-800 text-slate-300 px-2 py-0.5 rounded border border-slate-700">${state.currentRoom.code}</span>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    ${hudHtml}
                </div>
            `;
        }
        const modalHud = document.getElementById('race-players');
        if (modalHud) modalHud.innerHTML = hudHtml;
        const specHud = document.getElementById('endgame-live-spectator-hud');
        if (specHud) specHud.innerHTML = hudHtml;
        const specBannerCount = document.getElementById('spectator-active-count-badge');
        if (specBannerCount && typeof data.active_players_count === 'number') {
            specBannerCount.innerText = `${data.active_players_count} JOGANDO AGORA`;
        }
        const lobbySpecHud = document.getElementById('lobby-live-spectator-hud');
        if (lobbySpecHud) lobbySpecHud.innerHTML = hudHtml;
        const lobbySpecBox = document.getElementById('lobby-spectator-box');
        if (lobbySpecBox) {
            if (data.status === 'playing') {
                lobbySpecBox.classList.remove('hidden');
            } else {
                lobbySpecBox.classList.add('hidden');
            }
        }
    }
    } finally {
        isSyncing = false;
    }
};

export const requestJoinRoom = async (code) => {
    if (window.adWatchedForRoom !== code) {
        import('./ui.js').then(ui => {
            ui.simulateAd(() => {
                window.adWatchedForRoom = code;
                requestJoinRoom(code);
            });
        });
        return;
    }

    const data = await apiCall('api/room_request_join.php', { room_code: code, uuid: state.uuid, player_name: state.playerName || generateRandomName() });
    
    if (data.success && (data.status === 'approved' || data.status === 'accepted')) {
        joinRoom(code);
        return;
    }

    if (data.success) {
        state.pendingRoomRequest = code;
        renderPublicRooms();
        showToast("Pedido enviado! Aguardando aprovação...");
        
        const reqId = data.request_id;
        window.pendingJoinRequestId = reqId;
        
        if (window.joinRequestInterval) clearInterval(window.joinRequestInterval);
        window.joinRequestInterval = setInterval(async () => {
            console.log("Polling status for reqId:", reqId);
            const check = await fetch(`api/room_request_status.php?id=${reqId}&_t=${Date.now()}`).then(r => r.json());
            console.log("Polling result:", check);
            if (check.success && (check.status === 'accepted' || check.status === 'approved')) {
                clearInterval(window.joinRequestInterval);
                state.pendingRoomRequest = null;
                renderPublicRooms();
                console.log("Status approved! Calling joinRoom...");
                joinRoom(code);
            } else if (check.success && check.status === 'rejected') {
                clearInterval(window.joinRequestInterval);
                state.pendingRoomRequest = null;
                renderPublicRooms();
                showToast("Seu pedido foi recusado pelo host.");
            }
        }, 2000);
    } else {
        showToast(data.error || "Erro ao pedir entrada.");
    }
};

export const cancelJoinRequest = async () => {
    if (window.joinRequestInterval) clearInterval(window.joinRequestInterval);
    
    if (window.pendingJoinRequestId) {
        await apiCall('api/room_cancel_request.php', { request_id: window.pendingJoinRequestId });
        window.pendingJoinRequestId = null;
    }
    state.pendingRoomRequest = null;
    renderPublicRooms();
    showToast("Pedido cancelado.");
};

export const exitLobby = async () => {
    if (state.currentRoom) {
        await apiCall('api/room_leave.php', { room_code: state.currentRoom.code, uuid: state.uuid });
    }
    if (state.syncInterval) clearInterval(state.syncInterval);
    state.currentRoom = null;
    document.getElementById('lobby-modal').classList.add('hidden');
    document.getElementById('btn-multiplayer-hud').classList.add('hidden');
    document.getElementById('game-area').classList.add('hidden');
    document.getElementById('keyboard').classList.add('hidden');
    document.getElementById('endgame-modal').classList.add('hidden');
    document.getElementById('modes-modal').classList.remove('hidden');
    window.history.pushState({}, '', window.location.pathname);
    initMode(state.currentMode || 1);
};

export const updateRoomConfig = async () => {
    if (!state.currentRoom) return;
    const maxPlayers = document.getElementById('lobby-max-players').value;
    const isPublic = document.getElementById('lobby-is-public').checked ? 0 : 1;
    
    await apiCall('api/room_config.php', {
        uuid: state.uuid,
        room_code: state.currentRoom.code,
        max_players: maxPlayers,
        is_private: isPublic
    });
};

export const promotePlayer = async (targetUuid, isPromote) => {
    import('./ui.js').then(ui => {
        ui.confirmAction(
            isPromote === 1 ? "Promover a Co-Admin?" : "Remover Co-Admin?",
            isPromote === 1 ? "Este jogador poderá aprovar entradas, iniciar partidas e expulsar jogadores normais." : "O jogador perderá os privilégios de Co-Admin.",
            async () => {
                const data = await apiCall('api/room_promote.php', {
                    room_code: state.currentRoom.code,
                    host_uuid: state.uuid,
                    target_uuid: targetUuid,
                    action: isPromote === 1 ? 'promote' : 'demote'
                });
                if (data.success) {
                    showToast(isPromote === 1 ? "Jogador promovido!" : "Privilégios removidos.");
                    syncRoom('ping');
                } else {
                    showToast(data.error || "Erro ao atualizar permissões.");
                }
            }
        );
    });
};

// Expose heavily used global functions
window.joinRoom = joinRoom;
window.startMultiplayerFlow = startMultiplayerFlow;
window.exitLobby = exitLobby;
window.requestJoinRoom = requestJoinRoom;
window.cancelJoinRequest = cancelJoinRequest;
window.loadPublicRooms = loadPublicRooms;
window.updateRoomConfig = updateRoomConfig;
window.promotePlayer = promotePlayer;
window.copyLobbyLink = copyLobbyLink;
