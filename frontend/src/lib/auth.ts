export interface AuthUser {
  id: number;
  username?: string;
  name: string;
  email?: string;
  role: string;
  department_id?: number | null;
  department_name?: string | null;
  avatar?: string | null;
  is_active?: boolean;
  permissions?: string[];
}

export interface AuthSession {
  token: string;
  token_type?: string;
  expires_in?: number;
  user: AuthUser;
}

const STORAGE_KEY = "erp_auth_session";

function readFromStorage(storage: Storage): AuthSession | null {
  try {
    const raw = storage.getItem(STORAGE_KEY);
    if (!raw) {
      return null;
    }

    const parsed = JSON.parse(raw) as AuthSession;
    if (!parsed?.token || !parsed?.user) {
      return null;
    }

    return parsed;
  } catch {
    return null;
  }
}

function writeToStorage(storage: Storage, session: AuthSession): void {
  storage.setItem(STORAGE_KEY, JSON.stringify(session));
}

function clearStorage(storage: Storage): void {
  storage.removeItem(STORAGE_KEY);
}

export function getStoredSession(): AuthSession | null {
  return readFromStorage(localStorage) ?? readFromStorage(sessionStorage);
}

export function hasPersistentStoredSession(): boolean {
  return readFromStorage(localStorage) !== null;
}

export function getStoredToken(): string | null {
  return getStoredSession()?.token ?? null;
}

export function saveStoredSession(session: AuthSession, persist: boolean): void {
  if (persist) {
    writeToStorage(localStorage, session);
    clearStorage(sessionStorage);
    return;
  }

  writeToStorage(sessionStorage, session);
  clearStorage(localStorage);
}

export function clearStoredSession(): void {
  clearStorage(localStorage);
  clearStorage(sessionStorage);
}
