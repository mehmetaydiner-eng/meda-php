<?php
/**
 * includes/footer.php
 * templates/base.html dosyasının kapanış (script) kısmının PHP karşılığı.
 * Her sayfada içerik bittikten sonra require edilir.
 *
 * Sayfaya özel ekstra script için $extra_js değişkenini header'dan önce
 * tanımlamanıza gerek yok; bu dosyadan önce bir string olarak set edip
 * echo edebilirsiniz: $extra_js = '<script>...</script>';
 */
if (!isset($extra_js)) $extra_js = '';
?>
</div><!-- /.main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

<?= $extra_js ?>

<script>
    // Hamburger menü
    document.getElementById('hamburger').addEventListener('click', function() {
        document.getElementById('navMenu').classList.toggle('open');
    });

    // Alert'leri otomatik kapat - "alert-kalici" sınıflı olanlar hariç
    // (örn. onay gerektiren uyarılar - kullanıcı okuyup bir işlem yapana
    // kadar kaybolmamalı, bkz. fatura_xml_onizleme.php'deki iptal uyarısı)
    setTimeout(function() {
        document.querySelectorAll('.alert:not(.alert-kalici)').forEach(function(alert) {
            new bootstrap.Alert(alert).close();
        });
    }, 5000);

    // ========== TODO BADGE GÜNCELLEME ==========
    function updateTodoBadge() {
        var badge = document.getElementById('todo-badge');
        if (!badge) return;

        fetch('<?= BASE_URL ?>/api/todo_count.php')
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(function(data) {
                badge.textContent = data.count || 0;
            })
            .catch(function() {
                badge.textContent = '0';
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateTodoBadge();
    });

    // ========== DÖVİZ KURU ==========
    document.addEventListener('DOMContentLoaded', function() {
        fetch('https://api.exchangerate-api.com/v4/latest/USD')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                var usdTry = data.rates.TRY;
                var usdEur = data.rates.EUR;
                var eurTry = usdTry / usdEur;

                document.getElementById('usd-kur').textContent = usdTry.toFixed(2);
                document.getElementById('eur-kur').textContent = eurTry.toFixed(2);

                var now = new Date();
                var saat = now.getHours().toString().padStart(2, '0');
                var dakika = now.getMinutes().toString().padStart(2, '0');
                document.getElementById('kur-guncelleme').textContent = '(' + saat + ':' + dakika + ')';
            })
            .catch(function() {
                document.getElementById('usd-kur').textContent = '46.45';
                document.getElementById('eur-kur').textContent = '53.48';
                document.getElementById('kur-guncelleme').textContent = '(kapalı)';
            });
    });

    // ========== TEMA DEĞİŞTİRİCİ ==========
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);

        document.querySelectorAll('.theme-btn').forEach(function(btn) {
            btn.classList.remove('active');
        });

        document.querySelectorAll('.theme-btn').forEach(function(btn) {
            if (btn.classList.contains('theme-btn-' + theme)) {
                btn.classList.add('active');
            }
        });

        localStorage.setItem('theme', theme);
    }

    document.addEventListener('DOMContentLoaded', function() {
        var savedTheme = localStorage.getItem('theme') || 'dark';
        setTheme(savedTheme);
    });
</script>

</body>
</html>
