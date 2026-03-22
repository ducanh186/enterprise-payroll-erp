import axios from "axios";
import { getStoredToken } from "./auth";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? "http://localhost:8000/api",
  headers: {
    "Content-Type": "application/json",
  },
});

// Attach token from localStorage on every request
api.interceptors.request.use((config) => {
  const token = getStoredToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;

export interface ApiMeta {
  current_page?: number;
  per_page?: number;
  total?: number;
  last_page?: number;
}

export interface ApiEnvelope<T> {
  success?: boolean;
  message?: string;
  data?: T;
  meta?: ApiMeta;
  errors?: unknown;
}

export interface ApiResult<T> {
  success: boolean;
  message?: string;
  data: T;
  meta?: ApiMeta;
  errors?: unknown;
}

function normalizeResult<T>(payload: unknown): ApiResult<T> {
  if (payload && typeof payload === "object" && "data" in payload) {
    const envelope = payload as ApiEnvelope<T>;
    return {
      success: envelope.success ?? true,
      message: envelope.message,
      data: (envelope.data ?? payload) as T,
      meta: envelope.meta,
      errors: envelope.errors,
    };
  }

  return {
    success: true,
    data: payload as T,
  };
}

export async function apiGet<T>(url: string, params?: Record<string, unknown>) {
  const response = await api.get(url, { params });
  return normalizeResult<T>(response.data);
}

export async function apiPost<T>(
  url: string,
  body?: unknown,
  params?: Record<string, unknown>,
) {
  const response = await api.post(url, body, { params });
  return normalizeResult<T>(response.data);
}

export async function apiPut<T>(
  url: string,
  body?: unknown,
  params?: Record<string, unknown>,
) {
  const response = await api.put(url, body, { params });
  return normalizeResult<T>(response.data);
}

export async function apiDelete<T>(
  url: string,
  params?: Record<string, unknown>,
) {
  const response = await api.delete(url, { params });
  return normalizeResult<T>(response.data);
}

export function getApiErrorMessage(error: unknown, fallback = "Đã xảy ra lỗi") {
  if (axios.isAxiosError(error)) {
    const response = error.response?.data as ApiEnvelope<unknown> | undefined;
    if (typeof response?.message === "string" && response.message) {
      return response.message;
    }

    const data = error.response?.data as { message?: string } | undefined;
    if (typeof data?.message === "string" && data.message) {
      return data.message;
    }
  }

  if (error instanceof Error && error.message) {
    return error.message;
  }

  return fallback;
}
