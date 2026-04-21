import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Merge Tailwind classes with clsx for conditional composition,
 * then dedupe conflicting utilities via twMerge.
 *
 * Usage: cn('p-4', condition && 'bg-red-500', props.class)
 */
export function cn(...inputs) {
    return twMerge(clsx(inputs));
}
