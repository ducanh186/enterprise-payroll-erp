import axios from "axios";
import { clearStoredSession, getStoredToken } from "./auth";

export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? "http://localhost:8001/api";

const api = axios.create({
  baseURL: API_BASE_URL,
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

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (
      axios.isAxiosError(error) &&
      error.response?.status === 401 &&
      !error.config?.url?.includes("/auth/login")
    ) {
      clearStoredSession();
      window.dispatchEvent(new Event("auth:session-cleared"));
    }

    return Promise.reject(error);
  },
);

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

export async function apiUpload<T>(
  url: string,
  file: File,
  fields?: Record<string, string | number | boolean | undefined>,
) {
  const formData = new FormData();
  formData.append("file", file);

  Object.entries(fields ?? {}).forEach(([key, value]) => {
    if (value !== undefined && value !== "") {
      formData.append(key, String(value));
    }
  });

  const response = await api.post(url, formData, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
  });

  return normalizeResult<T>(response.data);
}

export async function apiDownloadFile(url: string, fallbackName: string) {
  const response = await api.get(resolveApiPath(url), { responseType: "blob" });
  const disposition = response.headers["content-disposition"];
  const fileName = filenameFromDisposition(disposition) ?? fallbackName;
  const blobUrl = window.URL.createObjectURL(response.data);
  const link = document.createElement("a");

  link.href = blobUrl;
  link.download = fileName;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(blobUrl);
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

export function apiFileUrl(path: string) {
  if (!path || path.startsWith("http://") || path.startsWith("https://")) {
    return path;
  }

  if (path.startsWith("/api/")) {
    return API_BASE_URL.replace(/\/api\/?$/, "") + path;
  }

  return path;
}

function resolveApiPath(url: string) {
  if (url.startsWith("/api/")) {
    return url.slice(4);
  }

  if (url.startsWith("http://") || url.startsWith("https://")) {
    const parsed = new URL(url);
    if (parsed.pathname.startsWith("/api/")) {
      return `${parsed.pathname.slice(4)}${parsed.search}`;
    }
  }

  return url;
}

function filenameFromDisposition(disposition?: string) {
  if (!disposition) {
    return null;
  }

  const match = disposition.match(/filename="?([^";]+)"?/i);

  return match?.[1] ?? null;
}
