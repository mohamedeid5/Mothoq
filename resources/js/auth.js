import { computed, readonly, reactive } from 'vue';
import { authApi } from './api';

const TOKEN_STORAGE_KEY = 'mothoq_auth_token';

const state = reactive({
    user: null,
    token: localStorage.getItem(TOKEN_STORAGE_KEY),
    initialized: false,
});

let initializationPromise = null;

function persistSession(user, token) {
    state.user = user;
    state.token = token;
    localStorage.setItem(TOKEN_STORAGE_KEY, token);
}

function clearSession() {
    state.user = null;
    state.token = null;
    localStorage.removeItem(TOKEN_STORAGE_KEY);
}

async function initialize() {
    if (state.initialized) {
        return;
    }

    if (! state.token) {
        state.initialized = true;
        return;
    }

    try {
        const response = await authApi.me(state.token);
        state.user = response.data;
    } catch {
        clearSession();
    } finally {
        state.initialized = true;
    }
}

function ensureInitialized() {
    initializationPromise ??= initialize();

    return initializationPromise;
}

async function login(credentials) {
    const response = await authApi.login({
        ...credentials,
        device_name: 'Mothoq web',
    });

    persistSession(response.data.user, response.data.token);

    return response.data.user;
}

async function logout() {
    const token = state.token;

    clearSession();

    if (token) {
        await authApi.logout(token).catch(() => null);
    }
}

function homeForRole(role = state.user?.role) {
    if (role === 'admin') {
        return { name: 'admin.dashboard' };
    }

    if (role === 'center_owner') {
        return { name: 'owner.dashboard' };
    }

    return { name: 'home' };
}

export const auth = {
    state: readonly(state),
    isAuthenticated: computed(() => Boolean(state.user && state.token)),
    ensureInitialized,
    homeForRole,
    login,
    logout,
};
