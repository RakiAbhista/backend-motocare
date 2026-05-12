import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { LayoutDashboard, Users, Wrench, Store, Car, ShoppingCart, LogOut, X } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

export default function Sidebar({ isOpen, onClose }) {
    const { url } = usePage();

    const menuItems = [
        { name: 'Dashboard', icon: LayoutDashboard, path: '/admin/dashboard' },
        { name: 'Manajemen Akun', icon: Users, path: '/admin/accounts' },
        { name: 'Manajemen Services', icon: Wrench, path: '/admin/services' },
        { name: 'Manajemen Workshops', icon: Store, path: '/admin/workshops' },
        { name: 'Manajemen Vehicles', icon: Car, path: '/admin/vehicles' },
        { name: 'Manajemen Orders', icon: ShoppingCart, path: '/admin/orders' },
    ];

    const handleLogout = async () => {
        try {
            await axios.post('/auth/logout');
            localStorage.removeItem('token');
            delete axios.defaults.headers.common['Authorization'];
            toast.success('Logout berhasil!');
            window.location.href = '/auth/login';
        } catch (error) {
            toast.error('Gagal logout.');
        }
    };

    return (
        <>
            {/* Desktop Sidebar */}
            <div className="hidden md:flex fixed left-4 top-4 w-64 bg-primary text-white rounded-2xl shadow-xl flex-col h-[calc(100vh-2rem)] overflow-hidden z-40">
                {/* Logo/Brand */}
                <div className="p-8 pb-4">
                    <h1 className="text-2xl font-bold tracking-widest uppercase">Motocare</h1>
                </div>

                {/* Navigation Links */}
                <nav className="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                    {menuItems.map((item) => {
                        const isActive = url.startsWith(item.path);
                        return (
                            <Link
                                key={item.name}
                                href={item.path}
                                className={`flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 ${
                                    isActive 
                                    ? 'bg-secondary text-white shadow-md' 
                                    : 'text-white/80 hover:bg-white/10 hover:text-white'
                                }`}
                            >
                                <item.icon className={`w-5 h-5 ${isActive ? 'text-white' : 'text-white/70'}`} />
                                <span className="font-medium text-sm">{item.name}</span>
                            </Link>
                        );
                    })}
                </nav>

                {/* Logout Button */}
                <div className="p-4 border-t border-white/10">
                    <button
                        onClick={handleLogout}
                        className="flex items-center gap-3 px-4 py-3 w-full text-left rounded-xl transition-all duration-200 text-white/80 hover:bg-white/10 hover:text-white"
                    >
                        <LogOut className="w-5 h-5 text-white/70" />
                        <span className="font-medium text-sm">Log Out</span>
                    </button>
                </div>
            </div>

            {/* Mobile Sidebar */}
            {isOpen && (
                <div className="fixed inset-0 bg-black/50 z-35 md:hidden" onClick={onClose} />
            )}
            <div className={`fixed left-0 top-0 w-64 h-screen bg-primary text-white rounded-r-2xl shadow-xl flex flex-col z-50 md:hidden transform transition-transform duration-500 ease-in-out ${isOpen ? 'translate-x-0' : '-translate-x-full'}`}>
                {/* Close Button */}
                <div className="flex justify-end p-4">
                    <button onClick={onClose} className="text-white hover:bg-white/10 p-2 rounded-lg">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                {/* Logo/Brand */}
                <div className="px-8 pb-4">
                    <h1 className="text-2xl font-bold tracking-widest uppercase">Motocare</h1>
                </div>

                {/* Navigation Links */}
                <nav className="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                    {menuItems.map((item) => {
                        const isActive = url.startsWith(item.path);
                        return (
                            <Link
                                key={item.name}
                                href={item.path}
                                onClick={onClose}
                                className={`flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 ${
                                    isActive 
                                    ? 'bg-secondary text-white shadow-md' 
                                    : 'text-white/80 hover:bg-white/10 hover:text-white'
                                }`}
                            >
                                <item.icon className={`w-5 h-5 ${isActive ? 'text-white' : 'text-white/70'}`} />
                                <span className="font-medium text-sm">{item.name}</span>
                            </Link>
                        );
                    })}
                </nav>

                {/* Logout Button */}
                <div className="p-4 border-t border-white/10">
                    <button
                        onClick={handleLogout}
                        className="flex items-center gap-3 px-4 py-3 w-full text-left rounded-xl transition-all duration-200 text-white/80 hover:bg-white/10 hover:text-white"
                    >
                        <LogOut className="w-5 h-5 text-white/70" />
                        <span className="font-medium text-sm">Log Out</span>
                    </button>
                </div>
            </div>
        </>
    );
}
