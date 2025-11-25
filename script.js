$(document).ready(function() {
    
    // --- VARIABEL GLOBAL ---
    let sisaDetik = 0; 
    let timerInterval;

    // --- 1. LOGIC LOGIN (CONNECT DATABASE) ---
    $('#form-login').on('submit', function(e) {
        e.preventDefault(); // Jangan refresh halaman

        let usernameVal = $('#username').val();
        let passwordVal = $('#password').val();

        // Tombol jadi loading
        let btnSubmit = $(this).find('button[type="submit"]');
        btnSubmit.text('Memproses...').prop('disabled', true);

        // AJAX Request ke Backend PHP
        $.ajax({
            url: 'api/login.php', // Pastikan file ini ada di folder api/
            type: 'POST',
            data: {
                username: usernameVal,
                password: passwordVal
            },
            dataType: 'json',
            success: function(response) {
                // Balikin tombol
                btnSubmit.text('MASUK SEKARANG').prop('disabled', false);

                if(response.status === 'success') {
                    // --- LOGIN BERHASIL ---
                    let userData = response.data;
                    
                    // Sembunyikan Login
                    $('#login-page').fadeOut(300, function() {
                        
                        // CEK ROLE: Apakah Admin atau User?
                        if(userData.role_name === 'Admin') {
                            // Tampilkan Halaman Admin
                            $('#admin-page').fadeIn(300);
                        } else {
                            // Tampilkan Halaman User
                            $('#dashboard-page').fadeIn(300);
                            $('#display-username').text(userData.username);
                            
                            // AMBIL WAKTU DARI DATABASE
                            // userData.billing_seconds didapat dari PHP
                            sisaDetik = parseInt(userData.billing_seconds);
                            mulaiTimer();
                        }
                    });

                } else {
                    // --- LOGIN GAGAL ---
                    alert("Gagal: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                btnSubmit.text('MASUK SEKARANG').prop('disabled', false);
                console.error(xhr.responseText);
                alert("Terjadi kesalahan sistem! Cek Console.");
            }
        });
    });

    // --- 2. LOGIC TIMER ---
    function mulaiTimer() {
        // Reset timer biar ga dobel
        clearInterval(timerInterval);

        timerInterval = setInterval(function() {
            if(sisaDetik > 0) {
                sisaDetik--; // Kurangi 1 detik
                updateTampilanWaktu();
                cekNotifikasi();
            } else {
                // Waktu Habis
                clearInterval(timerInterval);
                $('#timer').text("WAKTU HABIS");
                alert("Waktu Habis! Komputer akan terkunci.");
                // Disini nanti bisa tambah logic logout otomatis
            }
        }, 1000); // 1000 ms = 1 detik
    }

    function updateTampilanWaktu() {
        let jam = Math.floor(sisaDetik / 3600);
        let sisa = sisaDetik % 3600;
        let menit = Math.floor(sisa / 60);
        let detik = sisa % 60;

        // Tambah angka 0 di depan biar rapi (01:05:09)
        let formatWaktu = 
            (jam < 10 ? "0" + jam : jam) + ":" + 
            (menit < 10 ? "0" + menit : menit) + ":" + 
            (detik < 10 ? "0" + detik : detik);
        
        $('#timer').text(formatWaktu);
    }

    // --- 3. LOGIC NOTIFIKASI (Req Non-Functional) ---
    function cekNotifikasi() {
        // Jika sisa waktu kurang dari 1 menit (60 detik)
        if(sisaDetik <= 60 && sisaDetik > 0) {
            $('#timer').addClass('blink-red');
            $('#alert-box').html('<div class="alert alert-warning py-1">⚠️ Waktu tinggal sedikit!</div>');
        } else {
            $('#timer').removeClass('blink-red');
            $('#alert-box').empty();
        }
    }

    // --- 4. LOGIC LOGOUT ---
    $('.btn-logout').click(function() {
        // Sembunyikan dashboard, munculkan login
        $('#dashboard-page').hide();
        $('#admin-page').hide();
        $('#login-page').show();
        
        // Reset Form & Timer
        $('#username').val('');
        $('#password').val('');
        clearInterval(timerInterval);
    });
});