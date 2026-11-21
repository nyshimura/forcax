import { state } from './state.js';
import { apiCall } from './api.js';
import { showToast, saveLocalState, copyTextToClipboard } from './utils.js';
import { t } from './config.js';
import { initMode, resetGame, updateUI } from './game.js';
import { joinRoom, syncRoom, exitLobby, loadPublicRooms } from './multiplayer.js';

export const updateLanguageUI = () => {
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
            el.placeholder = t(key);
        } else {
            el.innerText = t(key);
        }
    });
};

export const updateCountdown = () => {
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setHours(24, 0, 0, 0);
    const diff = tomorrow - now;
    
    const h = Math.floor(diff / (1000 * 60 * 60)).toString().padStart(2, '0');
    const m = Math.floor((diff / 1000 / 60) % 60).toString().padStart(2, '0');
    const s = Math.floor((diff / 1000) % 60).toString().padStart(2, '0');
    
    const el = document.getElementById('countdown');
    if (el) el.innerText = `${h}:${m}:${s}`;
};

export const selectMode = (mode) => {
    document.getElementById('modes-modal').classList.add('hidden');
    if (state.currentMode !== mode) {
        initMode(mode);
    }
};

export const openProfile = () => {
    const input = document.getElementById('profile-name');
    if(input) input.value = state.playerName || '';
    const preview = document.getElementById('profile-avatar-preview');
    if(preview) preview.src = 'https://api.dicebear.com/9.x/adventurer/svg?seed=' + encodeURIComponent(state.playerName || 'Visitante');
    document.getElementById('profile-modal').classList.remove('hidden');
};

export const saveProfile = async () => {
    const val = document.getElementById('profile-name').value.trim();
    if (val) {
        state.playerName = val;
        localStorage.setItem('forca_player_name', state.playerName);
        showToast("Apelido salvo com sucesso!");
        document.getElementById('profile-modal').classList.add('hidden');
        document.getElementById('btn-user').classList.add('text-emerald-400');
        
        if (window.pendingProfileDialogAction === 'createRoom') {
            document.getElementById('modes-modal').classList.add('hidden');
            openCreateRoomSettings();
        } else if (window.pendingProfileDialogAction === 'publicRooms') {
            document.getElementById('modes-modal').classList.add('hidden');
            openPublicRooms();
        }

        if (state.currentRoom && state.currentRoom.code) {
            updateLobbyName(state.playerName);
        }
        window.loadedRooms = [];
        window.pendingProfileDialogAction = null;
    } else {
        showToast("Digite um apelido válido.");
    }
};

export const toggleRoomPrivacy = (isPrivate) => {
    const btnPrivate = document.getElementById('btn-room-private');
    const btnPublic = document.getElementById('btn-room-public');
    const desc = document.getElementById('room-privacy-desc');
    
    if (isPrivate) {
        btnPrivate.className = "flex-1 py-2 text-sm font-bold rounded-md bg-indigo-600 text-white transition-colors";
        btnPublic.className = "flex-1 py-2 text-sm font-bold rounded-md text-slate-400 hover:text-white transition-colors";
        desc.innerText = "Somente jogadores aprovados por você poderão entrar.";
    } else {
        btnPublic.className = "flex-1 py-2 text-sm font-bold rounded-md bg-indigo-600 text-white transition-colors";
        btnPrivate.className = "flex-1 py-2 text-sm font-bold rounded-md text-slate-400 hover:text-white transition-colors";
        desc.innerText = "Qualquer pessoa poderá ver e entrar na sua sala.";
    }
};

export const simulateAd = (callback) => {
    const settings = window.FORCA_SETTINGS || {};
    const provider = settings.ad_provider || 'adsterra';
    const adsterraCode = (settings.adsterra_code || '').trim();
    const adsenseCode = (settings.adsense_code || '').trim();

    // 1. Google H5 Games AdSense (Rewarded AdBreak) when provider === 'adsense' and code is present
    if (provider === 'adsense' && adsenseCode !== '' && typeof window.adBreak === 'function') {
        try {
            let rewarded = false;
            window.adBreak({
                type: 'reward',
                name: 'forcax_reward',
                beforeReward: (showAdFn) => { showAdFn(); },
                adViewed: () => { rewarded = true; },
                adDismissed: () => { rewarded = false; },
                adBreakDone: () => {
                    if (callback) callback();
                }
            });
            return;
        } catch (e) {
            // Fall through to modal if AdSense hasn't finished initializing
        }
    }

    const adModal = document.getElementById('ad-simulation-modal');
    if (adModal) adModal.classList.remove('hidden');
    
    const countEl = document.getElementById('ad-countdown');
    const btn = document.getElementById('btn-close-ad');
    const slotEl = document.getElementById('ad-dynamic-slot');
    const badgeEl = document.getElementById('ad-modal-badge');

    const isRealAdsterra = (provider === 'adsterra' && adsterraCode !== '');
    let timeLeft = isRealAdsterra ? 5 : 1;

    if (slotEl) {
        if (isRealAdsterra) {
            if (badgeEl) badgeEl.innerHTML = `<i class="fas fa-bolt text-amber-400"></i> Anúncio Patrocinado`;
            slotEl.innerHTML = '';
            const iframe = document.createElement('iframe');
            iframe.style.width = '100%';
            iframe.style.height = '260px';
            iframe.style.border = 'none';
            iframe.style.overflow = 'hidden';
            iframe.setAttribute('scrolling', 'no');
            slotEl.appendChild(iframe);
            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><style>body{margin:0;padding:0;display:flex;align-items:center;justify-content:center;background:#18181b;color:#fff;font-family:sans-serif;min-height:250px;overflow:hidden;}</style></head><body>${adsterraCode}</body></html>`);
            doc.close();
        } else {
            if (badgeEl) badgeEl.innerHTML = `<i class="fas fa-flask text-indigo-400"></i> Modo Simulação`;
            slotEl.innerHTML = `
                <div class="w-16 h-16 bg-indigo-500/20 text-indigo-400 rounded-full mx-auto flex items-center justify-center text-3xl mb-3 border-2 border-indigo-500 relative overflow-hidden">
                    <i class="fas fa-bullhorn absolute z-10"></i>
                    <div class="absolute inset-0 border-4 border-indigo-400 border-t-transparent rounded-full animate-spin z-0"></div>
                </div>
                <h2 class="text-lg font-black mb-1 uppercase tracking-widest text-white">Anúncio Simulado</h2>
                <p class="text-slate-400 text-xs max-w-xs">Configure seu código Adsterra ou AdSense no Painel Admin para exibir anúncios reais aqui.</p>
            `;
        }
    }
    
    if (countEl) countEl.innerText = timeLeft;
    if (btn) {
        btn.disabled = true;
        btn.innerText = `Aguarde ${timeLeft}s...`;
        btn.className = "w-full bg-slate-700 text-slate-500 font-bold py-3.5 rounded-xl uppercase transition-colors cursor-not-allowed text-sm";
        btn.onclick = null;
    }
    
    const intv = setInterval(() => {
        timeLeft--;
        if (countEl) countEl.innerText = Math.max(0, timeLeft);
        if (btn && timeLeft > 0) btn.innerText = `Aguarde ${timeLeft}s...`;
        if (timeLeft <= 0) {
            clearInterval(intv);
            if (countEl) countEl.innerText = "0";
            if (isRealAdsterra && btn) {
                // Give player a clickable button to claim reward & close the ad modal
                btn.disabled = false;
                btn.innerText = "RECEBER RECOMPENSA E CONTINUAR";
                btn.className = "w-full bg-emerald-600 hover:bg-emerald-500 text-white font-black py-3.5 rounded-xl uppercase transition-colors cursor-pointer text-sm shadow-lg";
                btn.onclick = () => {
                    if (slotEl) slotEl.innerHTML = '';
                    if (adModal) adModal.classList.add('hidden');
                    if (callback) callback();
                };
            } else {
                if (slotEl) slotEl.innerHTML = '';
                if (adModal) adModal.classList.add('hidden');
                if (callback) callback();
            }
        }
    }, 1000);
};

export const watchAdToCreateRoom = () => {
    document.getElementById('profile-modal').classList.add('hidden');
    document.getElementById('create-room-modal').classList.add('hidden');
    simulateAd(() => {
        showToast("Sala VIP Liberada!");
        createRoom();
    });
};

export const createRoom = async () => {
    const isPrivate = !document.getElementById('btn-room-public').classList.contains('bg-indigo-600');
    const maxMistakes = parseInt(document.getElementById('room-max-mistakes').value) || 6;

    // O modo Multiplayer é SEMPRE 1 palavra para todos disputarem quem acerta primeiro com menos erros.
    const data = await apiCall('api/room_create.php', { mode: 1, lang: state.gameState.language, uuid: state.uuid, is_private: isPrivate ? 1 : 0, max_mistakes: maxMistakes });
    if (data.success) {
        document.getElementById('create-room-modal').classList.add('hidden');
        window.adWatchedForRoom = data.room_code;
        joinRoom(data.room_code);
    } else {
        showToast(data.error || "Erro ao criar sala.");
    }
};

export const watchAdForWord = () => {
    simulateAd(async () => {
        showToast("Recompensa concedida! (+1 Jogo)");
        await playExtraWord();
    });
};

export const playExtraWord = async () => {
    try {
        const res = await fetch(`api/extra_word.php?mode=${state.currentMode}&lang=${state.gameState.language}&_t=${Date.now()}`);
        const data = await res.json();
        
        if (data.success && data.words && data.words.length > 0) {
            state.gameState.words = data.words.map((w, index) => ({ id: 'extra_'+index, length: w.word.length, hint: w.hint, word: w.word }));
            resetGame();
            const guessResponse = await apiCall('api/guess.php', {
                mode: state.currentMode,
                guesses: [],
                override_words: state.gameState.words.map(w => w.word)
            });
            if (guessResponse && !guessResponse.error) {
                state.gameState.boardState = guessResponse.state;
            }
            updateUI();
        } else {
            showToast("Erro ao carregar nova palavra.");
        }
    } catch (e) {
        showToast("Erro na conexão ao carregar palavra.");
    }
};


export const requestExtraWordUI = () => {
    if (!state.gameState.isGameOver) {
        showToast("Termine a partida atual primeiro!");
        return;
    }
    watchAdForWord();
};

export const hostRequestNextMatch = async () => {
    if (!state.currentRoom) return;
    if (state.currentRoom.status === 'playing' && state.currentRoom.active_players_count > 0) {
        showToast(`Aguarde! Ainda há ${state.currentRoom.active_players_count} jogador(es) na partida.`);
        return;
    }
    const btn = document.getElementById('btn-next-match-multi');
    if (btn) btn.disabled = true;
    
    simulateAd(async () => {
        const data = await apiCall('api/room_next_match.php', { room_code: state.currentRoom.code, uuid: state.uuid });
        if (btn) btn.disabled = false;
        if (data.success && data.words) {
            state.currentRoom.status = 'playing';
            state.currentRoom.match_id = data.match_id || null;
            state.currentRoom.started_at = data.started_at || state.currentRoom.started_at;
            state.currentRoom.elapsed_seconds = 0;
            state.currentRoom.last_sync_ts = Date.now();
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
                updateUI();
            }

            document.getElementById('lobby-modal').classList.add('hidden');
            document.getElementById('multiplayer-hud-modal').classList.add('hidden');
            document.getElementById('game-area').classList.remove('hidden');
            document.getElementById('keyboard').classList.remove('hidden');
            document.getElementById('multiplayer-hud-players-inline').classList.remove('hidden');
            document.getElementById('btn-multiplayer-hud').classList.remove('hidden');
            
            showToast("Nova partida começou!");
        } else {
            showToast(data.error || "Erro ao iniciar nova partida.");
        }
    });
};

export const confirmAction = (title, message, onConfirm) => {
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 bg-black/95 flex items-center justify-center p-4 z-[999] backdrop-blur-sm transition-opacity';
    overlay.innerHTML = `
        <div class="bg-[#121213] border border-slate-700 w-full max-w-sm rounded-2xl p-6 shadow-2xl text-center">
            <div class="w-16 h-16 bg-rose-500/20 text-rose-500 rounded-full mx-auto flex items-center justify-center text-3xl mb-4 border border-rose-500/30">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="text-xl font-black mb-2 uppercase tracking-widest text-white">${title}</h2>
            <p class="text-slate-400 text-sm mb-8">${message}</p>
            <div class="flex gap-3">
                <button id="btn-cancel-action" class="flex-1 bg-[#2a2a2c] hover:bg-slate-700 text-white py-3 rounded-xl font-bold uppercase text-sm transition-colors border border-slate-600">Cancelar</button>
                <button id="btn-confirm-action" class="flex-1 bg-rose-600 hover:bg-rose-500 text-white py-3 rounded-xl font-bold uppercase text-sm transition-colors shadow-lg shadow-rose-900/50">Confirmar</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    document.getElementById('btn-cancel-action').onclick = () => {
        overlay.remove();
    };

    document.getElementById('btn-confirm-action').onclick = () => {
        overlay.remove();
        onConfirm();
    };
};

export const closeRoom = async () => {
    if (!state.currentRoom) return;
    confirmAction("Encerrar Sala?", "Tem certeza que deseja encerrar esta sala para todos os jogadores?", async () => {
        const data = await apiCall('api/room_close.php', { room_code: state.currentRoom.code, uuid: state.uuid });
        if (data.success) {
            showToast("Sala encerrada com sucesso.");
            exitLobby();
        } else {
            showToast(data.error || "Erro ao encerrar.");
        }
    });
};

export const toggleEditPermission = async (targetUuid, val) => {
    // stub
    showToast("Permissão alterada.");
};

export const kickPlayer = async (targetUuid) => {
    confirmAction("Expulsar Jogador?", "Tem certeza que deseja expulsar este jogador?", async () => {
        const data = await apiCall('api/room_kick.php', { room_code: state.currentRoom.code, host_uuid: state.uuid, target_uuid: targetUuid });
        if (data.success) {
            showToast("Jogador expulso!");
            syncRoom('ping');
        } else {
            showToast(data.error || "Erro ao expulsar jogador.");
        }
    });
};

export const approveJoinRequest = async (reqId, action) => {
    await apiCall('api/room_approve_join.php', { room_code: state.currentRoom.code, host_uuid: state.uuid, request_id: reqId, action: action });
    syncRoom('ping');
};

export const startRoom = () => {
    if (state.currentRoom && state.currentRoom.status === 'playing' && state.currentRoom.active_players_count > 0) {
        showToast(`Aguarde! Ainda há ${state.currentRoom.active_players_count} jogador(es) ativo(s) na partida.`);
        return;
    }
    const btn = document.getElementById('btn-start-room');
    if (btn) btn.disabled = true;
    
    if (state.currentRoom && state.currentRoom.status === 'finished') {
        hostRequestNextMatch();
    } else {
        syncRoom('start');
    }
    
    // Re-enable after a short delay in case of errors
    setTimeout(() => { if (btn && !(state.currentRoom && state.currentRoom.status === 'playing' && state.currentRoom.active_players_count > 0)) btn.disabled = false; }, 2000);
};

export const closeEndgame = () => {
    document.getElementById('endgame-modal').classList.add('hidden');
    if (state.currentRoom) {
        document.getElementById('game-area').classList.add('hidden');
        document.getElementById('keyboard').classList.add('hidden');
        document.getElementById('multiplayer-hud-players-inline').classList.add('hidden');
        document.getElementById('lobby-modal').classList.remove('hidden');
    } else {
        document.getElementById('already-played').classList.remove('hidden');
    }
};

export const loadStats = async (mode = state.currentMode) => {
    try {
        // Update active tabs
        [1, 2, 4, 'multiplayer'].forEach(m => {
            const btn = document.getElementById('tab-stat-' + m);
            if (btn) {
                if (m === mode) {
                    btn.className = "flex-1 py-2 text-[11px] sm:text-xs font-bold rounded-md bg-emerald-600 text-white transition-colors";
                } else {
                    btn.className = "flex-1 py-2 text-[11px] sm:text-xs font-bold rounded-md text-slate-400 hover:text-white transition-colors";
                }
            }
        });

        const dailyView = document.getElementById('stats-daily-view');
        const mpView = document.getElementById('stats-multiplayer-view');

        const response = await fetch(`api/stats.php?mode=${encodeURIComponent(mode)}`, { headers: { 'X-UUID': state.uuid || '' } });
        const data = await response.json();

        if (mode === 'multiplayer') {
            if (dailyView) dailyView.classList.add('hidden');
            if (mpView) mpView.classList.remove('hidden');

            if (data.personal) {
                document.getElementById('mp-stat-jogos').innerText = data.personal.total ?? 0;
                document.getElementById('mp-stat-vitorias').innerText = (data.personal.win_rate ?? 0) + '%';
                document.getElementById('mp-stat-empates').innerText = data.personal.draws ?? 0;
                document.getElementById('mp-stat-recorde').innerText = data.personal.best_score ?? 0;
            }
            const globalMatchesEl = document.getElementById('mp-stat-global-matches');
            if (globalMatchesEl) globalMatchesEl.innerText = data.global_matches ?? 0;

            const rankEl = document.getElementById('mp-stat-ranking');
            if (rankEl) {
                const ranking = Array.isArray(data.ranking) ? data.ranking : [];
                if (ranking.length === 0) {
                    rankEl.innerHTML = `<div class="text-center text-slate-500 text-xs py-6 italic">Nenhuma partida multiplayer registrada ainda.</div>`;
                } else {
                    rankEl.innerHTML = ranking.map((r, idx) => {
                        const medal = idx === 0
                            ? '<i class="fas fa-crown text-amber-400"></i>'
                            : (idx === 1 ? '<i class="fas fa-medal text-slate-300"></i>'
                            : (idx === 2 ? '<i class="fas fa-medal text-orange-500"></i>'
                            : `<span class="text-xs font-black text-slate-400">#${idx + 1}</span>`));
                        const rowHighlight = r.is_me
                            ? 'bg-indigo-950/50 border-indigo-500/60'
                            : 'bg-[#121213] border-slate-700/70';
                        return `
                            <div class="flex items-center justify-between p-2.5 rounded-xl border ${rowHighlight}">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-7 h-7 rounded-lg bg-slate-800 flex items-center justify-center shrink-0">${medal}</div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-bold text-white truncate">${r.player_name}${r.is_me ? ' <span class="text-[10px] text-indigo-300">(Você)</span>' : ''}</span>
                                        <span class="text-[10px] text-slate-400">${r.wins} vitórias &bull; ${r.matches} partidas</span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-2">
                                    <div class="text-xs font-black text-emerald-400">${r.best_score} pts</div>
                                    <div class="text-[10px] text-slate-500">recorde</div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            }

            document.getElementById('stats-modal').classList.remove('hidden');
            return;
        }

        if (dailyView) dailyView.classList.remove('hidden');
        if (mpView) mpView.classList.add('hidden');
        
        if (data.personal) {
            document.getElementById('stat-jogos').innerText = data.personal.total;
            document.getElementById('stat-vitorias').innerText = data.personal.win_rate + '%';
            document.getElementById('stat-sequencia').innerText = data.personal.current_streak;
            document.getElementById('stat-melhor').innerText = data.personal.max_streak;
            
            // Render distribution chart
            const dist = data.personal.distribution;
            const maxVal = Math.max(...dist, 1);
            let distHtml = '';
            dist.forEach((val, idx) => {
                const pct = Math.round((val / maxVal) * 100);
                distHtml += `
                    <div class="flex items-center text-sm">
                        <div class="w-6 text-slate-400 font-bold text-right pr-2">${idx}</div>
                        <div class="flex-1 bg-slate-800 h-6 rounded-md relative overflow-hidden">
                            <div class="h-full bg-emerald-600 transition-all duration-1000" style="width: ${pct}%"></div>
                            <div class="absolute inset-0 flex items-center justify-end pr-2 text-xs font-bold text-white">${val > 0 ? val : ''}</div>
                        </div>
                    </div>
                `;
            });
            document.getElementById('stat-distribution').innerHTML = distHtml;
        }
        
        if (data.regional) {
            const cc = data.regional.country_code || 'BR';
            const flag = String.fromCodePoint(...cc.toUpperCase().split('').map(c => 127397 + c.charCodeAt()));
            document.getElementById('stat-country-name').innerText = flag + ' ' + cc;
            document.getElementById('stat-country-rate').innerText = data.regional.country_win_rate + '%';
            document.getElementById('stat-global-rate').innerText = data.regional.global_win_rate + '%';
            const globalTotalEl = document.getElementById('stat-global-total');
            if (globalTotalEl) {
                const modeTotal = data.regional.global_total_games ?? 0;
                const allTotal = data.regional.all_modes_total_games ?? modeTotal;
                globalTotalEl.innerText = `${modeTotal} (Total geral: ${allTotal})`;
            }
        }
        
        document.getElementById('stats-modal').classList.remove('hidden');
    } catch (e) {
        console.error(e);
    }
};

export const shareResult = async () => {
    const dateStr = state.todayDate.split('-').reverse().slice(0, 2).join('/');
    const isMultiplayer = Boolean(state.currentRoom && state.currentRoom.code);
    const modeTitle = isMultiplayer ? `MULTIPLAYER (SALA ${state.currentRoom.code})` : t('TITLE_' + state.currentMode);
    let text = `Forcax - ${modeTitle}\n🗓️ ${dateStr}\n💔 ${state.gameState.mistakes}/${state.gameState.maxMistakes}\n\n`;

    const data = window.endgameData;
    const wordsList = (data && Array.isArray(data.full_words) && data.full_words.length > 0)
        ? data.full_words
        : (state.gameState.words ? state.gameState.words.map(w => w.word).filter(w => w && !w.includes('?')) : []);

    if (wordsList.length > 0) {
        const endGuesses = (data && data.guesses) ? data.guesses : (state.gameState.guesses || []);
        wordsList.forEach((word) => {
            let row = '';
            for (let j = 0; j < word.length; j++) {
                const char = word[j];
                const normChar = char.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toUpperCase();
                if (endGuesses.includes(normChar)) row += '🟩';
                else row += '🟥';
            }
            text += row + '\n';
        });
    }

    const shareUrl = window.location.origin + window.location.pathname;
    text += `\n🔗 ${shareUrl}`;

    // Always copy to clipboard first (works on HTTP, HTTPS, Desktop and Mobile)
    const copied = await copyTextToClipboard(text);

    // Update button label temporarily for immediate visual feedback inside the modal
    const shareBtn = document.querySelector('#endgame-share-row button');
    if (shareBtn) {
        const originalHtml = shareBtn.innerHTML;
        shareBtn.innerHTML = `<i class="fas fa-check text-emerald-300"></i> <span>${t('COPIED')}</span>`;
        shareBtn.classList.remove('bg-blue-600', 'hover:bg-blue-500');
        shareBtn.classList.add('bg-emerald-600', 'hover:bg-emerald-500');
        setTimeout(() => {
            shareBtn.innerHTML = originalHtml;
            shareBtn.classList.remove('bg-emerald-600', 'hover:bg-emerald-500');
            shareBtn.classList.add('bg-blue-600', 'hover:bg-blue-500');
        }, 2500);
    }

    if (copied) {
        showToast(t('COPIED'));
    }

    // On mobile devices that support native share sheet, also trigger navigator.share
    if (navigator.share && /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent)) {
        try {
            await navigator.share({
                title: 'Forcax',
                text: text
            });
        } catch (err) {
            // User cancelled share sheet; clipboard already has the text
        }
    }
};

export const changeNameInLobby = () => {
    window.pendingProfileDialogAction = 'syncLobbyName';
    openProfile();
};

export const openCreateRoomSettings = () => {
    document.getElementById('profile-modal').classList.add('hidden');
    document.getElementById('create-room-modal').classList.remove('hidden');
};

export const openPublicRooms = () => {
    document.getElementById('modes-modal').classList.add('hidden');
    document.getElementById('public-rooms-modal').classList.remove('hidden');
    loadPublicRooms(false);
};

export const closePublicRooms = (goBack = true) => {
    document.getElementById('public-rooms-modal').classList.add('hidden');
    if (goBack) document.getElementById('modes-modal').classList.remove('hidden');
};

export const searchRoomByCode = () => {
    const code = document.getElementById('search-room-code').value.trim().toUpperCase();
    if (code) joinRoom(code);
};

export const updateLobbyName = async (newName) => {
    if (!state.currentRoom) return;
    await apiCall('api/room_sync.php', {
        room_code: state.currentRoom.code,
        uuid: state.uuid,
        action: 'update_name',
        name: newName
    });
    syncRoom('ping'); 
};
