import React, { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import apiClient from '@/api/client';
import { Users, Store, Wrench, ShoppingCart, ChevronDown } from 'lucide-react';
import {
    LineChart, Line, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer
} from 'recharts';

export default function Dashboard() {
    const [filterType, setFilterType] = useState('all');
    const [filterPeriod, setFilterPeriod] = useState('1-week');
    const [typeOpen, setTypeOpen] = useState(false);
    const [periodOpen, setPeriodOpen] = useState(false);

    const { data: dashboardData, isLoading, error } = useQuery({
        queryKey: ['adminDashboard', filterType, filterPeriod],
        queryFn: async () => {
            const res = await apiClient.get('/api/v1/admin/dashboard', {
                params: {
                    type: filterType,
                    period: filterPeriod
                }
            });
            return res.data?.data || {};
        }
    });

    const stats = [
        { name: 'Total Customers', value: dashboardData?.statistics?.total_customers || 0, icon: Users, color: 'bg-blue-100 text-blue-600' },
        { name: 'Total Mechanics', value: dashboardData?.statistics?.total_mechanics || 0, icon: Wrench, color: 'bg-green-100 text-green-600' },
        { name: 'Total CS', value: dashboardData?.statistics?.total_customer_service || 0, icon: Store, color: 'bg-purple-100 text-purple-600' },
        { name: 'Orders Completed Today', value: `${dashboardData?.statistics?.total_completed_today || 0}/${dashboardData?.statistics?.total_orders_today || 0}`, icon: ShoppingCart, color: 'bg-orange-100 text-orange-600' },
    ];

    // Transform order chart data untuk Recharts
    const chartData = dashboardData?.order_chart?.labels?.map((label, index) => ({
        name: label,
        orders: dashboardData?.order_chart?.counts?.[index] || 0
    })) || [];

    // Transform top services untuk horizontal bar chart
    const servicesData = dashboardData?.top_services?.map(service => ({
        name: service.service_name,
        usage: service.usage_count,
        price: service.base_price
    })) || [];

    const typeOptions = [
        { value: 'all', label: 'All Orders' },
        { value: 'normal', label: 'Normal Orders' },
        { value: 'emergency', label: 'Emergency Orders' }
    ];

    const periodOptions = [
        { value: '1-week', label: '1 Week' },
        { value: '1-month', label: '1 Month' },
        { value: '3-months', label: '3 Months' },
        { value: '6-months', label: '6 Months' },
        { value: '1-year', label: '1 Year' }
    ];

    return (
        <AdminLayout title="Dashboard">
            <Head title="Admin Dashboard" />

            {isLoading ? (
                <div className="flex items-center justify-center h-64">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>
            ) : error ? (
                <div className="p-4 bg-red-100 text-red-600 rounded-xl">
                    Error loading dashboard data. Please make sure the API is accessible.
                </div>
            ) : (
                <div className="space-y-6">
                    {/* Statistics - Full Width */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        {stats.map((stat) => (
                            <div key={stat.name} className="bg-white rounded-2xl shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
                                <div className={`p-4 rounded-xl ${stat.color}`}>
                                    <stat.icon className="w-6 h-6" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">{stat.name}</p>
                                    <h3 className="text-2xl font-bold text-gray-900">{stat.value}</h3>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Order Chart & Top Services */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Order Chart */}
                        <div className="bg-white rounded-2xl shadow-sm p-6">
                            <div className="mb-6">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="font-bold text-gray-800">Order Chart</h3>
                                </div>
                                <div className="flex gap-4 flex-wrap">
                                    {/* Type Filter Dropdown */}
                                    <div className="relative">
                                        <button
                                            onClick={() => setTypeOpen(!typeOpen)}
                                            className="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                                        >
                                            <span className="text-sm font-medium">
                                                {typeOptions.find(o => o.value === filterType)?.label}
                                            </span>
                                            <ChevronDown className={`w-4 h-4 transition-transform ${typeOpen ? 'rotate-180' : ''}`} />
                                        </button>
                                        {typeOpen && (
                                            <div className="absolute top-full left-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-max">
                                                {typeOptions.map((option) => (
                                                    <button
                                                        key={option.value}
                                                        onClick={() => {
                                                            setFilterType(option.value);
                                                            setTypeOpen(false);
                                                        }}
                                                        className={`block w-full text-left px-4 py-2 hover:bg-gray-100 ${
                                                            filterType === option.value ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700'
                                                        }`}
                                                    >
                                                        {option.label}
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                    </div>

                                    {/* Period Filter Dropdown */}
                                    <div className="relative">
                                        <button
                                            onClick={() => setPeriodOpen(!periodOpen)}
                                            className="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                                        >
                                            <span className="text-sm font-medium">
                                                {periodOptions.find(o => o.value === filterPeriod)?.label}
                                            </span>
                                            <ChevronDown className={`w-4 h-4 transition-transform ${periodOpen ? 'rotate-180' : ''}`} />
                                        </button>
                                        {periodOpen && (
                                            <div className="absolute top-full left-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-max">
                                                {periodOptions.map((option) => (
                                                    <button
                                                        key={option.value}
                                                        onClick={() => {
                                                            setFilterPeriod(option.value);
                                                            setPeriodOpen(false);
                                                        }}
                                                        className={`block w-full text-left px-4 py-2 hover:bg-gray-100 ${
                                                            filterPeriod === option.value ? 'bg-green-50 text-green-600 font-medium' : 'text-gray-700'
                                                        }`}
                                                    >
                                                        {option.label}
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {chartData.length > 0 ? (
                                <ResponsiveContainer width="100%" height={300}>
                                    <LineChart data={chartData} margin={{ top: 5, right: 30, left: 0, bottom: 5 }}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="name" />
                                        <YAxis />
                                        <Tooltip />
                                        <Legend />
                                        <Line
                                            type="monotone"
                                            dataKey="orders"
                                            stroke="#3b82f6"
                                            strokeWidth={2}
                                            dot={{ fill: '#3b82f6', r: 4 }}
                                            activeDot={{ r: 6 }}
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex items-center justify-center h-64 bg-gray-50 rounded-xl">
                                    <p className="text-gray-400">No data available for this period</p>
                                </div>
                            )}
                        </div>

                        {/* Top Services Bar Chart */}
                        <div className="bg-white rounded-2xl shadow-sm p-6">
                            <h3 className="font-bold text-gray-800 mb-4">Top Services (by usage)</h3>
                            {servicesData.length > 0 ? (
                                <ResponsiveContainer width="100%" height={300}>
                                    <BarChart
                                        data={servicesData}
                                        layout="vertical"
                                        margin={{ top: 5, right: 30, left: 150, bottom: 5 }}
                                    >
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis type="number" />
                                        <YAxis type="category" dataKey="name" width={140} fontSize={12} />
                                        <Tooltip />
                                        <Bar dataKey="usage" fill="#8b5cf6" name="Usage Count" />
                                    </BarChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex items-center justify-center h-64 bg-gray-50 rounded-xl">
                                    <p className="text-gray-400">No services data available</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Latest Activities & Latest Orders */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Latest Activities Table */}
                        <div className="bg-white rounded-2xl shadow-sm p-6">
                            <h3 className="font-bold text-gray-800 mb-4">Latest Activities</h3>
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead>
                                        <tr className="bg-gray-50 border-b border-gray-200">
                                            <th className="px-4 py-3 font-medium text-gray-700">Type</th>
                                            <th className="px-4 py-3 font-medium text-gray-700">Description</th>
                                            <th className="px-4 py-3 font-medium text-gray-700">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200">
                                        {dashboardData?.latest_activities && dashboardData.latest_activities.length > 0 ? (
                                            dashboardData.latest_activities.map((activity, idx) => (
                                                <tr key={idx} className="hover:bg-gray-50">
                                                    <td className="px-4 py-3">
                                                        <span className="px-2 py-1 bg-blue-100 text-blue-600 rounded text-xs font-semibold whitespace-nowrap">
                                                            {activity.type.replace('_', ' ').toUpperCase()}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-gray-600">{activity.description}</td>
                                                    <td className="px-4 py-3 text-gray-600 whitespace-nowrap">
                                                        {new Date(activity.timestamp).toLocaleString('id-ID', { 
                                                            year: 'numeric',
                                                            month: 'short',
                                                            day: 'numeric',
                                                            hour: '2-digit',
                                                            minute: '2-digit'
                                                        })}
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="3" className="px-4 py-8 text-center text-gray-500">No activities yet</td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* Latest Orders Table */}
                        <div className="bg-white rounded-2xl shadow-sm p-6">
                            <h3 className="font-bold text-gray-800 mb-4">Latest Orders</h3>
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead>
                                        <tr className="bg-gray-50 border-b border-gray-200">
                                            <th className="px-4 py-3 font-medium text-gray-700">Order ID</th>
                                            <th className="px-4 py-3 font-medium text-gray-700">Customer</th>
                                            <th className="px-4 py-3 font-medium text-gray-700">Status</th>
                                            <th className="px-4 py-3 font-medium text-gray-700">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200">
                                        {dashboardData?.latest_orders && dashboardData.latest_orders.length > 0 ? (
                                            dashboardData.latest_orders.map((order) => (
                                                <tr key={order.id} className="hover:bg-gray-50">
                                                    <td className="px-4 py-3 font-bold text-blue-600">#{order.id}</td>
                                                    <td className="px-4 py-3 text-gray-800">{order.customer_name}</td>
                                                    <td className="px-4 py-3">
                                                        <span className={`px-2 py-1 rounded text-xs font-semibold whitespace-nowrap ${
                                                            order.status === 'completed' ? 'bg-green-100 text-green-600' :
                                                            order.status === 'pending' ? 'bg-yellow-100 text-yellow-600' :
                                                            order.status === 'in_progress' ? 'bg-blue-100 text-blue-600' :
                                                            order.status === 'cancelled' ? 'bg-red-100 text-red-600' :
                                                            'bg-gray-100 text-gray-600'
                                                        }`}>
                                                            {order.status.replace('_', ' ').toUpperCase()}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-gray-600 whitespace-nowrap">
                                                        {new Date(order.created_at).toLocaleString('id-ID', {
                                                            year: 'numeric',
                                                            month: 'short',
                                                            day: 'numeric',
                                                            hour: '2-digit',
                                                            minute: '2-digit'
                                                        })}
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="4" className="px-4 py-8 text-center text-gray-500">No orders yet</td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
