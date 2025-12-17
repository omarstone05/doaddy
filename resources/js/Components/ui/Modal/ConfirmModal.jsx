import React from 'react';
import { Modal, ModalHeader, ModalBody, ModalFooter } from './Modal';
import { Button } from '../Button';
import { AlertTriangle, Info } from 'lucide-react';

export function ConfirmModal({ 
    isOpen,
    onClose,
    onConfirm,
    title = 'Confirm Action',
    message = 'Are you sure you want to proceed?',
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    variant = 'primary',
    loading = false,
}) {
    const icons = {
        primary: <Info className="w-6 h-6 text-teal-500" />,
        danger: <AlertTriangle className="w-6 h-6 text-red-500" />,
    };

    const iconBg = {
        primary: 'bg-teal-50',
        danger: 'bg-red-50',
    };

    return (
        <Modal isOpen={isOpen} onClose={onClose} size="sm">
            <div className="p-6">
                <div className="flex flex-col items-center text-center">
                    <div className={`w-12 h-12 rounded-full ${iconBg[variant]} flex items-center justify-center mb-4`}>
                        {icons[variant]}
                    </div>
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">
                        {title}
                    </h3>
                    <p className="text-sm text-gray-500 mb-6">
                        {message}
                    </p>
                    <div className="flex items-center gap-3 w-full">
                        <Button
                            variant="secondary"
                            className="flex-1"
                            onClick={onClose}
                            disabled={loading}
                        >
                            {cancelLabel}
                        </Button>
                        <Button
                            variant={variant === 'danger' ? 'danger' : 'primary'}
                            className="flex-1"
                            onClick={onConfirm}
                            loading={loading}
                        >
                            {confirmLabel}
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>
    );
}

export default ConfirmModal;

