import React from 'react';

export default function Footer() {
    const currentYear = new Date().getFullYear();

    return (
        <footer className="bg-white border-t border-gray-200 mt-auto">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <img 
                            src="/assets/logos/penda.png" 
                            alt="Penda Digital" 
                            className="h-8 w-auto object-contain"
                        />
                        <div className="text-xs text-gray-500">
                            <p>© {currentYear} All rights reserved.</p>
                            <p>
                                This is a product of Penda Digital, a registered company in the Republic of Zambia.
                            </p>
                        </div>
                    </div>
                    <div className="text-xs text-gray-500 text-center md:text-right">
                        <p>Copyright © {currentYear} Penda Digital. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </footer>
    );
}

