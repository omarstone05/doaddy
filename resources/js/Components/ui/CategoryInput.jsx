import React, { useState, useEffect, useRef } from 'react';
import { Search, Plus, X } from 'lucide-react';
import axios from 'axios';

export default function CategoryInput({ 
    value = '', 
    onChange, 
    placeholder = 'e.g., Utilities, Rent, Supplies',
    type = 'expense', // 'expense', 'income', 'general'
    className = '',
    id = 'category',
    errors = null
}) {
    const [searchTerm, setSearchTerm] = useState(value || '');
    const [suggestions, setSuggestions] = useState([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [canAddNew, setCanAddNew] = useState(false);
    const inputRef = useRef(null);
    const dropdownRef = useRef(null);
    const timeoutRef = useRef(null);

    // Fetch categories from API
    const fetchCategories = async (term) => {
        if (!term || term.length < 1) {
            setSuggestions([]);
            setCanAddNew(false);
            return;
        }

        setIsLoading(true);
        try {
            const response = await axios.get('/api/categories/search', {
                params: {
                    q: term,
                    type: type
                }
            });

            const categories = response.data.categories || [];
            setSuggestions(categories);
            
            // Check if the search term matches any existing category (case-insensitive)
            const exactMatch = categories.some(
                cat => cat.toLowerCase() === term.toLowerCase()
            );
            
            // Can add new if there's a search term and it doesn't exactly match
            setCanAddNew(term.trim().length > 0 && !exactMatch);
        } catch (error) {
            console.error('Failed to fetch categories:', error);
            setSuggestions([]);
            setCanAddNew(term.trim().length > 0);
        } finally {
            setIsLoading(false);
        }
    };

    // Debounced search
    useEffect(() => {
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        timeoutRef.current = setTimeout(() => {
            fetchCategories(searchTerm);
        }, 200);

        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, [searchTerm, type]);

    // Update search term when value prop changes
    useEffect(() => {
        setSearchTerm(value || '');
    }, [value]);

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (
                dropdownRef.current &&
                !dropdownRef.current.contains(event.target) &&
                inputRef.current &&
                !inputRef.current.contains(event.target)
            ) {
                setShowSuggestions(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    const handleInputChange = (e) => {
        const newValue = e.target.value;
        setSearchTerm(newValue);
        setShowSuggestions(true);
        onChange(newValue);
    };

    const handleSelectCategory = (category) => {
        setSearchTerm(category);
        setShowSuggestions(false);
        onChange(category);
        inputRef.current?.blur();
    };

    const handleAddNew = () => {
        const newCategory = searchTerm.trim();
        if (newCategory) {
            handleSelectCategory(newCategory);
        }
    };

    const handleFocus = () => {
        if (searchTerm) {
            fetchCategories(searchTerm);
        }
        setShowSuggestions(true);
    };

    const filteredSuggestions = suggestions.filter(cat =>
        cat.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
        <div className="relative">
            <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                <input
                    ref={inputRef}
                    id={id}
                    type="text"
                    value={searchTerm}
                    onChange={handleInputChange}
                    onFocus={handleFocus}
                    placeholder={placeholder}
                    className={`w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent ${className}`}
                    autoComplete="off"
                />
            </div>

            {errors && (
                <p className="mt-1 text-sm text-red-600">{errors}</p>
            )}

            {/* Dropdown */}
            {showSuggestions && (filteredSuggestions.length > 0 || canAddNew || isLoading) && (
                <div
                    ref={dropdownRef}
                    className="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto"
                >
                    {isLoading && (
                        <div className="px-4 py-3 text-sm text-gray-500 text-center">
                            Searching...
                        </div>
                    )}

                    {!isLoading && filteredSuggestions.length > 0 && (
                        <div className="py-1">
                            {filteredSuggestions.map((category, index) => (
                                <button
                                    key={index}
                                    type="button"
                                    onClick={() => handleSelectCategory(category)}
                                    className="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-teal-50 transition-colors flex items-center gap-2"
                                >
                                    <Search className="h-4 w-4 text-gray-400" />
                                    <span>{category}</span>
                                </button>
                            ))}
                        </div>
                    )}

                    {!isLoading && canAddNew && searchTerm.trim() && (
                        <div className="border-t border-gray-200 py-1">
                            <button
                                type="button"
                                onClick={handleAddNew}
                                className="w-full px-4 py-2 text-left text-sm text-teal-600 hover:bg-teal-50 transition-colors flex items-center gap-2 font-medium"
                            >
                                <Plus className="h-4 w-4" />
                                <span>Add "{searchTerm.trim()}" as new category</span>
                            </button>
                        </div>
                    )}

                    {!isLoading && filteredSuggestions.length === 0 && !canAddNew && searchTerm && (
                        <div className="px-4 py-3 text-sm text-gray-500 text-center">
                            No categories found
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

