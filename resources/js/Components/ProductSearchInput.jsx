import { useState, useEffect, useRef, useCallback } from 'react';
import { Search, X, Check } from 'lucide-react';
import { createPortal } from 'react-dom';
import { cn } from '@/lib/utils';

export default function ProductSearchInput({ 
    value, 
    onChange, 
    onProductSelect, 
    onTextChange, 
    placeholder = "Type to search products...",
    maxResults = 5,
    disabled = false,
}) {
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [isSearching, setIsSearching] = useState(false);
    const [showResults, setShowResults] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [highlightedIndex, setHighlightedIndex] = useState(0);
    const [dropdownPosition, setDropdownPosition] = useState({ top: 0, left: 0, width: 0 });
    
    const searchTimeoutRef = useRef(null);
    const inputRef = useRef(null);
    const containerRef = useRef(null);
    const dropdownRef = useRef(null);

    // Update dropdown position
    const updateDropdownPosition = useCallback(() => {
        if (containerRef.current) {
            const rect = containerRef.current.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const spaceBelow = viewportHeight - rect.bottom;
            const dropdownHeight = Math.min(searchResults.length * 60 + 16, maxResults * 60 + 16);
            
            // Position above if not enough space below
            const shouldPositionAbove = spaceBelow < dropdownHeight && rect.top > spaceBelow;
            
            setDropdownPosition({
                top: shouldPositionAbove 
                    ? rect.top + window.scrollY - dropdownHeight - 4
                    : rect.bottom + window.scrollY + 4,
                left: rect.left + window.scrollX,
                width: rect.width,
                positionAbove: shouldPositionAbove,
            });
        }
    }, [searchResults.length, maxResults]);

    // Handle click outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (
                containerRef.current && 
                !containerRef.current.contains(event.target) &&
                dropdownRef.current &&
                !dropdownRef.current.contains(event.target)
            ) {
                setShowResults(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Update position when showing results
    useEffect(() => {
        if (showResults) {
            updateDropdownPosition();
            window.addEventListener('scroll', updateDropdownPosition, true);
            window.addEventListener('resize', updateDropdownPosition);
            return () => {
                window.removeEventListener('scroll', updateDropdownPosition, true);
                window.removeEventListener('resize', updateDropdownPosition);
            };
        }
    }, [showResults, updateDropdownPosition]);

    const searchProducts = async (query) => {
        if (query.length < 1) {
            setSearchResults([]);
            setShowResults(false);
            return;
        }

        setIsSearching(true);
        try {
            const axios = window.axios || (await import('axios')).default;
            
            const response = await axios.get('/quotations/search-products', {
                params: { q: query },
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            
            if (response.data && Array.isArray(response.data)) {
                setSearchResults(response.data);
                setShowResults(true);
                setHighlightedIndex(0);
            } else {
                console.warn('Unexpected response format:', response.data);
                setSearchResults([]);
            }
        } catch (error) {
            console.error('Error searching products:', error);
            setSearchResults([]);
        } finally {
            setIsSearching(false);
        }
    };

    const handleInputChange = (e) => {
        const query = e.target.value;
        setSearchQuery(query);
        setHighlightedIndex(0);

        // Notify parent of text change for manual entry
        if (onTextChange) {
            onTextChange(query);
        }

        // Clear timeout if exists
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }

        // If query is cleared, reset
        if (!query) {
            setSelectedProduct(null);
            setSearchResults([]);
            setShowResults(false);
            if (onChange) onChange('');
            if (onProductSelect) onProductSelect(null);
            return;
        }

        // Search for products when user types
        if (query.length >= 1) {
            searchTimeoutRef.current = setTimeout(() => {
                searchProducts(query);
            }, 200);
        }
    };

    const handleProductSelect = (product) => {
        setSelectedProduct(product);
        setSearchQuery(product.name);
        setShowResults(false);
        if (onChange) onChange(product.id);
        if (onProductSelect) onProductSelect(product);
    };

    const handleClear = () => {
        setSearchQuery('');
        setSelectedProduct(null);
        setSearchResults([]);
        setShowResults(false);
        if (onChange) onChange('');
        if (onProductSelect) onProductSelect(null);
        if (onTextChange) onTextChange('');
        if (inputRef.current) inputRef.current.focus();
    };

    // Handle keyboard navigation
    const handleKeyDown = (e) => {
        if (!showResults) {
            if (e.key === 'ArrowDown' && searchResults.length > 0) {
                setShowResults(true);
            }
            return;
        }

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                setHighlightedIndex(prev => 
                    prev < Math.min(searchResults.length, maxResults) - 1 ? prev + 1 : prev
                );
                break;
            case 'ArrowUp':
                e.preventDefault();
                setHighlightedIndex(prev => prev > 0 ? prev - 1 : 0);
                break;
            case 'Enter':
                e.preventDefault();
                if (searchResults[highlightedIndex]) {
                    handleProductSelect(searchResults[highlightedIndex]);
                }
                break;
            case 'Escape':
                setShowResults(false);
                break;
            case 'Tab':
                setShowResults(false);
                break;
        }
    };

    const renderDropdown = () => {
        if (!showResults) return null;

        const dropdown = (
            <div
                ref={dropdownRef}
                className="fixed bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden"
                style={{
                    top: dropdownPosition.top,
                    left: dropdownPosition.left,
                    width: dropdownPosition.width,
                    zIndex: 99999,
                    maxHeight: maxResults * 60 + 16,
                }}
            >
                {isSearching ? (
                    <div className="p-4 text-center text-sm text-gray-500">
                        <div className="animate-spin h-5 w-5 border-2 border-teal-500 border-t-transparent rounded-full mx-auto mb-2"></div>
                        Searching...
                    </div>
                ) : searchResults.length === 0 ? (
                    <div className="p-4 text-center text-sm text-gray-500">
                        No products found
                    </div>
                ) : (
                    <div 
                        className="overflow-y-auto"
                        style={{ maxHeight: maxResults * 60 }}
                    >
                        {searchResults.slice(0, maxResults).map((product, index) => {
                            const isHighlighted = index === highlightedIndex;
                            const isSelected = String(product.id) === String(value);
                            
                            return (
                                <button
                                    key={product.id}
                                    type="button"
                                    onClick={() => handleProductSelect(product)}
                                    onMouseEnter={() => setHighlightedIndex(index)}
                                    className={cn(
                                        "w-full text-left px-4 py-3 transition-colors",
                                        isHighlighted && "bg-teal-50",
                                        isSelected && !isHighlighted && "bg-gray-50",
                                    )}
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="flex-1 min-w-0">
                                            <div className="font-medium text-sm text-gray-900 truncate">
                                                {product.name}
                                            </div>
                                            {product.sku && (
                                                <div className="text-xs text-gray-500">SKU: {product.sku}</div>
                                            )}
                                            {product.selling_price && (
                                                <div className="text-xs text-teal-600 font-medium">
                                                    K{parseFloat(product.selling_price).toFixed(2)}
                                                </div>
                                            )}
                                        </div>
                                        {isSelected && (
                                            <Check className="h-4 w-4 text-teal-600 flex-shrink-0 ml-2" />
                                        )}
                                    </div>
                                </button>
                            );
                        })}
                        {searchResults.length > maxResults && (
                            <div className="px-4 py-2 text-xs text-gray-400 text-center border-t border-gray-100">
                                {searchResults.length - maxResults} more results...
                            </div>
                        )}
                    </div>
                )}
            </div>
        );

        return createPortal(dropdown, document.body);
    };

    return (
        <div ref={containerRef} className="relative w-full">
            <div
                className={cn(
                    "relative flex items-center w-full rounded-lg border bg-white transition-all duration-200",
                    "border-gray-300 focus-within:ring-2 focus-within:ring-teal-500/20 focus-within:border-teal-500",
                    disabled && "bg-gray-100 cursor-not-allowed opacity-60",
                    showResults && "ring-2 ring-teal-500/20 border-teal-500",
                )}
            >
                <Search className="absolute left-3 h-4 w-4 text-gray-400 pointer-events-none" />
                <input
                    ref={inputRef}
                    type="text"
                    value={searchQuery}
                    onChange={handleInputChange}
                    onFocus={() => {
                        if (searchResults.length > 0) {
                            setShowResults(true);
                        }
                    }}
                    onKeyDown={handleKeyDown}
                    placeholder={placeholder}
                    disabled={disabled}
                    className={cn(
                        "w-full pl-10 pr-10 py-2 text-sm bg-transparent outline-none font-medium",
                        disabled && "cursor-not-allowed",
                    )}
                    required
                />
                {searchQuery && !disabled && (
                    <button
                        type="button"
                        onClick={handleClear}
                        className="absolute right-2 p-1 hover:bg-gray-100 rounded-full transition-colors"
                    >
                        <X className="h-4 w-4 text-gray-400" />
                    </button>
                )}
            </div>
            {renderDropdown()}
        </div>
    );
}
