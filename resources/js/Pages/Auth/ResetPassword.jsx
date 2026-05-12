import React, { useEffect, useState } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import axios from 'axios';

export default function ResetPassword() {
    const queryParams = new URLSearchParams(window.location.search);
    const emailParam = queryParams.get('email') || '';

    const { data, setData, errors, setError, clearErrors } = useForm({
        email: emailParam,
        otp: '',
        password: '',
        password_confirmation: '',
    });
    const [processing, setProcessing] = useState(false);

    const [otpVerified, setOtpVerified] = useState(false);
    const [token, setToken] = useState('');

    const verifyOtp = async (e) => {
        e.preventDefault();
        clearErrors();
        setProcessing(true);

        try {
            const response = await axios.post('/auth/verify-otp', {
                email: data.email,
                otp: data.otp
            });
            toast.success('OTP berhasil diverifikasi!');
            setToken(response.data.token || 'verified'); // Save token if backend provides one for reset
            setOtpVerified(true);
        } catch (error) {
            if (error.response && error.response.data.errors) {
                const apiErrors = error.response.data.errors;
                for (const field in apiErrors) {
                    setError(field, apiErrors[field][0]);
                }
            } else {
                toast.error(error.response?.data?.message || 'OTP tidak valid.');
            }
        } finally {
            setProcessing(false);
        }
    };

    const resetPassword = async (e) => {
        e.preventDefault();
        clearErrors();
        setProcessing(true);

        try {
            await axios.post('/auth/reset-password', {
                email: data.email,
                otp: data.otp,
                password: data.password,
                password_confirmation: data.password_confirmation,
                token: token
            });
            toast.success('Password berhasil diubah!');
            window.location.href = '/auth/login';
        } catch (error) {
            if (error.response && error.response.data.errors) {
                const apiErrors = error.response.data.errors;
                for (const field in apiErrors) {
                    setError(field, apiErrors[field][0]);
                }
            } else {
                toast.error(error.response?.data?.message || 'Gagal mengubah password.');
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <GuestLayout title="Reset Password" subtitle={!otpVerified ? "Masukkan kode OTP yang dikirim ke email Anda." : "Masukkan password baru Anda."}>
            <Head title="Reset Password" />

            {!otpVerified ? (
                <form onSubmit={verifyOtp} className="mt-8">
                    <div>
                        <TextInput
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            className="mt-1 block w-full"
                            placeholder="Email ID"
                            onChange={(e) => setData('email', e.target.value)}
                            readOnly={!!emailParam}
                        />
                        <InputError message={errors.email} className="mt-2" />
                    </div>

                    <div className="mt-6">
                        <TextInput
                            id="otp"
                            type="text"
                            name="otp"
                            value={data.otp}
                            className="mt-1 block w-full tracking-widest text-center"
                            placeholder="X X X X X X"
                            isFocused={true}
                            onChange={(e) => setData('otp', e.target.value)}
                        />
                        <InputError message={errors.otp} className="mt-2" />
                    </div>

                    <div className="mt-10">
                        <PrimaryButton className="w-full" disabled={processing}>
                            VERIFY OTP
                        </PrimaryButton>
                    </div>
                </form>
            ) : (
                <form onSubmit={resetPassword} className="mt-8">
                    <div>
                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className="mt-1 block w-full"
                            placeholder="New Password"
                            isFocused={true}
                            onChange={(e) => setData('password', e.target.value)}
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
                            placeholder="Confirm New Password"
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                        />
                        <InputError message={errors.password_confirmation} className="mt-2" />
                    </div>

                    <div className="mt-10">
                        <PrimaryButton className="w-full" disabled={processing}>
                            RESET PASSWORD
                        </PrimaryButton>
                    </div>
                </form>
            )}
        </GuestLayout>
    );
}
