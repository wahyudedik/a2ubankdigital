import React, { createContext, useState, useContext, useCallback } from 'react';
import AlertDialog from '../components/modals/AlertDialog';

const ModalContext = createContext();

export const useModal = () => useContext(ModalContext);

export const ModalProvider = ({ children }) => {
    const [modalProps, setModalProps] = useState(null);
    const [resolvePromise, setResolvePromise] = useState(null);

    const showConfirmation = useCallback((props) => {
        return new Promise((resolve) => {
            setModalProps({
                ...props,
                type: 'warning',
                onConfirm: () => {
                    resolve(true);
                    setModalProps(null);
                },
            });
            setResolvePromise(() => () => resolve(false));
        });
    }, []);

    const showAlert = useCallback((props) => {
         setModalProps({
            ...props,
            type: props.type || 'info',
        });
    }, []);

    const hideModal = () => {
        if (resolvePromise) {
            resolvePromise();
            setResolvePromise(null);
        }
        setModalProps(null);
    };

    return (
        <ModalContext.Provider value={{ showConfirmation, showAlert, hideModal }}>
            {children}
            <AlertDialog modalProps={modalProps} hideModal={hideModal} />
        </ModalContext.Provider>
    );
};
