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
