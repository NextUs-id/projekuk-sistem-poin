/**
 * public/js/app.js
 * ════════════════
 * Utilitas inti yang dipakai oleh SEMUA halaman frontend SiPoin.
 * 
 * Isi:
 *   - api()            → Wrapper fetch() untuk panggil API
 *   - checkAuth()      → Cek apakah user sudah login
 *   - requireLogin()   → Paksa login, redirect jika belum
 *   - logout()         → Proses logout
 *   - renderLayout()   → Buat sidebar + topbar layout (per role)
 *   - showAlert()      → Tampilkan notifikasi toast
 *   - formatDate()     → Format tanggal ke bahasa Indonesia
 *   - escapeHtml()     → Cegah XSS injection
 *   - levelBadge()     → Badge level siswa (Hijau/Kuning/Merah/DO)
 *   - statusBadge()    → Badge status user (aktif/nonaktif/DO)
 */


// ═══════════════════════════════════════════════════════════════
//  INJECT GLOBAL STYLES & FONT
//  Kita inject Google Fonts "Inter" + custom CSS lewat JavaScript
//  agar tidak perlu copy-paste <link> di setiap halaman HTML.
// ═══════════════════════════════════════════════════════════════

(function injectGlobalAssets() {
    // --- Google Fonts: Poppins ---
    const fontLink = document.createElement('link');
    fontLink.rel = 'stylesheet';
    fontLink.href = 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap';
    document.head.appendChild(fontLink);

    // --- Custom CSS (minimal, ~30 baris) ---
    const style = document.createElement('style');
    style.textContent = `
        /* Pakai Poppins sebagai font utama seluruh aplikasi */
        * { font-family: 'Poppins', system-ui, sans-serif; }

        /* Modern EduTech Design Tokens */
        :root {
            --color-primary: #0F172A; /* Slate 900 - Dark Navy */
            --color-accent: #C4F168; /* Lime Green */
            --color-secondary: #64748B; /* Slate 500 */
            --color-bg: #F8FAFC;     /* Slate 50 */
        }

        /* Modern Sidebar Active State - SMK TI Bali Global Denpasar Design */
        .sidebar-link.active {
            background-color: var(--color-accent);
            color: var(--color-primary);
            font-weight: 600;
            border-radius: 0.75rem; /* rounded-xl */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .sidebar-link.active:hover {
            transform: none;
            background-color: var(--color-accent);
        }
        
        /* Efek hover pada link sidebar — bg change */
        .sidebar-link {
            transition: all 0.2s ease;
            color: #94a3b8; /* Slate 400 - softer */
        }
        .sidebar-link:hover {
            background-color: rgba(255, 255, 255, 0.1); /* white/10 */
            color: #ffffff;
        }

        /* Animasi slide-in untuk alert/toast notification */
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to   { transform: translateX(0);    opacity: 1; }
        }
        .animate-slide-in { animation: slideIn 0.3s ease; }

        /* Smooth transition untuk hover cards */
        .hover-lift {
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .hover-lift:hover {
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        /* Overlay gelap saat sidebar terbuka di mobile */
        .sidebar-overlay {
            transition: opacity 0.3s ease;
        }

        /* Sidebar slide animation di mobile */
        .sidebar-panel {
            transition: transform 0.3s ease;
        }

        /* Helper khusus untuk Sidebar Collapsed (Desktop) */
        @media (min-width: 768px) {
            /* Saat collapsed, kurangi width jadi w-20 (5rem = 80px) */
            .sidebar-panel.desktop-collapsed {
                width: 5rem !important;
                transform: translateX(0) !important; /* Tetap terlihat, tidak geser ke kiri */
            }
            
            /* Sembunyikan teks saat collapsed */
            .sidebar-panel.desktop-collapsed .nav-label,
            .sidebar-panel.desktop-collapsed .brand-text,
            .sidebar-panel.desktop-collapsed .user-detail {
                display: none !important;
            }

            /* Center logo & avatar saat collapsed */
            .sidebar-panel.desktop-collapsed .brand-logo {
                margin: 0 auto;
            }
            .sidebar-panel.desktop-collapsed .user-avatar {
                margin: 0 auto;
            }

            /* Footer layout saat collapsed: Stack Avatar & Logout */
            .sidebar-panel.desktop-collapsed .px-4.py-4.border-t .flex {
                flex-direction: column;
                gap: 0.75rem; /* Jarak antara avatar dan logout */
                align-items: center;
                justify-content: center;
            }
            
            /* Pastikan tombol logout tetap muncul dan centered */
            .sidebar-panel.desktop-collapsed button[title="Logout"] {
                display: flex !important;
                margin: 0 auto;
                background-color: #f9fafb; /* gray-50 */
                color: #6b7280;            /* gray-500 */
                border-radius: 0.5rem;
                padding: 0.5rem;           /* size sedikit lebih besar */
                width: 2.25rem;            /* w-9 */
                height: 2.25rem;           /* h-9 */
                justify-content: center;
                align-items: center;
            }
            .sidebar-panel.desktop-collapsed button[title="Logout"]:hover {
                background-color: #f3f4f6; /* gray-100 */
                color: #ef4444;            /* red-500 (biar warning saat di-hover) */
            }
            
            /* Center navigation icons */
            .sidebar-panel.desktop-collapsed .sidebar-link {
                justify-content: center;
                padding-left: 0;
                padding-right: 0;
            }
        }

        /* Nested Menu - Folder Style */
        .sidebar-folder {
            cursor: pointer;
            user-select: none;
        }
        
        .sidebar-folder-toggle {
            transition: transform 0.2s ease;
            display: flex;
        }
        
        .sidebar-folder.expanded .sidebar-folder-toggle {
            transform: rotate(90deg);
        }
        
        .sidebar-children {
            display: none;
            padding-left: 1.25rem;
        }
        
        .sidebar-folder.expanded .sidebar-children {
            display: block;
        }
        
        .sidebar-child {
            font-size: 0.8125rem;
        }
        
        /* Active state for child items */
        .sidebar-child.active {
            background-color: var(--color-accent);
            color: var(--color-primary);
            font-weight: 600;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .sidebar-child.active:hover {
            transform: none;
            background-color: var(--color-accent);
        }
        
        /* Active state for folder itself (when on folder's page) */
        .sidebar-folder > div.active {
            background-color: var(--color-accent);
            color: var(--color-primary);
            font-weight: 600;
        }
        
        .sidebar-folder > div.active:hover {
            background-color: var(--color-accent);
            transform: none;
        }
    `;
    document.head.appendChild(style);
})();


// ═══════════════════════════════════════════════════════════════
//  API WRAPPER
//  Fungsi utama untuk komunikasi frontend ↔ backend.
//  Semua request API harus lewat fungsi ini.
// ═══════════════════════════════════════════════════════════════

/**
 * Panggil API backend.
 * 
 * Contoh penggunaan:
 *   const result = await api('/api/dashboard');
 *   const result = await api('/api/users/create', { method:'POST', body: { username:'test' } });
 *   const result = await api('/api/surat/upload', { method:'POST', body: formData });
 * 
 * @param {string} url       URL endpoint API (contoh: '/api/dashboard')
 * @param {object} options   Opsi fetch() tambahan (method, body, headers, dll)
 * @returns {object}         Response JSON dari server, atau error object
 */
async function api(url, options = {}) {
    const config = {
        credentials: 'same-origin',  // Kirim session cookie otomatis
        headers: {},
        ...options,
    };

    // Jika body adalah object biasa (bukan FormData untuk upload file),
    // kita otomatis stringify ke JSON dan set Content-Type
    if (config.body && !(config.body instanceof FormData)) {
        config.headers['Content-Type'] = 'application/json';
        config.body = JSON.stringify(config.body);
    }

    try {
        const res = await fetch(url, config);
        const data = await res.json();
        return data;
    } catch (err) {
        console.error('API Error:', err);
        return { ok: false, error: 'Gagal terhubung ke server.' };
    }
}


// ═══════════════════════════════════════════════════════════════
//  AUTH (Autentikasi)
//  Fungsi untuk cek login, paksa login, dan logout.
// ═══════════════════════════════════════════════════════════════

/**
 * Cek apakah user sudah login.
 * @returns {object|null} Data user jika sudah login, null jika belum
 */
async function checkAuth() {
    const res = await api('/api/me');
    if (res.ok && res.authenticated) {
        return res.user;  // { username, nama_asli, role }
    }
    return null;
}

/**
 * Pastikan user sudah login.
 * Jika belum login → redirect otomatis ke halaman /login.
 * @returns {object|null} Data user, atau null (sebelum redirect)
 */
async function requireLogin() {
    const user = await checkAuth();
    if (!user) {
        window.location.href = '/login';
        return null;
    }
    return user;
}

/**
 * Logout user dan redirect ke halaman login.
 * Memanggil POST /api/logout untuk hapus session di server.
 */
async function logout() {
    await api('/api/logout', { method: 'POST' });
    window.location.href = '/login';
}


// ═══════════════════════════════════════════════════════════════
//  SVG ICONS
//  Koleksi ikon SVG yang dipakai di sidebar dan UI.
//  Semua ikon berukuran 20x20px, stroke-based.
//  Sumber: Heroicons (https://heroicons.com)
// ═══════════════════════════════════════════════════════════════

const ICONS = {
    // Ikon sidebar menu
    dashboard: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z"/></svg>',
    users: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>',
    clipboard: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/></svg>',
    pencil: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>',
    folder: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/></svg>',
    chart: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>',
    log: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>',

    // Ikon topbar
    search: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>',
    plus: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>',
    hamburger: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>',
    logout: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>',
    chevronDoubleLeft: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m18.75 4.5-7.5 7.5 7.5 7.5m-6-15L5.25 12l7.5 7.5" /></svg>',
    chevronRight: '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>',
    sparkles: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" /></svg>',
};


// ═══════════════════════════════════════════════════════════════
//  LAYOUT — SIDEBAR + TOP BAR
//  Fungsi utama yang men-generate layout seluruh halaman.
//  Dipanggil oleh setiap halaman setelah user login.
//
//  Cara pakai di HTML:
//    <body class="bg-gray-50 min-h-screen">
//      <div id="app"></div>
//      <script src="/js/app.js"></script>
//      <script>
//        (async () => {
//          const user = await requireLogin();
//          if (!user) return;
//          renderLayout(user);
//          // ... kode halaman ...
//        })();
//      </script>
//    </body>
// ═══════════════════════════════════════════════════════════════

/**
 * Render layout sidebar + top bar.
 * 
 * Apa yang dilakukan:
 *   1. Buat sidebar (logo, nav links, user info)
 *   2. Buat top bar (search, quick add, hamburger mobile)
 *   3. Pindahkan semua konten <main> yang sudah ada ke dalam layout
 *   4. Setup event listeners (mobile toggle, sidebar close)
 *
 * @param {object} user  Data user dari checkAuth() { username, nama_asli, role }
 */
function renderLayout(user) {
    const appEl = document.getElementById('app');
    if (!appEl) return;

    const role = user.role;
    const nama = user.nama_asli || user.username;

    // ── Menu items per role ──────────────────────────────────
    // Setiap role punya menu sidebar yang berbeda.
    // 'icon' merujuk ke key di object ICONS di atas.
    // 'href' adalah path halaman tujuan.
    const menus = {
        admin: [
            { label: 'Dashboard', icon: 'dashboard', href: '/dashboard' },
            { label: 'Nexus Intelligence', icon: 'sparkles', href: '/bk/nexus-intelligence' },
            { label: 'Kelola Users', icon: 'users', href: '/admin/users' },
            { label: 'Kelola Kelas & Jurusan', icon: 'folder', href: '/admin/kelola-kelas' },
            { label: 'Jenis Pelanggaran', icon: 'clipboard', href: '/admin/jenis' },
            { label: 'Input Pelanggaran', icon: 'pencil', href: '/pelanggaran/input' },
            {
                label: 'Kelola Surat',
                icon: 'folder',
                href: '/bk/surat',
                isFolder: true,
                children: [
                    // { label: 'Surat Orang Tua', icon: 'clipboard', href: '/bk/surat-orangtua' },
                    { label: 'Surat Pemanggilan', icon: 'clipboard', href: '/bk/surat-pemanggilan' },
                    { label: 'Surat Perjanjian', icon: 'clipboard', href: '/bk/surat-perjanjian' },
                    { label: 'Rekapitulasi Surat', icon: 'clipboard', href: '/bk/rekapitulasi-surat' },
                    { label: 'Pengurangan Poin', icon: 'clipboard', href: '/bk/surat-pengurangan' },
                    { label: 'Surat DO', icon: 'clipboard', href: '/bk/surat-do' },
                    { label: 'Surat Pindah', icon: 'clipboard', href: '/bk/surat-pindah' },
                ]
            },
            { label: 'Log Aktivitas', icon: 'log', href: '/log' },
        ],
        bk: [
            { label: 'Dashboard', icon: 'dashboard', href: '/dashboard' },
            { label: 'Nexus Intelligence', icon: 'sparkles', href: '/bk/nexus-intelligence' },
            { label: 'Input Pelanggaran', icon: 'pencil', href: '/pelanggaran/input' },
            {
                label: 'Kelola Surat',
                icon: 'folder',
                href: '/bk/surat',
                isFolder: true,
                children: [
                    // { label: 'Surat Orang Tua', icon: 'clipboard', href: '/bk/surat-orangtua' },
                    { label: 'Surat Pemanggilan', icon: 'clipboard', href: '/bk/surat-pemanggilan' },
                    { label: 'Surat Perjanjian', icon: 'clipboard', href: '/bk/surat-perjanjian' },
                    { label: 'Rekapitulasi Surat', icon: 'clipboard', href: '/bk/rekapitulasi-surat' },
                    { label: 'Pengurangan Poin', icon: 'clipboard', href: '/bk/surat-pengurangan' },
                    { label: 'Surat DO', icon: 'clipboard', href: '/bk/surat-do' },
                    { label: 'Surat Pindah', icon: 'clipboard', href: '/bk/surat-pindah' },
                ]
            },
            { label: 'Statistik', icon: 'chart', href: '/bk/stats' },
            { label: 'Log Aktivitas', icon: 'log', href: '/log' },
        ],
        guru: [
            { label: 'Dashboard', icon: 'dashboard', href: '/dashboard' },
            { label: 'Input Pelanggaran', icon: 'pencil', href: '/pelanggaran/input' },
            { label: 'Log Aktivitas', icon: 'log', href: '/log' },
        ],
        kepsek: [
            { label: 'Dashboard', icon: 'dashboard', href: '/dashboard' },
            { label: 'Statistik', icon: 'chart', href: '/bk/stats' },
            { label: 'Log Aktivitas', icon: 'log', href: '/log' },
        ],
        siswa: [
            { label: 'Dashboard', icon: 'dashboard', href: '/dashboard' },
        ],
    };

    // Label role untuk badge di sidebar
    const roleLabel = {
        admin: 'Admin', bk: 'Guru BK', guru: 'Guru', kepsek: 'Kepsek', siswa: 'Siswa',
    };

    const items = menus[role] || menus.siswa;

    // ── Deteksi halaman aktif ────────────────────────────────
    // Bandingkan pathname saat ini dengan href di menu
    // untuk menentukan link mana yang diberi class 'active'
    const currentPath = window.location.pathname.replace(/\.html$/, '').replace(/\/$/, '') || '/';

    // ── Generate inisial untuk avatar ────────────────────────
    // Ambil 2 huruf pertama dari nama user
    const initials = nama.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();

    // ── Cek apakah role boleh "Tambah Pelanggaran" ───────────
    const canAddViolation = ['admin', 'bk', 'guru'].includes(role);

    // ── Ambil konten <main> yang sudah ada di halaman ────────
    // Kita akan pindahkan ini ke dalam layout wrapper
    const existingMain = document.querySelector('main');
    const mainContent = existingMain ? existingMain.innerHTML : '';
    const mainClasses = existingMain ? existingMain.className : '';
    if (existingMain) existingMain.remove();

    // ── Cek State Sidebar (Collapsed/Expanded) dari LocalStorage ──
    const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    const sidebarClass = isCollapsed ? 'desktop-collapsed' : '';
    const mainMarginClass = isCollapsed ? 'md:ml-20' : 'md:ml-64';
    const toggleIconTransform = isCollapsed ? 'rotate(180deg)' : 'rotate(0deg)';
    const toggleTitle = isCollapsed ? 'Tampilkan Sidebar' : 'Sembunyikan Sidebar';

    // ── Ambil State Folder dari LocalStorage ──
    let folderStates = {};
    try {
        folderStates = JSON.parse(localStorage.getItem('sidebar-folder-states') || '{}');
    } catch (e) { }

    // ══════════════════════════════════════════════════════════
    //  RENDER HTML LENGKAP
    // ══════════════════════════════════════════════════════════
    appEl.innerHTML = `
        <!-- ═══════════════════════════════════════════════════ -->
        <!--  OVERLAY (untuk mobile — klik overlay tutup sidebar) -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div id="sidebar-overlay" class="sidebar-overlay fixed inset-0 bg-black/30 z-40 hidden md:hidden" onclick="closeSidebar()"></div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!--  SIDEBAR (fixed di kiri, lebar 256px / w-64)        -->
        <!-- ═══════════════════════════════════════════════════ -->
        <aside id="sidebar" class="sidebar-panel fixed top-0 left-0 z-50 w-64 h-screen bg-[#0F172A] flex flex-col -translate-x-full md:translate-x-0 transition-all duration-300 ${sidebarClass}">

            <!-- Logo + Brand -->
            <div class="px-5 py-5 border-b border-gray-100 flex items-center h-[73px]"> <!-- Fixed height agar tidak lompat -->
                <div class="flex items-center gap-2.5 w-full overflow-hidden">
                    <a href="/dashboard" class="text-xl font-bold text-white tracking-tight flex-shrink-0 brand-logo">
                        Nexus
                    </a>
                    <div class="brand-text flex flex-col min-w-0">
                       <span class="text-[10px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded-full font-semibold uppercase tracking-wider w-fit">${roleLabel[role] || role}</span>
                       <p class="text-[11px] text-slate-400 mt-1 truncate">Student Management</p>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto overflow-x-hidden">
                ${items.map(m => {
        // Cek apakah link ini adalah halaman yang sedang aktif
        const isActive = currentPath === m.href || currentPath === m.href + '.html';

        // Jika item adalah folder (memiliki children)
        if (m.isFolder && m.children) {
            // Cek apakah ada child yang aktif ATAU folder sedang expanded di localStorage
            const hasActiveChild = m.children.some(c =>
                currentPath === c.href || currentPath === c.href + '.html'
            );
            // Jika folder itu sendiri yang aktif, juga expand dan berikan active style
            const isFolderActive = currentPath === m.href || currentPath === m.href + '.html';
            const folderExpanded = (hasActiveChild || folderStates[m.label] || isFolderActive) ? 'expanded' : '';
            const folderActiveClass = isFolderActive ? 'active' : '';

            // Generate child items HTML
            const childrenHtml = m.children.map(c => {
                const childActive = currentPath === c.href || currentPath === c.href + '.html';
                return `
                                <a href="${c.href}" 
                                   class="sidebar-link sidebar-child flex items-center gap-3 px-3 py-2 rounded-lg text-sm ${childActive ? 'active' : 'text-slate-400 hover:text-white hover:bg-white/10 transition-colors'}"
                                   title="${c.label}">
                                    <div class="flex-shrink-0">${ICONS[c.icon] || ''}</div>
                                    <span class="nav-label whitespace-nowrap">${c.label}</span>
                                </a>
                            `;
            }).join('');

            return `
                            <div class="sidebar-folder ${folderExpanded}" data-folder="${m.label}" data-href="${m.href}">
                                <div class="flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm text-slate-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer ${folderActiveClass}"
                                     onclick="toggleSidebarFolder(event, this)"
                                     title="${m.label}">
                                    <div class="flex-shrink-0 sidebar-folder-toggle">
                                        ${ICONS.chevronRight}
                                    </div>
                                    <div class="flex-shrink-0">${ICONS[m.icon] || ''}</div>
                                    <span class="nav-label whitespace-nowrap">${m.label}</span>
                                </div>
                                <div class="sidebar-children">
                                    ${childrenHtml}
                                </div>
                            </div>
                        `;
        }

        // Regular menu item (tanpa children)
        return `
                        <a href="${m.href}"
                           class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm ${isActive ? 'active' : 'text-slate-400 hover:text-white hover:bg-white/10 transition-colors'}"
                           title="${m.label}">
                            <div class="flex-shrink-0">${ICONS[m.icon] || ''}</div>
                            <span class="nav-label whitespace-nowrap">${m.label}</span>
                        </a>
                    `;
    }).join('')}
            </nav>

            <!-- User Info Footer -->
            <div class="px-4 py-4 border-t border-slate-800">
                <div class="flex items-center gap-3 overflow-hidden">
                    <!-- Avatar dengan gradient Lime -> Navy -->
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#C4F168] to-lime-400 flex items-center justify-center text-[#0F172A] text-xs font-bold flex-shrink-0 user-avatar">
                        ${initials}
                    </div>
                    <div class="flex-1 min-w-0 user-detail">
                        <p class="text-sm font-semibold text-white truncate">${escapeHtml(nama)}</p>
                        <p class="text-[11px] text-slate-400">${roleLabel[role] || role}</p>
                    </div>
                    <!-- Tombol Logout -->
                    <button onclick="logout()" class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors cursor-pointer user-detail" title="Logout" aria-label="Logout">
                        ${ICONS.logout}
                    </button>
                </div>
            </div>
        </aside>

        <!-- ═══════════════════════════════════════════════════ -->
        <!--  MAIN WRAPPER (sebelah kanan sidebar)              -->
        <!--  Di desktop: margin-left = lebar sidebar (256px)   -->
        <!--  Di mobile: full width                             -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div id="main-wrapper" class="${mainMarginClass} min-h-screen flex flex-col transition-all duration-300">

            <!-- ═══════════════════════════════════════════════ -->
            <!--  TOP BAR (sticky di atas)                       -->
            <!-- ═══════════════════════════════════════════════ -->
            <header class="sticky top-0 z-30 bg-white border-b border-slate-200 h-16 flex items-center px-4 sm:px-6 gap-4">

                <!-- Hamburger (Mobile Only) -->
                <button id="menu-toggle" onclick="openSidebar()" class="md:hidden p-2 -ml-2 rounded-lg hover:bg-slate-100 text-slate-500 cursor-pointer" aria-label="Buka menu">
                    ${ICONS.hamburger}
                </button>
                
                <!-- Toggle Sidebar (Desktop Only) -->
                <!-- Pakai icon arrow (chevronDoubleLeft) sesuai request user -->
                <button onclick="toggleSidebarDesktop()" class="hidden md:flex p-2 -ml-2 mr-2 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-900 cursor-pointer transition-colors" title="${toggleTitle}">
                    <div style="transition: transform 0.3s ease; transform: ${toggleIconTransform};">
                        ${ICONS.chevronDoubleLeft}
                    </div>
                </button>

                <!-- Search Box -->
                <div class="flex-1 max-w-md relative group">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">${ICONS.search}</span>
                    <input type="text" 
                           id="global-search"
                           placeholder="Cari nama, NIS, kelas..." 
                           class="w-full !pl-10 !pr-4 !py-2.5 rounded-full border border-slate-200 text-sm focus:border-[#0F172A] focus:ring-2 focus:ring-[#C4F168]/50 outline-none transition-all bg-slate-50 focus:bg-white placeholder:text-slate-400"
                           autocomplete="off">
                    
                    <!-- Search Results Dropdown -->
                    <div id="global-search-results" class="absolute top-[calc(100%+0.5rem)] left-0 w-full lg:w-[400px] bg-white rounded-3xl shadow-[0_20px_40px_-15px_rgba(0,0,0,0.1)] border border-slate-100 hidden z-[100] max-h-[60vh] overflow-y-auto flex-col overflow-hidden opacity-0 origin-top scale-95 transition-all duration-200">
                        <div id="search-results-content" class="p-3 space-y-1"></div>
                    </div>
                </div>

                <!-- Spacer (push items ke kanan) -->
                <div class="flex-1"></div>

                <!-- Quick Add Button (admin/bk/guru saja) -->
                ${canAddViolation ? `
                    <a href="/pelanggaran/input" class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 bg-[#0F172A] text-white rounded-full text-sm font-semibold hover:bg-[#1e293b] transition-colors shadow-lg shadow-slate-900/20">
                        ${ICONS.plus}
                        <span>Tambah Pelanggaran</span>
                    </a>
                ` : ''}

                <!-- Nama user (desktop only) -->
                <div class="hidden lg:flex items-center gap-2 text-sm text-gray-500">
                    <span>Halo, <strong class="text-gray-700">${escapeHtml(nama.split(' ')[0])}</strong></span>
                </div>
            </header>

            <!-- ═══════════════════════════════════════════════ -->
            <!--  PAGE CONTENT (area scrollable)                 -->
            <!--  Konten dari <main> asli dipindahkan ke sini   -->
            <!-- ═══════════════════════════════════════════════ -->
            <main id="page-content" class="${mainClasses || 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full'}">
                ${mainContent}
            </main>
        </div>
    `;

    setTimeout(() => initGlobalSearch(role), 100);
}

// ── Fungsi Desktop Sidebar Toggle (Collapse/Expand) ────────────────
function toggleSidebarDesktop() {
    const sidebar = document.getElementById('sidebar');
    const mainWrapper = document.getElementById('main-wrapper');
    const toggleBtnIcon = document.querySelector('button[title="Sembunyikan Sidebar"] div') ||
        document.querySelector('button[title="Tampilkan Sidebar"] div');

    // Toggle class 'desktop-collapsed'
    sidebar.classList.toggle('desktop-collapsed');
    const isCollapsed = sidebar.classList.contains('desktop-collapsed');

    // Simpan state ke localStorage
    localStorage.setItem('sidebar-collapsed', isCollapsed);

    // Sesuaikan margin konten utama
    if (isCollapsed) {
        mainWrapper.classList.remove('md:ml-64');
        mainWrapper.classList.add('md:ml-20'); // Width saat collapsed (w-20 = 5rem = 80px)

        // Putar ikon panah
        if (toggleBtnIcon) toggleBtnIcon.style.transform = 'rotate(180deg)';
        if (toggleBtnIcon && toggleBtnIcon.parentElement) toggleBtnIcon.parentElement.title = "Tampilkan Sidebar";
    } else {
        mainWrapper.classList.add('md:ml-64');
        mainWrapper.classList.remove('md:ml-20');

        // Balikin ikon panah
        if (toggleBtnIcon) toggleBtnIcon.style.transform = 'rotate(0deg)';
        if (toggleBtnIcon && toggleBtnIcon.parentElement) toggleBtnIcon.parentElement.title = "Sembunyikan Sidebar";
    }
}

// ── Fungsi mobile sidebar toggle ─────────────────────────────

/** Buka sidebar di mobile (geser dari kiri) */
function openSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (sidebar) sidebar.classList.remove('-translate-x-full');
    if (overlay) overlay.classList.remove('hidden');
}

/** Tutup sidebar di mobile */
function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (sidebar) sidebar.classList.add('-translate-x-full');
    if (overlay) overlay.classList.add('hidden');
}

/** Toggle expand/collapse folder di sidebar */
function toggleSidebarFolder(event, divElement) {
    // Prevent default to avoid any unintended behavior
    event.preventDefault();

    const folderDiv = divElement.closest('.sidebar-folder');
    if (!folderDiv) return;

    // Toggle expanded state
    folderDiv.classList.toggle('expanded');

    // Update chevron rotation
    const chevron = divElement.querySelector('.sidebar-folder-toggle');
    if (chevron) {
        chevron.style.transform = folderDiv.classList.contains('expanded') ? 'rotate(90deg)' : 'rotate(0deg)';
    }

    // Simpan state ke localStorage
    const folderName = folderDiv.dataset.folder;
    const isExpanded = folderDiv.classList.contains('expanded');

    // Ambil state existing dari localStorage
    let folderStates = {};
    try {
        folderStates = JSON.parse(localStorage.getItem('sidebar-folder-states') || '{}');
    } catch (e) { }

    folderStates[folderName] = isExpanded;
    localStorage.setItem('sidebar-folder-states', JSON.stringify(folderStates));

    // Navigate to folder href ONLY if not already on that page
    const href = folderDiv.dataset.href;
    const currentPath = window.location.pathname.replace(/\.html$/, '').replace(/\/$/, '') || '/';
    if (href && href !== currentPath && href !== currentPath + '.html') {
        window.location.href = href;
    }
}

// ── Fungsi Global Search ─────────────────────────────────────
function initGlobalSearch(role) {
    const input = document.getElementById('global-search');
    const dropdown = document.getElementById('global-search-results');
    const content = document.getElementById('search-results-content');
    if (!input || !dropdown) return;

    let debounceTimer;

    function showDropdown() {
        dropdown.classList.remove('hidden');
        setTimeout(() => dropdown.classList.remove('scale-95', 'opacity-0'), 10);
    }

    function hideDropdown() {
        dropdown.classList.add('scale-95', 'opacity-0');
        setTimeout(() => dropdown.classList.add('hidden'), 200);
    }

    // Close on click outside
    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            hideDropdown();
        }
    });

    input.addEventListener('focus', () => {
        if (input.value.trim().length >= 2) showDropdown();
    });

    input.addEventListener('input', (e) => {
        const query = e.target.value.trim();

        if (query.length < 2) {
            hideDropdown();
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            showDropdown();
            content.innerHTML = '<div class="p-6 text-center text-slate-400 text-xs"><svg class="w-5 h-5 animate-spin mx-auto mb-2 text-slate-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>Mencari data...</div>';

            const res = await api('/api/siswa?q=' + encodeURIComponent(query));
            if (res.ok && res.data && res.data.length > 0) {
                content.innerHTML = res.data.map(s => `
                    <a href="/siswa/profil?id=${s.id_siswa}" class="flex items-center gap-4 px-4 py-3 bg-white hover:bg-slate-50 border border-transparent hover:border-slate-100 rounded-2xl transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 text-primary flex items-center justify-center font-black shadow-sm group-hover:bg-primary group-hover:text-accent transition-colors">
                            ${(s.nama || '').charAt(0).toUpperCase()}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-primary truncate group-hover:text-clip">${escapeHtml(s.nama)}</p>
                            <p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest mt-0.5">
                                ${escapeHtml(s.kelas)} &bull; ${escapeHtml(s.nis)}
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 group-hover:text-primary group-hover:translate-x-1 transition-all"></i>
                    </a>
                `).join('');
                if (window.lucide) lucide.createIcons();
            } else {
                content.innerHTML = '<div class="p-6 text-center text-slate-400 text-xs font-medium">Siswa tidak ditemukan.</div>';
            }
        }, 300);
    });
}

// ── Backward compatibility ───────────────────────────────────
// Agar halaman lama yang masih pakai renderNavbar() tetap jalan
function renderNavbar(user) {
    renderLayout(user);
}


// ═══════════════════════════════════════════════════════════════
//  ALERT / TOAST NOTIFICATION
//  Tampilkan pesan sementara di pojok kanan atas halaman.
//  Otomatis hilang setelah 5 detik.
// ═══════════════════════════════════════════════════════════════

/**
 * Tampilkan notifikasi toast.
 * 
 * @param {string} message  Pesan yang ditampilkan
 * @param {string} type     Tipe alert: 'success' | 'error' | 'info' | 'warning'
 * 
 * Contoh:
 *   showAlert('Berhasil disimpan!', 'success');
 *   showAlert('Gagal menghapus.', 'error');
 */
/**
 * Escape HTML special characters to prevent XSS.
 */
function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function showAlert(message, type = 'info') {
    // Hapus alert sebelumnya kalau ada
    const existing = document.getElementById('app-alert');
    if (existing) existing.remove();

    // Warna berdasarkan tipe
    const colors = {
        success: 'bg-green-50 text-green-800 border-green-200',
        error: 'bg-red-50 text-red-800 border-red-200',
        info: 'bg-blue-50 text-blue-800 border-blue-200',
        warning: 'bg-yellow-50 text-yellow-800 border-yellow-200',
    };

    // Icon berdasarkan tipe
    const icons = {
        success: '✅',
        error: '❌',
        info: 'ℹ️',
        warning: '⚠️',
    };

    // Buat elemen alert
    const div = document.createElement('div');
    div.id = 'app-alert';
    div.className = `fixed top-4 right-4 z-[100] max-w-md px-4 py-3 rounded-xl border shadow-lg ${colors[type] || colors.info} animate-slide-in`;
    div.innerHTML = `
        <div class="flex items-start gap-2">
            <span>${icons[type] || icons.info}</span>
            <p class="text-sm font-medium flex-1">${message}</p>
            <button onclick="this.closest('#app-alert').remove()" class="text-lg leading-none opacity-50 hover:opacity-100 cursor-pointer">&times;</button>
        </div>
    `;

    document.body.appendChild(div);

    // Otomatis hilang setelah 5 detik
    setTimeout(() => {
        if (div.parentNode) div.remove();
    }, 5000);
}


// ═══════════════════════════════════════════════════════════════
//  UTILITY FUNCTIONS
//  Fungsi-fungsi bantu yang dipakai di banyak halaman.
// ═══════════════════════════════════════════════════════════════

/**
 * Format tanggal ke bahasa Indonesia.
 * Contoh: "2026-02-11" → "11 Februari 2026"
 */
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const d = new Date(dateStr);
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

/**
 * Format tanggal + waktu.
 * Contoh: "2026-02-11 18:00:00" → "11 Februari 2026 18:00"
 */
function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return `${formatDate(dateStr)} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

/**
 * Escape HTML untuk mencegah XSS injection.
 * Contoh: "<script>" → "&lt;script&gt;"
 */
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

/**
 * Badge level siswa.
 * Level 0=Hijau, 1=Kuning, 2=Merah, 3=DO
 * Contoh: levelBadge(0) → '<span class="...">Hijau</span>'
 */
function levelBadge(level) {
    const labels = {
        0: { text: 'Hijau', class: 'bg-green-100 text-green-700' },
        1: { text: 'Kuning', class: 'bg-yellow-100 text-yellow-700' },
        2: { text: 'Merah', class: 'bg-red-100 text-red-700' },
        3: { text: 'DO', class: 'bg-gray-200 text-gray-700' },
    };
    const l = labels[level] || labels[0];
    return `<span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold ${l.class}">${l.text}</span>`;
}

/**
 * Badge status user (aktif, nonaktif, pindah, DO).
 * Contoh: statusBadge('aktif') → '<span class="...">aktif</span>'
 */
function statusBadge(status) {
    const colors = {
        aktif: 'badge-aktif',
        nonaktif: 'badge-nonaktif',
        pindah: 'badge-pindah',
        DO: 'badge-do',
    };
    const labels = {
        aktif: 'Aktif',
        nonaktif: 'Non-Aktif',
        pindah: 'Pindah',
        DO: 'DO',
    };
    return `<span class="${colors[status] || colors.aktif}">${escapeHtml(labels[status] || status)}</span>`;
}
