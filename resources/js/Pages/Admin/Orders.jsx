import React, { useState, useMemo } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiClient from '@/api/client';
import { Eye, Trash2, X, Search } from 'lucide-react';
import { toast } from 'sonner';

export default function Orders() {
    const queryClient = useQueryClient();
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState(null);
    const [searchCustomer, setSearchCustomer] = useState('');
    const [filterStatus, setFilterStatus] = useState('');

    const { data: orders, isLoading, error } = useQuery({
        queryKey: ['adminOrders'],
        queryFn: async () => {
            const res = await apiClient.get('/api/v1/admin/orders');
            return res.data?.data?.data || res.data?.data || [];
        }
    });

    // Delete Mutation
    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            const res = await apiClient.delete(`/api/v1/admin/orders/${id}`);
            return res.data;
        },
        onSuccess: () => {
            toast.success('Order berhasil dihapus');
            queryClient.invalidateQueries({ queryKey: ['adminOrders'] });
            setIsDeleteModalOpen(false);
            setSelectedOrder(null);
        },
        onError: (error) => {
            const message = error.response?.data?.message || 'Gagal menghapus order';
            toast.error(message);
        }
    });

    const handleDeleteClick = (order) => {
        setSelectedOrder(order);
        setIsDeleteModalOpen(true);
    };

    const handleDeleteConfirm = () => {
        deleteMutation.mutate(selectedOrder.id);
    };

    // Filter dan search logic
    const filteredOrders = useMemo(() => {
        return orders?.filter((order) => {
            const matchesCustomer = order.customer?.name?.toLowerCase().includes(searchCustomer.toLowerCase()) || 
                                   order.id?.toString().includes(searchCustomer);
            const matchesStatus = filterStatus === '' || order.status === filterStatus;
            return matchesCustomer && matchesStatus;
        }) || [];
    }, [orders, searchCustomer, filterStatus]);

    return (
        <AdminLayout title="Manajemen Orders">
            <Head title="Manajemen Orders" />

            <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div className="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 className="font-bold text-gray-800">Daftar Transaksi / Order</h3>
                </div>

                {/* Filter dan Search */}
                <div className="p-4 border-b border-gray-100 bg-gray-50">
                    <div className="flex flex-col sm:flex-row gap-3 items-center justify-between flex-wrap">
                        <div className="flex gap-2 items-center flex-wrap">
                            <div className="relative w-full sm:w-64">
                                <Search className="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
                                <input type="text" placeholder="Cari customer atau ID..." value={searchCustomer} onChange={(e) => setSearchCustomer(e.target.value)} className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-300 shadow-sm hover:border-gray-300 transition-all" />
                            </div>

                            <select value={filterStatus} onChange={(e) => setFilterStatus(e.target.value)} className="px-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-300 bg-white hover:border-gray-300 transition-all shadow-sm">
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>

                            {(searchCustomer || filterStatus) && (
                                <button onClick={() => { setSearchCustomer(''); setFilterStatus(''); }} className="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded-lg transition-colors whitespace-nowrap">
                                    ✕ Reset
                                </button>
                            )}
                        </div>

                        <span className="text-xs text-gray-500 whitespace-nowrap">
                            {filteredOrders.length}/{orders?.length || 0}
                        </span>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-gray-50 text-gray-500 text-sm border-b border-gray-100">
                                <th className="px-6 py-4 font-medium">Order ID</th>
                                <th className="px-6 py-4 font-medium">Pelanggan</th>
                                <th className="px-6 py-4 font-medium">Mekanik</th>
                                <th className="px-6 py-4 font-medium">Status</th>
                                <th className="px-6 py-4 font-medium">Total</th>
                                <th className="px-6 py-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {isLoading ? (
                                <tr>
                                    <td colSpan="6" className="px-6 py-8 text-center text-gray-500">Loading data...</td>
                                </tr>
                            ) : error ? (
                                <tr>
                                    <td colSpan="6" className="px-6 py-8 text-center text-red-500">Gagal memuat data.</td>
                                </tr>
                            ) : orders?.length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="px-6 py-8 text-center text-gray-500">Belum ada data.</td>
                                </tr>
                            ) : filteredOrders?.length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="px-6 py-8 text-center text-gray-500">Tidak ada hasil yang sesuai.</td>
                                </tr>
                            ) : (
                                filteredOrders?.map((order) => (
                                    <tr key={order.id} className="hover:bg-gray-50 transition-colors">
                                        <td className="px-6 py-4 text-sm font-bold text-blue-600">#{order.id}</td>
                                        <td className="px-6 py-4 text-sm text-gray-800">{order.customer?.name || '-'}</td>
                                        <td className="px-6 py-4 text-sm text-gray-600">{order.mechanic?.user?.name || '-'}</td>
                                        <td className="px-6 py-4 text-sm">
                                            <span className={`px-3 py-1 rounded-full text-xs font-semibold capitalize ${
                                                order.status === 'completed' ? 'bg-green-100 text-green-600' :
                                                order.status === 'pending' ? 'bg-yellow-100 text-yellow-600' :
                                                order.status === 'cancelled' ? 'bg-red-100 text-red-600' :
                                                order.status === 'in_progress' ? 'bg-blue-100 text-blue-600' :
                                                'bg-gray-100 text-gray-600'
                                            }`}>
                                                {order.status || 'Pending'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                            Rp {order.total_price?.toLocaleString() || '0'}
                                        </td>
                                        <td className="px-6 py-4 text-sm flex gap-2">
                                            <button className="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">
                                                <Eye className="w-4 h-4" />
                                            </button>
                                            <button onClick={() => handleDeleteClick(order)} className="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
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
                        <h2 className="text-lg font-bold text-gray-800 mb-4">Hapus Order?</h2>
                        <p className="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus order <span className="font-semibold">#{selectedOrder?.id}</span> atas nama <span className="font-semibold">{selectedOrder?.customer?.name}</span>? Tindakan ini tidak dapat dibatalkan.</p>
                        
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
