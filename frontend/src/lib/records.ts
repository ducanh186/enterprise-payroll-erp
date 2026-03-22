type AnyRecord = Record<string, unknown>;

export function isRecord(value: unknown): value is AnyRecord {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

export function toArray<T>(value: unknown): T[] {
  if (Array.isArray(value)) return value as T[];
  if (!isRecord(value)) return [];

  for (const key of ["items", "data", "results", "rows", "list"] as const) {
    const nested = value[key];
    if (Array.isArray(nested)) return nested as T[];
  }

  return [];
}

function readPath(value: unknown, path: string): unknown {
  const segments = path.split(".").filter(Boolean);
  let current: unknown = value;

  for (const segment of segments) {
    if (!isRecord(current)) return undefined;
    current = current[segment];
  }

  return current;
}

export function pickValue(
  value: unknown,
  paths: string[],
  fallback: unknown = undefined,
): unknown {
  for (const path of paths) {
    const candidate = readPath(value, path);
    if (candidate !== undefined && candidate !== null && candidate !== "") {
      return candidate;
    }
  }

  return fallback;
}

export function textValue(
  value: unknown,
  paths: string[],
  fallback = "—",
): string {
  const candidate = pickValue(value, paths);
  if (candidate === undefined || candidate === null || candidate === "") {
    return fallback;
  }

  return String(candidate);
}

export function numberValue(
  value: unknown,
  paths: string[],
  fallback = 0,
): number {
  const candidate = pickValue(value, paths);
  const num = Number(candidate);
  return Number.isFinite(num) ? num : fallback;
}

export function boolValue(
  value: unknown,
  paths: string[],
  fallback = false,
): boolean {
  const candidate = pickValue(value, paths);

  if (typeof candidate === "boolean") return candidate;
  if (typeof candidate === "number") return candidate !== 0;
  if (typeof candidate === "string") {
    const normalized = candidate.trim().toLowerCase();
    if (["1", "true", "yes", "y", "active"].includes(normalized)) return true;
    if (["0", "false", "no", "n", "inactive"].includes(normalized)) return false;
  }

  return fallback;
}

