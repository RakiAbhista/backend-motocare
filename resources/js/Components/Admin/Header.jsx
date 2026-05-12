import React from 'react';
import { Bell, ChevronDown, UserCircle, Menu } from 'lucide-react';

export default function Header({ title, onMenuClick }) {
    return (
        <header className="h-20 bg-white flex items-center justify-between px-4 md:px-8">
            {/* Mobile Menu Button */}
            <button 
                onClick={onMenuClick}
                className="md:hidden p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
                <Menu className="w-6 h-6 text-primary" />
            </button>

            {/* Page Title */}
            <div className="flex-1 md:flex-none">
                <h2 className="text-lg md:text-2xl font-bold text-primary truncate">{title}</h2>
            </div>

            {/* Right side actions */}
            <div className="flex items-center gap-2 md:gap-6">
                {/* Notification Bell */}
                <button className="relative p-2 rounded-full text-secondary hover:bg-background transition-colors">
                    <Bell className="w-5 md:w-6 h-5 md:h-6" />
                    <span className="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-danger rounded-full border-2 border-white"></span>
                </button>

                {/* Profile Dropdown */}
                <div className="flex items-center gap-2 md:gap-3 pl-2 md:pl-6 border-l border-gray-200 cursor-pointer hover:opacity-80 transition-opacity">
                    <div className="w-8 md:w-10 h-8 md:h-10 rounded-full bg-primary-light text-primary flex items-center justify-center shrink-0">
                        <UserCircle className="w-5 md:w-6 h-5 md:h-6" />
                    </div>
                    <div className="hidden md:block">
                        <p className="text-sm font-bold text-gray-800">Owner Admin</p>
                        <p className="text-xs text-gray-500">Administrator</p>
                    </div>
                    <ChevronDown className="hidden md:block w-4 h-4 text-gray-500" />
                </div>
            </div>
        </header>
    );
}
