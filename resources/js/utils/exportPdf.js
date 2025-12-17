import html2pdf from 'html2pdf.js';

/**
 * Export a DOM element to PDF
 * @param {string} elementId - The ID of the element to export
 * @param {string} filename - The name of the PDF file (without extension)
 * @param {object} options - Additional options for html2pdf
 */
export const exportToPdf = (elementId, filename = 'report', options = {}) => {
    const element = document.getElementById(elementId);
    
    if (!element) {
        console.error(`Element with ID "${elementId}" not found`);
        return;
    }

    const defaultOptions = {
        margin: [10, 10, 10, 10],
        filename: `${filename}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
            scale: 2,
            useCORS: true,
            letterRendering: true,
            logging: false,
        },
        jsPDF: { 
            unit: 'mm', 
            format: 'a4', 
            orientation: 'portrait' 
        },
        pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
    };

    const mergedOptions = { ...defaultOptions, ...options };

    // Add loading state
    const exportButton = document.querySelector('[data-export-button]');
    if (exportButton) {
        exportButton.disabled = true;
        exportButton.innerHTML = '<svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Exporting...';
    }

    html2pdf()
        .set(mergedOptions)
        .from(element)
        .save()
        .then(() => {
            // Restore button state
            if (exportButton) {
                exportButton.disabled = false;
                exportButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export';
            }
        })
        .catch((error) => {
            console.error('PDF export failed:', error);
            if (exportButton) {
                exportButton.disabled = false;
                exportButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export';
            }
        });
};

export default exportToPdf;

