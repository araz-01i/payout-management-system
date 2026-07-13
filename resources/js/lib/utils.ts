import type { ClassValue } from "clsx"
import { clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function toUrl(value: string | { toString: () => string }): string {
  if (typeof value === "string") return value
  if (typeof value.toString === "function") return value.toString()
  return String(value)
}
