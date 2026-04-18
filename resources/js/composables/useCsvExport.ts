/**
 * Provides CSV generation and download utilities.
 * Produces BOM-prefixed, Excel-compatible CSV files.
 */
export function useCsvExport() {
    function toCsvRow(values: (string | number | null | undefined)[]): string {
        return values
            .map((v) => {
                const s = (v == null ? '' : String(v)).replace(/"/g, '""');

                return `"${s}"`;
            })
            .join(',');
    }

    function downloadCsv(
        filename: string,
        header: string[],
        rows: (string | number | null | undefined)[][],
    ): void {
        // sep=, forces Excel to use comma as delimiter regardless of locale
        const lines = [toCsvRow(header), ...rows.map(toCsvRow)].join('\r\n');
        const csv = 'sep=,\r\n' + lines;
        const blob = new Blob(['\uFEFF' + csv], {
            type: 'text/csv;charset=utf-8;',
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    }

    return { downloadCsv };
}
