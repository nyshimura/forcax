<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Forcax</title>
  
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#121213">
  <link rel="apple-touch-icon" href="icon.svg">
  <link rel="icon" type="image/svg+xml" href="icon.svg">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] },
          colors: { termo: { bg: '#121213', correct: '#538d4e' } },
          animation: {
            'pop': 'pop 0.15s ease-in-out',
            'shake': 'shake 0.5s ease-in-out',
            'flip': 'flip 0.5s ease-in-out forwards',
          },
          keyframes: {
            pop: {
              '0%': { transform: 'scale(0.8)' },
              '50%': { transform: 'scale(1.1)' },
              '100%': { transform: 'scale(1)' },
            },
            shake: {
              '0%, 100%': { transform: 'translateX(0)' },
              '20%, 60%': { transform: 'translateX(-6px)' },
              '40%, 80%': { transform: 'translateX(6px)' },
            },
            flip: {
              '0%': { transform: 'rotateX(0deg)' },
              '50%': { transform: 'rotateX(90deg)' },
              '100%': { transform: 'rotateX(0deg)' },
            }
          }
      } }
    }
  </script>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="h-[100dvh] bg-[#121213] text-[#d7dadc] flex flex-col font-sans overflow-hidden antialiased">
    <!-- Header -->
    <header class="w-full max-w-5xl mx-auto border-b border-[#27272a] h-14 lg:h-16 px-4 lg:px-8 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-2 lg:gap-3">
            <span class="hidden md:inline-flex items-center gap-2 font-black text-lg tracking-widest text-white mr-2">
                <i class="fas fa-gamepad text-emerald-400"></i> FORCA<span class="text-emerald-400">X</span>
            </span>
            <button id="btn-help" class="p-2 lg:px-3 lg:py-1.5 lg:bg-[#1a1a1b] lg:border lg:border-slate-700 lg:rounded-lg text-zinc-400 hover:text-white flex items-center gap-2 transition-colors" onclick="loadStats()" title="Estatísticas">
                <i class="fas fa-chart-bar"></i>
                <span class="hidden lg:inline text-xs font-bold uppercase tracking-wider">Estatísticas</span>
            </button>
            <button id="btn-user" class="p-2 lg:px-3 lg:py-1.5 lg:bg-[#1a1a1b] lg:border lg:border-slate-700 lg:rounded-lg text-zinc-400 hover:text-white flex items-center gap-2 transition-colors" onclick="openProfile()" title="Meu Perfil">
                <i class="fas fa-user"></i>
                <span class="hidden lg:inline text-xs font-bold uppercase tracking-wider">Perfil</span>
            </button>
            <button id="btn-multiplayer-hud" onclick="document.getElementById('multiplayer-hud-modal').classList.remove('hidden')" class="p-2 lg:px-3 lg:py-1.5 lg:bg-indigo-950/40 lg:border lg:border-indigo-500/40 lg:rounded-lg text-indigo-400 hover:text-indigo-300 hidden flex items-center gap-2 transition-colors" title="Placar Copa VIP">
                <i class="fas fa-trophy"></i>
                <span class="hidden lg:inline text-xs font-bold uppercase tracking-wider">Placar</span>
            </button>
        </div>
        <button onclick="document.getElementById('modes-modal').classList.remove('hidden')" class="flex items-center gap-2 bg-[#1a1a1b] hover:bg-[#2a2a2c] px-3 sm:px-5 py-1.5 sm:py-2 rounded-full border border-slate-600 transition-colors shadow-lg">
            <span class="text-[10px] sm:text-xs text-slate-400 uppercase font-bold tracking-widest">MODO:</span>
            <span id="title-mode" class="font-black text-xs sm:text-sm tracking-widest text-emerald-400 uppercase">SOLO</span>
            <div class="bg-slate-700 rounded-full w-5 h-5 flex items-center justify-center ml-1">
                <i class="fas fa-chevron-down text-[10px] text-white"></i>
            </div>
        </button>
        <div class="flex items-center gap-2">
            <button onclick="startMultiplayerFlow()" class="hidden md:flex items-center gap-1.5 bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600/30 border border-emerald-500/30 px-3.5 py-1.5 rounded-full text-xs font-bold transition-colors">
                <i class="fas fa-users"></i> MULTIPLAYER
            </button>
            <button onclick="requestExtraWordUI()" class="flex items-center gap-1 bg-indigo-600/20 text-indigo-400 hover:bg-indigo-600/40 border border-indigo-500/30 px-3 sm:px-3.5 py-1.5 rounded-full text-xs font-bold transition-colors">
                <i class="fas fa-plus"></i> NOVA
            </button>
        </div>
    </header>

    <main class="flex-1 max-w-6xl w-full mx-auto flex flex-col justify-between items-center px-2 sm:px-6 py-1 sm:py-3 gap-2 min-h-0 overflow-y-auto">
        <div id="loading-box" class="flex flex-col items-center py-6 hidden">
            <div class="w-8 h-8 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
            <span class="text-xs font-medium text-zinc-400 mt-2" data-i18n="LOADING">Carregando...</span>
        </div>
        <div id="already-played" class="text-center py-4 mb-2 hidden">
            <h2 class="text-xl font-bold text-white mb-2" data-i18n="ALREADY_PLAYED">Você já jogou este modo hoje!</h2>
            <p class="text-zinc-400 text-sm" data-i18n="COME_BACK">Volte amanhã para uma nova palavra.</p>
        </div>

        <div id="toast-container" class="fixed top-16 left-1/2 -translate-x-1/2 z-[1050] flex flex-col gap-2 pointer-events-none"></div>

        <div id="game-area" class="w-full flex-1 flex flex-col lg:flex-row items-center lg:items-stretch justify-center gap-2 lg:gap-6 min-h-0 hidden">
            <div class="w-full flex-1 flex flex-col items-center justify-between min-h-0 gap-2">
                <div id="boards-container" class="grid w-full gap-2 lg:gap-4 flex-1 min-h-0 items-center"></div>
                
                <div id="mistakes-info" class="text-center text-sm lg:text-base font-bold text-zinc-400 my-1 shrink-0 bg-[#18181b] border border-[#27272a] px-5 py-2 rounded-full shadow">
                    <span data-i18n="MISTAKES_REMAINING">Erros Restantes</span>: <span id="lives-counter" class="text-emerald-400 text-xl lg:text-2xl font-black"></span>
                    <span class="mx-3 text-zinc-600">|</span>
                    <span data-i18n="HINTS">Dicas</span>: <span id="hints-counter" class="text-yellow-400 text-xl lg:text-2xl font-black"></span>
                </div>
            </div>

            <div id="multiplayer-hud-players-inline" class="w-full max-w-sm sm:max-w-md lg:w-80 xl:w-96 lg:max-w-none flex flex-col gap-1.5 overflow-y-auto max-h-48 lg:max-h-full my-1 lg:my-0 hidden shrink-0 px-2 lg:p-4 lg:bg-[#18181b] lg:border lg:border-[#27272a] lg:rounded-2xl"></div>
        </div>

        <div id="keyboard" class="w-full max-w-md md:max-w-xl lg:max-w-2xl pb-1 shrink-0 select-none flex flex-col gap-1.5 hidden"></div>

        <div id="sponsor-container" class="w-full max-w-md md:max-w-xl pb-4 shrink-0 hidden flex flex-col items-center">
            <span class="text-[10px] text-slate-500 uppercase tracking-widest mb-1" data-i18n="PATROCINIO">PATROCÍNIO</span>
            <a id="sponsor-link" href="#" target="_blank" class="w-full block hover:opacity-90 transition-opacity rounded-xl overflow-hidden shadow-lg border border-slate-800">
                <img id="sponsor-img" src="" alt="Sponsor" class="w-full h-auto max-h-32 object-contain bg-black/50">
            </a>
        </div>
    </main>

    <!-- Modes Modal -->
    <div id="modes-modal" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-[80] hidden backdrop-blur-sm">
        <div class="bg-[#121213] border border-slate-700 w-full max-w-sm rounded-3xl p-6 shadow-2xl relative animate-pop">
            <button onclick="document.getElementById('modes-modal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-white w-8 h-8 flex items-center justify-center rounded-full bg-slate-800 transition-colors">
                <i class="fas fa-times"></i>
            </button>
            
            <h2 class="text-2xl font-black mb-6 uppercase tracking-widest text-white text-center">Modos de Jogo</h2>
            
            <div class="space-y-3">
                <button onclick="selectMode(1)" class="w-full bg-[#1e1e1e] hover:bg-[#2a2a2c] border border-slate-700 p-4 rounded-xl flex items-center text-left transition-colors group">
                    <div class="w-12 h-12 bg-emerald-500/20 text-emerald-400 rounded-lg flex items-center justify-center text-xl font-black mr-4 group-hover:scale-110 transition-transform">1</div>
                    <div>
                        <div class="font-black tracking-wider text-emerald-400 uppercase">Solo</div>
                        <div class="text-xs text-slate-400">Adivinhe 1 palavra</div>
                    </div>
                </button>

                <button onclick="selectMode(2)" class="w-full bg-[#1e1e1e] hover:bg-[#2a2a2c] border border-slate-700 p-4 rounded-xl flex items-center text-left transition-colors group">
                    <div class="w-12 h-12 bg-yellow-500/20 text-yellow-400 rounded-lg flex items-center justify-center text-xl font-black mr-4 group-hover:scale-110 transition-transform">2</div>
                    <div>
                        <div class="font-black tracking-wider text-yellow-400 uppercase">Dueto</div>
                        <div class="text-xs text-slate-400">Adivinhe 2 palavras ao mesmo tempo</div>
                    </div>
                </button>

                <button onclick="selectMode(4)" class="w-full bg-[#1e1e1e] hover:bg-[#2a2a2c] border border-slate-700 p-4 rounded-xl flex items-center text-left transition-colors group">
                    <div class="w-12 h-12 bg-rose-500/20 text-rose-400 rounded-lg flex items-center justify-center text-xl font-black mr-4 group-hover:scale-110 transition-transform">4</div>
                    <div>
                        <div class="font-black tracking-wider text-rose-400 uppercase">Quarteto</div>
                        <div class="text-xs text-slate-400">Adivinhe 4 palavras simultâneas</div>
                    </div>
                </button>

                <div class="border-t border-slate-800 my-2 pt-2"></div>

                <button onclick="startMultiplayerFlow()" class="w-full bg-indigo-600/20 hover:bg-indigo-600/30 border border-indigo-500/30 p-4 rounded-xl flex items-center text-left transition-colors group">
                    <div class="w-12 h-12 bg-indigo-500 text-white rounded-lg flex items-center justify-center text-xl font-black mr-4 group-hover:scale-110 transition-transform shadow-[0_0_15px_rgba(99,102,241,0.5)]">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="font-black tracking-wider text-indigo-400 uppercase">Multiplayer</div>
                        <div class="text-xs text-slate-300">Jogue com amigos em tempo real</div>
                    </div>
                </button>

                <button onclick="openPublicRooms()" class="w-full bg-[#1e1e1e] hover:bg-[#2a2a2c] border border-emerald-500/30 p-4 rounded-xl flex items-center text-left transition-colors group mt-2">
                    <div class="w-12 h-12 bg-emerald-500/20 text-emerald-400 rounded-lg flex items-center justify-center text-xl font-black mr-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-search"></i>
                    </div>
                    <div>
                        <div class="font-black tracking-wider text-emerald-400 uppercase">Procurar Salas</div>
                        <div class="text-xs text-slate-400">Encontre partidas públicas abertas</div>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Public Rooms Modal -->
    <div id="public-rooms-modal" class="fixed inset-0 bg-black/90 flex items-center justify-center hidden z-[80] p-4 backdrop-blur-sm">
        <div class="bg-[#1e1e1e] border border-slate-700 p-6 md:p-8 rounded-2xl w-full max-w-lg shadow-2xl flex flex-col max-h-[90vh]">
            <div class="flex justify-between items-center mb-4 shrink-0">
                <h2 class="text-xl md:text-2xl font-black uppercase tracking-wider text-emerald-400">Salas</h2>
                <button onclick="closePublicRooms()" class="text-slate-400 hover:text-white transition-colors text-xl w-10 h-10 flex items-center justify-center rounded-full bg-slate-800 hover:bg-slate-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="flex flex-col gap-3 mb-4 shrink-0">
                <div class="flex gap-2">
                    <input type="text" id="search-room-code" placeholder="CÓDIGO DA SALA" class="flex-1 min-w-0 bg-[#2a2a2c] text-white p-3 rounded-xl border border-slate-700 focus:outline-none focus:border-emerald-500 font-mono font-bold tracking-widest uppercase">
                    <button onclick="searchRoomByCode()" class="shrink-0 bg-indigo-600 hover:bg-indigo-500 text-white px-4 rounded-xl font-bold transition-colors">
                        <i class="fas fa-sign-in-alt md:mr-2"></i> <span class="hidden md:inline">ENTRAR</span>
                    </button>
                </div>
                <div class="flex bg-slate-800 rounded-lg p-1">
                    <button id="filter-room-all" onclick="setRoomFilter('all')" class="flex-1 py-2 text-xs font-bold rounded-md bg-emerald-600 text-white transition-colors">TODAS</button>
                    <button id="filter-room-public" onclick="setRoomFilter('public')" class="flex-1 py-2 text-xs font-bold rounded-md text-slate-400 hover:text-white transition-colors">PÚBLICAS</button>
                    <button id="filter-room-private" onclick="setRoomFilter('private')" class="flex-1 py-2 text-xs font-bold rounded-md text-slate-400 hover:text-white transition-colors">PRIVADAS</button>
                </div>
            </div>
            
            <div id="public-rooms-list" class="flex-1 overflow-y-auto min-h-[200px] flex flex-col gap-2">
                <!-- Rooms will be injected here -->
                <div class="text-center text-slate-400 font-bold py-10 uppercase tracking-widest text-sm">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Buscando...
                </div>
            </div>
            
            <button onclick="loadPublicRooms()" class="w-full mt-4 h-12 bg-slate-700 hover:bg-slate-600 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2 shrink-0">
                <i class="fas fa-sync-alt"></i> ATUALIZAR
            </button>
        </div>
    </div>

    <!-- Endgame Modal -->
    <div id="endgame-modal" class="fixed inset-0 bg-black/90 flex items-center justify-center hidden z-50 p-4 backdrop-blur-sm">
        <div class="bg-[#1e1e1e] border border-slate-700 p-6 md:p-8 rounded-2xl w-full max-w-lg shadow-2xl flex flex-col items-center max-h-[90vh]">
            <h2 id="endgame-title" class="text-2xl md:text-3xl font-black mb-4 uppercase tracking-wider shrink-0"></h2>
            
            <div id="endgame-content" class="w-full space-y-4 mb-4 overflow-y-auto overflow-x-hidden pr-2 flex-1 min-h-0">
                <!-- Content will be injected here -->
            </div>
            
            <div class="w-full flex flex-col items-center border-t border-slate-700 pt-4 mb-4 shrink-0" id="endgame-countdown-container">
                <span class="text-slate-400 mb-2" data-i18n="NEXT_WORD_IN">Próxima palavra em:</span>
                <span id="countdown" class="text-3xl font-bold font-mono tracking-widest text-emerald-400">00:00:00</span>
            </div>

            <div id="endgame-premium-actions" class="w-full flex flex-col gap-2 mb-4 hidden shrink-0">
                <button onclick="playExtraWord()" class="w-full h-12 bg-amber-500 hover:bg-amber-400 text-black font-black uppercase tracking-wider rounded-xl transition-colors shadow-[0_0_15px_rgba(245,158,11,0.4)] hidden" id="btn-play-infinite">
                    <i class="fas fa-infinity mr-2"></i> JOGAR NOVAMENTE (VIP)
                </button>
                <button onclick="watchAdForWord()" class="w-full h-12 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl transition-colors hidden" id="btn-watch-ad">
                    <i class="fas fa-play-circle mr-2"></i> ASSISTIR ANÚNCIO (+1 PALAVRA)
                </button>
                <button onclick="hostRequestNextMatch()" class="w-full h-12 bg-indigo-600 hover:bg-indigo-500 text-white font-black uppercase tracking-wider rounded-xl transition-colors shadow-[0_0_15px_rgba(79,70,229,0.4)] hidden" id="btn-next-match-multi">
                    <i class="fas fa-forward mr-2"></i> PRÓXIMA PARTIDA (+ ANÚNCIO)
                </button>
            </div>

            <div class="flex gap-4 w-full shrink-0 mb-2" id="endgame-share-row">
                <button onclick="shareResult()" class="w-full h-12 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2">
                    <span data-i18n="BTN_SHARE">COMPARTILHAR</span>
                </button>
            </div>
            
            <div class="flex gap-4 w-full shrink-0" id="endgame-secondary-row">
                <button onclick="closeEndgame()" class="flex-1 h-12 bg-slate-700 hover:bg-slate-600 text-white font-bold rounded-xl transition-colors" data-i18n="BTN_CLOSE">
                    FECHAR
                </button>
                <button onclick="closeEndgame(); loadStats()" class="flex-1 h-12 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2">
                    <span data-i18n="BTN_STATISTICS">ESTATÍSTICAS</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Modal -->
    <div id="stats-modal" class="fixed inset-0 bg-black/90 hidden z-50 overflow-y-auto backdrop-blur-sm">
        <div class="min-h-screen flex items-center justify-center p-4 py-8">
            <div class="bg-[#1e1e1e] border border-slate-700 p-6 rounded-2xl w-full max-w-md shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-black uppercase tracking-wider text-emerald-400" data-i18n="MODAL_STATS_TITLE">Estatísticas</h2>
                <button onclick="document.getElementById('stats-modal').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="flex bg-slate-800 rounded-lg p-1 mb-6">
                <button class="flex-1 py-2 text-xs font-bold rounded-md bg-emerald-600 text-white transition-colors" onclick="loadStats(1)" id="tab-stat-1" data-i18n="TITLE_1">FORCA</button>
                <button class="flex-1 py-2 text-xs font-bold rounded-md text-slate-400 hover:text-white transition-colors" onclick="loadStats(2)" id="tab-stat-2" data-i18n="TITLE_2">DUETO</button>
                <button class="flex-1 py-2 text-xs font-bold rounded-md text-slate-400 hover:text-white transition-colors" onclick="loadStats(4)" id="tab-stat-4" data-i18n="TITLE_4">QUARTETO</button>
            </div>

            <div class="grid grid-cols-4 gap-2 mb-8 text-center">
                <div class="flex flex-col items-center">
                    <span id="stat-jogos" class="text-3xl font-bold">0</span>
                    <span class="text-xs text-slate-400 mt-1" data-i18n="MODAL_STATS_GAMES">Jogos</span>
                </div>
                <div class="flex flex-col items-center">
                    <span id="stat-vitorias" class="text-3xl font-bold">0</span>
                    <span class="text-xs text-slate-400 mt-1" data-i18n="MODAL_STATS_WIN_PERCENT">% Vitórias</span>
                </div>
                <div class="flex flex-col items-center">
                    <span id="stat-sequencia" class="text-3xl font-bold">0</span>
                    <span class="text-xs text-slate-400 mt-1" data-i18n="MODAL_STATS_STREAK">Sequência</span>
                </div>
                <div class="flex flex-col items-center">
                    <span id="stat-melhor" class="text-3xl font-bold">0</span>
                    <span class="text-xs text-slate-400 mt-1" data-i18n="MODAL_STATS_BEST_STREAK">Melhor Seq</span>
                </div>
            </div>

            <h3 class="text-lg font-bold mb-4 uppercase tracking-widest text-slate-300" data-i18n="MODAL_STATS_MISTAKES">Distribuição de Erros (Vitórias)</h3>
            <div id="stat-distribution" class="flex flex-col gap-1 mb-8">
                <!-- Bar chart injected here -->
            </div>

            <h3 class="text-lg font-bold mb-4 uppercase tracking-widest text-slate-300" data-i18n="REGIONAL_VS_GLOBAL">Regionais vs Global</h3>
            <div class="bg-slate-800 rounded-xl p-4 mb-8 flex justify-between items-center">
                <div class="text-center w-1/2 border-r border-slate-700">
                    <div class="text-sm text-slate-400 mb-1" id="stat-country-name">🇧🇷 BR</div>
                    <div class="text-2xl font-bold text-emerald-400" id="stat-country-rate">0%</div>
                </div>
                <div class="text-center w-1/2">
                    <div class="text-sm text-slate-400 mb-1" data-i18n="GLOBAL">🌍 Global</div>
                    <div class="text-2xl font-bold text-blue-400" id="stat-global-rate">0%</div>
                </div>
            </div>

        </div>
    </div>
</div>    <!-- Profile Modal -->
    <div id="profile-modal" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-[90] hidden">
        <div class="bg-[#121213] border border-slate-700 w-full max-w-md rounded-2xl p-6 shadow-2xl relative animate-pop">
            <button onclick="document.getElementById('profile-modal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-white w-8 h-8 flex items-center justify-center rounded-full bg-slate-800">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="text-center">
                <div class="w-20 h-20 bg-emerald-500/20 text-emerald-400 rounded-full mx-auto flex items-center justify-center text-4xl mb-4 border-2 border-emerald-500 overflow-hidden">
                    <img id="profile-avatar-preview" src="https://api.dicebear.com/9.x/adventurer/svg?seed=Visitante" alt="Avatar" class="w-full h-full object-cover bg-slate-700">
                </div>
                <h2 class="text-2xl font-black mb-2 uppercase tracking-widest text-emerald-400">Meu Perfil</h2>
                <p class="text-slate-400 text-sm mb-6">Como você quer ser chamado nas partidas Multiplayer?</p>
                
                <input type="text" id="profile-name" oninput="document.getElementById('profile-avatar-preview').src = 'https://api.dicebear.com/9.x/adventurer/svg?seed=' + encodeURIComponent(this.value || 'Visitante')" placeholder="Seu Apelido" class="w-full bg-[#1e1e1e] border border-slate-700 rounded-lg p-4 text-white font-bold text-center text-xl focus:outline-none focus:border-emerald-500 transition-colors mb-4 uppercase tracking-wider" maxlength="15">
                
                <button onclick="saveProfile()" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-black uppercase tracking-wider py-4 rounded-lg transition-colors shadow-lg">
                    SALVAR APELIDO
                </button>
                
                <div class="mt-6 border-t border-slate-800 pt-6">
                    <button onclick="openCreateRoomSettings()" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-black uppercase tracking-wider py-4 rounded-lg transition-colors shadow-[0_0_15px_rgba(79,70,229,0.4)] mb-3">
                        <i class="fas fa-cog mr-2"></i> CONFIGURAR SALA
                    </button>
                    
                    <a href="#" target="_blank" id="btn-donate" class="w-full bg-[#FF813F] hover:bg-[#ff945e] text-white font-black uppercase tracking-wider py-4 rounded-lg transition-colors shadow-[0_0_15px_rgba(255,129,63,0.4)] hidden block">
                        <i class="fas fa-coffee mr-2"></i> APOIAR O PROJETO
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Room Settings Modal -->
    <div id="create-room-modal" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-[95] hidden">
        <div class="bg-[#121213] border border-slate-700 w-full max-w-sm rounded-2xl p-6 shadow-2xl relative animate-pop">
            <button onclick="document.getElementById('create-room-modal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-white w-8 h-8 flex items-center justify-center rounded-full bg-slate-800">
                <i class="fas fa-times"></i>
            </button>
            
            <h2 class="text-2xl font-black mb-6 uppercase tracking-widest text-indigo-400">Criar Sala</h2>
            
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-slate-400 text-xs font-bold mb-2 uppercase tracking-wider">Visibilidade da Sala</label>
                    <div class="flex bg-slate-800 rounded-lg p-1">
                        <button class="flex-1 py-2 text-sm font-bold rounded-md bg-indigo-600 text-white transition-colors" id="btn-room-private" onclick="toggleRoomPrivacy(true)">
                            <i class="fas fa-lock mr-2"></i>Privada
                        </button>
                        <button class="flex-1 py-2 text-sm font-bold rounded-md text-slate-400 hover:text-white transition-colors" id="btn-room-public" onclick="toggleRoomPrivacy(false)">
                            <i class="fas fa-globe mr-2"></i>Pública
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-500 mt-2 italic" id="room-privacy-desc">Somente jogadores aprovados por você poderão entrar.</p>
                </div>

                <div>
                    <label class="block text-slate-400 text-xs font-bold mb-2 uppercase tracking-wider">Tentativas por Palavra</label>
                    <select id="room-max-mistakes" class="w-full bg-[#1e1e1e] border border-slate-700 rounded-lg p-3 text-white font-bold focus:outline-none focus:border-indigo-500 transition-colors uppercase">
                        <option value="6" selected>6 Tentativas (Padrão)</option>
                        <option value="8">8 Tentativas (Fácil)</option>
                        <option value="4">4 Tentativas (Difícil)</option>
                        <option value="1">1 Tentativa (Extremo)</option>
                    </select>
                </div>
            </div>

            <button onclick="document.getElementById('create-room-modal').classList.add('hidden'); watchAdToCreateRoom()" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-black uppercase tracking-wider py-4 rounded-lg transition-colors shadow-[0_0_15px_rgba(79,70,229,0.4)]">
                <i class="fas fa-play-circle mr-2"></i> CRIAR (+ ANÚNCIO)
            </button>
        </div>
    </div>

    <!-- Ad / Rewarded Modal (Supports Adsterra, Google H5 Games AdSense & Simulation Fallback) -->
    <div id="ad-simulation-modal" class="fixed inset-0 bg-black/95 flex items-center justify-center p-4 z-[999] hidden backdrop-blur-sm">
        <div class="bg-[#121213] border border-slate-700 w-full max-w-md rounded-2xl p-6 shadow-2xl relative animate-pop text-center flex flex-col items-center">
            <div class="flex items-center justify-between w-full mb-3 border-b border-slate-800 pb-2">
                <span class="text-xs font-black uppercase tracking-widest text-indigo-400 flex items-center gap-1.5" id="ad-modal-badge">
                    <i class="fas fa-ad"></i> Patrocinado
                </span>
                <span class="text-xs font-mono font-bold bg-indigo-500/20 text-indigo-300 px-2.5 py-0.5 rounded-full border border-indigo-500/30">
                    Recompensa em <span id="ad-countdown">5</span>s
                </span>
            </div>

            <!-- Dynamic Ad Slot (Iframe for Adsterra or Simulation Graphic) -->
            <div id="ad-dynamic-slot" class="w-full min-h-[250px] bg-[#18181b] border border-slate-800 rounded-xl flex flex-col items-center justify-center p-3 mb-4 overflow-hidden">
                <div class="w-16 h-16 bg-indigo-500/20 text-indigo-400 rounded-full mx-auto flex items-center justify-center text-3xl mb-3 border-2 border-indigo-500 relative overflow-hidden">
                    <i class="fas fa-bullhorn absolute z-10"></i>
                    <div class="absolute inset-0 border-4 border-indigo-400 border-t-transparent rounded-full animate-spin z-0"></div>
                </div>
                <h2 class="text-lg font-black mb-1 uppercase tracking-widest text-white" id="ad-modal-title">Carregando Recompensa</h2>
                <p class="text-slate-400 text-xs max-w-xs" id="ad-modal-desc">Aguarde alguns segundos para liberar seu acesso VIP.</p>
            </div>
            
            <button id="btn-close-ad" class="w-full bg-slate-700 text-slate-500 font-bold py-3.5 rounded-xl uppercase transition-colors text-sm" disabled>
                Aguarde...
            </button>
        </div>
    </div>

    <!-- Lobby Modal -->
    <div id="lobby-modal" class="fixed inset-0 bg-[#121213] flex flex-col p-4 lg:p-8 z-[70] hidden overflow-y-auto">
        <header class="w-full max-w-4xl mx-auto border-b border-[#27272a] h-14 flex items-center justify-between shrink-0 mb-6">
            <h1 class="text-xl lg:text-2xl font-black uppercase tracking-widest text-emerald-400"><i class="fas fa-crown mr-2"></i>SALA VIP MULTIPLAYER</h1>
            <button onclick="exitLobby()" class="text-slate-400 hover:text-white text-sm font-bold flex items-center gap-2 bg-[#1e1e1e] hover:bg-rose-950/40 border border-slate-700 hover:border-rose-500/40 px-4 py-2 rounded-xl transition-colors">
                <i class="fas fa-sign-out-alt"></i> <span class="hidden sm:inline">SAIR DA SALA</span>
            </button>
        </header>

        <div class="max-w-4xl w-full mx-auto grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6 pb-12">
            <!-- Left Column on PC: Room Code, QR Code & Host Settings -->
            <div class="lg:col-span-5 flex flex-col gap-4">
                <div class="bg-[#1e1e1e] p-6 rounded-2xl border border-slate-700 text-center relative">
                    <h2 class="text-slate-400 text-sm font-bold uppercase mb-2">Código da Sala</h2>
                    <div class="text-4xl lg:text-5xl font-black tracking-[0.2em] text-white select-all" id="lobby-code">ABCD</div>
                    <button onclick="copyLobbyLink()" class="absolute top-4 right-4 text-emerald-400 hover:text-emerald-300 text-xl bg-slate-800/80 w-10 h-10 rounded-lg flex items-center justify-center border border-slate-700" title="Copiar Link de Convite"><i class="fas fa-copy"></i></button>
                    <div class="mt-4 flex justify-center">
                        <img id="lobby-qrcode" src="" alt="QR Code" class="w-32 h-32 lg:w-40 lg:h-40 rounded-lg bg-white p-1.5 shadow-lg">
                    </div>
                    <p class="text-xs text-slate-400 mt-3">Aponte a câmera ou copie o link para convidar amigos</p>
                </div>

                <div id="lobby-host-settings" class="bg-[#1e1e1e] p-5 rounded-2xl border border-slate-700 flex flex-col gap-3 hidden shrink-0">
                    <h2 class="text-slate-400 text-sm font-bold uppercase"><i class="fas fa-sliders-h mr-1.5"></i>Configurações da Sala</h2>
                    
                    <div class="flex items-center justify-between">
                        <label class="text-white text-sm font-bold">Máximo de Jogadores:</label>
                        <select id="lobby-max-players" onchange="updateRoomConfig()" class="bg-[#2a2a2c] text-white p-2 rounded-lg border border-slate-600 focus:outline-none focus:border-emerald-500">
                            <option value="2">2</option>
                            <option value="4" selected>4</option>
                            <option value="10">10</option>
                            <option value="999">Ilimitado</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <label class="text-white text-sm font-bold">Sala Pública:</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                          <input type="checkbox" id="lobby-is-public" onchange="updateRoomConfig()" class="sr-only peer">
                          <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        </label>
                    </div>
                    <p class="text-[11px] text-slate-400 leading-tight">Salas públicas aparecem na lista para todos.</p>
                    <button onclick="closeRoom()" class="w-full h-10 mt-2 bg-rose-600/20 text-rose-500 hover:bg-rose-600 hover:text-white border border-rose-600/30 font-bold rounded-lg transition-colors text-xs uppercase tracking-wider">
                        <i class="fas fa-trash-alt mr-2"></i> ENCERRAR SALA
                    </button>
                </div>
            </div>

            <!-- Right Column on PC: Players List, Join Requests & Start Action -->
            <div class="lg:col-span-7 flex flex-col gap-4 justify-between">
                <div class="flex flex-col gap-4">
                    <div id="lobby-spectator-box" class="bg-[#1e1e1e] p-5 rounded-2xl border border-emerald-500/40 flex flex-col gap-3 hidden shrink-0">
                        <div class="flex items-center justify-between">
                            <h2 class="text-emerald-400 text-sm font-black uppercase tracking-wider">
                                <i class="fas fa-satellite-dish fa-spin mr-1.5"></i> Partida em Andamento ao Vivo
                            </h2>
                            <button onclick="spectateMatch()" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-1.5 rounded-lg uppercase transition-colors flex items-center gap-1.5 shadow">
                                <i class="fas fa-tv"></i> Ver Tabuleiro
                            </button>
                        </div>
                        <div id="lobby-live-spectator-hud" class="space-y-1.5 max-h-52 overflow-y-auto"></div>
                    </div>

                    <div id="lobby-requests-container" class="bg-[#1e1e1e] p-5 rounded-2xl border border-amber-500/40 flex flex-col gap-2 hidden shrink-0">
                        <h2 class="text-amber-400 text-sm font-bold uppercase"><i class="fas fa-user-clock mr-1.5"></i>Pedidos de Entrada</h2>
                        <div id="lobby-requests" class="max-h-40 overflow-y-auto space-y-2">
                            <!-- Requests list -->
                        </div>
                    </div>

                    <div class="bg-[#1e1e1e] p-5 rounded-2xl border border-slate-700 flex flex-col flex-1">
                        <h2 class="text-slate-400 text-sm font-bold uppercase mb-4"><i class="fas fa-users mr-1.5"></i>Jogadores na Sala (<span id="lobby-count">1</span>)</h2>
                        <div id="lobby-players" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-2.5">
                            <!-- Players list -->
                        </div>
                    </div>
                </div>

                <div>
                    <button id="btn-start-room" onclick="startRoom()" class="w-full h-14 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl transition-colors hidden text-lg uppercase tracking-wider shrink-0 shadow-lg">
                        <i class="fas fa-play mr-2"></i> INICIAR PARTIDA
                    </button>
                    <div id="lobby-waiting-host" class="text-center text-slate-400 font-bold uppercase tracking-wider hidden py-4 bg-[#1e1e1e] rounded-2xl border border-slate-800">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Aguardando o Host Iniciar...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Multiplayer HUD Modal -->
    <div id="multiplayer-hud-modal" class="fixed inset-0 bg-black/90 flex items-center justify-center hidden z-[80] p-4 backdrop-blur-sm">
        <div class="bg-[#1e1e1e] border border-slate-700 p-6 rounded-2xl w-full max-w-sm shadow-2xl flex flex-col">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black uppercase tracking-wider text-emerald-400">Copa VIP 🏆</h2>
                <button onclick="document.getElementById('multiplayer-hud-modal').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors text-xl w-10 h-10 flex items-center justify-center rounded-full bg-slate-800 hover:bg-slate-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="race-players" class="flex flex-col gap-3">
                <!-- Race progress -->
            </div>
            <button onclick="document.getElementById('multiplayer-hud-modal').classList.add('hidden')" class="w-full h-12 bg-slate-700 hover:bg-slate-600 text-white font-bold rounded-xl transition-colors mt-6 uppercase tracking-wider">
                FECHAR E VOLTAR AO JOGO
            </button>
        </div>
    </div>

    <script type="module" src="js/main.js?v=9"></script>
</body>
</html>