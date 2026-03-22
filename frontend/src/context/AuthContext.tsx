import { createContext, useContext, useState } from "react";
import type { ReactNode } from "react";
import api, { getApiErrorMessage } from "../lib/api";
import type { AuthSession, AuthUser } from "../lib/auth";
import { clearStoredSession, getStoredSession, saveStoredSession } from "../lib/auth";

interface AuthContextValue {
  user: AuthUser | null;
  token: string | null;
  isAuthenticated: boolean;
  login: (
    username: string,
    password: string,
    options?: { remember?: boolean },
  ) => Promise<AuthSession>;
  logout: () => void;
}

const AuthContext = createContext<AuthContextValue | null>(null);

function hydrateSession(): AuthSession | null {
  return getStoredSession();
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [session, setSession] = useState<AuthSession | null>(hydrateSession);

  const login: AuthContextValue["login"] = async (
    username,
    password,
    options,
  ) => {
    const response = await api.post("/auth/login", { username, password });
    const payload = response.data?.data ?? response.data;
    const nextSession: AuthSession = {
      token: payload?.token ?? "",
      token_type: payload?.token_type,
      expires_in: payload?.expires_in,
      user: payload?.user,
    };

    if (!nextSession.token || !nextSession.user) {
      throw new Error("Login response is missing token or user data.");
    }

    const persist = options?.remember ?? true;
    saveStoredSession(nextSession, persist);
    setSession(nextSession);
    return nextSession;
  };

  const logout = () => {
    clearStoredSession();
    setSession(null);
  };

  return (
    <AuthContext.Provider
      value={{
        user: session?.user ?? null,
        token: session?.token ?? null,
        isAuthenticated: session !== null,
        login,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used inside AuthProvider");
  return ctx;
}

export function readAuthError(error: unknown): string {
  return getApiErrorMessage(error, "Email hoặc mật khẩu không đúng.");
}
