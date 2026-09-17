const API_BASE_URL = '/api/v1';

async function request(path, options = {}) {
    const response = await fetch(`${API_BASE_URL}${path}`, {
        headers: { Accept: 'application/json', ...options.headers },
        ...options,
    });

    if (! response.ok) {
        const payload = await response.json().catch(() => null);
        const error = new Error(payload?.message ?? 'تعذر الاتصال بالخدمة. حاول مرة أخرى.');
        error.status = response.status;
        error.errors = payload?.errors ?? {};
        throw error;
    }

    if (response.status === 204) {
        return null;
    }

    return response.json();
}

function bearerHeaders(token) {
    return token ? { Authorization: `Bearer ${token}` } : {};
}

function toQueryString(filters) {
    const query = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            query.set(key, String(value));
        }
    });

    return query.size === 0 ? '' : `?${query.toString()}`;
}

export const catalogApi = {
    governorates: () => request('/governorates'),
    cities: (governorate) => request(`/governorates/${encodeURIComponent(governorate)}/cities`),
    services: () => request('/services'),
    carBrands: () => request('/car-brands'),
};

export const serviceCenterApi = {
    index: (filters = {}) => request(`/service-centers${toQueryString(filters)}`),
    show: (slug) => request(`/service-centers/${encodeURIComponent(slug)}`),
};

export const authApi = {
    login: (credentials) => request('/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(credentials),
    }),
    me: (token) => request('/auth/me', {
        headers: bearerHeaders(token),
    }),
    logout: (token) => request('/auth/logout', {
        method: 'DELETE',
        headers: bearerHeaders(token),
    }),
};

export const ownerApi = {
    serviceCenters: (token) => request('/owner/service-centers', {
        headers: bearerHeaders(token),
    }),
};

export function storageUrl(path) {
    if (! path) return null;
    if (/^(https?:\/\/|\/)/.test(path)) return path;
    return `/storage/${path}`;
}
