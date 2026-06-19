# Catatan Chat Tanya-Jawab (Tugas 3)

Berikut adalah riwayat obrolan/diskusi saya dengan ChatGPT / Claude dan Gemini pas lagi ngerjain dan nyelesaiin perbaikan tugas 3.

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
> "Pas bikin user di database lokal lewat seeder, kan password-nya harus di-hash. Bagusnya pake Bcrypt atau Hash::make? Terus bedanya apa?"

**Hasil:**
Penjelasan kalau `Hash::make()` di Laravel secara default sudah menggunakan Bcrypt, jadi aman dan disarankan langsung digunakan.

---

**Tanya:**
> "Udah masuk datanya. Sekarang saya bingung bagian SSO. Cara bikin middleware di Laravel buat ngecek token JWT dari header Authorization Bearer gimana ya? Untuk sementara pake HS256 lokal dulu aja biar gampang dicoba."

**Hasil:**
Dibuatkan file middleware `VerifyJwtToken.php` dasar untuk verifikasi token simetris HS256.

---

**Tanya:**
> "Kok pas saya coba masukin token dari SSO dosen ke middleware, malah muncul error 'Signature verification failed' ya? Padahal tokennya gak expired. Apa karena SSO dosen gak pake HS256?"

**Hasil:**
Penjelasan kalau SSO dosen menggunakan algoritma asimetris RS256, jadi verifikasinya butuh public key dari server SSO, bukan pake secret key lokal.

---

**Tanya:**
> "Oh pantesan error. Terus gimana caranya biar middleware Laravel saya bisa ambil public key secara dinamis dari URL JWKS dosen di https://iae-sso.virtualfri.id/api/v1/auth/jwks? Saya pusing cara nge-decode modulus 'n' sama eksponen 'e' dari JWKS itu biar jadi public key PEM."

**Hasil:**
Diberikan fungsi helper base64UrlDecode dan cara merakit public key PEM menggunakan OpenSSL PHP agar tanda tangan RS256 bisa diverifikasi.

---

**Tanya:**
> "Di middleware SSO, setelah saya berhasil verifikasi token JWT dan dapet data user (nama & email), data itu harus diapain ya? Soalnya di database lokal saya belum tentu ada user itu."

**Hasil:**
Diberikan solusi untuk mencocokkan email di database lokal. Kalau belum ada, otomatis daftarkan (register) secara real-time, lalu kaitkan session login-nya.

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
> "Di audit SOAP, kenapa isi XML payload-nya ada tag `<![CDATA[ ... ]]>`? Fungsinya buat apa ya? Gak bisa langsung ditaruh teks JSON biasa aja?"

**Hasil:**
Penjelasan kalau tag CDATA dipakai agar karakter khusus (seperti tanda petik, kurung kurawal, atau tanda kurang/lebih dari) di payload JSON tidak dibaca sebagai tag XML baru oleh parser XML server dosen.

---

**Tanya:**
> "Saya coba login di web, tapi kok muncul 'SOAP Audit: Gagal (HTTP 403)' di dashboard ya? Salahnya di mana ya? Saya kirimnya pake token bearer user biasa."

**Hasil:**
Penjelasan kalau SOAP Audit butuh token Machine-to-Machine (M2M) dengan menyertakan parameter `nim` (102022400076) di body request token `/api/v1/auth/token`.

---

**Tanya:**
> "Kalau misalnya server SOAP dosen lagi down atau mati, apa transaksi checkout lelang di web saya harus ikutan error dan gagal? Cara bikin fallback-nya biar transaksi tetep sukses gimana ya?"

**Hasil:**
Diberikan logika fallback (fail-safe) di mana jika request SOAP gagal/timeout, sistem otomatis men-generate receipt number tiruan (`REC-SOAP-FALLBACK-...`) agar database transaction tetap bisa dicommit.

---

**Tanya:**
> "Oh, harus pake token M2M sama masukin NIM di body request ya. Oke, itu udah jalan. Sekarang bagian RabbitMQ. Saya udah install php-amqplib di Laravel. Cara ngirim event pemenang lelang ke port 5672 lokal secara asinkron gimana ya?"

**Hasil:**
Mendapatkan contoh implementasi socket `AMQPStreamConnection` dan cara publish message JSON.

---

**Tanya:**
> "Di laptop saya gak ada RabbitMQ lokal, jadi pas saya coba tes kodenya selalu error connection refused. Ada gak cara biar saya tetep bisa ngetes jalan atau tidaknya tanpa harus install RabbitMQ lokal?"

**Hasil:**
Diberikan solusi membuat driver ganda. Kita bisa pakai driver HTTP untuk publish ke REST API gateway dosen di `/api/v1/messages/publish` yang tidak butuh koneksi socket port 5672 lokal.

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
