import { state } from './state.js';

export const apiCall = async (endpoint, payload = null) => {
    const options = {
        method: payload ? 'POST' : 'GET',
        headers: { 'Content-Type': 'application/json', 'X-UUID': state.uuid }
    };
    if (payload) options.body = JSON.stringify(payload);
    const res = await fetch(endpoint, options);
    return await res.json();
};
