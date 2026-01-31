/**
 * Currency utilities for Addy
 * Provides currency data and formatting functions
 */

// Comprehensive list of supported currencies
export const currencies = [
    // Africa
    { code: 'ZMW', symbol: 'K', name: 'Zambian Kwacha', locale: 'en-ZM' },
    { code: 'ZAR', symbol: 'R', name: 'South African Rand', locale: 'en-ZA' },
    { code: 'BWP', symbol: 'P', name: 'Botswana Pula', locale: 'en-BW' },
    { code: 'KES', symbol: 'KSh', name: 'Kenyan Shilling', locale: 'en-KE' },
    { code: 'TZS', symbol: 'TSh', name: 'Tanzanian Shilling', locale: 'sw-TZ' },
    { code: 'UGX', symbol: 'USh', name: 'Ugandan Shilling', locale: 'en-UG' },
    { code: 'NGN', symbol: '₦', name: 'Nigerian Naira', locale: 'en-NG' },
    { code: 'GHS', symbol: 'GH₵', name: 'Ghanaian Cedi', locale: 'en-GH' },
    { code: 'MWK', symbol: 'MK', name: 'Malawian Kwacha', locale: 'en-MW' },
    { code: 'ZWL', symbol: 'Z$', name: 'Zimbabwean Dollar', locale: 'en-ZW' },
    { code: 'MZN', symbol: 'MT', name: 'Mozambican Metical', locale: 'pt-MZ' },
    { code: 'NAD', symbol: 'N$', name: 'Namibian Dollar', locale: 'en-NA' },
    { code: 'EGP', symbol: 'E£', name: 'Egyptian Pound', locale: 'ar-EG' },
    { code: 'MAD', symbol: 'MAD', name: 'Moroccan Dirham', locale: 'ar-MA' },
    
    // Major World Currencies
    { code: 'USD', symbol: '$', name: 'US Dollar', locale: 'en-US' },
    { code: 'EUR', symbol: '€', name: 'Euro', locale: 'de-DE' },
    { code: 'GBP', symbol: '£', name: 'British Pound', locale: 'en-GB' },
    { code: 'JPY', symbol: '¥', name: 'Japanese Yen', locale: 'ja-JP', decimals: 0 },
    { code: 'CNY', symbol: '¥', name: 'Chinese Yuan', locale: 'zh-CN' },
    { code: 'AUD', symbol: 'A$', name: 'Australian Dollar', locale: 'en-AU' },
    { code: 'CAD', symbol: 'C$', name: 'Canadian Dollar', locale: 'en-CA' },
    { code: 'CHF', symbol: 'CHF', name: 'Swiss Franc', locale: 'de-CH' },
    { code: 'INR', symbol: '₹', name: 'Indian Rupee', locale: 'en-IN' },
    { code: 'BRL', symbol: 'R$', name: 'Brazilian Real', locale: 'pt-BR' },
    { code: 'MXN', symbol: 'MX$', name: 'Mexican Peso', locale: 'es-MX' },
    { code: 'AED', symbol: 'AED', name: 'UAE Dirham', locale: 'ar-AE' },
    { code: 'SAR', symbol: 'SAR', name: 'Saudi Riyal', locale: 'ar-SA' },
    { code: 'SGD', symbol: 'S$', name: 'Singapore Dollar', locale: 'en-SG' },
    { code: 'HKD', symbol: 'HK$', name: 'Hong Kong Dollar', locale: 'en-HK' },
    { code: 'NZD', symbol: 'NZ$', name: 'New Zealand Dollar', locale: 'en-NZ' },
    { code: 'SEK', symbol: 'kr', name: 'Swedish Krona', locale: 'sv-SE' },
    { code: 'NOK', symbol: 'kr', name: 'Norwegian Krone', locale: 'nb-NO' },
    { code: 'DKK', symbol: 'kr', name: 'Danish Krone', locale: 'da-DK' },
    { code: 'PLN', symbol: 'zł', name: 'Polish Zloty', locale: 'pl-PL' },
    { code: 'RUB', symbol: '₽', name: 'Russian Ruble', locale: 'ru-RU' },
    { code: 'TRY', symbol: '₺', name: 'Turkish Lira', locale: 'tr-TR' },
    { code: 'THB', symbol: '฿', name: 'Thai Baht', locale: 'th-TH' },
    { code: 'PHP', symbol: '₱', name: 'Philippine Peso', locale: 'en-PH' },
    { code: 'IDR', symbol: 'Rp', name: 'Indonesian Rupiah', locale: 'id-ID', decimals: 0 },
    { code: 'MYR', symbol: 'RM', name: 'Malaysian Ringgit', locale: 'ms-MY' },
    { code: 'VND', symbol: '₫', name: 'Vietnamese Dong', locale: 'vi-VN', decimals: 0 },
    { code: 'PKR', symbol: '₨', name: 'Pakistani Rupee', locale: 'ur-PK' },
    { code: 'BDT', symbol: '৳', name: 'Bangladeshi Taka', locale: 'bn-BD' },
    { code: 'COP', symbol: 'COL$', name: 'Colombian Peso', locale: 'es-CO' },
    { code: 'ARS', symbol: 'AR$', name: 'Argentine Peso', locale: 'es-AR' },
    { code: 'CLP', symbol: 'CLP$', name: 'Chilean Peso', locale: 'es-CL', decimals: 0 },
    { code: 'PEN', symbol: 'S/', name: 'Peruvian Sol', locale: 'es-PE' },
    { code: 'ILS', symbol: '₪', name: 'Israeli Shekel', locale: 'he-IL' },
];

/**
 * Get currency by code
 */
export function getCurrency(code) {
    return currencies.find(c => c.code === code) || currencies[0]; // Default to ZMW
}

/**
 * Get currency symbol by code
 */
export function getCurrencySymbol(code) {
    const currency = getCurrency(code);
    return currency.symbol;
}

/**
 * Format amount with currency
 * @param {number} amount - The amount to format
 * @param {string} currencyCode - The currency code (e.g., 'ZMW', 'USD')
 * @param {object} options - Additional options
 * @returns {string} Formatted currency string
 */
export function formatCurrency(amount, currencyCode = 'ZMW', options = {}) {
    const currency = getCurrency(currencyCode);
    const num = parseFloat(amount) || 0;
    const decimals = options.decimals ?? currency.decimals ?? 2;
    
    const formatted = num.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
    
    // Symbol position (before or after)
    const symbolAfter = options.symbolAfter ?? false;
    
    if (symbolAfter) {
        return `${formatted} ${currency.symbol}`;
    }
    return `${currency.symbol} ${formatted}`;
}

/**
 * Format amount without currency symbol
 * @param {number} amount - The amount to format
 * @param {number} decimals - Number of decimal places
 * @returns {string} Formatted number string
 */
export function formatAmount(amount, decimals = 2) {
    const num = parseFloat(amount) || 0;
    return num.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

/**
 * Get currencies grouped by region
 */
export function getCurrenciesByRegion() {
    return {
        'Africa': currencies.filter(c => 
            ['ZMW', 'ZAR', 'BWP', 'KES', 'TZS', 'UGX', 'NGN', 'GHS', 'MWK', 'ZWL', 'MZN', 'NAD', 'EGP', 'MAD'].includes(c.code)
        ),
        'Americas': currencies.filter(c => 
            ['USD', 'CAD', 'BRL', 'MXN', 'COP', 'ARS', 'CLP', 'PEN'].includes(c.code)
        ),
        'Europe': currencies.filter(c => 
            ['EUR', 'GBP', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN', 'RUB', 'TRY'].includes(c.code)
        ),
        'Asia & Pacific': currencies.filter(c => 
            ['JPY', 'CNY', 'AUD', 'INR', 'AED', 'SAR', 'SGD', 'HKD', 'NZD', 'THB', 'PHP', 'IDR', 'MYR', 'VND', 'PKR', 'BDT', 'ILS'].includes(c.code)
        ),
    };
}

export default {
    currencies,
    getCurrency,
    getCurrencySymbol,
    formatCurrency,
    formatAmount,
    getCurrenciesByRegion,
};
