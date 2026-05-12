import React, { useState } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import axios from 'axios';

export default function ForgotPassword() {
    const { data, setData, errors, setError, clearErrors } = useForm({
        email: '',
    });
    const [processing, setProcessing] = useState(false);
    const [status, setStatus] = useState(null);

    const submit = async (e) => {
        e.preventDefault();
        clearErrors();
        setProcessing(true);
        setStatus(null);

        try {
            const response = await axios.post('/auth/forgot-password', data);
            setStatus(response.data.message || 'OTP telah dikirim ke email Anda.');
            toast.success('Berhasil mengirim OTP!');
            // Redirect to reset password page to enter OTP
            setTimeout(() => {
                window.location.href = `/auth/reset-password?email=${encodeURIComponent(data.email)}`;
            }, 2000);
        } catch (error) {
            if (error.response && error.response.data.errors) {
                const apiErrors = error.response.data.errors;
                for (const field in apiErrors) {
                    setError(field, apiErrors[field][0]);
                }
            } else {
                toast.error(error.response?.data?.message || 'Gagal mengirim OTP.');
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <GuestLayout title="Forgot Password" subtitle="Lupa password Anda? Tidak masalah. Beri tahu kami alamat email Anda dan kami akan mengirimi Anda kode OTP untuk memilih yang baru.">
            <Head title="Forgot Password" />

            {status && <div className="mb-4 font-medium text-sm text-success text-center">{status}</div>}

            <form onSubmit={submit} className="mt-8">
                <div>
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        isFocused={true}
                        placeholder="Email ID"
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="flex flex-col items-center justify-center mt-10 space-y-4">
                    <PrimaryButton className="w-full" disabled={processing}>
                        SEND OTP
                    </PrimaryButton>
                    
                    <div className="text-sm mt-4">
                        <Link href="/auth/login" className="text-secondary hover:text-primary font-medium">
                            Back to login
                        </Link>
                    </div>
                </div>
            </form>
        </GuestLayout>
    );
}
