import React, { useState, useMemo } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiClient from '@/api/client';
import { Plus, Trash2, Edit, X, Search } from 'lucide-react';
import { toast } from 'sonner';

export default function Workshops() {
    const queryClient = useQueryClient();
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [selectedWorkshop, setSelectedWorkshop] = useState(null);
    const [formData, setFormData] = useState({ name: '', latitude: '', longitude: '' });
    const [searchWorkshop, setSearchWorkshop] = useState('');

    const { data: workshops, isLoading, error } = useQuery({
        queryKey: ['adminWorkshops'],
        queryFn: async () => {
            const res = await apiClient.get('/api/v1/admin/workshops');
            return res.data?.data?.data || res.data?.data || [];
        }
    });

    // Create Mutation
    const createMutation = useMutation({
        mutationFn: async (data) => {
            const res = await apiClient.post('/api/v1/admin/workshops', data);
            return res.data;
        },
        onSuccess: () => {
            toast.success('Bengkel berhasil dibuat');
            queryClient.invalidateQueries({ queryKey: ['adminWorkshops'] });
            setIsCreateModalOpen(false);
            setFormData({ name: '', latitude: '', longitude: '' });
        },
        onError: (error) => {
            const message = error.response?.data?.message || 'Gagal membuat bengkel';
            toast.error(message);
        }
    });

    // Update Mutation
    const updateMutation = useMutation({
        mutationFn: async (data) => {
            const res = await apiClient.put(`/api/v1/admin/workshops/${selectedWorkshop.id}`, data);
            return res.data;
        },
        onSuccess: () => {
            toast.success('Bengkel berhasil diupdate');
            queryClient.invalidateQueries({ queryKey: ['adminWorkshops'] });
            setIsEditModalOpen(false);
            setSelectedWorkshop(null);
            setFormData({ name: '', latitude: '', longitude: '' });
        },
        onError: (error) => {
            const message = error.response?.data?.message || 'Gagal mengupdate bengkel';
            toast.error(message);
        }
    });

    // Delete Mutation
    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            const res = await apiClient.delete(`/api/v1/admin/workshops/${id}`);
            return res.data;
        },
        onSuccess: () => {
            toast.success('Bengkel berhasil dihapus');
            queryClient.invalidateQueries({ queryKey: ['adminWorkshops'] });
            setIsDeleteModalOpen(false);
            setSelectedWorkshop(null);
        },
        onError: (error) => {
            const message = error.response?.data?.message || 'Gagal menghapus bengkel';
            toast.error(message);
        }
    });

    const handleCreateClick = () => {
        setFormData({ name: '', latitude: '', longitude: '' });
        setIsCreateModalOpen(true);
    };

    const handleEditClick = (workshop) => {
        setSelectedWorkshop(workshop);
        setFormData({ name: workshop.name, latitude: workshop.latitude, longitude: workshop.longitude });
        setIsEditModalOpen(true);
    };

    const handleDeleteClick = (workshop) => {
        setSelectedWorkshop(workshop);
        setIsDeleteModalOpen(true);
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        createMutation.mutate({ ...formData, latitude: parseFloat(formData.latitude), longitude: parseFloat(formData.longitude) });
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        updateMutation.mutate({ ...formData, latitude: parseFloat(formData.latitude), longitude: parseFloat(formData.longitude) });
    };

    const handleDeleteConfirm = () => {
        deleteMutation.mutate(selectedWorkshop.id);
    };

    // Filter dan search logic
    const filteredWorkshops = useMemo(() => {
        return workshops?.filter((workshop) => {
            return workshop.name.toLowerCase().includes(searchWorkshop.toLowerCase());
        }) || [];
    }, [workshops, searchWorkshop]);

    return (
        <AdminLayout title="Manajemen Bengkel">
            <Head title="Manajemen Bengkel" />

            <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div className="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 className="font-bold text-gray-800">Daftar Bengkel</h3>
                    <button onClick={handleCreateClick} className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <Plus className="w-4 h-4" />
                        Tambah Bengkel
                    </button>
                </div>

                {/* Search */}
                <div className="p-4 border-b border-gray-100 bg-gray-50">
                    <div className="flex flex-col sm:flex-row gap-3 items-center justify-between flex-wrap">
                        <div className="flex gap-2 items-center flex-wrap">
                            <div className="relative w-full sm:w-64">
                                <Search className="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
                                <input type="text" placeholder="Cari bengkel..." value={searchWorkshop} onChange={(e) => setSearchWorkshop(e.target.value)} className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-300 shadow-sm hover:border-gray-300 transition-all" />
                            </div>

                            {searchWorkshop && (
                                <button onClick={() => setSearchWorkshop('')} className="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded-lg transition-colors whitespace-nowrap">
                                    ✕ Reset
                                </button>
                            )}
                        </div>

                        <span className="text-xs text-gray-500 whitespace-nowrap">
                            {filteredWorkshops.length}/{workshops?.length || 0}
                        </span>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-gray-50 text-gray-500 text-sm border-b border-gray-100">
                                <th className="px-6 py-4 font-medium">Nama Bengkel</th>
                                <th className="px-6 py-4 font-medium">Latitude</th>
                                <th className="px-6 py-4 font-medium">Longitude</th>
                                <th className="px-6 py-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {isLoading ? (
                                <tr>
                                    <td colSpan="4" className="px-6 py-8 text-center text-gray-500">Loading data...</td>
                                </tr>
                            ) : error ? (
                                <tr>
                                    <td colSpan="4" className="px-6 py-8 text-center text-red-500">Gagal memuat data.</td>
                                </tr>
                            ) : workshops?.length === 0 ? (
                                <tr>
                                    <td colSpan="4" className="px-6 py-8 text-center text-gray-500">Belum ada data.</td>
                                </tr>
                            ) : filteredWorkshops?.length === 0 ? (
                                <tr>
                                    <td colSpan="4" className="px-6 py-8 text-center text-gray-500">Tidak ada hasil yang sesuai.</td>
                                </tr>
                            ) : (
                                filteredWorkshops?.map((workshop) => (
                                    <tr key={workshop.id} className="hover:bg-gray-50 transition-colors">
                                        <td className="px-6 py-4 text-sm text-gray-800 font-medium">{workshop.name}</td>
                                        <td className="px-6 py-4 text-sm text-gray-600">{workshop.latitude}</td>
                                        <td className="px-6 py-4 text-sm text-gray-600">{workshop.longitude}</td>
                                        <td className="px-6 py-4 text-sm flex gap-2">
                                            <button onClick={() => handleEditClick(workshop)} className="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors">
                                                <Edit className="w-4 h-4" />
                                            </button>
                                            <button onClick={() => handleDeleteClick(workshop)} className="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
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

            {/* Create Modal */}
            {isCreateModalOpen && (
                <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50">
                    <div className="bg-white rounded-2xl p-6 w-full max-w-md">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold text-gray-800">Tambah Bengkel</h2>
                            <button onClick={() => setIsCreateModalOpen(false)} className="text-gray-400 hover:text-gray-600">
                                <X className="w-6 h-6" />
                            </button>
                        </div>

                        <form onSubmit={handleCreateSubmit} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Nama Bengkel</label>
                                <input type="text" required value={formData.name} onChange={(e) => setFormData({...formData, name: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Latitude</label>
                                <input type="number" step="0.0001" required value={formData.latitude} onChange={(e) => setFormData({...formData, latitude: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Longitude</label>
                                <input type="number" step="0.0001" required value={formData.longitude} onChange={(e) => setFormData({...formData, longitude: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div className="flex gap-2 pt-4">
                                <button type="button" onClick={() => setIsCreateModalOpen(false)} className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    Batal
                                </button>
                                <button type="submit" disabled={createMutation.isPending} className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                                    {createMutation.isPending ? 'Loading...' : 'Simpan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Edit Modal */}
            {isEditModalOpen && (
                <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50">
                    <div className="bg-white rounded-2xl p-6 w-full max-w-md">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold text-gray-800">Edit Bengkel</h2>
                            <button onClick={() => setIsEditModalOpen(false)} className="text-gray-400 hover:text-gray-600">
                                <X className="w-6 h-6" />
                            </button>
                        </div>

                        <form onSubmit={handleEditSubmit} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Nama Bengkel</label>
                                <input type="text" required value={formData.name} onChange={(e) => setFormData({...formData, name: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Latitude</label>
                                <input type="number" step="0.0001" required value={formData.latitude} onChange={(e) => setFormData({...formData, latitude: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Longitude</label>
                                <input type="number" step="0.0001" required value={formData.longitude} onChange={(e) => setFormData({...formData, longitude: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div className="flex gap-2 pt-4">
                                <button type="button" onClick={() => setIsEditModalOpen(false)} className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    Batal
                                </button>
                                <button type="submit" disabled={updateMutation.isPending} className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                                    {updateMutation.isPending ? 'Loading...' : 'Simpan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Delete Confirmation Modal */}
            {isDeleteModalOpen && (
                <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50">
                    <div className="bg-white rounded-2xl p-6 w-full max-w-sm">
                        <h2 className="text-lg font-bold text-gray-800 mb-4">Hapus Bengkel?</h2>
                        <p className="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus bengkel <span className="font-semibold">{selectedWorkshop?.name}</span>? Tindakan ini tidak dapat dibatalkan.</p>
                        
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
