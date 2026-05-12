import React, { useState, useMemo } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiClient from '@/api/client';
import { Plus, Trash2, Edit, X, Search } from 'lucide-react';
import { toast } from 'sonner';

export default function Accounts() {
    const queryClient = useQueryClient();
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [selectedUser, setSelectedUser] = useState(null);
    const [formData, setFormData] = useState({ name: '', email: '', phone_number: '', password: '', password_confirmation: '', role: 'customer' });
    const [searchName, setSearchName] = useState('');
    const [filterRole, setFilterRole] = useState('');

    const { data: accounts, isLoading, error } = useQuery({
        queryKey: ['adminAccounts'],
        queryFn: async () => {
            const res = await apiClient.get('/api/v1/admin/users');
            return res.data?.data?.data || res.data?.data || [];
        }
    });

    // Create Mutation
    const createMutation = useMutation({
        mutationFn: async (data) => {
            const res = await apiClient.post('/api/v1/admin/users', data);
            return res.data;
        },
        onSuccess: () => {
            toast.success('User berhasil dibuat');
            queryClient.invalidateQueries({ queryKey: ['adminAccounts'] });
            setIsCreateModalOpen(false);
            setFormData({ name: '', email: '', phone_number: '', password: '', password_confirmation: '', role: 'customer' });
        },
        onError: (error) => {
            const message = error.response?.data?.message || 'Gagal membuat user';
            toast.error(message);
        }
    });

    // Update Mutation
    const updateMutation = useMutation({
        mutationFn: async (data) => {
            const res = await apiClient.put(`/api/v1/admin/users/${selectedUser.id}`, data);
            return res.data;
        },
        onSuccess: () => {
            toast.success('User berhasil diupdate');
            queryClient.invalidateQueries({ queryKey: ['adminAccounts'] });
            setIsEditModalOpen(false);
            setSelectedUser(null);
            setFormData({ name: '', email: '', phone_number: '', password: '', password_confirmation: '', role: 'customer' });
        },
        onError: (error) => {
            const message = error.response?.data?.message || 'Gagal mengupdate user';
            toast.error(message);
        }
    });

    // Delete Mutation
    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            const res = await apiClient.delete(`/api/v1/admin/users/${id}`);
            return res.data;
        },
        onSuccess: () => {
            toast.success('User berhasil dihapus');
            queryClient.invalidateQueries({ queryKey: ['adminAccounts'] });
            setIsDeleteModalOpen(false);
            setSelectedUser(null);
        },
        onError: (error) => {
            const message = error.response?.data?.message || 'Gagal menghapus user';
            toast.error(message);
        }
    });

    const handleCreateClick = () => {
        setFormData({ name: '', email: '', phone_number: '', password: '', password_confirmation: '', role: 'customer' });
        setIsCreateModalOpen(true);
    };

    const handleEditClick = (user) => {
        setSelectedUser(user);
        setFormData({ name: user.name, email: user.email, phone_number: user.phone_number, role: user.role, password: '', password_confirmation: '' });
        setIsEditModalOpen(true);
    };

    const handleDeleteClick = (user) => {
        setSelectedUser(user);
        setIsDeleteModalOpen(true);
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        createMutation.mutate(formData);
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        const updateData = { ...formData };
        if (!updateData.password) {
            delete updateData.password;
            delete updateData.password_confirmation;
        }
        updateMutation.mutate(updateData);
    };

    const handleDeleteConfirm = () => {
        deleteMutation.mutate(selectedUser.id);
    };

    // Filter dan search logic
    const filteredAccounts = useMemo(() => {
        return accounts?.filter((account) => {
            const matchesName = account.name.toLowerCase().includes(searchName.toLowerCase());
            const matchesRole = filterRole === '' || account.role === filterRole;
            return matchesName && matchesRole;
        }) || [];
    }, [accounts, searchName, filterRole]);

    return (
        <AdminLayout title="Manajemen Akun">
            <Head title="Manajemen Akun" />

            <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div className="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 className="font-bold text-gray-800">Daftar Pengguna</h3>
                    <button onClick={handleCreateClick} className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <Plus className="w-4 h-4" />
                        Tambah Akun
                    </button>
                </div>

                {/* Filter dan Search */}
                <div className="p-4 border-b border-gray-100 bg-gray-50">
                    <div className="flex flex-col sm:flex-row gap-3 items-center justify-between flex-wrap">
                        <div className="flex gap-2 items-center flex-wrap">
                            <div className="relative w-full sm:w-64">
                                <Search className="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
                                <input type="text" placeholder="Cari nama..." value={searchName} onChange={(e) => setSearchName(e.target.value)} className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-300 shadow-sm hover:border-gray-300 transition-all" />
                            </div>

                            <select value={filterRole} onChange={(e) => setFilterRole(e.target.value)} className="px-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-300 bg-white hover:border-gray-300 transition-all shadow-sm">
                                <option value="">Semua Role</option>
                                <option value="customer">Customer</option>
                                <option value="mechanic">Mechanic</option>
                                <option value="customer_service">Customer Service</option>
                                <option value="admin">Admin</option>
                            </select>

                            {(searchName || filterRole) && (
                                <button onClick={() => { setSearchName(''); setFilterRole(''); }} className="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded-lg transition-colors whitespace-nowrap">
                                    ✕ Reset
                                </button>
                            )}
                        </div>

                        <span className="text-xs text-gray-500 whitespace-nowrap">
                            {filteredAccounts.length}/{accounts?.length || 0}
                        </span>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-gray-50 text-gray-500 text-sm border-b border-gray-100">
                                <th className="px-6 py-4 font-medium">Nama</th>
                                <th className="px-6 py-4 font-medium">Email</th>
                                <th className="px-6 py-4 font-medium">No Telp</th>
                                <th className="px-6 py-4 font-medium">Role</th>
                                <th className="px-6 py-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {isLoading ? (
                                <tr>
                                    <td colSpan="5" className="px-6 py-8 text-center text-gray-500">
                                        Loading data...
                                    </td>
                                </tr>
                            ) : error ? (
                                <tr>
                                    <td colSpan="5" className="px-6 py-8 text-center text-red-500">
                                        Gagal memuat data.
                                    </td>
                                </tr>
                            ) : accounts?.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="px-6 py-8 text-center text-gray-500">
                                        Belum ada data.
                                    </td>
                                </tr>
                            ) : filteredAccounts?.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="px-6 py-8 text-center text-gray-500">
                                        Tidak ada hasil yang sesuai.
                                    </td>
                                </tr>
                            ) : (
                                filteredAccounts?.map((user) => (
                                    <tr key={user.id} className="hover:bg-gray-50 transition-colors">
                                        <td className="px-6 py-4 text-sm text-gray-800 font-medium">{user.name}</td>
                                        <td className="px-6 py-4 text-sm text-gray-600">{user.email}</td>
                                        <td className="px-6 py-4 text-sm text-gray-600">{user.phone_number}</td>
                                        <td className="px-6 py-4 text-sm">
                                            <span className="px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-xs font-semibold capitalize">
                                                {user.role || 'User'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-sm flex gap-2">
                                            <button onClick={() => handleEditClick(user)} className="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors">
                                                <Edit className="w-4 h-4" />
                                            </button>
                                            <button onClick={() => handleDeleteClick(user)} className="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
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
                    <div className="bg-white rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold text-gray-800">Tambah Akun</h2>
                            <button onClick={() => setIsCreateModalOpen(false)} className="text-gray-400 hover:text-gray-600">
                                <X className="w-6 h-6" />
                            </button>
                        </div>

                        <form onSubmit={handleCreateSubmit} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Nama</label>
                                <input type="text" required value={formData.name} onChange={(e) => setFormData({...formData, name: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" required value={formData.email} onChange={(e) => setFormData({...formData, email: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">No Telp</label>
                                <input type="tel" required value={formData.phone_number} onChange={(e) => setFormData({...formData, phone_number: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Password</label>
                                <input type="password" required value={formData.password} onChange={(e) => setFormData({...formData, password: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                                <input type="password" required value={formData.password_confirmation} onChange={(e) => setFormData({...formData, password_confirmation: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                <select value={formData.role} onChange={(e) => setFormData({...formData, role: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="customer">Customer</option>
                                    <option value="mechanic">Mechanic</option>
                                    <option value="customer_service">Customer Service</option>
                                    <option value="admin">Admin</option>
                                </select>
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
                    <div className="bg-white rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold text-gray-800">Edit Akun</h2>
                            <button onClick={() => setIsEditModalOpen(false)} className="text-gray-400 hover:text-gray-600">
                                <X className="w-6 h-6" />
                            </button>
                        </div>

                        <form onSubmit={handleEditSubmit} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Nama</label>
                                <input type="text" required value={formData.name} onChange={(e) => setFormData({...formData, name: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" required value={formData.email} onChange={(e) => setFormData({...formData, email: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">No Telp</label>
                                <input type="tel" required value={formData.phone_number} onChange={(e) => setFormData({...formData, phone_number: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Password (Kosongkan jika tidak ingin mengubah)</label>
                                <input type="password" value={formData.password} onChange={(e) => setFormData({...formData, password: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                                <input type="password" value={formData.password_confirmation} onChange={(e) => setFormData({...formData, password_confirmation: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                <select value={formData.role} onChange={(e) => setFormData({...formData, role: e.target.value})} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="customer">Customer</option>
                                    <option value="mechanic">Mechanic</option>
                                    <option value="customer_service">Customer Service</option>
                                    <option value="admin">Admin</option>
                                </select>
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
                        <h2 className="text-lg font-bold text-gray-800 mb-4">Hapus Akun?</h2>
                        <p className="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus akun <span className="font-semibold">{selectedUser?.name}</span>? Tindakan ini tidak dapat dibatalkan.</p>
                        
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
