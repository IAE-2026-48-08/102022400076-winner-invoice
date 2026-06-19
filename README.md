# Winner & Invoice Service

Ini adalah repositori untuk service Winner & Invoice (Tugas 3 Integrasi Aplikasi Enterprise).

## Fitur & Integrasi Utama

1. **Autentikasi SSO**: Menggunakan JWT RS256 dengan mengambil public key secara dinamis dari JWKS server SSO Dosen.
2. **Audit SOAP**: Mengirimkan data transaksi secara sinkron ke server SOAP audit dosen menggunakan token M2M (menyertakan NIM mahasiswa). Dilengkapi fallback jika server SOAP mati.
3. **Event Publisher (RabbitMQ)**: Mengirimkan event `winner.invoice.created` saat invoice berhasil dibuat. Bisa menggunakan protokol AMQP langsung atau HTTP API.

---

## Cara Setup & Menjalankan Aplikasi

Ikuti langkah-langkah berikut untuk menjalankan project ini di lokal:

1. **Install dependensi**
   ```bash
   composer install
   ```

2. **Salin file konfigurasi env**
   ```bash
   copy .env.example .env
   ```

3. **Generate App Key**
   ```bash
   php artisan key:generate
   ```

4. **Migrasi Database & Seeding**
   ```bash
   php artisan migrate --seed
   ```

5. **Jalankan Server Lokal**
   ```bash
   php artisan serve
   ```

---

## Konfigurasi Penting di `.env`

Pastikan variabel-variabel berikut sudah disesuaikan di file `.env` Anda:

```env
SSO_BASE_URL=https://iae-sso.virtualfri.id
SSO_API_KEY=KEY-MHS-166
SSO_TEAM_ID=TEAM-02
SOAP_AUDIT_URL=https://iae-sso.virtualfri.id/soap/v1/audit
SSO_JWT_KEY=dosen_secret_key
SSO_JWT_ALGO=RS256

# Pilihan driver RabbitMQ: 'amqp' (lokal) atau 'http' (dosen gateway)
RABBITMQ_DRIVER=http
RABBITMQ_HOST=localhost
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_QUEUE=winner_invoice_queue
```

---

## File Dokumentasi Lainnya
*   **[analisis_tugas_3.md](file:///c:/Users/LOQ%2015IRX9/Documents/102022400076-winner-invoice/analisis_tugas_3.md)**: Berisi analisis transaksi kritis, diagram sekuens (Mermaid), dan penjelasan teknis integrasi.
*   **[PromptEngineeringLog.md](file:///c:/Users/LOQ%2015IRX9/Documents/102022400076-winner-invoice/PromptEngineeringLog.md)**: Log interaksi rekayasa prompt selama pengerjaan Tugas 3.
