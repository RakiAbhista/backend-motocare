import React from 'react';

export default function GuestLayout({ children, title, subtitle }) {
    return (
        <div className="min-h-screen flex w-full font-sans bg-white">
            {/* Left Side - 60% with Gradient and Visuals */}
            <div className="hidden lg:flex lg:w-[60%] bg-gradient-to-br from-primary to-secondary relative overflow-hidden items-center justify-center">
                {/* Abstract decorative circles/waves (simulating the reference image) */}
                <div className="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] rounded-full bg-white/10 blur-3xl" />
                <div className="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] rounded-full bg-white/10 blur-3xl" />
                
                {/* Grid Pattern overlay */}
                <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdHRlcm4gaWQ9InNtYWxsR3JpZCIgd2lkdGg9IjEwIiBoZWlnaHQ9IjEwIiBwYXR0ZXJuVW5pdHM9InVzZXJTcGFjZU9uVXNlIj48cGF0aCBkPSJNMTAgMEwwIDBMMCAxMCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMSkiIHN0cm9rZS13aWR0aD0iMC41Ii8+PC9wYXR0ZXJuPjxyZWN0IHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgZmlsbD0idXJsKCNzbWFsbEdyaWQpIi8+PHBhdGggZD0iTTQwIDBMMCAwTDAgNDAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjIpIiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-30 mix-blend-overlay"></div>

                <div className="relative z-10 text-white text-center p-12 max-w-2xl">
                    <div className="flex items-center gap-2 mb-16 justify-start">
                        <div className="w-8 h-8 rounded-full border-2 border-white/50 flex items-center justify-center">
                            <div className="w-4 h-4 rounded-full bg-white"></div>
                        </div>
                        <span className="font-bold tracking-widest text-sm uppercase">Motocare</span>
                    </div>

                    <h2 className="text-xl font-medium mb-2 opacity-90 text-left">Nice to see you again</h2>
                    <h1 className="text-5xl font-bold mb-6 tracking-wide text-left">WELCOME BACK</h1>
                    
                    <p className="text-left text-white/80 text-sm leading-relaxed max-w-lg mt-12">
                        Platform manajemen bengkel terlengkap. Kelola pelanggan, layanan, dan inventaris kendaraan Anda dalam satu dashboard terintegrasi yang mudah digunakan.
                    </p>
                </div>
            </div>

            {/* Right Side - 40% with Form */}
            <div className="w-full lg:w-[40%] flex flex-col justify-center items-center p-8 sm:p-12 lg:p-16">
                <div className="w-full max-w-md">
                    <div className="mb-10 text-center">
                        <h2 className="text-3xl font-bold text-primary mb-2">{title}</h2>
                        {subtitle && <p className="text-sm text-gray-500">{subtitle}</p>}
                    </div>

                    {children}
                </div>
            </div>
        </div>
    );
}
