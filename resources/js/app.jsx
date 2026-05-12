import "../css/app.css";

import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { Toaster } from 'sonner';
import axios from 'axios';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';
const queryClient = new QueryClient();

// Set base URL dan authentication header
const appUrl = document.querySelector('meta[name="app-url"]')?.content || window.location.origin;
axios.defaults.baseURL = appUrl;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Restore token from localStorage jika ada
const token = localStorage.getItem('token');
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(
            <QueryClientProvider client={queryClient}>
                <App {...props} />
                <Toaster richColors position="top-right" />
            </QueryClientProvider>
        );
    },
    progress: { color: "#3564C4" },
});
