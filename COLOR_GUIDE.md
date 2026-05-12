# Motocare Color System Guide

Dokumen ini berisi panduan penggunaan warna (Color System) untuk aplikasi Motocare agar konsisten di berbagai platform (Web & Android).

## 🎨 Brand Colors (Core)

| Name | Hex Code | Preview | Usage |
| :--- | :--- | :---: | :--- |
| **Primary** | `#3564C4` | ![](https://singlecolorimage.com/get/3564C4/40x40) | Warna utama untuk tombol, header, dan elemen branding. |
| **Primary Light** | `#E8EEF8` | ![](https://singlecolorimage.com/get/E8EEF8/40x40) | Background elemen aktif, hover state, atau kartu sekunder. |
| **Secondary** | `#119CFF` | ![](https://singlecolorimage.com/get/119CFF/40x40) | Warna aksen, link, dan elemen interaktif pendukung. |
| **Background** | `#F0F7FF` | ![](https://singlecolorimage.com/get/F0F7FF/40x40) | Warna latar belakang halaman utama. |

## 🚦 Semantic Colors (Feedback)

| Name | Hex Code | Preview | Usage |
| :--- | :--- | :---: | :--- |
| **Success** | `#10B981` | ![](https://singlecolorimage.com/get/10B981/40x40) | Indikator status berhasil, selesai, atau aktif. |
| **Warning** | `#F59E0B` | ![](https://singlecolorimage.com/get/F59E0B/40x40) | Indikator peringatan atau proses yang tertunda. |
| **Danger** | `#EF4444` | ![](https://singlecolorimage.com/get/EF4444/40x40) | Indikator kesalahan, pembatalan, atau aksi kritis. |

---

## 🛠 Implementation (Web / Tailwind CSS)

Warna-warna ini sudah dikonfigurasi di `app.css`. Anda bisa menggunakannya dengan class Tailwind:

```html
<!-- Background -->
<div class="bg-primary text-white">Button</div>
<div class="bg-primary-light text-primary">Badge</div>
<div class="bg-background">Page Wrapper</div>

<!-- Text -->
<p class="text-secondary">Informasi Pendukung</p>
<p class="text-danger">Terjadi kesalahan!</p>

<!-- Border & Ring -->
<input class="border-primary focus:ring-primary" />
```

---

## 📱 Implementation (Android)

Untuk penerapan di Android, gunakan format berikut sesuai dengan metode pengembangan Anda:

### 1. Jetpack Compose (Kotlin)
Tambahkan ke `Color.kt`:

```kotlin
val Primary = Color(0xFF3564C4)
val PrimaryLight = Color(0xFFE8EEF8)
val Secondary = Color(0xFF119CFF)
val Background = Color(0xFFF0F7FF)

val Success = Color(0xFF10B981)
val Warning = Color(0xFFF59E0B)
val Danger = Color(0xFFEF4444)
```

### 2. XML (View System)
Tambahkan ke `res/values/colors.xml`:

```xml
<resources>
    <color name="primary">#3564C4</color>
    <color name="primary_light">#E8EEF8</color>
    <color name="secondary">#119CFF</color>
    <color name="background">#F0F7FF</color>
    
    <color name="success">#10B981</color>
    <color name="warning">#F59E0B</color>
    <color name="danger">#EF4444</color>
</resources>
```

---

## 💡 Tips Penggunaan
1. **Kontras**: Gunakan teks putih (`#FFFFFF`) di atas background `Primary` untuk aksesibilitas yang baik.
2. **Konsistensi**: Hindari menggunakan warna Hex secara hardcoded di dalam komponen. Gunakan variabel yang sudah disediakan.
3. **Hierarchy**: Gunakan `Primary` untuk aksi utama dan `Secondary` atau `Primary Light` untuk aksi tambahan.
