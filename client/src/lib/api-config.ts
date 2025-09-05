// API Configuration for PHP Backend
export const API_BASE_URL = '/api/php';

export const apiRequest = async (
  method: string,
  endpoint: string,
  data?: unknown
): Promise<Response> => {
  const url = `${API_BASE_URL}${endpoint}`;
  
  const res = await fetch(url, {
    method,
    headers: data ? { "Content-Type": "application/json" } : {},
    body: data ? JSON.stringify(data) : undefined,
    credentials: "include",
  });

  if (!res.ok) {
    const text = (await res.text()) || res.statusText;
    throw new Error(`${res.status}: ${text}`);
  }

  return res;
};

export const apiGet = async (endpoint: string) => {
  const res = await apiRequest('GET', endpoint);
  return res.json();
};

export const apiPost = async (endpoint: string, data: unknown) => {
  const res = await apiRequest('POST', endpoint, data);
  return res.json();
};

export const apiPatch = async (endpoint: string, data: unknown) => {
  const res = await apiRequest('PATCH', endpoint, data);
  return res.json();
};

export const apiDelete = async (endpoint: string) => {
  const res = await apiRequest('DELETE', endpoint);
  return res.status === 204 ? null : res.json();
};