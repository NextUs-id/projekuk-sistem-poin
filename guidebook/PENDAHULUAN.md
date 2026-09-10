# BAB 1. PENDAHULUAN

## 1.1 Latar Belakang
Kedisiplinan merupakan elemen fundamental dalam proses pembentukan karakter siswa di lingkungan pendidikan. Di SMK TI Bali Global Denpasar, pemantauan terhadap perilaku dan kepatuhan siswa terhadap tata tertib sekolah menjadi prioritas utama Guru Bimbingan Konseling (BK). Namun, proses pengelolaan data kedisiplinan yang masih bersifat konvensional atau manual memiliki berbagai kelemahan, di antaranya:

*   **Inefisiensi Waktu:** Pencatatan di buku fisik memerlukan waktu lama untuk direkapitulasi secara berkala.
*   **Risiko Kehilangan Data:** Dokumen fisik rentan terhadap kerusakan atau kehilangan.
*   **Kurangnya Transparansi:** Siswa seringkali tidak mengetahui akumulasi poin mereka secara tepat waktu hingga surat peringatan diterbitkan.
*   **Lambatnya Administrasi:** Pembuatan surat panggilan orang tua atau surat perjanjian harus diketik ulang satu per satu, yang memperlambat tindakan preventif sekolah.

Oleh karena itu, dikembangkanlah **Sipoin (Sistem Poin Pelanggaran Siswa)**. Aplikasi berbasis web ini dirancang untuk mendigitalisasi seluruh alur kedisiplinan, mulai dari input pelanggaran oleh guru, otomatisasi penentuan level (Hijau/Kuning/Merah), hingga pencetakan dokumen surat resmi yang instan dan terstandarisasi.

## 1.2 Rumusan Masalah
Berdasarkan latar belakang di atas, rumusan masalah dalam pengembangan sistem ini adalah:
1. Bagaimana mentransformasi sistem pencatatan poin manual menjadi database digital yang terintegrasi?
2. Bagaimana membangun fitur otomatisasi yang dapat mengklasifikasikan tingkat pelanggaran siswa secara *real-time*?
3. Bagaimana memfasilitasi Guru BK dalam menerbitkan dokumen administratif (surat teguran/panggilan) secara cepat dan akurat?

## 1.3 Tujuan Proyek
Tujuan utama dari pengembangan sistem SiPoin adalah:
1. Menyediakan platform manajemen kedisiplinan yang aman, terpusat, dan mudah diakses oleh pihak sekolah.
2. Meningkatkan efektivitas kerja Guru BK melalui fitur rekapitulasi otomatis dan statistik pelanggaran.
3. Mewujudkan transparansi informasi kedisiplinan bagi siswa untuk mendorong kesadaran mematuhi aturan sekolah.
4. Menstandarisasi format dokumen resmi sekolah (Surat Panggilan, Perjanjian, DO/Pindah) agar seragam dan profesional.

## 1.4 Manfaat Proyek
*   **Bagi Sekolah:** Meningkatkan citra profesionalisme sekolah melalui pemanfaatan teknologi informasi dalam manajemen kesiswaan.
*   **Bagi Guru & BK:** Mengurangi beban administratif dan memudahkan pengambilan keputusan berdasarkan data statistik yang akurat.
*   **Bagi Siswa:** Memberikan peringatan dini (early warning) mengenai status kedisiplinan mereka sehingga dapat melakukan perbaikan perilaku sebelum mencapai batas poin kritis.
*   **Bagi Orang Tua:** Memperoleh kepastian informasi mengenai perkembangan perilaku putra-putrinya di sekolah melalui dokumen resmi yang valid.

## 1.5 Ruang Lingkup
Aplikasi SiPoin ini mencakup batasan-batasan sebagai berikut:
1. Pengelolaan data kategori dan jenis pelanggaran beserta bobot poinnya.
2. Pencatatan pelanggaran siswa yang dilakukan oleh Admin, Guru BK, atau Guru Mata Pelajaran.
3. Pemantauan level kedisiplinan siswa (Hijau-0, Kuning-1, Merah-2) berdasarkan akumulasi poin.
4. Fitur pencetakan surat otomatis (Surat Panggilan Ortu, Surat Pernyataan Siswa, Surat DO/Pindah).
5. Visualisasi data statistik pelanggaran per kelas dan per jenis kategori.
