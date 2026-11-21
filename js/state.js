export const state = {
    uuid: '',
    playerName: '',
    currentMode: 1,
    gameState: { guesses: [], mistakes: 0, maxMistakes: 6, words: [], language: 'pt' },
    hintsRemaining: 1,
    todayDate: new Date().toISOString().split('T')[0],
    
    // Multiplayer state
    currentRoom: null,
    pendingRoomRequest: null,
    syncInterval: null,
    pollRequestInterval: null,
    amIHost: false
};

// Initialize UUID
let storedUuid = localStorage.getItem('forca_uuid');
if (!storedUuid) {
    storedUuid = 'uuid_' + Math.random().toString(36).substr(2, 9);
    localStorage.setItem('forca_uuid', storedUuid);
}
state.uuid = storedUuid;

// Initialize Player Name
state.playerName = localStorage.getItem('forca_player_name') || '';

// Expose to window for debugging or inline HTML events if needed
window.APP_STATE = state;
