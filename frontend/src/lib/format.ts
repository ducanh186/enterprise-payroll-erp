const currencyFormatter = new Intl.NumberFormat("vi-VN", {
  style: "currency",
  currency: "VND",
  maximumFractionDigits: 0,
});

const numberFormatter = new Intl.NumberFormat("vi-VN");

export function formatCurrency(value: number | string | null | undefined): string {
  const num = Number(value ?? 0);
  return currencyFormatter.format(Number.isFinite(num) ? num : 0);
}

export function formatNumber(value: number | string | null | undefined): string {
  const num = Number(value ?? 0);
  return numberFormatter.format(Number.isFinite(num) ? num : 0);
}

export function formatPercent(value: number | string | null | undefined): string {
  const num = Number(value ?? 0);
  return `${Number.isFinite(num) ? num.toFixed(1).replace(/\.0$/, "") : "0"}%`;
}

export function formatDate(value: string | Date | null | undefined): string {
  if (!value) return "N/A";
  const date = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  return new Intl.DateTimeFormat("vi-VN").format(date);
}

export function formatDateTime(value: string | Date | null | undefined): string {
  if (!value) return "N/A";
  const date = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  return new Intl.DateTimeFormat("vi-VN", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date);
}

export function formatMonthLabel(month?: number | string, year?: number | string): string {
  if (!month || !year) return "N/A";
  return `Th${String(month).padStart(2, "0")}/${year}`;
}

export function formatCompactDate(value: string | Date | null | undefined): string {
  if (!value) return "N/A";
  const date = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  return date.toLocaleDateString("vi-VN", {
    month: "short",
    day: "2-digit",
  });
}

