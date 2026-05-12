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

export function isoToDisplayDate(value: string | Date | null | undefined): string {
  if (!value) return "";
  if (value instanceof Date) {
    const day = String(value.getDate()).padStart(2, "0");
    const month = String(value.getMonth() + 1).padStart(2, "0");
    return `${day}/${month}/${value.getFullYear()}`;
  }

  const text = String(value);
  const isoMatch = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (isoMatch) {
    return `${isoMatch[3]}/${isoMatch[2]}/${isoMatch[1]}`;
  }

  return text;
}

export function displayDateToIso(value: string): string {
  const text = value.trim();
  if (!text) return "";
  if (/^\d{4}-\d{2}-\d{2}$/.test(text)) return text;

  const match = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
  if (!match) return text;

  const day = match[1].padStart(2, "0");
  const month = match[2].padStart(2, "0");
  return `${match[3]}-${month}-${day}`;
}
