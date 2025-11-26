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
    $('.btn-beli').click(function() {
        let paketID = $(this).data('id');
        let tambahDetik = 0;
        if(paketID == 1) tambahDetik = 3600;
        if(paketID == 2) tambahDetik = 7200;
        if(paketID == 3) tambahDetik = 18000;

        let currentUser = $('#display-username').text();

        if(confirm("Yakin ingin membeli paket ini?")) {
            $.ajax({
                url: 'api/buy.php',
                type: 'POST',
                data: { username: currentUser, seconds: tambahDetik },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        alert("✅ " + response.message);
                        sisaDetik = parseInt(response.new_time);
                        updateTampilanWaktu(); 
                    } else {
                        alert("❌ Gagal: " + response.message);
                    }
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