// Tailwind config fragment for Addy Business 2.1
export const tailwindExtend = {
    colors: {
        teal: {
            50: '#E6F5F4',
            100: '#CCE9E8',
            200: '#99D4D1',
            300: '#66BEBA',
            400: '#33A9A3',
            500: '#00635D',
            600: '#00504A',
            700: '#003C38',
            800: '#002825',
            900: '#001413',
        },
        green: {
            50: '#F0FAF1',
            100: '#E1F5E3',
            200: '#C3EBC7',
            300: '#A5E0AB',
            400: '#91D998',
            500: '#7DCD85',
            600: '#64A46A',
            700: '#4B7B50',
            800: '#325235',
            900: '#19291B',
        },
        mint: {
            50: '#F9FDF9',
            100: '#F4FBF4',
            200: '#E9F7E9',
            300: '#DFF3DF',
            400: '#D4EFD4',
            500: '#C2E1C2',
            600: '#9BB49B',
            700: '#748774',
        },
    },
    fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
    },
    borderRadius: {
        '2xl': '1.5rem',
        '3xl': '2rem',
    },
    boxShadow: {
        card: '0 1px 3px 0 rgba(0, 0, 0, 0.05)',
        'card-hover': '0 4px 12px 0 rgba(0, 0, 0, 0.08)',
    },
};

export default tailwindExtend;
