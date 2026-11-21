import { state } from './state.js';

export const apiCall = async (endpoint, payload = null, timeoutMs = 10000) => {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);

    try {
        const options = {
            method: payload ? 'POST' : 'GET',
            headers: { 'Content-Type': 'application/json', 'X-UUID': state.uuid },
            signal: controller.signal
        };
        if (payload) options.body = JSON.stringify(payload);

        const res = await fetch(endpoint, options);
        if (!res.ok) {
            return { error: `Erro HTTP ${res.status}` };
        }
        return await res.json();
    } catch (err) {
        if (err && err.name === 'AbortError') {
            return { error: 'Tempo limite de conexão excedido. Tente novamente.' };
        }
        return { error: 'Falha na conexão com o servidor.' };
    } finally {
        clearTimeout(timer);
    }
};
