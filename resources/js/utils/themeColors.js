/**
 * Get color theme for organization based on index
 * Index 0 = first company (default teal-mint theme)
 * Index 1+ = different themes with good readability
 */
export function getOrganizationTheme(themeIndex = 0) {
  const themes = [
    // Theme 0: Default (first company) - Teal to Mint
    {
      gradient: 'from-teal-500 to-mint-300',
      primaryGradient: 'from-teal-600 to-teal-700',
      secondaryGradient: 'from-teal-600 to-teal-500',
      accentColor: 'teal',
      iconColor: 'text-teal-600',
      textColor: 'text-white',
      textOpacity: {
        primary: 'text-white',
        secondary: 'text-white/95',
        tertiary: 'text-white/90',
        quaternary: 'text-white/85',
      },
      cardBg: 'bg-white',
      cardBorder: 'border-teal-300',
      cardHover: 'hover:border-teal-400',
    },
    // Theme 1: Blue to Cyan
    {
      gradient: 'from-blue-600 to-cyan-400',
      primaryGradient: 'from-blue-700 to-blue-800',
      secondaryGradient: 'from-blue-600 to-blue-500',
      accentColor: 'blue',
      iconColor: 'text-blue-600',
      textColor: 'text-white',
      textOpacity: {
        primary: 'text-white',
        secondary: 'text-white/95',
        tertiary: 'text-white/90',
        quaternary: 'text-white/85',
      },
      cardBg: 'bg-white',
      cardBorder: 'border-blue-300',
      cardHover: 'hover:border-blue-400',
    },
    // Theme 2: Purple to Pink
    {
      gradient: 'from-purple-600 to-pink-400',
      primaryGradient: 'from-purple-700 to-purple-800',
      secondaryGradient: 'from-purple-600 to-purple-500',
      accentColor: 'purple',
      iconColor: 'text-purple-600',
      textColor: 'text-white',
      textOpacity: {
        primary: 'text-white',
        secondary: 'text-white/95',
        tertiary: 'text-white/90',
        quaternary: 'text-white/85',
      },
      cardBg: 'bg-white',
      cardBorder: 'border-purple-300',
      cardHover: 'hover:border-purple-400',
    },
    // Theme 3: Indigo to Blue
    {
      gradient: 'from-indigo-600 to-blue-400',
      primaryGradient: 'from-indigo-700 to-indigo-800',
      secondaryGradient: 'from-indigo-600 to-indigo-500',
      accentColor: 'indigo',
      iconColor: 'text-indigo-600',
      textColor: 'text-white',
      textOpacity: {
        primary: 'text-white',
        secondary: 'text-white/95',
        tertiary: 'text-white/90',
        quaternary: 'text-white/85',
      },
      cardBg: 'bg-white',
      cardBorder: 'border-indigo-300',
      cardHover: 'hover:border-indigo-400',
    },
    // Theme 4: Emerald to Teal
    {
      gradient: 'from-emerald-600 to-teal-400',
      primaryGradient: 'from-emerald-700 to-emerald-800',
      secondaryGradient: 'from-emerald-600 to-emerald-500',
      accentColor: 'emerald',
      iconColor: 'text-emerald-600',
      textColor: 'text-white',
      textOpacity: {
        primary: 'text-white',
        secondary: 'text-white/95',
        tertiary: 'text-white/90',
        quaternary: 'text-white/85',
      },
      cardBg: 'bg-white',
      cardBorder: 'border-emerald-300',
      cardHover: 'hover:border-emerald-400',
    },
    // Theme 5: Orange to Amber
    {
      gradient: 'from-orange-600 to-amber-400',
      primaryGradient: 'from-orange-700 to-orange-800',
      secondaryGradient: 'from-orange-600 to-orange-500',
      accentColor: 'orange',
      iconColor: 'text-orange-600',
      textColor: 'text-white',
      textOpacity: {
        primary: 'text-white',
        secondary: 'text-white/95',
        tertiary: 'text-white/90',
        quaternary: 'text-white/85',
      },
      cardBg: 'bg-white',
      cardBorder: 'border-orange-300',
      cardHover: 'hover:border-orange-400',
    },
    // Theme 6: Rose to Red
    {
      gradient: 'from-rose-600 to-red-400',
      primaryGradient: 'from-rose-700 to-rose-800',
      secondaryGradient: 'from-rose-600 to-rose-500',
      accentColor: 'rose',
      iconColor: 'text-rose-600',
      textColor: 'text-white',
      textOpacity: {
        primary: 'text-white',
        secondary: 'text-white/95',
        tertiary: 'text-white/90',
        quaternary: 'text-white/85',
      },
      cardBg: 'bg-white',
      cardBorder: 'border-rose-300',
      cardHover: 'hover:border-rose-400',
    },
    // Theme 7: Violet to Purple
    {
      gradient: 'from-violet-600 to-purple-400',
      primaryGradient: 'from-violet-700 to-violet-800',
      secondaryGradient: 'from-violet-600 to-violet-500',
      accentColor: 'violet',
      iconColor: 'text-violet-600',
      textColor: 'text-white',
      textOpacity: {
        primary: 'text-white',
        secondary: 'text-white/95',
        tertiary: 'text-white/90',
        quaternary: 'text-white/85',
      },
      cardBg: 'bg-white',
      cardBorder: 'border-violet-300',
      cardHover: 'hover:border-violet-400',
    },
  ];

  // Use modulo to cycle through themes if there are more organizations than themes
  return themes[themeIndex % themes.length];
}

