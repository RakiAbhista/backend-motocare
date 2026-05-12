import React, { useState } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import axios from 'axios';

export default function Login() {
    const { data, setData, errors, setError, clearErrors } = useForm({
        email: '',
        password: '',
        remember: false,
    });
    const [processing, setProcessing] = useState(false);

    const submit = async (e) => {
        e.preventDefault();
        clearErrors();
        setProcessing(true);

        try {
            const response = await axios.post('/auth/login', data);
            
            if (response.data && response.data.access_token) {
                localStorage.setItem('token', response.data.access_token);
                
                // Optional: Save user data for easier access
                if (response.data.user) {
                    localStorage.setItem('user', JSON.stringify(response.data.user));
                }
                
                axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.access_token}`;
                toast.success('Login berhasil!');
                
                // Redirect based on role
                const userRole = response.data.user?.role?.toLowerCase();
                
                setTimeout(() => {
                    if (userRole === 'admin' || userRole === 'owner') {
                        window.location.href = '/admin/dashboard';
                    } else if (userRole === 'customer_service' || userRole === 'cs') {
                        window.location.href = '/customer-service'; // Ubah sesuai route yang ada
                    } else if (userRole === 'mechanic' || userRole === 'mekanik') {
                        window.location.href = '/mechanic'; // Ubah sesuai route yang ada
                    } else {
                        window.location.href = '/'; // Default redirect untuk Customer
                    }
                }, 800);
            }
        } catch (error) {
            if (error.response && error.response.data.errors) {
                // Loop through object and set each error
                const apiErrors = error.response.data.errors;
                for (const field in apiErrors) {
                    setError(field, apiErrors[field][0]);
                }
            } else {
                toast.error(error.response?.data?.message || 'Login gagal, periksa kredensial Anda.');
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <GuestLayout title="Login Account" subtitle="Silakan masukkan kredensial akun Anda.">
            <Head title="Log in" />

            <form onSubmit={submit} className="mt-8">
                <div>
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        isFocused={true}
                        placeholder="Email ID"
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-6">
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        placeholder="Password"
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="flex items-center justify-between mt-6">
                    <label className="flex items-center">
                        <input
                            type="checkbox"
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                            className="rounded border-gray-300 text-primary shadow-sm focus:ring-primary"
                        />
                        <span className="ms-2 text-sm text-gray-600">Keep me signed in</span>
                    </label>

                    <Link
                        href="/auth/forgot-password"
                        className="underline text-sm text-secondary hover:text-primary rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                    >
                        Forgot password?
                    </Link>
                </div>

                <div className="flex flex-col items-center justify-center mt-10 space-y-4">
                    <PrimaryButton className="w-full" disabled={processing}>
                        LOGIN
                    </PrimaryButton>
                    
                    <div className="text-sm mt-4">
                        <span className="text-gray-500">Not a member yet? </span>
                        <Link href="/auth/register" className="text-secondary hover:text-primary font-medium">
                            Register now
                        </Link>
                    </div>
                </div>
            </form>
        </GuestLayout>
    );
}
