import React, { useState } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import axios from 'axios';

export default function Register() {
    const { data, setData, errors, setError, clearErrors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });
    const [processing, setProcessing] = useState(false);

    const submit = async (e) => {
        e.preventDefault();
        clearErrors();
        setProcessing(true);

        try {
            const response = await axios.post('/auth/register', data);
            toast.success('Registrasi berhasil! Silakan login.');
            window.location.href = '/auth/login';
        } catch (error) {
            if (error.response && error.response.data.errors) {
                const apiErrors = error.response.data.errors;
                for (const field in apiErrors) {
                    setError(field, apiErrors[field][0]);
                }
            } else {
                toast.error(error.response?.data?.message || 'Registrasi gagal.');
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <GuestLayout title="Create Account" subtitle="Daftar untuk mulai mengelola bengkel Anda.">
            <Head title="Register" />

            <form onSubmit={submit} className="mt-8">
                <div>
                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        className="mt-1 block w-full"
                        autoComplete="name"
                        isFocused={true}
                        placeholder="Full Name"
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div className="mt-6">
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        placeholder="Email ID"
                        onChange={(e) => setData('email', e.target.value)}
                        required
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
                        autoComplete="new-password"
                        placeholder="Password"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-6">
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        placeholder="Confirm Password"
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        required
                    />
                    <InputError message={errors.password_confirmation} className="mt-2" />
                </div>

                <div className="flex flex-col items-center justify-center mt-10 space-y-4">
                    <PrimaryButton className="w-full" disabled={processing}>
                        REGISTER
                    </PrimaryButton>
                    
                    <div className="text-sm mt-4">
                        <span className="text-gray-500">Already a member? </span>
                        <Link href="/auth/login" className="text-secondary hover:text-primary font-medium">
                            Login here
                        </Link>
                    </div>
                </div>
            </form>
        </GuestLayout>
    );
}
