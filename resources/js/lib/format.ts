/**
 * Format a size in bytes using Brazilian Portuguese units (e.g. "1,5 MB").
 */
export function formatBytes(bytes: number): string {
    const units = ['B', 'KB', 'MB', 'GB'];
    let value = bytes;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit++;
    }

    return `${value.toLocaleString('pt-BR', { maximumFractionDigits: unit === 0 ? 0 : 1 })} ${units[unit]}`;
}

/**
 * Format a duration in seconds as "h:mm:ss" or "m:ss".
 */
export function formatDuration(totalSeconds: number): string {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = Math.floor(totalSeconds % 60);
    const pad = (value: number) => String(value).padStart(2, '0');

    return hours > 0
        ? `${hours}:${pad(minutes)}:${pad(seconds)}`
        : `${minutes}:${pad(seconds)}`;
}

/**
 * Format a total length in seconds as rounded hours and minutes (e.g. "1 h 05 min", "12 min").
 */
export function formatTotalDuration(totalSeconds: number): string {
    const totalMinutes = Math.max(1, Math.round(totalSeconds / 60));
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    if (hours === 0) {
        return `${minutes} min`;
    }

    return minutes === 0
        ? `${hours} h`
        : `${hours} h ${String(minutes).padStart(2, '0')} min`;
}
