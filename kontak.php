<?php
/**
 * kontak.php — VERSI DIPERBAIKI
 * Fix: CSRF token sekarang disimpan di session (bukan hanya di hidden field)
 * Fix: Link navigasi diperbaiki (kontak.html → kontak.php)
 */
session_start();

$sukses = $_SESSION['sukses'] ?? null;
$error  = $_SESSION['error']  ?? null;
$data   = $_SESSION['data']   ?? [];

unset($_SESSION['sukses'], $_SESSION['error'], $_SESSION['data']);

// FIX: Generate CSRF token dan simpan di session
// Sebelumnya token hanya di-generate tapi tidak disimpan di session untuk dicek
$csrf_token = bin2hex(random_bytes(16));
$_SESSION['csrf_token'] = $csrf_token;

// ============================================================
// GANTI INI DENGAN DATA NYATA KAMU:
// ============================================================
$email_nyata   = 'zaidan@email.com';       // ← Ganti dengan email asli
$telepon_nyata = '+62 812-3456-7890';      // ← Ganti dengan nomor WhatsApp asli
$github_url    = 'https://github.com/USERNAME_KAMU';    // ← Ganti USERNAME_KAMU
$linkedin_url  = 'https://linkedin.com/in/USERNAME_KAMU'; // ← Ganti USERNAME_KAMU
$instagram_url = 'https://instagram.com/USERNAME_KAMU';   // ← Ganti USERNAME_KAMU
// ============================================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Zaidan Ammar | Kontak</title>

  <!-- SEO & Open Graph (FIX: ditambahkan agar link bisa di-share) -->
  <meta name="description" content="Hubungi Zaidan Ammar — Full-Stack Developer, mahasiswa Universitas Paramadina, terbuka untuk proyek web, mobile, dan backend." />
  <meta property="og:title" content="Zaidan Ammar | Kontak" />
  <meta property="og:description" content="Punya proyek? Hubungi Zaidan — Developer Full-Stack, Next.js, Flutter, Node.js." />
  <meta property="og:type" content="website" />

  <link rel="stylesheet" href="style.css" />
  <style>
    .avail-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: var(--green-dim);
      border: 1px solid var(--green);
      border-radius: var(--r-sm);
      padding: 0.5rem 1rem;
      font-family: var(--font-display);
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 2rem;
    }
    .avail-dot {
      width: 7px; height: 7px;
      border-radius: 50%;
      background: var(--accent);
      animation: pulse 2s infinite;
      box-shadow: 0 0 6px var(--accent);
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50%       { opacity: 0.3; transform: scale(0.8); }
    }
    .form-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--r);
      padding: 2.5rem;
      transition: border-color var(--trans);
    }
    .form-card:focus-within { border-color: var(--green); }
    .form-card h3 { font-size: 1.35rem; margin-bottom: 2rem; }
    .alert {
      border-radius: var(--r-sm);
      padding: 1rem 1.25rem;
      margin-bottom: 1.5rem;
      font-family: var(--font-display);
      font-size: 0.88rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      display: flex;
      align-items: center;
      gap: 0.6rem;
      animation: fadeUp 0.4s ease forwards;
    }
    .alert-success { background: rgba(58,90,58,0.25); border: 1px solid var(--accent); color: var(--accent); }
    .alert-error   { background: rgba(180,50,50,0.18); border: 1px solid #c05555; color: #e07777; }
    .alert-icon {
      width: 22px; height: 22px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.75rem; flex-shrink: 0; font-weight: 900;
    }
    .alert-success .alert-icon { background: var(--accent); color: var(--bg); }
    .alert-error   .alert-icon { background: #c05555; color: #fff; }
    .field-error {
      font-family: var(--font-display);
      font-size: 0.72rem; font-weight: 700;
      letter-spacing: 0.08em; text-transform: uppercase;
      color: #e07777; margin-top: 0.4rem;
      display: flex; align-items: center; gap: 0.4rem;
    }
    .field-error::before {
      content: '!';
      display: inline-flex; align-items: center; justify-content: center;
      width: 14px; height: 14px;
      border-radius: 50%; background: #c05555; color: #fff;
      font-size: 0.6rem; font-weight: 900; flex-shrink: 0;
    }
    input.invalid, textarea.invalid, select.invalid {
      border-color: #c05555 !important;
      box-shadow: 0 0 0 3px rgba(192,85,85,0.15);
    }
    .char-hint { font-size: 0.75rem; color: var(--muted); text-align: right; margin-top: 0.3rem; }
    .form-success { text-align: center; padding: 3rem 1rem; animation: fadeUp 0.5s ease forwards; }
    .success-box {
      width: 64px; height: 64px;
      border: 2px solid var(--accent);
      border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.25rem;
      font-family: var(--font-display); font-size: 1.8rem; font-weight: 900;
      color: var(--accent);
      animation: successPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }
    @keyframes successPop {
      from { transform: scale(0) rotate(-10deg); }
      to   { transform: scale(1) rotate(0); }
    }
    .php-badge {
      display: inline-flex; align-items: center; gap: 0.4rem;
      font-family: var(--font-display); font-size: 0.65rem; font-weight: 700;
      letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--muted); border: 1px solid var(--border);
      border-radius: 3px; padding: 0.2rem 0.6rem;
      margin-left: 0.75rem; vertical-align: middle;
    }
    .php-dot { width: 5px; height: 5px; border-radius: 50%; background: #8892BF; }
  </style>
</head>
<body>

  <div class="nav-progress" id="progress"></div>

  <nav class="main-nav">
    <div class="nav-brand">Zaidan<span>.</span></div>
    <div class="nav-toggle" onclick="document.getElementById('navLinks').classList.toggle('open')">
      <span></span><span></span><span></span>
    </div>
    <ul class="nav-links" id="navLinks">
      <li><a href="index.html">Beranda</a></li>
      <li><a href="tentang.html">Tentang</a></li>
      <li><a href="portfolio.html">Portfolio</a></li>
      <li><a href="kontak.php" class="active">Kontak</a></li>
    </ul>
  </nav>

  <main>
    <section>
      <div class="container">
        <span class="label reveal">Kontak</span>
        <h2 style="margin-bottom: 0.75rem;" class="reveal reveal-delay-1">
          Hubungi Saya
          <span class="php-badge"><span class="php-dot"></span>PHP Backend</span>
        </h2>
        <p style="margin-bottom: 3rem; font-size: 1rem; max-width: 480px;" class="reveal reveal-delay-2">
          Punya proyek yang ingin dibangun? Butuh developer yang bisa diandalkan dari sisi frontend maupun backend? Kirim pesan dan saya balas secepatnya.
        </p>

        <div class="grid-contact">

          <!-- Kiri: info kontak -->
          <div class="reveal reveal-delay-1">
            <div class="avail-badge">
              <span class="avail-dot"></span>
              Terbuka untuk Proyek Baru
            </div>

            <div class="contact-row">
              <span class="c-key">Lokasi</span>
              <div class="c-val">
                <strong>Domisili</strong>
                <span>Jakarta Timur, Indonesia</span>
              </div>
            </div>
            <div class="contact-row">
              <span class="c-key">Email</span>
              <div class="c-val">
                <strong>Surat Elektronik</strong>
                <span><a href="mailto:<?= htmlspecialchars($email_nyata) ?>"><?= htmlspecialchars($email_nyata) ?></a></span>
              </div>
            </div>
            <div class="contact-row">
              <span class="c-key">WA</span>
              <div class="c-val">
                <strong>WhatsApp</strong>
                <span><a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $telepon_nyata) ?>"><?= htmlspecialchars($telepon_nyata) ?></a></span>
              </div>
            </div>
            <div class="contact-row">
              <span class="c-key">Org</span>
              <div class="c-val">
                <strong>Khatulistiwa MAPA Paramadina</strong>
                <span>Ketua Umum &mdash; Periode 2026–2027</span>
              </div>
            </div>
            <div class="contact-row">
              <span class="c-key">Resp</span>
              <div class="c-val">
                <strong>Waktu Respons</strong>
                <span>Dalam 24 jam pada hari kerja</span>
              </div>
            </div>

            <div class="soc-links">
              <a href="<?= htmlspecialchars($github_url) ?>" target="_blank" rel="noopener" class="soc-link" title="GitHub">GH</a>
              <a href="<?= htmlspecialchars($linkedin_url) ?>" target="_blank" rel="noopener" class="soc-link" title="LinkedIn">IN</a>
              <a href="<?= htmlspecialchars($instagram_url) ?>" target="_blank" rel="noopener" class="soc-link" title="Instagram">IG</a>
            </div>

            <h3 style="color: var(--white); margin: 2.5rem 0 1.25rem; font-size: 1.1rem;">Yang Bisa Saya Bantu</h3>
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); overflow: hidden;">
              <table>
                <thead><tr><th>Layanan</th><th>Stack</th></tr></thead>
                <tbody>
                  <tr><td>Web App Full-Stack</td><td>Next.js, Node.js</td></tr>
                  <tr><td>Mobile App</td><td>Flutter</td></tr>
                  <tr><td>REST API &amp; Backend</td><td>Express.js</td></tr>
                  <tr><td>Dashboard &amp; Visualisasi</td><td>React, Chart.js</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Kanan: form -->
          <div class="reveal reveal-delay-2">
            <div class="form-card">

              <?php if ($sukses): ?>
                <div class="form-success">
                  <div class="success-box">✓</div>
                  <h3>Pesan Terkirim!</h3>
                  <p style="margin-top: 0.5rem; font-size: 0.95rem; max-width: 320px; margin-left: auto; margin-right: auto; color: var(--muted);">
                    Terima kasih, <strong style="color:var(--text)"><?= htmlspecialchars($sukses['nama']) ?></strong>!
                    Pesan Anda sudah diterima dan akan dibalas dalam 24 jam pada hari kerja.
                  </p>
                  <div style="background:var(--bg-2); border:1px solid var(--border); border-radius:var(--r-sm); padding:1.25rem 1.5rem; margin:1.5rem auto 0; max-width:320px; text-align:left;">
                    <div style="font-family:var(--font-display); font-size:0.65rem; font-weight:700; letter-spacing:0.18em; text-transform:uppercase; color:var(--muted); margin-bottom:0.75rem;">Ringkasan Kiriman</div>
                    <div style="font-size:0.85rem; display:grid; gap:0.4rem;">
                      <div><span style="color:var(--muted)">Nama &nbsp;&nbsp;:</span> <span style="color:var(--text)"><?= htmlspecialchars($sukses['nama']) ?></span></div>
                      <div><span style="color:var(--muted)">Email &nbsp;&nbsp;:</span> <span style="color:var(--text)"><?= htmlspecialchars($sukses['email']) ?></span></div>
                      <div><span style="color:var(--muted)">Topik &nbsp;&nbsp;:</span> <span style="color:var(--accent)"><?= htmlspecialchars($sukses['topik']) ?></span></div>
                      <div><span style="color:var(--muted)">Dikirim:</span> <span style="color:var(--text)"><?= htmlspecialchars($sukses['waktu']) ?></span></div>
                    </div>
                  </div>
                  <a href="kontak.php" class="btn btn-ghost" style="margin-top: 1.75rem;">Kirim Pesan Lain</a>
                </div>

              <?php else: ?>
                <h3>Kirim Pesan</h3>

                <?php if ($error): ?>
                  <div class="alert alert-error">
                    <span class="alert-icon">✕</span>
                    <?= htmlspecialchars($error) ?>
                  </div>
                <?php endif; ?>

                <form action="proses_kontak.php" method="POST" novalidate>

                  <div class="form-row">
                    <div class="form-group">
                      <label for="nama">Nama <span style="color:#c05555">*</span></label>
                      <input type="text" id="nama" name="nama"
                        placeholder="Nama lengkap Anda"
                        value="<?= htmlspecialchars($data['nama'] ?? '') ?>"
                        class="<?= isset($data['err_nama']) ? 'invalid' : '' ?>"
                        maxlength="100" />
                      <?php if (isset($data['err_nama'])): ?>
                        <div class="field-error"><?= htmlspecialchars($data['err_nama']) ?></div>
                      <?php endif; ?>
                    </div>
                    <div class="form-group">
                      <label for="instansi">Instansi / Kampus</label>
                      <input type="text" id="instansi" name="instansi"
                        placeholder="Opsional"
                        value="<?= htmlspecialchars($data['instansi'] ?? '') ?>"
                        maxlength="100" />
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="email">Email <span style="color:#c05555">*</span></label>
                    <input type="email" id="email" name="email"
                      placeholder="email@contoh.com"
                      value="<?= htmlspecialchars($data['email'] ?? '') ?>"
                      class="<?= isset($data['err_email']) ? 'invalid' : '' ?>"
                      maxlength="150" />
                    <?php if (isset($data['err_email'])): ?>
                      <div class="field-error"><?= htmlspecialchars($data['err_email']) ?></div>
                    <?php endif; ?>
                  </div>

                  <div class="form-group">
                    <label for="telepon">Nomor Telepon / WhatsApp</label>
                    <input type="tel" id="telepon" name="telepon"
                      placeholder="+62 8xx-xxxx-xxxx"
                      value="<?= htmlspecialchars($data['telepon'] ?? '') ?>"
                      class="<?= isset($data['err_telepon']) ? 'invalid' : '' ?>"
                      maxlength="20" />
                    <?php if (isset($data['err_telepon'])): ?>
                      <div class="field-error"><?= htmlspecialchars($data['err_telepon']) ?></div>
                    <?php endif; ?>
                  </div>

                  <div class="form-group">
                    <label for="topik">Topik</label>
                    <select id="topik" name="topik">
                      <?php
                        $opsi_topik = [
                          ''            => '— Pilih Topik —',
                          'web'         => 'Pengembangan Web App',
                          'mobile'      => 'Pengembangan Mobile App',
                          'api'         => 'Backend / REST API',
                          'konsultasi'  => 'Konsultasi Teknis',
                          'kolaborasi'  => 'Kolaborasi Proyek Kampus',
                          'lain'        => 'Lainnya',
                        ];
                        $topik_val = $data['topik'] ?? '';
                        foreach ($opsi_topik as $val => $label):
                          $sel = ($topik_val === $val) ? 'selected' : '';
                      ?>
                        <option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="pesan">Pesan <span style="color:#c05555">*</span></label>
                    <textarea id="pesan" name="pesan"
                      placeholder="Ceritakan proyek atau kebutuhan Anda..."
                      maxlength="500"
                      oninput="updateChar(this)"
                      class="<?= isset($data['err_pesan']) ? 'invalid' : '' ?>"
                    ><?= htmlspecialchars($data['pesan'] ?? '') ?></textarea>
                    <div class="char-hint"><span id="charCount"><?= strlen($data['pesan'] ?? '') ?></span>/500 karakter</div>
                    <?php if (isset($data['err_pesan'])): ?>
                      <div class="field-error"><?= htmlspecialchars($data['err_pesan']) ?></div>
                    <?php endif; ?>
                  </div>

                  <!-- FIX: CSRF token sekarang disimpan di $_SESSION['csrf_token'] -->
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />

                  <button type="submit" class="btn btn-solid" style="width:100%; justify-content:center; font-size:1rem; padding:1rem 2rem;">
                    Kirim Pesan →
                  </button>

                </form>
              <?php endif; ?>

            </div>
          </div>

        </div>
      </div>
    </section>
  </main>

  <footer>
    <div class="container">
      <div class="foot-brand">Zaidan Ammar</div>
      <p>Web Developer · Ketua Umum Khatulistiwa MAPA Paramadina 2026–2027</p>
      <nav class="foot-links">
        <a href="index.html">Beranda</a>
        <a href="tentang.html">Tentang</a>
        <a href="portfolio.html">Portfolio</a>
        <a href="kontak.php">Kontak</a>
      </nav>
      <p style="margin-top:0.75rem; font-size:0.78rem;">&copy; 2025 Zaidan Ammar. Hak Cipta Dilindungi.</p>
    </div>
  </footer>

  <script>
    window.addEventListener('scroll', () => {
      const total = document.documentElement.scrollHeight - window.innerHeight;
      document.getElementById('progress').style.width = (total > 0 ? (window.scrollY / total) * 100 : 0) + '%';
    });
    function updateChar(el) {
      document.getElementById('charCount').textContent = el.value.length;
    }
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
  </script>

</body>
</html>