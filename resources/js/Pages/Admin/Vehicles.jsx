import React, { useState, useMemo } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiClient from '@/api/client';
import { Trash2, Eye, X, Search } from 'lucide-react';
import { toast } from 'sonner';

export default function Vehicles() {
    const queryClient = useQueryClient();
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [selectedVehicle, setSelectedVehicle] = useState(null);
    const [searchPlate, setSearchPlate] = useState('');

    const { data: vehicles, isLoading, error } = useQuery({
        queryKey: ['adminVehicles'],
        queryFn: async () => {
            const res = await apiClient.get('/api/v1/admin/vehicles');
            return res.data?.data?.data || res.data?.data || [];
        }
    });

    // Delete Mutation
    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            const res = await apiClient.delete(`/api/v1/admin/vehicles/${id}`);
            return res.data;
        },
        onSuccess: () => {
            toast.success('Kendaraan berhasil dihapus');
            queryClient.invalidateQueries({ queryKey: ['adminVehicles'] });
            setIsDeleteModalOpen(false);
            setSelectedVehicle(null);
        },
        onError: (error) => {
            const message = error.response?.data?.message || 'Gagal menghapus kendaraan';
            toast.error(message);
        }
    });

    const handleDeleteClick = (vehicle) => {
        setSelectedVehicle(vehicle);
        setIsDeleteModalOpen(true);
    };

    const handleDeleteConfirm = () => {
        deleteMutation.mutate(selectedVehicle.id);
    };

    // Filter dan search logic
    const filteredVehicles = useMemo(() => {
        return vehicles?.filter((vehicle) => {
            return vehicle.plate_number.toLowerCase().includes(searchPlate.toLowerCase());
        }) || [];
    }, [vehicles, searchPlate]);

    return (
        <AdminLayout title="Manajemen Vehicles">
            <Head title="Manajemen Vehicles" />

            <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div className="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 className="font-bold text-gray-800">Daftar Kendaraan Terdaftar</h3>
                </div>

                {/* Search */}
                <div className="p-4 border-b border-gray-100 bg-gray-50">
                    <div className="flex flex-col sm:flex-row gap-3 items-center justify-between flex-wrap">
                        <div className="flex gap-2 items-center flex-wrap">
                            <div className="relative w-full sm:w-64">
                                <Search className="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
                                <input type="text" placeholder="Cari plat nomor..." value={searchPlate} onChange={(e) => setSearchPlate(e.target.value)} className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-300 shadow-sm hover:border-gray-300 transition-all" />
                            </div>

                            {searchPlate && (
                                <button onClick={() => setSearchPlate('')} className="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded-lg transition-colors whitespace-nowrap">
                                    ✕ Reset
                                </button>
                            )}
                        </div>

                        <span className="text-xs text-gray-500 whitespace-nowrap">
                            {filteredVehicles.length}/{vehicles?.length || 0}
                        </span>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-gray-50 text-gray-500 text-sm border-b border-gray-100">
                                <th className="px-6 py-4 font-medium">Plat Nomor</th>
                                <th className="px-6 py-4 font-medium">Pemilik</th>
                                <th className="px-6 py-4 font-medium">Merek/Model</th>
                                <th className="px-6 py-4 font-medium">Tahun</th>
                                <th className="px-6 py-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {isLoading ? (
                                <tr>
                                    <td colSpan="5" className="px-6 py-8 text-center text-gray-500">Loading data...</td>
                                </tr>
                            ) : error ? (
                                <tr>
                                    <td colSpan="5" className="px-6 py-8 text-center text-red-500">Gagal memuat data.</td>
                                </tr>
                            ) : vehicles?.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="px-6 py-8 text-center text-gray-500">Belum ada data.</td>
                                </tr>
                            ) : filteredVehicles?.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="px-6 py-8 text-center text-gray-500">Tidak ada hasil yang sesuai.</td>
                                </tr>
                            ) : (
                                filteredVehicles?.map((vehicle) => (
                                    <tr key={vehicle.id} className="hover:bg-gray-50 transition-colors">
                                        <td className="px-6 py-4 text-sm text-gray-800 font-bold tracking-widest">{vehicle.plate_number}</td>
                                        <td className="px-6 py-4 text-sm text-gray-600">{vehicle.user?.name || '-'}</td>
                                        <td className="px-6 py-4 text-sm text-gray-600">{vehicle.brand} {vehicle.model}</td>
                                        <td className="px-6 py-4 text-sm text-gray-600">{vehicle.manufacturing_year || '-'}</td>
                                        <td className="px-6 py-4 text-sm flex gap-2">
                                            <button className="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">
                                                <Eye className="w-4 h-4" />
                                            </button>
                                            <button onClick={() => handleDeleteClick(vehicle)} className="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Delete Confirmation Modal */}
            {isDeleteModalOpen && (
                <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50">
                    <div className="bg-white rounded-2xl p-6 w-full max-w-sm">
                        <h2 className="text-lg font-bold text-gray-800 mb-4">Hapus Kendaraan?</h2>
                        <p className="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus kendaraan <span className="font-semibold">{selectedVehicle?.plate_number}</span> atas nama <span className="font-semibold">{selectedVehicle?.user?.name}</span>? Tindakan ini tidak dapat dibatalkan.</p>
                        
                        <div className="flex gap-2">
                            <button onClick={() => setIsDeleteModalOpen(false)} className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                Batal
                            </button>
                            <button onClick={handleDeleteConfirm} disabled={deleteMutation.isPending} className="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                                {deleteMutation.isPending ? 'Loading...' : 'Hapus'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
