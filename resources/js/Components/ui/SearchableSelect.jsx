import { useState, useEffect, useRef, useCallback, useMemo } from 'react';
import { Search, X, ChevronDown, Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import { createPortal } from 'react-dom';

/**
 * SearchableSelect - A type-to-search dropdown component
 * 
 * @param {string} value - Selected value (id)
 * @param {function} onChange - Callback when selection changes (receives id)
 * @param {function} onSelect - Callback when item is selected (receives full object)
 * @param {array} options - Array of options: [{ id, name, ...otherFields }]
 * @param {string} placeholder - Placeholder text
 * @param {string} label - Label text
 * @param {boolean} required - Whether the field is required
 * @param {string} error - Error message to display
 * @param {string} searchEndpoint - API endpoint for server-side search (optional)
 * @param {function} renderOption - Custom render function for options
 * @param {string} displayField - Field to display in the input (default: 'name')
 * @param {string} valueField - Field to use as value (default: 'id')
 * @param {boolean} allowCreate - Whether to allow creating new items
 * @param {function} onCreate - Callback when creating new item
 * @param {boolean} disabled - Whether the input is disabled
 * @param {string} className - Additional CSS classes
 */
export default function SearchableSelect({
    value,
    onChange,
    onSelect,
    options: propOptions = [],
    placeholder = "Type to search...",
    label,
    required = false,
    error,
    searchEndpoint,
    renderOption,
    displayField = 'name',
    valueField = 'id',
    allowCreate = false,
    onCreate,
    disabled = false,
    className = '',
    maxResults = 5,
}) {
    const [searchQuery, setSearchQuery] = useState('');
    const [isOpen, setIsOpen] = useState(false);
    const [isSearching, setIsSearching] = useState(false);
    const [serverOptions, setServerOptions] = useState([]);
    const [highlightedIndex, setHighlightedIndex] = useState(0);
    const [dropdownPosition, setDropdownPosition] = useState({ top: 0, left: 0, width: 0 });
    
    const inputRef = useRef(null);
    const containerRef = useRef(null);
    const dropdownRef = useRef(null);
    const searchTimeoutRef = useRef(null);

    // Combine prop options with server options
    const allOptions = useMemo(() => {
        if (searchEndpoint && serverOptions.length > 0) {
            return serverOptions;
        }
        return propOptions;
    }, [propOptions, serverOptions, searchEndpoint]);

    // Filter options based on search query
    const filteredOptions = useMemo(() => {
        if (!searchQuery.trim()) {
            return allOptions.slice(0, maxResults * 2); // Show more when not searching
        }
        
        const query = searchQuery.toLowerCase();
        return allOptions.filter(option => {
            const name = option[displayField]?.toLowerCase() || '';
            const email = option.email?.toLowerCase() || '';
            const phone = option.phone?.toLowerCase() || '';
            const sku = option.sku?.toLowerCase() || '';
            return name.includes(query) || email.includes(query) || phone.includes(query) || sku.includes(query);
        }).slice(0, maxResults * 2);
    }, [allOptions, searchQuery, displayField, maxResults]);

    // Get selected option display text
    const selectedOption = useMemo(() => {
        if (!value) return null;
        return allOptions.find(opt => String(opt[valueField]) === String(value));
    }, [value, allOptions, valueField]);

    // Update dropdown position
    const updateDropdownPosition = useCallback(() => {
        if (containerRef.current) {
            const rect = containerRef.current.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const spaceBelow = viewportHeight - rect.bottom;
            const spaceAbove = rect.top;
            const dropdownHeight = Math.min(filteredOptions.length * 56 + 16, maxResults * 56 + 16);
            
            // Position above if not enough space below
            const shouldPositionAbove = spaceBelow < dropdownHeight && spaceAbove > spaceBelow;
            
            setDropdownPosition({
                top: shouldPositionAbove 
                    ? rect.top + window.scrollY - dropdownHeight - 4
                    : rect.bottom + window.scrollY + 4,
                left: rect.left + window.scrollX,
                width: rect.width,
                positionAbove: shouldPositionAbove,
            });
        }
    }, [filteredOptions.length, maxResults]);

    // Handle click outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (
                containerRef.current && 
                !containerRef.current.contains(event.target) &&
                dropdownRef.current &&
                !dropdownRef.current.contains(event.target)
            ) {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Update position when opening or options change
    useEffect(() => {
        if (isOpen) {
            updateDropdownPosition();
            window.addEventListener('scroll', updateDropdownPosition, true);
            window.addEventListener('resize', updateDropdownPosition);
            return () => {
                window.removeEventListener('scroll', updateDropdownPosition, true);
                window.removeEventListener('resize', updateDropdownPosition);
            };
        }
    }, [isOpen, updateDropdownPosition]);

    // Server-side search
    const searchServer = useCallback(async (query) => {
        if (!searchEndpoint) return;
        
        setIsSearching(true);
        try {
            const axios = window.axios || (await import('axios')).default;
            const response = await axios.get(searchEndpoint, {
                params: { q: query },
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            
            if (response.data && Array.isArray(response.data)) {
                setServerOptions(response.data);
            }
        } catch (error) {
            console.error('Error searching:', error);
            setServerOptions([]);
        } finally {
            setIsSearching(false);
        }
    }, [searchEndpoint]);

    // Handle input change
    const handleInputChange = (e) => {
        const query = e.target.value;
        setSearchQuery(query);
        setHighlightedIndex(0);
        
        if (!isOpen) {
            setIsOpen(true);
        }

        // Clear timeout if exists
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }

        // Server-side search with debounce
        if (searchEndpoint && query.length >= 1) {
            searchTimeoutRef.current = setTimeout(() => {
                searchServer(query);
            }, 200);
        }
    };

    // Handle option select
    const handleSelect = (option) => {
        setSearchQuery('');
        setIsOpen(false);
        if (onChange) onChange(option[valueField]);
        if (onSelect) onSelect(option);
    };

    // Handle clear
    const handleClear = (e) => {
        e.stopPropagation();
        setSearchQuery('');
        if (onChange) onChange('');
        if (onSelect) onSelect(null);
        inputRef.current?.focus();
    };

    // Handle keyboard navigation
    const handleKeyDown = (e) => {
        if (!isOpen) {
            if (e.key === 'ArrowDown' || e.key === 'Enter') {
                setIsOpen(true);
            }
            return;
        }

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                setHighlightedIndex(prev => 
                    prev < filteredOptions.length - 1 ? prev + 1 : prev
                );
                break;
            case 'ArrowUp':
                e.preventDefault();
                setHighlightedIndex(prev => prev > 0 ? prev - 1 : 0);
                break;
            case 'Enter':
                e.preventDefault();
                if (filteredOptions[highlightedIndex]) {
                    handleSelect(filteredOptions[highlightedIndex]);
                }
                break;
            case 'Escape':
                setIsOpen(false);
                break;
            case 'Tab':
                setIsOpen(false);
                break;
        }
    };

    // Default option renderer
    const defaultRenderOption = (option, isHighlighted, isSelected) => (
        <div className="flex items-center justify-between">
            <div className="flex-1 min-w-0">
                <div className="font-medium text-sm text-gray-900 truncate">
                    {option[displayField]}
                </div>
                {option.email && (
                    <div className="text-xs text-gray-500 truncate">{option.email}</div>
                )}
                {option.phone && !option.email && (
                    <div className="text-xs text-gray-500 truncate">{option.phone}</div>
                )}
                {option.sku && (
                    <div className="text-xs text-gray-500">SKU: {option.sku}</div>
                )}
                {option.selling_price !== undefined && (
                    <div className="text-xs text-teal-600 font-medium">
                        K{parseFloat(option.selling_price).toFixed(2)}
                    </div>
                )}
            </div>
            {isSelected && (
                <Check className="h-4 w-4 text-teal-600 flex-shrink-0 ml-2" />
            )}
        </div>
    );

    const renderDropdown = () => {
        if (!isOpen) return null;

        const dropdown = (
            <div
                ref={dropdownRef}
                className="fixed bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden"
                style={{
                    top: dropdownPosition.top,
                    left: dropdownPosition.left,
                    width: dropdownPosition.width,
                    zIndex: 99999,
                    maxHeight: maxResults * 56 + 16,
                }}
            >
                {isSearching ? (
                    <div className="p-4 text-center text-sm text-gray-500">
                        <div className="animate-spin h-5 w-5 border-2 border-teal-500 border-t-transparent rounded-full mx-auto mb-2"></div>
                        Searching...
                    </div>
                ) : filteredOptions.length === 0 ? (
                    <div className="p-4 text-center text-sm text-gray-500">
                        {searchQuery ? 'No results found' : 'Start typing to search...'}
                        {allowCreate && searchQuery && onCreate && (
                            <button
                                type="button"
                                onClick={() => {
                                    onCreate(searchQuery);
                                    setIsOpen(false);
                                }}
                                className="mt-2 w-full px-3 py-2 text-sm font-medium text-teal-600 hover:bg-teal-50 rounded-lg transition-colors"
                            >
                                + Create "{searchQuery}"
                            </button>
                        )}
                    </div>
                ) : (
                    <div 
                        className="overflow-y-auto"
                        style={{ maxHeight: maxResults * 56 }}
                    >
                        {filteredOptions.slice(0, maxResults).map((option, index) => {
                            const isHighlighted = index === highlightedIndex;
                            const isSelected = String(option[valueField]) === String(value);
                            
                            return (
                                <button
                                    key={option[valueField]}
                                    type="button"
                                    onClick={() => handleSelect(option)}
                                    onMouseEnter={() => setHighlightedIndex(index)}
                                    className={cn(
                                        "w-full text-left px-4 py-3 transition-colors",
                                        isHighlighted && "bg-teal-50",
                                        isSelected && !isHighlighted && "bg-gray-50",
                                    )}
                                >
                                    {renderOption 
                                        ? renderOption(option, isHighlighted, isSelected)
                                        : defaultRenderOption(option, isHighlighted, isSelected)
                                    }
                                </button>
                            );
                        })}
                        {filteredOptions.length > maxResults && (
                            <div className="px-4 py-2 text-xs text-gray-400 text-center border-t border-gray-100">
                                {filteredOptions.length - maxResults} more results...
                            </div>
                        )}
                    </div>
                )}
            </div>
        );

        // Use portal to render dropdown at document body level
        return createPortal(dropdown, document.body);
    };

    return (
        <div className={cn("w-full", className)}>
            {label && (
                <label className="block text-sm font-medium text-gray-700 mb-1.5">
                    {label}
                    {required && <span className="text-red-500 ml-1">*</span>}
                </label>
            )}
            <div ref={containerRef} className="relative">
                <div
                    className={cn(
                        "relative flex items-center w-full rounded-xl border bg-white transition-all duration-200 cursor-text",
                        error 
                            ? "border-red-300 focus-within:ring-2 focus-within:ring-red-500/20 focus-within:border-red-500" 
                            : "border-gray-300 focus-within:ring-2 focus-within:ring-teal-500/20 focus-within:border-teal-500",
                        disabled && "bg-gray-100 cursor-not-allowed opacity-60",
                        isOpen && "ring-2 ring-teal-500/20 border-teal-500",
                    )}
                    onClick={() => {
                        if (!disabled) {
                            setIsOpen(true);
                            inputRef.current?.focus();
                        }
                    }}
                >
                    <Search className="absolute left-3 h-4 w-4 text-gray-400 pointer-events-none" />
                    <input
                        ref={inputRef}
                        type="text"
                        value={isOpen ? searchQuery : (selectedOption?.[displayField] || '')}
                        onChange={handleInputChange}
                        onFocus={() => setIsOpen(true)}
                        onKeyDown={handleKeyDown}
                        placeholder={selectedOption ? selectedOption[displayField] : placeholder}
                        disabled={disabled}
                        className={cn(
                            "w-full pl-10 pr-16 py-2.5 text-sm bg-transparent outline-none",
                            !isOpen && selectedOption && "text-gray-900 font-medium",
                            isOpen && "text-gray-900",
                            disabled && "cursor-not-allowed",
                        )}
                    />
                    <div className="absolute right-2 flex items-center gap-1">
                        {(value || searchQuery) && !disabled && (
                            <button
                                type="button"
                                onClick={handleClear}
                                className="p-1 hover:bg-gray-100 rounded-full transition-colors"
                            >
                                <X className="h-4 w-4 text-gray-400" />
                            </button>
                        )}
                        <ChevronDown 
                            className={cn(
                                "h-4 w-4 text-gray-400 transition-transform",
                                isOpen && "rotate-180"
                            )} 
                        />
                    </div>
                </div>
                {renderDropdown()}
            </div>
            {error && (
                <p className="mt-1.5 text-sm text-red-500">{error}</p>
            )}
        </div>
    );
}

