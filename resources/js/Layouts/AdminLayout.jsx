import React, { useState } from 'react';
import Sidebar from '@/Components/Admin/Sidebar';
import Header from '@/Components/Admin/Header';

export default function AdminLayout({ children, title }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="min-h-screen bg-background flex">
            {/* Sidebar (Fixed) */}
            <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />

            {/* Main Content Area */}
            <div className="flex-1 flex flex-col md:ml-72 px-3 md:px-4">
                {/* Header (Fixed Top) */}
                <div className="fixed top-4 left-3 md:left-75 right-3 md:right-4 h-20 bg-white rounded-2xl shadow-sm z-20">
                    <Header title={title} onMenuClick={() => setSidebarOpen(!sidebarOpen)} />
                </div>

                {/* Page Content */}
                <main className="flex-1 overflow-y-auto mt-24 pt-4 pb-4">
                    <div className="pb-8">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
