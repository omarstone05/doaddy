import { colors } from './colors';
import { typography } from './typography';
import { spacing } from './spacing';
import { shadows } from './shadows';

export const designPrinciples = {
    gradientRule: 'Exactly one teal→mint gradient hero card per screen',
    paletteBudget: '95% neutral surfaces, 5% purposeful color',
    actionColor: 'Teal (#00635D) drives all primary actions and links',
    mintUsage: 'Mint is partner color for gradients only, never standalone',
    chartColor: 'Use green (#7DCD85) for positive metrics and chart fills',
    typography: 'Large, bold teal numerals (4xl–6xl) for hero metrics',
};

export const componentHierarchy = [
    'Page (bg-gray-50)',
    'Navigation (bg-white, teal active state)',
    'Content Area (max-w-[1600px])',
    'Hero Card (teal→mint gradient)',
    'Metric / Chart / Action Cards (white with teal accents)',
];

export const rules = [
    'Only one gradient hero card per experience',
    'Teal-500 for buttons, links, pills, and active nav items',
    'White cards with subtle gray borders and card shadows',
    'Use gray-700/900 for headings and body text instead of colored text',
    'Keep charts limited to teal and green families for clarity',
    'Reserve mint for gradients or supportive illustration flourishes',
];

export const addyTheme = {
    name: 'Addy Business Design System',
    version: '2.1',
    released: '2025-11-08',
    colors,
    typography,
    spacing,
    shadows,
    designPrinciples,
    componentHierarchy,
    rules,
};

export default addyTheme;
