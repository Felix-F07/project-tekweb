$(document).ready(function() {
    
    // --- VARIABEL GLOBAL ---
    let sisaDetik = 0; 
    let timerInterval;

    // ==========================================
    // 0. CEK AUTO LOGIN (Remember Me)
    // ==========================================
    cekStatusLogin();

    function cekStatusLogin() {
        // Cek Session Storage (Hilang kalau browser ditutup, Aman buat warnet)
        let savedUser = sessionStorage.getItem('warnet_user');
        
        if(savedUser) {
            let userData = JSON.parse(savedUser);
            masukDashboard(userData);
        }
    }

    function masukDashboard(userData) {
        $('#login-page').removeClass('d-flex').hide();

        if(userData.role_name === 'Admin') {
            $('#admin-page').fadeIn(300);
        } else {
            $('#dashboard-page').fadeIn(300);
            $('#display-username').text(userData.username);
            
            // Ambil waktu terbaru dari Database biar sinkron
            $.ajax({
                url: 'api/get_time.php',
                type: 'POST',
                data: { username: userData.username },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        sisaDetik = parseInt(response.billing_seconds);
                        mulaiTimer();
                        
                        // Ambil paket dari DB dan riwayat pembelian untuk user ini
                        fetchPaketBilling();
                        fetchPurchaseHistory(userData.username);
                    }
                }
            });
        }
    }

    // ==========================================
    // 1. LOGIC LOGIN
    // ==========================================
    $('#form-login').on('submit', function(e) {
        e.preventDefault(); 
        let usernameVal = $('#username').val();
        let passwordVal = $('#password').val();
        let btnSubmit = $(this).find('button[type="submit"]');
        
        btnSubmit.text('Memproses...').prop('disabled', true);

        $.ajax({
            url: 'api/login.php', 
            type: 'POST',
            data: { username: usernameVal, password: passwordVal },
            dataType: 'json',
            success: function(response) {
                btnSubmit.text('MASUK SEKARANG').prop('disabled', false);

                if(response.status === 'success') {
                    let userData = response.data;
                    
                    // Simpan sesi login
                    sessionStorage.setItem('warnet_user', JSON.stringify(userData));
                    masukDashboard(userData);

                } else {
                    alert("Gagal: " + response.message);
                }
            },
            error: function() {
                btnSubmit.text('MASUK SEKARANG').prop('disabled', false);
                alert("Error koneksi server!");
            }
        });
    });

    // ==========================================
    // 2. LOGIC BELI PAKET (TOP UP)
    // ==========================================
    // Delegate click untuk tombol beli yang dibuat dinamis
    $(document).on('click', '.btn-beli', function() {
        let paketID = $(this).data('paket-id');
        let currentUser = $('#display-username').text();

        if(!paketID) return alert('ID paket tidak ditemukan.');

        if(confirm("Yakin ingin membeli paket ini?")) {
            $.ajax({
                url: 'api/buy.php',
                type: 'POST',
                data: { username: currentUser, paket_id: paketID },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        alert("✅ " + response.message);
                        sisaDetik = parseInt(response.new_time);
                        updateTampilanWaktu(); 
                        // Refresh history setelah pembelian
                        fetchPurchaseHistory(currentUser);
                    } else {
                        alert("❌ Gagal: " + response.message);
                    }
                },
                error: function() {
                    alert('Gagal koneksi ke server saat membeli paket.');
                }
            });
        }
    });

    // ==========================================
    // 3. LOGIC TIMER (AKURASI TINGGI)
    // ==========================================
    function mulaiTimer() {
        clearInterval(timerInterval);
        let currentUser = $('#display-username').text();

        timerInterval = setInterval(function() {
            if(sisaDetik > 0) {
                sisaDetik--; 
                updateTampilanWaktu();
                cekNotifikasi();

                // [UPDATE] AUTO SAVE SETIAP 5 DETIK
                // Biar kalau direfresh, waktunya gak balik jauh-jauh
                if(sisaDetik % 5 === 0) {
                    simpanWaktuKeDatabase(currentUser, 5); // Lapor kurangi 5 detik
                }

            } else {
                clearInterval(timerInterval);
                $('#timer').text("WAKTU HABIS");
                alert("Waktu Habis!");
                forceLogout();
            }
        }, 1000); 
    }

    // Fungsi Lapor ke Backend (Sekarang terima parameter jumlah detik)
    function simpanWaktuKeDatabase(username, jumlahDetik) {
        $.ajax({
            url: 'api/update_time.php',
            type: 'POST',
            data: { 
                username: username, 
                seconds: jumlahDetik // Kirim angka 5
            },
            success: function(response) {
                // Console log dimatikan biar gak nyampah di console
                // console.log("Auto-save sukses"); 
            }
        });
    }

    function updateTampilanWaktu() {
        let jam = Math.floor(sisaDetik / 3600);
        let sisa = sisaDetik % 3600;
        let menit = Math.floor(sisa / 60);
        let detik = sisa % 60;
        let formatWaktu = (jam < 10 ? "0" + jam : jam) + ":" + (menit < 10 ? "0" + menit : menit) + ":" + (detik < 10 ? "0" + detik : detik);
        $('#timer').text(formatWaktu);
    }

    function cekNotifikasi() {
        if(sisaDetik <= 60 && sisaDetik > 0) {
            $('#timer').addClass('blink-red');
            $('#alert-box').html('<div class="alert alert-warning py-1">⚠️ Waktu tinggal sedikit!</div>');
        } else {
            $('#timer').removeClass('blink-red');
            $('#alert-box').empty();
        }
    }

    // ==========================================
    // 5. FETCH & RENDER HISTORY PEMBELIAN
    // ==========================================
    function fetchPurchaseHistory(username) {
        $.ajax({
            url: 'api/history.php',
            type: 'POST',
            data: { username: username },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    renderHistory(response.data);
                } else {
                    $('#purchase-history').html('<p class="text-muted">Tidak ada riwayat pembelian.</p>');
                }
            },
            error: function() {
                $('#purchase-history').html('<p class="text-danger">Gagal memuat riwayat.</p>');
            }
        });
    }

    function renderHistory(items) {
        if(!items || items.length === 0) {
            $('#purchase-history').html('<p class="text-muted">Belum ada riwayat pembelian.</p>');
            return;
        }

        let html = '<ul class="list-group">';
        items.forEach(function(it) {
            html += '<li class="list-group-item d-flex justify-content-between align-items-start">'
                + '<div>'
                + '<div class="fw-bold">' + escapeHtml(it.paket_name) + '</div>'
                + (it.paket_description ? '<div><small class="text-muted">' + escapeHtml(it.paket_description) + '</small></div>' : '')
                + '<div><small class="text-muted">' + escapeHtml(it.created_at) + '</small></div>'
                + '</div>'
                + '<div class="text-end">Rp ' + formatPrice(it.price) + '</div>'
                + '</li>';
        });
        html += '</ul>';

        $('#purchase-history').html(html);
    }

    function formatPrice(val) {
        if(val === null || val === undefined) return '-';
        return Number(val).toLocaleString('id-ID');
    }

    // Very small helper to avoid XSS when inserting text
    function escapeHtml(text) {
        return $('<div/>').text(text).html();
    }

    // ==========================================
    // 6. FETCH PAKET BILLING DARI DATABASE
    // ==========================================
    function fetchPaketBilling() {
        $.ajax({
            url: 'api/paket.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    renderPaketList(response.data);
                } else {
                    $('#paket-list').html('<li class="list-group-item text-danger">Gagal memuat paket.</li>');
                }
            },
            error: function() {
                $('#paket-list').html('<li class="list-group-item text-danger">Gagal terhubung ke server.</li>');
            }
        });
    }

    function renderPaketList(items) {
        if(!items || items.length === 0) {
            $('#paket-list').html('<li class="list-group-item text-muted">Tidak ada paket tersedia.</li>');
            return;
        }

        let html = '';
        items.forEach(function(it) {
            html += '<li class="list-group-item d-flex justify-content-between align-items-center">'
                + '<div>'
                + '<div class="fw-bold">' + escapeHtml(it.paket_name) + '</div>'
                + (it.description ? '<div><small class="text-muted">' + escapeHtml(it.description) + '</small></div>' : '')
                + '<div><small class="text-muted mt-1">Rp ' + formatPrice(it.price) + '</small></div>'
                + '</div>'
                + '<button class="btn btn-outline-primary btn-sm btn-beli" data-paket-id="' + it.id + '">Beli</button>'
                + '</li>';
        });

        $('#paket-list').html(html);
    }

    // ==========================================
    // 4. LOGIC LOGOUT
    // ==========================================
    $('.btn-logout').click(function() {
        forceLogout();
    });

    function forceLogout() {
        sessionStorage.removeItem('warnet_user'); // Hapus sesi
        
        $('#dashboard-page').hide();
        $('#admin-page').hide();
        $('#login-page').addClass('d-flex').fadeIn(300);
        
        $('#username').val('');
        $('#password').val('');
        clearInterval(timerInterval);
    }
});