# Catatan Chat Tanya-Jawab (Tugas 3)

Berikut adalah riwayat obrolan/diskusi saya dengan ChatGPT / Gemini pas lagi ngerjain dan nyelesaiin perbaikan tugas 3
---

**Tanya:**
> "saya lagi dapet tugas bikin web lelang (Winner & Invoice) pake Laravel. Masih bingung mau bikin database-nya. Bagusnya struktur migrasi buat tabel `winners` (nyimpan id barang, id user, harga menang) sama tabel `invoices` (relasi ke winner, receipt number audit, nominal, status) kayak gimana ya?"

**Hasil:**
Dapat rancangan migrasi database SQLite untuk tabel `winners` dan `invoices` yang saling terhubung.

---

**Tanya:**
> "Oke makasih. Terus saya mau nyoba seeder Laravel buat masukin data user testing. Nama saya Raqieza Walloaz (NIM: 102022400076). Cara nulis di DatabaseSeeder.php nya gimana ya biar user saya langsung masuk ke db?"

**Hasil:**
Dibuatkan kode DatabaseSeeder.php untuk membuat user testing secara otomatis.

---

**Tanya:**
> "Udah masuk datanya. Sekarang saya bingung bagian SSO. Cara bikin middleware di Laravel buat ngecek token JWT dari header Authorization Bearer gimana ya? Untuk sementara pake HS256 lokal dulu aja biar gampang dicoba."

**Hasil:**
Dibuatkan file middleware `VerifyJwtToken.php` dasar untuk verifikasi token simetris HS256.

---

**Tanya:**
> "Ternyata pas dicoba, SSO dari dosen pake token RS256 dan public key-nya ada di URL JWKS https://iae-sso.virtualfri.id/api/v1/auth/jwks. Saya pusing cara ngubah middleware-nya biar bisa ambil public key secara dinamis dari URL itu terus dicocokin sama 'kid' di token. Ada cara gampangnya?"

**Hasil:**
Diberikan logika penarikan JWKS dan parsing modulus/eksponen menjadi public key PEM dengan OpenSSL PHP.

---

**Tanya:**
> "Kalau tiap request harus fetch JWKS terus, webnya jadi lambat banget ya? Terus pas saya jalanin unit test pake phpunit, malah error semua karena token test lokal saya pake HS256. Biar dua-duanya bisa jalan gimana ya?"

**Hasil:**
Diberikan solusi menggunakan Cache Laravel selama 24 jam untuk menyimpan JWKS, serta support dual-mode (RS256 & HS256) di middleware.

---

**Tanya:**
> "Oke, verifikasi token aman. Sekarang bagian SOAP Audit. Saya disuruh kirim data audit pas checkout lelang ke server SOAP dosen. Tapi formatnya harus XML SOAP dengan namespace http://iae.central/audit. Saya bingung cara nyusun request XML-nya di Laravel tanpa pake soap php."

**Hasil:**
Dibuatkan modul parser XML manual di `SoapAuditService.php` menggunakan raw body HTTP POST.

---

**Tanya:**
> "Saya coba login di web, tapi kok muncul 'SOAP Audit: Gagal (HTTP 403)' di dashboard ya? Salahnya di mana ya? Saya kirimnya pake token bearer user biasa."

**Hasil:**
Penjelasan kalau SOAP Audit butuh token Machine-to-Machine (M2M) dengan menyertakan parameter `nim` (102022400076) di body request token `/api/v1/auth/token`.

---

**Tanya:**
> "Oh, harus pake token M2M sama masukin NIM di body request ya. Oke, itu udah jalan. Sekarang bagian RabbitMQ. Saya udah install php-amqplib di Laravel. Cara ngirim event pemenang lelang ke port 5672 lokal secara asinkron gimana ya?"

**Hasil:**
Mendapatkan contoh implementasi socket `AMQPStreamConnection` dan cara publish message JSON.

---

**Tanya:**
> "Udah bisa kirim. Terus gimana caranya biar driver RabbitMQ-nya bisa diganti-ganti lewat file .env? Jadi di lokal bisa pake 'amqp' (lokal) tapi kalau dideploy bisa pake 'http' (gateway dosen)."

**Hasil:**
Menambahkan konfigurasi driver `RABBITMQ_DRIVER` di `.env` dan `config/services.php` agar driver bisa diganti dinamis.

---

**Tanya:**
> "Saya udah coba checkout baru di web dan sukses, tapi di dashboard RabbitMQ dosen kok event-nya gak muncul ya? Yang ada cuma event user.login aja."

**Hasil:**
Penjelasan kalau driver di `.env` lokal harus diubah ke `RABBITMQ_DRIVER=http` agar masuk ke dosen, serta menyesuaikan nama event menjadi `winner.invoice.created`.

---

**Tanya:**
> "Ternyata di .env lokal saya masih pake driver amqp, makanya gak masuk ke dosen. Udah saya ganti http sekarang. Tapi pas saya jalanin php artisan test, test-nya malah error karena nyoba konek ke RabbitMQ lokal yang mati. Cara ngatasinnya gimana ya?"

**Hasil:**
Diberikan solusi menambahkan override `<env name="RABBITMQ_DRIVER" value="http"/>` di file `phpunit.xml`.

---

**Tanya:**
> "Udah bisa lewat, test-nya hijau semua. Terakhir, saya harus bikin laporan analisis_tugas_3.md. Bisa tolong bikinin contoh Sequence Diagram pake Mermaid.js yang ngejelasin alur SSO, DB transaksi, SOAP, sama RabbitMQ ini?"

**Hasil:**
Mendapatkan Sequence Diagram Mermaid.js yang menggambarkan alur data dari verifikasi token hingga publish event.
