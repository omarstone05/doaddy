import { Menu, Transition } from '@headlessui/react';
import { ChevronDownIcon } from '@heroicons/react/24/outline';
import { Fragment } from 'react';

export default function AppSwitcher({ currentApp, availableApps }) {
    if (!currentApp || !availableApps || availableApps.length === 0) {
        return null;
    }

    return (
        <Menu as="div" className="relative">
            <Menu.Button className="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                {currentApp.icon && (
                    <img src={currentApp.icon} className="w-5 h-5" alt="" />
                )}
                <span>{currentApp.name}</span>
                <ChevronDownIcon className="w-4 h-4 text-gray-500" />
            </Menu.Button>

            <Transition
                as={Fragment}
                enter="transition ease-out duration-100"
                enterFrom="transform opacity-0 scale-95"
                enterTo="transform opacity-100 scale-100"
                leave="transition ease-in duration-75"
                leaveFrom="transform opacity-100 scale-100"
                leaveTo="transform opacity-0 scale-95"
            >
                <Menu.Items className="absolute left-0 mt-2 w-64 bg-white shadow-lg rounded-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                    <div className="p-2">
                        <div className="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Switch App
                        </div>

                        {availableApps.map((app) => (
                            <Menu.Item key={app.code}>
                                {({ active }) => (
                                    <a
                                        href={app.url}
                                        className={`flex items-center gap-3 px-3 py-2 rounded-md transition-colors ${
                                            active ? 'bg-gray-100' : ''
                                        } ${
                                            app.code === currentApp.code
                                                ? 'bg-blue-50 text-blue-700 font-medium'
                                                : 'text-gray-700'
                                        }`}
                                    >
                                        {app.icon && (
                                            <img src={app.icon} className="w-5 h-5" alt="" />
                                        )}
                                        <span>{app.name}</span>
                                    </a>
                                )}
                            </Menu.Item>
                        ))}

                        <div className="border-t mt-2 pt-2">
                            <Menu.Item>
                                {({ active }) => (
                                    <a
                                        href="/dashboard"
                                        className={`flex items-center gap-3 px-3 py-2 rounded-md transition-colors ${
                                            active ? 'bg-gray-100' : ''
                                        } text-gray-700`}
                                    >
                                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                        </svg>
                                        <span>Back to Penda Cloud</span>
                                    </a>
                                )}
                            </Menu.Item>
                        </div>
                    </div>
                </Menu.Items>
            </Transition>
        </Menu>
    );
}
