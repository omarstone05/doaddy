import React, { useState } from 'react';
import { useAddy } from '../../Contexts/AddyContext';
import AddyChat from './AddyChat';
import AddyInsights from './AddyInsights';

export default function AddyPanel() {
    const { isOpen } = useAddy();
    const [view, setView] = useState('chat'); // 'chat' or 'insights'

    if (!isOpen) return null;

    return view === 'chat' ? <AddyChat /> : <AddyInsights />;
}
