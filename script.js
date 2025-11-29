$(document).ready(function() {
    
    // --- VARIABEL GLOBAL ---
    let sisaDetik = 0; 
    let timerInterval;

    // ==========================================
    // 0. CEK AUTO LOGIN (Remember Me)
    // ==========================================
    cekStatusLogin();

    function cekStatusLogin() {
        let savedUser = sessionStorage.getItem('warnet_user');
        if(savedUser) {
            let userData = JSON.parse(savedUser);
            masukDashboard(userData);
        }
    }

    // Fungsi Utama Pengatur Dashboard
    function masukDashboard(userData) {
        $('#login-page').removeClass('d-flex').hide();

        // JIKA ADMIN
        if(userData.role_name === 'Admin') {
            $('#admin-page').fadeIn(300);
            loadAdminTable(); // Load Tabel User
        } 
        // JIKA USER BIASA
        else {
            $('#dashboard-page').fadeIn(300);
            $('#display-username').text(userData.username);
            
            // Ambil waktu terbaru & data lain
            $.ajax({
                url: 'api/get_time.php',
                type: 'POST',
                data: { username: userData.username },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        sisaDetik = parseInt(response.billing_seconds);
                        mulaiTimer();
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
    // 2. LOGIC USER (TIMER & BELI)
    // ==========================================
    
    // Timer dengan Auto Save per 5 Detik
    function mulaiTimer() {
        clearInterval(timerInterval);
        let currentUser = $('#display-username').text();

        timerInterval = setInterval(function() {
            if(sisaDetik > 0) {
                sisaDetik--; 
                updateTampilanWaktu();
                cekNotifikasi();

                // Auto Save
                if(sisaDetik % 5 === 0) {
                    simpanWaktuKeDatabase(currentUser, 5);
                }
            } else {
                clearInterval(timerInterval);
                $('#timer').text("WAKTU HABIS");
                alert("Waktu Habis!");
                forceLogout();
            }
        }, 1000); 
    }

    function simpanWaktuKeDatabase(username, jumlahDetik) {
        $.ajax({
            url: 'api/update_time.php',
            type: 'POST',
            data: { username: username, seconds: jumlahDetik }
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

    // Beli Paket (User)
    $(document).on('click', '.btn-beli', function() {
        let paketID = $(this).data('paket-id');
        let currentUser = $('#display-username').text();

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
                        fetchPurchaseHistory(currentUser);
                    } else {
                        alert("❌ Gagal: " + response.message);
                    }
                }
            });
        }
    });

    // ==========================================
    // 3. LOGIC ADMIN (CRUD & TOPUP)
    // ==========================================

    // Load Tabel User
    window.loadAdminTable = function() {
        $.ajax({
            url: 'api/get_users.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    let html = '';
                    response.data.forEach(function(u) {
                        let jam = Math.floor(u.billing_seconds / 3600);
                        let menit = Math.floor((u.billing_seconds % 3600) / 60);
                        let displayTime = `${jam} Jam ${menit} Menit`;
                        let badgeClass = u.billing_seconds > 0 ? 'bg-success' : 'bg-danger';

                        html += `<tr>
                            <td>${u.id}</td>
                            <td class="fw-bold">${u.username}</td>
                            <td><span class="badge ${badgeClass}">${displayTime}</span></td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="hapusUser('${u.username}')">Hapus</button>
                            </td>
                        </tr>`;
                    });
                    $('#table-users-body').html(html);
                }
            }
        });
    };

    // Top Up Manual Admin
    $('#form-topup-manual').on('submit', function(e) {
        e.preventDefault();
        let targetUser = $('#adm-username').val();
        let targetMenit = $('#adm-menit').val();

        if(confirm(`Yakin tambah ${targetMenit} menit untuk ${targetUser}?`)) {
            $.ajax({
                url: 'api/admin_topup.php',
                type: 'POST',
                data: { username: targetUser, minutes: targetMenit },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        alert("✅ " + response.message);
                        $('#adm-username').val('');
                        $('#adm-menit').val('');
                        loadAdminTable();
                    } else {
                        alert("❌ Gagal: " + response.message);
                    }
                }
            });
        }
    });

    // Hapus User
    window.hapusUser = function(targetUser) {
        if(confirm(`⚠️ PERINGATAN: Yakin hapus user '${targetUser}' permanen?`)) {
            $.ajax({
                url: 'api/delete_user.php',
                type: 'POST',
                data: { username: targetUser },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        alert("User berhasil dihapus.");
                        loadAdminTable();
                    } else {
                        alert("Gagal menghapus user.");
                    }
                }
            });
        }
    };

    // ==========================================
    // 4. HELPER FUNCTIONS (USER)
    // ==========================================
    function fetchPaketBilling() {
        $.ajax({
            url: 'api/paket.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    let html = '';
                    response.data.forEach(function(it) {
                        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold">${escapeHtml(it.paket_name)}</div>
                                <div><small class="text-muted mt-1">Rp ${Number(it.price).toLocaleString('id-ID')}</small></div>
                            </div>
                            <button class="btn btn-outline-primary btn-sm btn-beli" data-paket-id="${it.id}">Beli</button>
                        </li>`;
                    });
                    $('#paket-list').html(html);
                }
            }
        });
    }

    function fetchPurchaseHistory(username) {
        $.ajax({
            url: 'api/history.php',
            type: 'POST',
            data: { username: username },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    let html = '<ul class="list-group">';
                    response.data.forEach(function(it) {
                        html += `<li class="list-group-item d-flex justify-content-between">
                            <div>
                                <div class="fw-bold">${escapeHtml(it.paket_name)}</div>
                                <div><small class="text-muted">${it.created_at}</small></div>
                            </div>
                            <div class="text-end">Rp ${Number(it.price).toLocaleString('id-ID')}</div>
                        </li>`;
                    });
                    html += '</ul>';
                    $('#purchase-history').html(html);
                }
            }
        });
    }

    function escapeHtml(text) { return $('<div/>').text(text).html(); }

    $('.btn-logout').click(function() { forceLogout(); });

    function forceLogout() {
        sessionStorage.removeItem('warnet_user');
        $('#dashboard-page').hide();
        $('#admin-page').hide();
        $('#login-page').addClass('d-flex').fadeIn(300);
        $('#username').val('');
        $('#password').val('');
        clearInterval(timerInterval);
    }
});