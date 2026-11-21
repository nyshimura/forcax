import { state } from './state.js';
import { apiCall } from './api.js';
import { showToast, saveLocalState, playSound, fireConfetti } from './utils.js';
import { updateLanguageUI } from './ui.js';
import { t } from './config.js';

export let isRequesting = false;

export const initMode = async (mode, isMultiplayer = false) => {
    state.currentMode = mode;
    
    let titles = {1: 'SOLO', 2: 'DUETO', 4: 'QUARTETO'};
    const titleMode = document.getElementById('title-mode');
    if(titleMode) titleMode.innerText = titles[mode];

    if (isMultiplayer || state.currentRoom) {
        return;
    }

    document.getElementById('game-area').classList.add('hidden');
    document.getElementById('keyboard').classList.add('hidden');
    document.getElementById('already-played').classList.add('hidden');
    document.getElementById('loading-box').classList.remove('hidden');

    const data = await apiCall(`api/daily_words.php?mode=${mode}`);
    if (state.currentRoom) {
        document.getElementById('loading-box').classList.add('hidden');
        return;
    }
    if (data.error) return showToast(data.error);

    state.gameState = { guesses: [], mistakes: 0, maxMistakes: mode === 1 ? 6 : (mode === 2 ? 8 : 11), words: [], language: data.language || 'pt' };
    updateLanguageUI();

    if (!isMultiplayer && data.played_modes && data.played_modes[mode]) {
        const history = data.played_modes[mode];
        if (mode === 1) state.gameState.words = data.words.slice(0, 1);
        else if (mode === 2) state.gameState.words = data.words.slice(1, 3);
        else if (mode === 4) state.gameState.words = data.words.slice(3, 7);
        
        state.gameState.mistakes = history.mistakes;
        state.gameState.maxMistakes = mode === 1 ? 6 : (mode === 2 ? 8 : 11);
        state.hintsRemaining = 0; 
        
        buildBoards(mode);
        const guesses = history.guesses || [];
        const full_words = history.full_words || [];
        
        if (history.is_win !== null) {
            state.gameState.isGameOver = true;
        }
        full_words.forEach((w, i) => {
            let completed = true;
            for (let j = 0; j < w.length; j++) {
                const char = w[j];
                const slot = document.getElementById(`slot-${i}-${j}`);
                if (!slot) continue;
                const normChar = char.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toUpperCase();
                if (guesses.includes(normChar)) {
                    slot.innerText = char;
                    slot.classList.add('bg-[#538d4e]', 'border-emerald-500');
                } else {
                    completed = false;
                    slot.innerText = char;
                    slot.classList.add('text-rose-400', 'bg-rose-950/20');
                }
            }
            if (completed) {
                const statusEl = document.getElementById(`board-status-${i}`);
                if(statusEl) statusEl.classList.remove('hidden');
            }
        });

        updateUI();
        
        const kb = document.getElementById('keyboard');
        kb.innerHTML = `<button onclick='showEndgame(${JSON.stringify({is_win: history.is_win, full_words: full_words, guesses: guesses})})' class="w-full h-14 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl mt-4 active:scale-95 transition-all text-lg">VER RESUMO DA PARTIDA</button>`;
        
        document.getElementById('loading-box').classList.add('hidden');
        document.getElementById('already-played').classList.remove('hidden');
        document.getElementById('game-area').classList.remove('hidden');
        kb.classList.remove('hidden');
        return;
    }

    let savedStateStr = localStorage.getItem(`termoForca_state_${mode}`);
    let savedState = savedStateStr ? JSON.parse(savedStateStr) : null;
    
    let targetWords = [];
    if (mode === 1) targetWords = data.words.slice(0, 1);
    else if (mode === 2) targetWords = data.words.slice(1, 3);
    else if (mode === 4) targetWords = data.words.slice(3, 7);

    let wordsMatch = false;
    if (savedState && savedState.gameState && savedState.gameState.words) {
        const savedWordsStr = JSON.stringify(savedState.gameState.words.map(w => w.length));
        const targetWordsStr = JSON.stringify(targetWords.map(w => w.length));
        wordsMatch = (savedWordsStr === targetWordsStr);
    }

    if (!isMultiplayer && savedState && savedState.date === state.todayDate && wordsMatch) {
        state.gameState = savedState.gameState;
        state.hintsRemaining = savedState.hints !== undefined ? savedState.hints : (savedState.hintsRemaining !== undefined ? savedState.hintsRemaining : 1);
        
        if (data.words && data.words.length > 0) {
            state.gameState.words.forEach((w) => {
                const serverWord = data.words.find(sw => sw.id === w.id);
                if (serverWord) w.hint = serverWord.hint;
            });
            if (!isMultiplayer) saveLocalState();
        }
    } else {
        if (mode === 1) state.gameState.words = data.words.slice(0, 1);
        else if (mode === 2) state.gameState.words = data.words.slice(1, 3);
        else if (mode === 4) state.gameState.words = data.words.slice(3, 7);

        state.gameState.guesses = [];
        state.gameState.mistakes = 0;
        state.gameState.maxMistakes = mode === 1 ? 6 : (mode === 2 ? 8 : 11);
        state.hintsRemaining = mode === 1 ? 1 : (mode === 2 ? 2 : 3);
        if (!isMultiplayer) saveLocalState();
    }
    
    buildBoards(mode);
    buildKeyboard();
    
    if (state.gameState.boardState) {
        state.gameState.boardState.forEach((board, i) => {
            const statusIcon = document.getElementById(`board-status-${i}`);
            if (board.completed && statusIcon) {
                statusIcon.classList.remove('hidden');
            }
            board.revealed.forEach((char, j) => {
                if (char) {
                    const slot = document.getElementById(`slot-${i}-${j}`);
                    if (slot) {
                        slot.innerText = char;
                        slot.classList.add('bg-[#538d4e]', 'border-emerald-500');
                    }
                }
            });
        });
    }
    updateUI();

    document.getElementById('loading-box').classList.add('hidden');
    document.getElementById('game-area').classList.remove('hidden');
    document.getElementById('keyboard').classList.remove('hidden');
};

export const buildBoards = (mode) => {
    const container = document.getElementById('boards-container');
    if (!container) return;
    const gridCols = mode === 4
        ? 'grid-cols-2 max-w-4xl mx-auto'
        : (mode === 2 ? 'grid-cols-1 md:grid-cols-2 max-w-4xl mx-auto' : 'grid-cols-1 max-w-md lg:max-w-2xl mx-auto');
    container.className = `grid w-full gap-1.5 sm:gap-3 lg:gap-4 ${gridCols}`;
    container.innerHTML = '';

    const isQuarteto = mode === 4;
    const isSolo = mode === 1;
    const svgSize = isSolo
        ? 'w-20 h-20 sm:w-28 sm:h-28 lg:w-36 lg:h-36'
        : (isQuarteto ? 'w-10 h-10 sm:w-14 sm:h-14 lg:w-20 lg:h-20' : 'w-14 h-14 sm:w-20 sm:h-20 lg:w-28 lg:h-28');
    const slotSize = isSolo
        ? 'w-8 h-10 sm:w-12 sm:h-14 lg:w-14 lg:h-16 text-xl sm:text-3xl lg:text-4xl'
        : (isQuarteto ? 'w-4 h-6 sm:w-6 sm:h-8 lg:w-9 lg:h-11 text-sm sm:text-base lg:text-xl' : 'w-6 h-8 sm:w-8 sm:h-10 lg:w-11 lg:h-13 text-lg lg:text-2xl');
    const padding = isQuarteto ? 'p-1.5 sm:p-2 lg:p-3' : (isSolo ? 'p-4 lg:p-6' : 'p-2 sm:p-3 lg:p-4');

    state.gameState.words.forEach((w, i) => {
        let slotsHtml = '';
        for(let j=0; j<w.length; j++) {
            slotsHtml += `<div id="slot-${i}-${j}" class="${slotSize} bg-[#1a1a1b] border-2 border-[#3a3a3c] rounded flex items-center justify-center font-black text-white shrink-0"></div>`;
        }

        container.innerHTML += `
            <div class="bg-[#18181b] border border-[#27272a] rounded-xl ${padding} flex flex-col items-center gap-1 relative justify-center">
                <div id="board-status-${i}" class="absolute top-1 left-2 text-xs font-bold text-emerald-400 hidden">✓</div>
                <button id="btn-hint-${i}" onclick="showHint(${i})" class="absolute top-1 right-2 text-zinc-500 hover:text-yellow-400 transition-colors text-sm" title="Dica">💡</button>
                <div class="relative ${svgSize} flex items-center justify-center shrink-0">
                    <svg viewBox="0 0 120 120" class="w-full h-full stroke-zinc-400 fill-none" stroke-width="4" stroke-linecap="round">
                        <line x1="20" y1="110" x2="100" y2="110" class="stroke-zinc-700" stroke-width="4" />
                        <line x1="40" y1="110" x2="40" y2="15" class="stroke-zinc-700" stroke-width="4" />
                        <line x1="40" y1="15" x2="85" y2="15" class="stroke-zinc-700" stroke-width="4" />
                        <line x1="40" y1="35" x2="60" y2="15" class="stroke-zinc-700" stroke-width="3" />
                        <line x1="85" y1="15" x2="85" y2="30" class="stroke-zinc-500" stroke-width="3" />
                        <circle id="hm-${i}-0" cx="85" cy="42" r="10" class="opacity-0 stroke-rose-500 fill-zinc-900" stroke-width="3.5" />
                        <line id="hm-${i}-1" x1="85" y1="52" x2="85" y2="78" class="opacity-0 stroke-rose-500" stroke-width="3.5" />
                        <line id="hm-${i}-2" x1="85" y1="58" x2="70" y2="68" class="opacity-0 stroke-rose-500" stroke-width="3.5" />
                        <line id="hm-${i}-3" x1="85" y1="58" x2="100" y2="68" class="opacity-0 stroke-rose-500" stroke-width="3.5" />
                        <line id="hm-${i}-4" x1="85" y1="78" x2="72" y2="96" class="opacity-0 stroke-rose-500" stroke-width="3.5" />
                        <line id="hm-${i}-5" x1="85" y1="78" x2="98" y2="96" class="opacity-0 stroke-rose-500" stroke-width="3.5" />
                        <!-- Extra parts for modes with more mistakes -->
                        <!-- Left foot -->
                        <line id="hm-${i}-6" x1="72" y1="96" x2="65" y2="96" class="opacity-0 stroke-rose-500" stroke-width="3.5" />
                        <!-- Right foot -->
                        <line id="hm-${i}-7" x1="98" y1="96" x2="105" y2="96" class="opacity-0 stroke-rose-500" stroke-width="3.5" />
                        <!-- Left hand -->
                        <line id="hm-${i}-8" x1="70" y1="68" x2="65" y2="65" class="opacity-0 stroke-rose-500" stroke-width="3.5" />
                        <!-- Right hand -->
                        <line id="hm-${i}-9" x1="100" y1="68" x2="105" y2="65" class="opacity-0 stroke-rose-500" stroke-width="3.5" />
                        <!-- Mouth -->
                        <line id="hm-${i}-10" x1="81" y1="46" x2="89" y2="46" class="opacity-0 stroke-rose-500" stroke-width="3.5" />
                    </svg>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-0.5 sm:gap-1">${slotsHtml}</div>
                <div id="hint-box-${i}" class="text-[10px] sm:text-xs text-yellow-500 mt-1 text-center hidden w-full px-1 italic line-clamp-2 leading-tight">
                    ${(w.hint || 'Sem dica disponível').replace(/'/g, "\\'")}
                </div>
            </div>
        `;
    });
};

export const buildKeyboard = () => {
    const rows = ["QWERTYUIOP", "ASDFGHJKLÇ", "ZXCVBNM"];
    const kb = document.getElementById('keyboard');
    if (!kb) return;
    kb.innerHTML = '';
    rows.forEach(row => {
        let div = document.createElement('div');
        div.className = 'flex justify-center gap-1 sm:gap-1.5 lg:gap-2 w-full';
        row.split('').forEach(k => {
            div.innerHTML += `<button onclick="handleGuess('${k}')" id="key-${k}" class="key-btn flex-1 h-10 sm:h-12 lg:h-14 bg-[#2f3031] hover:bg-[#3f4042] text-[#d7dadc] font-bold rounded-lg flex items-center justify-center cursor-pointer active:scale-95 border border-[#3a3a3c] text-sm sm:text-base lg:text-lg shrink-0 min-w-0 transition-colors shadow-sm">${k}</button>`;
        });
        kb.appendChild(div);
    });
};

export const handleGuess = async (letter) => {
    const endgameModal = document.getElementById('endgame-modal');
    if (state.gameState.isGameOver || (endgameModal && !endgameModal.classList.contains('hidden'))) return;
    if (state.gameState.guesses.includes(letter)) return;
    if (isRequesting) return;
    
    isRequesting = true;
    state.gameState.guesses.push(letter);
    
    const keyBtn = document.getElementById(`key-${letter}`);
    if (keyBtn) keyBtn.classList.add('animate-flip');

    const ow = state.gameState.words.map(w => w.word).filter(Boolean);
    const payload = {
        mode: state.currentMode,
        guesses: state.gameState.guesses
    };
    if (state.currentRoom && state.currentRoom.code) {
        payload.room_code = state.currentRoom.code;
    }
    if (ow.length === state.currentMode) {
        payload.override_words = ow;
    }

    const data = await apiCall('api/guess.php', payload);

    if (data.error) {
        isRequesting = false;
        return;
    }

    let madeMistake = (data.mistakes > state.gameState.mistakes);

    data.state.forEach((board, i) => {
        if (board.completed) {
            const st = document.getElementById(`board-status-${i}`);
            if (st) st.classList.remove('hidden');
        }
        board.revealed.forEach((char, j) => {
            const slot = document.getElementById(`slot-${i}-${j}`);
            if (char) {
                if (slot && !slot.innerText) {
                    slot.innerText = char;
                    slot.classList.add('bg-[#538d4e]', 'border-emerald-500', 'animate-flip');
                }
            } else if (data.is_game_over) {
                if (slot) {
                    if (data.full_words && data.full_words[i] && !data.hide_words) {
                        slot.innerText = data.full_words[i][j];
                        slot.classList.add('text-rose-400', 'bg-rose-950/20');
                    } else if (data.hide_words) {
                        slot.innerText = '?';
                        slot.classList.add('text-amber-400', 'bg-amber-950/20', 'border-amber-500/40');
                    }
                }
            }
        });
    });

    if (madeMistake) {
        const boardsArea = document.getElementById('boards-container');
        if (boardsArea) {
            boardsArea.classList.remove('animate-shake');
            void boardsArea.offsetWidth; // trigger reflow
            boardsArea.classList.add('animate-shake');
        }
    } else if (state.currentRoom) {
        let diffSeconds = 0;
        if (typeof state.currentRoom.elapsed_seconds === 'number') {
            const clientDelta = state.currentRoom.last_sync_ts ? Math.max(0, (Date.now() - state.currentRoom.last_sync_ts) / 1000) : 0;
            diffSeconds = state.currentRoom.elapsed_seconds + clientDelta;
        } else if (state.currentRoom.started_at) {
            const startedAt = new Date(state.currentRoom.started_at.replace(' ', 'T')).getTime();
            diffSeconds = Math.max(0, (Date.now() - startedAt) / 1000);
        }
        const bonus = Math.max(0, Math.floor(50 - diffSeconds));
        const increment = 50 + bonus;
        state.gameState.scoreIncrement = (state.gameState.scoreIncrement || 0) + increment;
        showToast(`+${increment} pontos!`, 'success');
    }

    state.gameState.mistakes = data.mistakes;
    if (data.max_mistakes) {
        state.gameState.maxMistakes = parseInt(data.max_mistakes);
    }
    state.gameState.boardState = data.state;
    
    if (data.is_game_over) {
        state.gameState.isGameOver = true;
    }

    if (state.currentRoom) {
        import('./multiplayer.js').then(mp => mp.syncRoom('update'));
    }

    updateUI();
    saveLocalState();

    if (data.is_game_over) {
        if (!state.currentRoom) {
            await apiCall('api/save_game.php', {
                mode: state.currentMode,
                is_win: data.is_win ? 1 : 0,
                mistakes: data.mistakes,
                guesses: state.gameState.guesses
            });
        }

        if (data.is_win) {
            fireConfetti();
        }

        setTimeout(() => {
            showEndgame(data);
        }, 2000);
    }
    
    isRequesting = false;
};

export const updateUI = () => {
    const lc = document.getElementById('lives-counter');
    if (lc) lc.innerText = Math.max(0, state.gameState.maxMistakes - state.gameState.mistakes);
    
    const hc = document.getElementById('hints-counter');
    if (hc) hc.innerText = state.hintsRemaining;
    
    state.gameState.words.forEach((w, i) => {
        for (let m = 0; m < 11; m++) {
            const part = document.getElementById(`hm-${i}-${m}`);
            if (part) {
                if (m < state.gameState.mistakes) part.classList.remove('opacity-0');
                else part.classList.add('opacity-0');
            }
        }
    });

    if (state.gameState.guesses && state.gameState.boardState) {
        state.gameState.guesses.forEach(letter => {
            const btn = document.getElementById(`key-${letter}`);
            if (btn) {
                let isCorrect = false;
                state.gameState.boardState.forEach(board => {
                    if (board.revealed.includes(letter)) isCorrect = true;
                });
                
                btn.className = `key-btn flex-1 h-10 sm:h-12 lg:h-14 font-bold rounded-lg flex items-center justify-center cursor-pointer border text-sm sm:text-base lg:text-lg shrink-0 min-w-0 transition-colors shadow-sm ${
                    isCorrect 
                    ? 'bg-[#538d4e] border-emerald-500 text-white' 
                    : 'bg-[#1a1a1b] border-[#27272a] text-zinc-600 opacity-50'
                }`;
            }
        });
    }
};

export const showHint = (boardIndex) => {
    if (state.hintsRemaining <= 0) return showToast('Você não tem mais dicas disponíveis!');
    const hintBox = document.getElementById(`hint-box-${boardIndex}`);
    if (!hintBox || !hintBox.classList.contains('hidden')) return; 

    state.hintsRemaining--;
    document.getElementById('hints-counter').innerText = state.hintsRemaining;
    hintBox.classList.remove('hidden');
    document.getElementById(`btn-hint-${boardIndex}`).classList.add('hidden');
    saveLocalState();
};

export const resetGame = () => {
    state.gameState.guesses = [];
    state.gameState.mistakes = 0;
    state.hintsRemaining = 1;
    state.gameState.isGameOver = false;
    
    document.getElementById('endgame-modal').classList.add('hidden');
    document.getElementById('already-played').classList.add('hidden');
    
    buildKeyboard();
    buildBoards(state.currentMode);
    updateUI();
    saveLocalState();
};

export const showEndgame = async (data) => {
    window.endgameData = data;
    const title = document.getElementById('endgame-title');

    const shouldHideWords = Boolean(
        data.hide_words ||
        (state.currentRoom && state.currentRoom.status === 'playing' && !data.is_win && (state.currentRoom.active_players_count > 0 || !data.full_words || data.full_words.length === 0))
    );

    if (shouldHideWords) {
        title.innerHTML = `<span class="inline-flex items-center gap-2"><i class="fas fa-skull-crossbones text-rose-500"></i> FIM DE TENTATIVAS</span>`;
        title.className = 'text-xl md:text-2xl font-black mb-3 uppercase tracking-wider text-rose-400 shrink-0';
    } else {
        title.innerText = data.is_win ? t('WIN') : (data.is_lose_race ? 'FIM DA CORRIDA' : t('LOSE'));
        title.className = `text-2xl md:text-3xl font-black mb-4 uppercase tracking-wider shrink-0 ${data.is_win ? 'text-emerald-400' : 'text-rose-400'}`;
    }

    const countdownBox = document.getElementById('endgame-countdown-container');
    if (countdownBox) {
        if (state.currentRoom) {
            countdownBox.classList.add('hidden');
        } else {
            countdownBox.classList.remove('hidden');
        }
    }

    const shareRow = document.getElementById('endgame-share-row');
    const secondaryRow = document.getElementById('endgame-secondary-row');
    if (shareRow) {
        if (shouldHideWords) shareRow.classList.add('hidden');
        else shareRow.classList.remove('hidden');
    }
    if (secondaryRow) {
        if (shouldHideWords) secondaryRow.classList.add('hidden');
        else secondaryRow.classList.remove('hidden');
    }

    const content = document.getElementById('endgame-content');
    content.innerHTML = '';

    if (shouldHideWords) {
        const activeCount = (state.currentRoom && state.currentRoom.active_players_count) ? state.currentRoom.active_players_count : 1;
        // Build visual representation of the player's own board progress so far
        const firstBoard = (state.gameState && state.gameState.boardState && state.gameState.boardState[0]) ? state.gameState.boardState[0] : null;
        const wordLen = firstBoard && Array.isArray(firstBoard.revealed)
            ? firstBoard.revealed.length
            : ((state.gameState && state.gameState.words && state.gameState.words[0]) ? state.gameState.words[0].length : 5);
        
        let slotsPreviewHtml = '';
        for (let idx = 0; idx < wordLen; idx++) {
            const revealedChar = (firstBoard && firstBoard.revealed && firstBoard.revealed[idx]) ? firstBoard.revealed[idx] : null;
            if (revealedChar) {
                slotsPreviewHtml += `<div class="w-8 h-10 sm:w-9 sm:h-11 rounded-lg bg-[#538d4e] border-2 border-emerald-400 flex items-center justify-center font-black text-base sm:text-lg text-white shadow">${revealedChar}</div>`;
            } else {
                slotsPreviewHtml += `<div class="w-8 h-10 sm:w-9 sm:h-11 rounded-lg bg-[#18181b] border-2 border-amber-500/40 flex items-center justify-center font-black text-base sm:text-lg text-amber-400/90"><i class="fas fa-lock text-xs"></i></div>`;
            }
        }

        content.innerHTML = `
            <div class="bg-gradient-to-b from-[#18181b] to-[#121213] p-4 sm:p-5 rounded-2xl border border-slate-700/80 flex flex-col gap-4 shadow-inner">
                <!-- Anti-Spoiler Secret Word Vault -->
                <div class="bg-[#121214] border border-amber-500/30 rounded-xl p-3.5 flex flex-col items-center gap-2.5 relative overflow-hidden">
                    <div class="flex items-center gap-2 text-amber-400 text-xs font-black uppercase tracking-widest">
                        <i class="fas fa-shield-alt"></i> Cofre Anti-Spoiler Ativo
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-1.5 my-0.5">
                        ${slotsPreviewHtml}
                    </div>
                    <p class="text-xs text-slate-400 text-center leading-relaxed max-w-md">
                        A palavra completa será revelada automaticamente assim que os outros <span id="spectator-active-count-badge" class="text-amber-300 font-black px-1.5 py-0.5 rounded bg-amber-500/10 border border-amber-500/20">${activeCount} JOGANDO AGORA</span> finalizarem!
                    </p>
                </div>

                <!-- Live Race Feed -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between px-1">
                        <span class="text-xs font-black uppercase tracking-widest text-emerald-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span> Disputa em Tempo Real
                        </span>
                        <span class="text-[10px] font-mono text-slate-400 bg-slate-800/80 px-2 py-0.5 rounded border border-slate-700">
                            SALA ${state.currentRoom ? state.currentRoom.code : ''}
                        </span>
                    </div>
                    <div id="endgame-live-spectator-hud" class="flex flex-col gap-2 max-h-56 overflow-y-auto pr-1">
                        <div class="text-xs text-slate-400 text-center py-4"><i class="fas fa-circle-notch fa-spin mr-1.5"></i>Sincronizando corrida...</div>
                    </div>
                </div>

                <!-- Spectator Controls -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                    <button onclick="spectateMatch()" class="h-11 bg-emerald-600 hover:bg-emerald-500 text-white font-black rounded-xl transition-all text-xs uppercase tracking-wider flex items-center justify-center gap-2 shadow-lg shadow-emerald-950/50">
                        <i class="fas fa-tv"></i> Ver no Tabuleiro
                    </button>
                    <button onclick="closeEndgame()" class="h-11 bg-[#27272a] hover:bg-slate-700 text-slate-200 font-bold rounded-xl transition-colors text-xs uppercase tracking-wider flex items-center justify-center gap-2 border border-slate-600/60">
                        <i class="fas fa-users"></i> Aguardar no Lobby
                    </button>
                </div>
            </div>
        `;
    } else {
        const wordsToShow = (data.full_words && data.full_words.length > 0)
            ? data.full_words
            : (state.gameState.words ? state.gameState.words.map(w => w.word).filter(w => w && !w.includes('?')) : []);

        for (let i = 0; i < wordsToShow.length; i++) {
            const word = wordsToShow[i];
            let formattedWord = '';
            const endGuesses = data.guesses || state.gameState.guesses || [];
            
            for (let j = 0; j < word.length; j++) {
                const char = word[j];
                const normChar = char.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toUpperCase();
                if (endGuesses.includes(normChar)) {
                    formattedWord += `<span class="w-8 h-10 rounded-lg bg-[#538d4e]/30 border border-emerald-500/60 text-emerald-300 flex items-center justify-center font-black text-lg">${char}</span>`;
                } else {
                    formattedWord += `<span class="w-8 h-10 rounded-lg bg-rose-950/40 border border-rose-500/50 text-rose-300 flex items-center justify-center font-black text-lg">${char}</span>`;
                }
            }

            content.innerHTML += `
                <div class="bg-[#121213] p-4 rounded-xl border border-slate-700/80">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Palavra da Rodada</span>
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-slate-800 text-slate-300">${word.length} Letras</span>
                    </div>
                    <div class="font-bold tracking-widest mb-3 flex flex-wrap gap-1.5">${formattedWord}</div>
                    <div class="text-xs sm:text-sm text-slate-400 leading-relaxed bg-[#18181b] p-3 rounded-lg border border-slate-800" id="wiki-${i}">Buscando resumo enciclopédico...</div>
                </div>
            `;
        }

        // If in multiplayer room and match finished, also show the Final Podium / Standings right inside the Endgame summary!
        if (state.currentRoom && Array.isArray(state.currentRoom.players) && state.currentRoom.players.length > 0) {
            import('./multiplayer.js').then(mp => {
                const podiumContainer = document.getElementById('endgame-multiplayer-podium');
                if (podiumContainer) {
                    podiumContainer.innerHTML = mp.renderLiveRacePlayersHtml(
                        state.currentRoom.players,
                        state.currentRoom.max_mistakes || 6,
                        state.currentRoom.status
                    );
                }
            });
            content.innerHTML += `
                <div class="bg-[#141416] p-4 rounded-xl border border-slate-700/80 flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-widest text-amber-400"><i class="fas fa-trophy mr-1.5"></i>Classificação da Sala</span>
                        <span class="text-[10px] font-mono text-slate-400">SALA ${state.currentRoom.code}</span>
                    </div>
                    <div id="endgame-multiplayer-podium" class="flex flex-col gap-2 max-h-48 overflow-y-auto pr-1"></div>
                </div>
            `;
        }
    }

    document.getElementById('endgame-modal').classList.remove('hidden');
    
    if (state.currentRoom) {
        document.getElementById('endgame-premium-actions').classList.remove('hidden');
        document.getElementById('btn-play-infinite').classList.add('hidden');
        document.getElementById('btn-watch-ad').classList.add('hidden');
        const btnNext = document.getElementById('btn-next-match-multi');
        if (state.amIHost || state.amICohost) {
            btnNext.classList.remove('hidden');
            const activeCount = state.currentRoom.active_players_count || 0;
            if (state.currentRoom.status === 'playing' && activeCount > 0) {
                btnNext.disabled = true;
                btnNext.classList.remove('bg-indigo-600', 'hover:bg-indigo-500');
                btnNext.classList.add('bg-slate-700', 'text-slate-400', 'cursor-not-allowed');
                btnNext.innerHTML = `<i class="fas fa-hourglass-half fa-spin mr-2"></i> AGUARDANDO ${activeCount} JOGADOR(ES)...`;
            } else {
                btnNext.disabled = false;
                btnNext.classList.remove('bg-slate-700', 'text-slate-400', 'cursor-not-allowed');
                btnNext.classList.add('bg-indigo-600', 'hover:bg-indigo-500');
                btnNext.innerHTML = '<i class="fas fa-forward mr-2"></i> PRÓXIMA PARTIDA (+ ANÚNCIO)';
            }
        } else {
            btnNext.classList.add('hidden');
        }
    } else {
        document.getElementById('btn-next-match-multi').classList.add('hidden');
        let isPremium = false; // Example placeholder
        if (!isPremium) {
            document.getElementById('endgame-premium-actions').classList.remove('hidden');
            document.getElementById('btn-play-infinite').classList.add('hidden');
            document.getElementById('btn-watch-ad').classList.remove('hidden');
        } else {
            document.getElementById('endgame-premium-actions').classList.remove('hidden');
            document.getElementById('btn-watch-ad').classList.add('hidden');
            document.getElementById('btn-play-infinite').classList.remove('hidden');
        }
    }

    if (shouldHideWords) {
        if (state.currentRoom && Array.isArray(state.currentRoom.players)) {
            import('./multiplayer.js').then(mp => {
                const specHud = document.getElementById('endgame-live-spectator-hud');
                if (specHud) {
                    specHud.innerHTML = mp.renderLiveRacePlayersHtml(
                        state.currentRoom.players,
                        state.currentRoom.max_mistakes || 6,
                        state.currentRoom.status
                    );
                }
            });
        }
        return;
    }

    const langRes = await apiCall('api/daily_words.php');
    const lang = langRes.language || 'pt';

    const wordsForWiki = (data.full_words && data.full_words.length > 0)
        ? data.full_words
        : (state.gameState.words ? state.gameState.words.map(w => w.word).filter(w => w && !w.includes('?')) : []);

    for (let i = 0; i < wordsForWiki.length; i++) {
        const word = wordsForWiki[i];
        try {
            const searchRes = await fetch(`https://${lang}.wikipedia.org/w/api.php?action=query&list=search&srsearch=${word}&utf8=&format=json&origin=*`);
            const searchData = await searchRes.json();
            
            if (searchData.query && searchData.query.search && searchData.query.search.length > 0) {
                const titleParam = encodeURIComponent(searchData.query.search[0].title);
                const res = await fetch(`https://${lang}.wikipedia.org/api/rest_v1/page/summary/${titleParam}`);
                const summary = await res.json();
                const wikiEl = document.getElementById(`wiki-${i}`);
                if (wikiEl) wikiEl.innerHTML = summary.extract || 'Nenhum resumo enciclopédico encontrado para esta palavra.';
            } else {
                const wikiEl = document.getElementById(`wiki-${i}`);
                if(wikiEl) wikiEl.innerText = 'Nenhum verbete encontrado na enciclopédia para esta palavra.';
            }
        } catch (e) {
            const wikiEl = document.getElementById(`wiki-${i}`);
            if(wikiEl) wikiEl.innerText = 'Não foi possível carregar o resumo.';
        }
    }


};

export const spectateMatch = () => {
    document.getElementById('endgame-modal').classList.add('hidden');
    document.getElementById('lobby-modal').classList.add('hidden');
    document.getElementById('game-area').classList.remove('hidden');
    document.getElementById('multiplayer-hud-players-inline').classList.remove('hidden');
    const kb = document.getElementById('keyboard');
    if (kb) {
        kb.classList.remove('hidden');
        kb.innerHTML = `
            <div class="bg-[#18181b] border border-amber-500/40 rounded-2xl p-4 text-center flex flex-col sm:flex-row items-center justify-between gap-3 shadow-lg">
                <div class="flex items-center gap-3 text-left">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-lg shrink-0">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div>
                        <div class="text-sm font-black uppercase tracking-wider text-amber-400">Modo Espectador (Aguardando Fim da Rodada)</div>
                        <div class="text-xs text-slate-400">A palavra será revelada assim que todos os jogadores concluírem.</div>
                    </div>
                </div>
                <div class="flex gap-2 w-full sm:w-auto shrink-0">
                    <button onclick="document.getElementById('endgame-modal').classList.remove('hidden')" class="flex-1 sm:flex-initial px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl uppercase transition-colors">
                        Ver Resumo
                    </button>
                    <button onclick="document.getElementById('lobby-modal').classList.remove('hidden')" class="flex-1 sm:flex-initial px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold rounded-xl uppercase transition-colors">
                        Ir ao Lobby
                    </button>
                </div>
            </div>
        `;
    }
};

window.handleGuess = handleGuess;
window.showHint = showHint;
window.showEndgame = showEndgame;
window.spectateMatch = spectateMatch;
