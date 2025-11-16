# Ejemplos de Integración Frontend - Loypi API

## 🚀 Setup Inicial

### Configuración Base (TypeScript/React)

```typescript
// config/api.ts
export const API_CONFIG = {
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://loypi-api.test/api',
    timeout: 30000,
};

// types/api.ts
export interface ApiResponse<T> {
    data: T;
    message?: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
}
```

---

## 🔐 Autenticación

### Owner Login Hook

```typescript
// hooks/useOwnerAuth.ts
import { useState } from 'react';
import { apiClient } from '../services/api';

export function useOwnerAuth() {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const login = async (email: string, password: string) => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(`${API_CONFIG.baseURL}/owner/auth/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password }),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Error al iniciar sesión');
            }

            const data = await response.json();
            
            // Guardar token y datos
            localStorage.setItem('owner_token', data.token);
            localStorage.setItem('owner_user', JSON.stringify(data.user));
            localStorage.setItem('owner_businesses', JSON.stringify(data.businesses));
            
            // Si hay negocios, guardar el primero como default
            if (data.businesses && data.businesses.length > 0) {
                localStorage.setItem('current_slug', data.businesses[0].slug);
            }

            return data;
        } catch (err) {
            const message = err instanceof Error ? err.message : 'Error desconocido';
            setError(message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const logout = async () => {
        const token = localStorage.getItem('owner_token');
        if (token) {
            try {
                await fetch(`${API_CONFIG.baseURL}/owner/auth/logout`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}` },
                });
            } catch (err) {
                console.error('Error al cerrar sesión:', err);
            }
        }
        localStorage.removeItem('owner_token');
        localStorage.removeItem('owner_user');
        localStorage.removeItem('owner_businesses');
        localStorage.removeItem('current_slug');
    };

    const getToken = () => localStorage.getItem('owner_token');
    const getUser = () => {
        const user = localStorage.getItem('owner_user');
        return user ? JSON.parse(user) : null;
    };
    const getBusinesses = () => {
        const businesses = localStorage.getItem('owner_businesses');
        return businesses ? JSON.parse(businesses) : [];
    };

    return { login, logout, loading, error, getToken, getUser, getBusinesses };
}
```

### Staff Login Hook

```typescript
// hooks/useStaffAuth.ts
export function useStaffAuth() {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const login = async (slug: string, pin: string) => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(`${API_CONFIG.baseURL}/${slug}/staff/auth/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pin }),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'PIN incorrecto');
            }

            const data = await response.json();
            localStorage.setItem('staff_token', data.token);
            localStorage.setItem('staff_user', JSON.stringify(data.staff));
            localStorage.setItem('current_slug', slug);
            localStorage.setItem('current_business', JSON.stringify(data.business));

            return data;
        } catch (err) {
            const message = err instanceof Error ? err.message : 'Error desconocido';
            setError(message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const logout = async () => {
        const token = localStorage.getItem('staff_token');
        const slug = localStorage.getItem('current_slug');
        if (token && slug) {
            try {
                await fetch(`${API_CONFIG.baseURL}/${slug}/staff/auth/logout`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}` },
                });
            } catch (err) {
                console.error('Error al cerrar sesión:', err);
            }
        }
        localStorage.removeItem('staff_token');
        localStorage.removeItem('staff_user');
        localStorage.removeItem('current_slug');
        localStorage.removeItem('current_business');
    };

    return { login, logout, loading, error };
}
```

### Customer OTP Hook

```typescript
// hooks/useCustomerAuth.ts
export function useCustomerAuth() {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [otpSent, setOtpSent] = useState(false);

    const requestOtp = async (slug: string, phone: string) => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(`${API_CONFIG.baseURL}/${slug}/customer/auth/request-otp`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ phone }),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Error al solicitar OTP');
            }

            setOtpSent(true);
            return await response.json();
        } catch (err) {
            const message = err instanceof Error ? err.message : 'Error desconocido';
            setError(message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const verifyOtp = async (slug: string, phone: string, otp: string) => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(`${API_CONFIG.baseURL}/${slug}/customer/auth/verify-otp`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ phone, otp }),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'OTP incorrecto');
            }

            const data = await response.json();
            localStorage.setItem('customer_token', data.token);
            localStorage.setItem('customer_user', JSON.stringify(data.customer));
            localStorage.setItem('current_slug', slug);

            return data;
        } catch (err) {
            const message = err instanceof Error ? err.message : 'Error desconocido';
            setError(message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    return { requestOtp, verifyOtp, loading, error, otpSent };
}
```

---

## 📊 Campañas

### Hook para Listar Campañas (Owner)

```typescript
// hooks/useCampaigns.ts
import { useState, useEffect } from 'react';

export function useCampaigns(slug: string, filters?: { active?: boolean }) {
    const [campaigns, setCampaigns] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        loadCampaigns();
    }, [slug, filters?.active]);

    const loadCampaigns = async () => {
        setLoading(true);
        setError(null);
        try {
            const token = localStorage.getItem('owner_token');
            const query = new URLSearchParams();
            if (filters?.active !== undefined) {
                query.append('active', filters.active.toString());
            }
            query.append('per_page', '50');

            const response = await fetch(
                `${API_CONFIG.baseURL}/${slug}/owner/campaigns?${query}`,
                {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    },
                }
            );

            if (!response.ok) throw new Error('Error al cargar campañas');

            const data = await response.json();
            setCampaigns(data.data);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Error desconocido');
        } finally {
            setLoading(false);
        }
    };

    return { campaigns, loading, error, refetch: loadCampaigns };
}
```

### Hook para Ver Campaña con Estadísticas

```typescript
// hooks/useCampaign.ts
export function useCampaign(slug: string, campaignId: string) {
    const [campaign, setCampaign] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (campaignId) loadCampaign();
    }, [slug, campaignId]);

    const loadCampaign = async () => {
        setLoading(true);
        setError(null);
        try {
            const token = localStorage.getItem('owner_token');
            const response = await fetch(
                `${API_CONFIG.baseURL}/${slug}/owner/campaigns/${campaignId}`,
                {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    },
                }
            );

            if (!response.ok) throw new Error('Error al cargar campaña');

            const data = await response.json();
            setCampaign(data);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Error desconocido');
        } finally {
            setLoading(false);
        }
    };

    return { campaign, loading, error, refetch: loadCampaign };
}
```

### Hook para Clientes en Campaña

```typescript
// hooks/useCampaignCustomers.ts
export function useCampaignCustomers(
    slug: string,
    campaignId: string,
    search?: string
) {
    const [customers, setCustomers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [pagination, setPagination] = useState({
        current_page: 1,
        total: 0,
        per_page: 15,
    });

    useEffect(() => {
        loadCustomers();
    }, [slug, campaignId, search]);

    const loadCustomers = async (page = 1) => {
        setLoading(true);
        setError(null);
        try {
            const token = localStorage.getItem('owner_token');
            const query = new URLSearchParams();
            if (search) query.append('search', search);
            query.append('per_page', '15');
            query.append('page', page.toString());

            const response = await fetch(
                `${API_CONFIG.baseURL}/${slug}/owner/campaigns/${campaignId}/customers?${query}`,
                {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    },
                }
            );

            if (!response.ok) throw new Error('Error al cargar clientes');

            const data = await response.json();
            setCustomers(data.customers.data);
            setPagination({
                current_page: data.customers.current_page,
                total: data.customers.total,
                per_page: data.customers.per_page,
            });
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Error desconocido');
        } finally {
            setLoading(false);
        }
    };

    return { customers, loading, error, pagination, refetch: loadCustomers };
}
```

---

## 🎫 Sellos (Staff)

### Hook para Agregar Sello

```typescript
// hooks/useStamps.ts
export function useAddStamp() {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const addStamp = async (
        slug: string,
        customerId: string,
        campaignId: string,
        meta?: object
    ) => {
        setLoading(true);
        setError(null);
        try {
            const token = localStorage.getItem('staff_token');
            const response = await fetch(
                `${API_CONFIG.baseURL}/${slug}/staff/stamps`,
                {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        customer_id: customerId,
                        campaign_id: campaignId,
                        meta: meta || {},
                    }),
                }
            );

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Error al agregar sello');
            }

            const data = await response.json();
            
            // Retornar información útil
            return {
                stamp: data.stamp,
                customerCampaign: data.customer_campaign,
                // Verificar si se desbloqueó algún premio
                rewardUnlocked: data.reward_unlocked || null,
            };
        } catch (err) {
            const message = err instanceof Error ? err.message : 'Error desconocido';
            setError(message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    return { addStamp, loading, error };
}
```

### Componente: Agregar Sello

```typescript
// components/AddStampForm.tsx
import { useState } from 'react';
import { useAddStamp } from '../hooks/useStamps';

export function AddStampForm({ slug, customerId, campaignId, onSuccess }: {
    slug: string;
    customerId: string;
    campaignId: string;
    onSuccess?: (data: any) => void;
}) {
    const [notes, setNotes] = useState('');
    const { addStamp, loading, error } = useAddStamp();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const result = await addStamp(slug, customerId, campaignId, {
                notes,
            });
            
            if (result.rewardUnlocked) {
                alert(`¡Premio desbloqueado! ${result.rewardUnlocked.name}`);
            }
            
            onSuccess?.(result);
            setNotes('');
        } catch (err) {
            // Error ya manejado en el hook
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            <textarea
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                placeholder="Notas (opcional)"
            />
            {error && <div className="error">{error}</div>}
            <button type="submit" disabled={loading}>
                {loading ? 'Agregando...' : 'Agregar Sello'}
            </button>
        </form>
    );
}
```

---

## 💰 Canjes (Staff)

### Hook para Canjear Premio

```typescript
// hooks/useRedemptions.ts
export function useRedeemReward() {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const redeem = async (
        slug: string,
        customerCampaignId: string,
        rewardId: string,
        meta?: object
    ) => {
        setLoading(true);
        setError(null);
        try {
            const token = localStorage.getItem('staff_token');
            const response = await fetch(
                `${API_CONFIG.baseURL}/${slug}/staff/redemptions`,
                {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        customer_campaign_id: customerCampaignId,
                        reward_id: rewardId,
                        meta: meta || {},
                    }),
                }
            );

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Error al canjear premio');
            }

            return await response.json();
        } catch (err) {
            const message = err instanceof Error ? err.message : 'Error desconocido';
            setError(message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    return { redeem, loading, error };
}
```

---

## 👥 Clientes

### Hook para Buscar Cliente

```typescript
// hooks/useCustomers.ts
export function useSearchCustomer(slug: string) {
    const [customers, setCustomers] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const search = async (query: string) => {
        if (!query || query.length < 2) {
            setCustomers([]);
            return;
        }

        setLoading(true);
        setError(null);
        try {
            const token = localStorage.getItem('staff_token');
            const response = await fetch(
                `${API_CONFIG.baseURL}/${slug}/staff/customers?search=${encodeURIComponent(query)}`,
                {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    },
                }
            );

            if (!response.ok) throw new Error('Error al buscar cliente');

            const data = await response.json();
            setCustomers(data.data);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Error desconocido');
        } finally {
            setLoading(false);
        }
    };

    return { customers, loading, error, search };
}
```

### Componente: Buscador de Cliente

```typescript
// components/CustomerSearch.tsx
import { useState, useEffect } from 'react';
import { useSearchCustomer } from '../hooks/useCustomers';
import { debounce } from 'lodash';

export function CustomerSearch({ slug, onSelect }: {
    slug: string;
    onSelect: (customer: any) => void;
}) {
    const [query, setQuery] = useState('');
    const { customers, loading, search } = useSearchCustomer(slug);

    const debouncedSearch = debounce((q: string) => {
        search(q);
    }, 300);

    useEffect(() => {
        if (query) {
            debouncedSearch(query);
        } else {
            debouncedSearch.cancel();
        }
    }, [query]);

    return (
        <div className="customer-search">
            <input
                type="text"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Buscar por nombre, teléfono o código..."
            />
            {loading && <div>Cargando...</div>}
            {customers.length > 0 && (
                <ul>
                    {customers.map((customer: any) => (
                        <li
                            key={customer.id}
                            onClick={() => {
                                onSelect(customer);
                                setQuery('');
                            }}
                        >
                            {customer.name || 'Sin nombre'} - {customer.phone} ({customer.short_code})
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
```

---

## 📱 Progreso del Cliente

### Hook para Progreso del Cliente

```typescript
// hooks/useCustomerProgress.ts
export function useCustomerProgress(slug: string) {
    const [progress, setProgress] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        loadProgress();
    }, [slug]);

    const loadProgress = async () => {
        setLoading(true);
        setError(null);
        try {
            const token = localStorage.getItem('customer_token');
            const response = await fetch(
                `${API_CONFIG.baseURL}/${slug}/customer/progress`,
                {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    },
                }
            );

            if (!response.ok) throw new Error('Error al cargar progreso');

            const data = await response.json();
            setProgress(data);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Error desconocido');
        } finally {
            setLoading(false);
        }
    };

    return { progress, loading, error, refetch: loadProgress };
}
```

### Hook para Premios Desbloqueados

```typescript
// hooks/useUnlockedRewards.ts
export function useUnlockedRewards(slug: string, redeemed?: boolean) {
    const [rewards, setRewards] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        loadRewards();
    }, [slug, redeemed]);

    const loadRewards = async () => {
        setLoading(true);
        setError(null);
        try {
            const token = localStorage.getItem('customer_token');
            const query = redeemed !== undefined ? `?redeemed=${redeemed}` : '';
            const response = await fetch(
                `${API_CONFIG.baseURL}/${slug}/customer/rewards/unlocked${query}`,
                {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    },
                }
            );

            if (!response.ok) throw new Error('Error al cargar premios');

            const data = await response.json();
            setRewards(data.data);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Error desconocido');
        } finally {
            setLoading(false);
        }
    };

    return { rewards, loading, error, refetch: loadRewards };
}
```

---

## 🛠️ Utilidades

### API Client Genérico

```typescript
// services/apiClient.ts
class ApiClient {
    private baseURL: string;
    private getToken: () => string | null;

    constructor(baseURL: string, getToken: () => string | null) {
        this.baseURL = baseURL;
        this.getToken = getToken;
    }

    private async request<T>(
        endpoint: string,
        options: RequestInit = {}
    ): Promise<T> {
        const token = this.getToken();
        const headers: HeadersInit = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...options.headers,
        };

        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const response = await fetch(`${this.baseURL}${endpoint}`, {
            ...options,
            headers,
        });

        if (!response.ok) {
            const error = await response.json().catch(() => ({
                message: 'Error desconocido',
            }));
            throw new Error(error.message || `HTTP ${response.status}`);
        }

        return response.json();
    }

    get<T>(endpoint: string) {
        return this.request<T>(endpoint, { method: 'GET' });
    }

    post<T>(endpoint: string, data?: any) {
        return this.request<T>(endpoint, {
            method: 'POST',
            body: data ? JSON.stringify(data) : undefined,
        });
    }

    put<T>(endpoint: string, data?: any) {
        return this.request<T>(endpoint, {
            method: 'PUT',
            body: data ? JSON.stringify(data) : undefined,
        });
    }

    delete<T>(endpoint: string) {
        return this.request<T>(endpoint, { method: 'DELETE' });
    }
}

// Instancias para cada tipo de usuario
export const ownerApi = new ApiClient(
    API_CONFIG.baseURL,
    () => localStorage.getItem('owner_token')
);

export const staffApi = new ApiClient(
    API_CONFIG.baseURL,
    () => localStorage.getItem('staff_token')
);

export const customerApi = new ApiClient(
    API_CONFIG.baseURL,
    () => localStorage.getItem('customer_token')
);
```

### Interceptor para Manejo de Errores

```typescript
// utils/errorHandler.ts
export function handleApiError(error: any): string {
    if (error instanceof Error) {
        // Error de red
        if (error.message.includes('fetch')) {
            return 'Error de conexión. Verifica tu internet.';
        }
        return error.message;
    }

    // Error de respuesta HTTP
    if (error.response) {
        const status = error.response.status;
        const data = error.response.data;

        switch (status) {
            case 401:
                return 'Sesión expirada. Por favor inicia sesión nuevamente.';
            case 403:
                return 'No tienes permisos para realizar esta acción.';
            case 404:
                return 'Recurso no encontrado.';
            case 422:
                return data.message || 'Error de validación.';
            case 429:
                return 'Demasiadas solicitudes. Intenta más tarde.';
            case 500:
                return 'Error del servidor. Intenta más tarde.';
            default:
                return data.message || 'Error desconocido.';
        }
    }

    return 'Error desconocido.';
}
```

---

## 📝 Ejemplo Completo: Dashboard Owner

```typescript
// pages/OwnerDashboard.tsx
import { useState } from 'react';
import { useCampaigns } from '../hooks/useCampaigns';
import { useCampaign } from '../hooks/useCampaign';
import { useCampaignCustomers } from '../hooks/useCampaignCustomers';

export function OwnerDashboard() {
    const slug = localStorage.getItem('current_slug') || '';
    const [selectedCampaignId, setSelectedCampaignId] = useState<string | null>(null);
    
    const { campaigns, loading: campaignsLoading } = useCampaigns(slug, { active: true });
    const { campaign, loading: campaignLoading } = useCampaign(slug, selectedCampaignId || '');
    const { customers, loading: customersLoading } = useCampaignCustomers(
        slug,
        selectedCampaignId || '',
        ''
    );

    if (campaignsLoading) return <div>Cargando campañas...</div>;

    return (
        <div className="dashboard">
            <h1>Mis Campañas</h1>
            
            <div className="campaigns-list">
                {campaigns.map((camp: any) => (
                    <div
                        key={camp.id}
                        onClick={() => setSelectedCampaignId(camp.id)}
                        className={selectedCampaignId === camp.id ? 'selected' : ''}
                    >
                        <h3>{camp.name}</h3>
                        <p>{camp.description}</p>
                        <span>Sellos requeridos: {camp.required_stamps || 'N/A'}</span>
                    </div>
                ))}
            </div>

            {selectedCampaignId && (
                <div className="campaign-details">
                    {campaignLoading ? (
                        <div>Cargando detalles...</div>
                    ) : (
                        <>
                            <h2>{campaign?.name}</h2>
                            {campaign?.stats && (
                                <div className="stats">
                                    <div>Clientes: {campaign.stats.total_customers}</div>
                                    <div>Sellos: {campaign.stats.total_stamps}</div>
                                    <div>Canjes: {campaign.stats.total_redemptions}</div>
                                </div>
                            )}
                            
                            <h3>Clientes Participantes</h3>
                            {customersLoading ? (
                                <div>Cargando clientes...</div>
                            ) : (
                                <ul>
                                    {customers.map((customer: any) => (
                                        <li key={customer.id}>
                                            {customer.customer.name} - {customer.stamps} sellos
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </>
                    )}
                </div>
            )}
        </div>
    );
}
```

---

## 🎯 Tips de Integración

1. **Manejo de Tokens**: Siempre verificar si el token existe antes de hacer peticiones
2. **Refresh Automático**: Implementar refresh de datos cuando el usuario vuelve a la app
3. **Optimistic Updates**: Actualizar UI inmediatamente, luego sincronizar con el servidor
4. **Cache**: Cachear respuestas que no cambian frecuentemente
5. **Debounce**: Usar debounce en búsquedas para evitar demasiadas peticiones
6. **Error Boundaries**: Implementar error boundaries para capturar errores inesperados

