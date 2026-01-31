import { usePage } from '@inertiajs/react';
import { getCurrency, getCurrencySymbol, formatCurrency as formatCurrencyUtil, formatAmount } from '@/utils/currency';

/**
 * Hook for currency formatting based on organization settings
 * Uses the organization's configured currency
 */
export function useCurrency() {
    const { props } = usePage();
    const organization = props.organization || props.auth?.organization;
    
    // Get the currency code from organization, default to ZMW
    const currencyCode = organization?.currency || 'ZMW';
    const currency = getCurrency(currencyCode);
    
    /**
     * Format a value with the organization's currency
     */
    const formatCurrency = (amount, options = {}) => {
        return formatCurrencyUtil(amount, currencyCode, options);
    };
    
    /**
     * Get just the currency symbol
     */
    const symbol = currency.symbol;
    
    /**
     * Format with just the number (no symbol)
     */
    const formatNumber = (amount, decimals = 2) => {
        return formatAmount(amount, decimals);
    };
    
    /**
     * Format for display in charts/tooltips
     */
    const formatForChart = (value) => {
        return `${symbol} ${formatAmount(value)}`;
    };
    
    return {
        currencyCode,
        currency,
        symbol,
        formatCurrency,
        formatNumber,
        formatForChart,
    };
}

export default useCurrency;
