<script>
    // ============================================================
    // GLOBAL DEĞİŞKENLER
    // ============================================================
    var sepet = [];
    var aramaTimeout = null;
    var currentBelge = 'FAT';
    var allCariler = [];
    var secilenUrunId = null;
    var alisFiyatiDegeri = 0;
    var alisFiyatiDoviz = 'TL';
    var API_BASE = <?= json_encode(BASE_URL) ?>;
    var ODEME_HESAPLARI = <?= json_encode(array_map(fn($h) => ['id' => (int)$h['id'], 'ad' => $h['hesap_adi']], $hesaplar)) ?>;
    var ODEME_KANALLARI = <?= json_encode($odeme_kanallari) ?>;
    var odemeSatirSayaci = 0;
    var karGorunur = false;

    // ============================================================
    // SAYFA YÜKLENDİĞİNDE
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        primPopupKontrolEt();
        odemeSatiriEkle();

        var aramaInput = document.getElementById('urunArama');
        if (aramaInput) {
            aramaInput.addEventListener('input', function() {
                clearTimeout(aramaTimeout);
                aramaTimeout = setTimeout(function() {
                    urunAra();
                }, 300);
            });
        }

        document.getElementById('urunArama').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                urunAra();
            }
        });

        document.getElementById('eklenecekKdv').value = 20;

        document.querySelectorAll('.belge-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                currentBelge = this.dataset.value;
                evrakNoGuncelle();
            });
        });

        document.getElementById('cariSelect').addEventListener('change', function() {
            document.getElementById('cariIdHidden').value = this.value;
        });

        document.getElementById('sepetTbody').addEventListener('input', function(e) {
            if (e.target.classList.contains('edit-input')) {
                var index = e.target.dataset.index;
                var alan = e.target.dataset.alan;
                var deger = e.target.value;
                if (index !== undefined && alan) {
                    sepetDuzenleAnlik(parseInt(index), alan, deger);
                }
            }
        });

        document.getElementById('sepetTbody').addEventListener('change', function(e) {
            if (e.target.classList.contains('edit-input')) {
                var index = e.target.dataset.index;
                var alan = e.target.dataset.alan;
                var deger = e.target.value;
                if (index !== undefined && alan) {
                    sepetDuzenle(parseInt(index), alan, deger);
                }
            }
        });

        setTimeout(function() {
            evrakNoGuncelle();
        }, 500);

        loadCariler();

        var cariModal = document.getElementById('yeniCariModal');
        if (cariModal) {
            cariModal.addEventListener('hidden.bs.modal', function() {
                document.getElementById('modal_cari_unvan').value = '';
                document.getElementById('modal_cari_turu').value = 'MÜŞTERİ';
                document.getElementById('modal_cari_vergi_no').value = '';
                document.getElementById('modal_cari_vd').value = '';
                document.getElementById('modal_cari_tel').value = '';
                document.getElementById('modal_cari_email').value = '';
                document.getElementById('modal_cari_yetkili').value = '';
                document.getElementById('modal_cari_adres').value = '';
                document.getElementById('modal_cari_aciklama').value = '';
            });
        }

        var urunModal = document.getElementById('yeniUrunModal');
        if (urunModal) {
            urunModal.addEventListener('hidden.bs.modal', function() {
                document.getElementById('modal_urun_kodu').value = '';
                document.getElementById('modal_urun_adi').value = '';
                document.getElementById('modal_barkod').value = '';
                document.getElementById('modal_seri_no').value = '';
                document.getElementById('modal_kategori').value = '';
                document.getElementById('modal_aciklama').value = '';
                document.getElementById('modal_stok_miktari').value = '0';
                document.getElementById('modal_min_stok').value = '0';
                document.getElementById('modal_max_stok').value = '0';
                document.getElementById('modal_alis_fiyati').value = '0';
                document.getElementById('modal_satis_fiyati').value = '0';
            });
        }

        document.getElementById('karToggleBtn').addEventListener('click', toggleKarGoster);
    });

    // ============================================================
    // CARİ İŞLEMLERİ
    // ============================================================
    function loadCariler() {
        fetch(API_BASE + '/api/cari_ara.php?q=&tur=TÜMÜ')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                allCariler = data;
                var select = document.getElementById('cariSelect');
                select.innerHTML = '<option value="">-- CARİ SEÇİN --</option>';
                allCariler.forEach(function(cari) {
                    var option = document.createElement('option');
                    option.value = cari.id;
                    option.text = cari.unvan;
                    select.appendChild(option);
                });
            })
            .catch(function(error) {
                console.log('Cari listesi yüklenemedi:', error);
            });
    }

    function cariAra(q) {
        var select = document.getElementById('cariSelect');
        var search = q.toLowerCase().trim();
        var secili = select.value;

        select.innerHTML = '<option value="">-- CARİ SEÇİN --</option>';

        if (search.length === 0) {
            allCariler.forEach(function(cari) {
                var option = document.createElement('option');
                option.value = cari.id;
                option.text = cari.unvan;
                select.appendChild(option);
            });
        } else {
            var found = false;
            allCariler.forEach(function(cari) {
                if (cari.unvan.toLowerCase().includes(search) ||
                    (cari.vergi_no && cari.vergi_no.includes(search)) ||
                    (cari.telefon && cari.telefon.includes(search))) {
                    var option = document.createElement('option');
                    option.value = cari.id;
                    option.text = cari.unvan;
                    select.appendChild(option);
                    found = true;
                }
            });
            if (!found) {
                var option = document.createElement('option');
                option.value = '';
                option.text = 'Sonuç bulunamadı';
                option.disabled = true;
                select.appendChild(option);
            }
        }

        if (secili) {
            select.value = secili;
            document.getElementById('cariIdHidden').value = secili;
        }
    }

    function modalCariKaydet() {
        var unvan = document.getElementById('modal_cari_unvan').value.trim().toUpperCase();
        if (!unvan) {
            showToast('❌ Ünvan zorunludur!', 'error');
            return;
        }

        var formData = new FormData();
        formData.append('csrf_token', CSRF_TOKEN);
        formData.append('unvan', unvan);
        formData.append('cari_turu', document.getElementById('modal_cari_turu').value);
        formData.append('vergi_no', document.getElementById('modal_cari_vergi_no').value.trim().toUpperCase());
        formData.append('vergi_dairesi', document.getElementById('modal_cari_vd').value.trim().toUpperCase());
        formData.append('telefon', document.getElementById('modal_cari_tel').value.trim());
        formData.append('email', document.getElementById('modal_cari_email').value.trim().toLowerCase());
        formData.append('yetkili', document.getElementById('modal_cari_yetkili').value.trim().toUpperCase());
        formData.append('adres', document.getElementById('modal_cari_adres').value.trim().toUpperCase());
        formData.append('aciklama', document.getElementById('modal_cari_aciklama').value.trim().toUpperCase());

        fetch(API_BASE + '/api/cari_ekle_ajax.php', { method: 'POST', body: formData })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    var select = document.getElementById('cariSelect');
                    var option = document.createElement('option');
                    option.value = data.cari_id;
                    option.text = data.unvan + ' - ' + (data.vergi_no || 'VERGİ NO YOK');
                    select.appendChild(option);
                    select.value = data.cari_id;
                    allCariler.push({ id: data.cari_id, unvan: data.unvan, vergi_no: data.vergi_no || '', telefon: data.telefon || '' });

                    var modal = bootstrap.Modal.getInstance(document.getElementById('yeniCariModal'));
                    if (modal) modal.hide();

                    document.getElementById('modal_cari_unvan').value = '';
                    document.getElementById('modal_cari_turu').value = 'MÜŞTERİ';
                    document.getElementById('modal_cari_vergi_no').value = '';
                    document.getElementById('modal_cari_vd').value = '';
                    document.getElementById('modal_cari_tel').value = '';
                    document.getElementById('modal_cari_email').value = '';
                    document.getElementById('modal_cari_yetkili').value = '';
                    document.getElementById('modal_cari_adres').value = '';
                    document.getElementById('modal_cari_aciklama').value = '';

                    showToast('✅ Cari başarıyla eklendi: ' + data.unvan, 'success');
                } else {
                    showToast('❌ Hata: ' + (data.message || 'Cari eklenemedi!'), 'error');
                }
            })
            .catch(function(error) {
                showToast('❌ Hata oluştu: ' + error, 'error');
            });
    }

    // ============================================================
    // EVRAK NUMARASI
    // ============================================================
    function evrakNoGuncelle() {
        var belge = currentBelge || 'FAT';
        var islemTuru = document.getElementById('islemTuru').value;
        // Eğer belge MAKBUZ ise, işlem türüne göre prefix belirle
        var prefix = belge;
        if (belge === 'MAKBUZ') {
            prefix = (islemTuru === 'ALIS') ? 'ALM' : 'STM';
        }
        var goster = document.getElementById('evrakNoGoster');

        fetch(API_BASE + '/api/numara_getir.php?prefix=' + prefix)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    goster.textContent = data.numara;
                    goster.className = 'no-value';
                } else {
                    goster.textContent = 'Hata!';
                    goster.className = 'no-value manual';
                }
            })
            .catch(function() {
                goster.textContent = '---';
                goster.className = 'no-value manual';
            });
    }

    function evrakNoDuzenle() {
        var modal = new bootstrap.Modal(document.getElementById('evrakNoModal'));
        var input = document.getElementById('evrakNoInput');
        var goster = document.getElementById('evrakNoGoster');
        var belgeSelect = document.getElementById('evrakNoBelge');

        var islemTuru = document.getElementById('islemTuru').value;
        // Modal'daki belge seçeneğini doğru set et
        if (currentBelge === 'MAKBUZ') {
            belgeSelect.value = (islemTuru === 'ALIS') ? 'ALM' : 'STM';
        } else {
            belgeSelect.value = currentBelge;
        }

        input.value = goster.textContent;
        modal.show();
    }

    function evrakNoKaydet() {
        var input = document.getElementById('evrakNoInput');
        var goster = document.getElementById('evrakNoGoster');
        var belge = document.getElementById('evrakNoBelge').value;
        var yeniNumara = input.value.trim();
        var islemTuruDeger = document.getElementById('islemTuru').value;

        if (!yeniNumara) {
            evrakNoGuncelle();
            bootstrap.Modal.getInstance(document.getElementById('evrakNoModal')).hide();
            return;
        }

        // ALIŞ işleminde bu alan TEDARİKÇİNİN KENDİ fatura numarasıdır
        if (islemTuruDeger === 'ALIS' && (belge === 'ALM' || belge === 'MAKBUZ')) {
            goster.textContent = yeniNumara;
            goster.className = 'no-value manual';
            bootstrap.Modal.getInstance(document.getElementById('evrakNoModal')).hide();
            showToast('✅ Tedarikçi fatura numarası kaydedildi: ' + yeniNumara, 'success');
            return;
        }

        fetch(API_BASE + '/api/numara_guncelle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prefix: belge, yeni_numara: yeniNumara, csrf_token: CSRF_TOKEN })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                goster.textContent = yeniNumara;
                goster.className = 'no-value manual';
                showToast('✅ Numara kalıcı olarak güncellendi: ' + yeniNumara, 'success');
            } else {
                showToast('❌ Hata: ' + data.message, 'error');
            }
        })
        .catch(function(error) {
            showToast('❌ Hata: ' + error, 'error');
        });
        bootstrap.Modal.getInstance(document.getElementById('evrakNoModal')).hide();
    }

    function belgeDegistir(btn) {
        document.querySelectorAll('.belge-btn').forEach(function(b) {
            b.classList.remove('aktif');
            b.classList.add('btn-outline-secondary');
        });
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('aktif');
        var deger = btn.dataset.value;
        document.getElementById('belgeTuru').value = deger;
        currentBelge = deger;
        evrakNoGuncelle();
    }

    // ============================================================
    // TOAST
    // ============================================================
    function showToast(message, type) {
        var toast = document.createElement('div');
        var bgColor = type === 'success' ? 'var(--badge-success-bg, #1a3a1a)' : 'var(--badge-warning-bg, #3a2a0d)';
        var textColor = type === 'success' ? 'var(--badge-success-text, #8ad4a0)' : 'var(--badge-warning-text, #d4c84a)';
        var borderColor = type === 'success' ? 'var(--border-color, #2a5a2a)' : 'var(--border-color, #5a3a1a)';

        toast.style.cssText = `
            position: fixed; bottom: 20px; right: 20px;
            background: ${bgColor};
            color: ${textColor};
            padding: 12px 20px;
            border-radius: 6px;
            border: 1px solid ${borderColor};
            z-index: 9999;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
            max-width: 400px;
        `;
        toast.innerHTML = message;
        document.body.appendChild(toast);

        setTimeout(function() {
            toast.remove();
        }, 4000);
    }

    // ============================================================
    // ÜRÜN ARAMA
    // ============================================================
    function urunAra() {
        var q = document.getElementById('urunArama').value.trim();
        var sonucDiv = document.getElementById('urunListesiSonuc');

        if (q.length < 1) {
            sonucDiv.innerHTML = '';
            return;
        }

        sonucDiv.innerHTML = '<div class="bos-mesaj"><i class="fas fa-spinner fa-spin"></i> Aranıyor...</div>';

        fetch(API_BASE + '/api/hizli_islem_urun_ara.php?q=' + encodeURIComponent(q))
            .then(function(response) { return response.json(); })
            .then(function(data) {
                var toolbar = document.getElementById('urunListesiToolbar');
                if (!data || data.length === 0) {
                    sonucDiv.innerHTML = '';
                    if (toolbar) toolbar.style.display = 'none';
                    return;
                }
                if (toolbar) toolbar.style.display = 'block';

                var islemTuru = document.getElementById('islemTuru').value;

                var html = '';
                data.forEach(function(urun, index) {
                    console.log('Ürün verisi:', urun);

                    var stok = parseFloat(urun.stok_miktari) || 0;
                    // İşlem türüne göre fiyat seç
                    var fiyatOrijinal, fiyatDoviz;
                    if (islemTuru === 'ALIS') {
                        fiyatOrijinal = parseFloat(urun.alis_fiyati) || 0;
                        fiyatDoviz = urun.alis_fiyati_doviz || 'TL';
                    } else {
                        fiyatOrijinal = parseFloat(urun.satis_fiyati) || 0;
                        fiyatDoviz = urun.satis_fiyati_doviz || 'TL';
                    }
                    var fiyatTL = paraBirimindenTLyeCevir(fiyatOrijinal, fiyatDoviz);
                    var alisOrijinal = parseFloat(urun.alis_fiyati) || 0;
                    var alisDoviz = urun.alis_fiyati_doviz || 'TL';
                    var alisTL = paraBirimindenTLyeCevir(alisOrijinal, alisDoviz);
                    var stokClass = stok <= 0 ? 'dusuk' : 'normal';
                    var adGuvenli = urun.urun_adi.replace(/"/g, '&quot;');

                    var fiyatGosterimi = fiyatDoviz === 'TL'
                        ? fiyatTL.toFixed(2) + ' ₺'
                        : fiyatOrijinal.toFixed(2) + ' ' + fiyatDoviz + ' <small>(≈' + fiyatTL.toFixed(2) + ' ₺)</small>';

                    html += `
                        <div class="urun-item${index === 0 ? ' secili' : ''}" data-id="${urun.id}" data-fiyat-tl="${fiyatTL}" data-alisfiyat-tl="${alisTL}" data-ad="${adGuvenli}"
                             onclick="if (event.target.type !== 'checkbox' && !event.target.closest('button')) urunSatiriSec(this)">
                            <input type="checkbox" class="urun-secim-checkbox" style="margin-right: 8px;" onclick="event.stopPropagation();">
                            <div>
                                <div class="urun-adi">${urun.urun_adi}</div>
                                <div class="urun-detay">Kod: ${urun.urun_kodu || '-'} | Barkod: ${urun.barkod || '-'}</div>
                            </div>
                            <div class="text-end">
                                <div class="urun-fiyat">${fiyatGosterimi}</div>
                                <div class="urun-stok ${stokClass}">Stok: ${stok}</div>
                            </div>
                            <div style="display: flex; gap: 4px;">
                                <button type="button" class="btn-detay-goster" onclick="urunDetayGoster(${urun.id})" title="Ürün detayını göster">
                                    <i class="fas fa-eye"></i> GÖSTER
                                </button>
                                <button type="button" class="btn-sepete-ekle" onclick="sepeteEkle(${urun.id}, '${urun.urun_adi.replace(/'/g, "\\'")}', ${fiyatTL}, ${alisTL})">
                                    <i class="fas fa-cart-plus"></i> EKLE
                                </button>
                            </div>
                        </div>
                    `;
                });

                sonucDiv.innerHTML = html;

                var eklenecekFiyatEl = document.getElementById('eklenecekFiyat');
                if (eklenecekFiyatEl && data.length > 0) {
                    var ilkUrun = data[0];
                    var ilkFiyat;
                    if (islemTuru === 'ALIS') {
                        ilkFiyat = paraBirimindenTLyeCevir(parseFloat(ilkUrun.alis_fiyati) || 0, ilkUrun.alis_fiyati_doviz || 'TL');
                    } else {
                        ilkFiyat = paraBirimindenTLyeCevir(parseFloat(ilkUrun.satis_fiyati) || 0, ilkUrun.satis_fiyati_doviz || 'TL');
                    }
                    eklenecekFiyatEl.value = ilkFiyat.toFixed(2);
                }
            })
            .catch(function(error) {
                console.error('Arama hatası:', error);
                sonucDiv.innerHTML = '';
            });
    }

    function urunSatiriSec(itemDiv) {
        document.querySelectorAll('.urun-item').forEach(function(el) {
            el.classList.remove('secili');
        });
        itemDiv.classList.add('secili');

        var fiyatKutusu = document.getElementById('eklenecekFiyat');
        if (fiyatKutusu) {
            fiyatKutusu.value = parseFloat(itemDiv.dataset.fiyatTl).toFixed(2);
        }
    }

    function secilileriSepeteEkle() {
        var secilenler = document.querySelectorAll('.urun-item input.urun-secim-checkbox:checked');
        if (secilenler.length === 0) {
            alert('Lütfen eklemek istediğiniz ürünlerin yanındaki kutucukları işaretleyin!');
            return;
        }

        var islemTuru = document.getElementById('islemTuru').value;
        var iskonto = parseFloat(document.getElementById('eklenecekIskonto').value) || 0;
        var kdvRaw = parseFloat(document.getElementById('eklenecekKdv').value); var kdv = isNaN(kdvRaw) ? 20 : kdvRaw;

        var eklenenSayisi = 0;
        secilenler.forEach(function(checkbox) {
            var itemDiv = checkbox.closest('.urun-item');
            if (!itemDiv) return;

            var fiyat = parseFloat(itemDiv.dataset.fiyatTl) || 0;
            var alis = parseFloat(itemDiv.dataset.alisfiyatTl) || 0;
            if (fiyat <= 0) return;

            sepet.push({
                id: itemDiv.dataset.id,
                urun_adi: itemDiv.dataset.ad,
                miktar: 1,
                birim_fiyat: fiyat,
                alis_fiyati: alis,
                iskonto: iskonto,
                kdv_orani: kdv,
            });
            checkbox.checked = false;
            eklenenSayisi++;
        });

        sepetGuncelle();
        document.getElementById('islemYapBtn').disabled = false;
        showToast('✅ ' + eklenenSayisi + ' ürün sepete eklendi!', 'success');
    }

    // ============================================================
    // SEPETE EKLE
    // ============================================================
    function paraBirimindenTLyeCevir(tutar, kaynakBirim) {
        if (kaynakBirim === 'TL' || kaynakBirim === 'TRY' || !kaynakBirim) {
            return tutar;
        }
        var usdKurEl = document.getElementById('usd-kur');
        var eurKurEl = document.getElementById('eur-kur');
        var usdKur = usdKurEl ? parseFloat((usdKurEl.textContent || '').replace(',', '.')) : NaN;
        var eurKur = eurKurEl ? parseFloat((eurKurEl.textContent || '').replace(',', '.')) : NaN;

        if (kaynakBirim === 'USD' && !isNaN(usdKur) && usdKur > 0) {
            return tutar * usdKur;
        }
        if (kaynakBirim === 'EUR' && !isNaN(eurKur) && eurKur > 0) {
            return tutar * eurKur;
        }
        return tutar;
    }

    function sepeteEkle(id, ad, fiyat, alis) {
        var miktar = parseInt(document.getElementById('eklenecekMiktar').value) || 1;
        var iskonto = parseFloat(document.getElementById('eklenecekIskonto').value) || 0;
        var kdvRaw = parseFloat(document.getElementById('eklenecekKdv').value); var kdv = isNaN(kdvRaw) ? 20 : kdvRaw;

        if (miktar <= 0) { alert('Geçerli miktar girin!'); return; }
        if (fiyat <= 0) { alert('Geçerli fiyat girin!'); return; }
        if (iskonto < 0 || iskonto > 100) { alert('İskonto 0-100 arasında olmalı!'); return; }
        if (kdv < 0 || kdv > 100) { alert('KDV 0-100 arasında olmalı!'); return; }

        sepet.push({
            id: id,
            urun_adi: ad,
            miktar: miktar,
            birim_fiyat: fiyat,
            alis_fiyati: alis || 0,
            iskonto: iskonto,
            kdv_orani: kdv
        });
        sepetGuncelle();
        document.getElementById('islemYapBtn').disabled = false;
    }

    function sepeteEkleFromSelected() {
        var firstItem = document.querySelector('.urun-item.secili') || document.querySelector('.urun-item');
        if (!firstItem) {
            alert('Lütfen önce bir ürün arayıp seçin!');
            return;
        }

        var id = firstItem.dataset.id;
        var ad = firstItem.querySelector('.urun-adi').textContent;

        var fiyatKutusu = document.getElementById('eklenecekFiyat');
        var fiyat = (fiyatKutusu && parseFloat(fiyatKutusu.value) > 0)
            ? parseFloat(fiyatKutusu.value)
            : parseFloat(firstItem.dataset.fiyatTl) || 0;
        var alis = parseFloat(firstItem.dataset.alisfiyatTl) || 0;

        var miktar = parseInt(document.getElementById('eklenecekMiktar').value) || 1;
        var iskonto = parseFloat(document.getElementById('eklenecekIskonto').value) || 0;
        var kdvRaw = parseFloat(document.getElementById('eklenecekKdv').value); var kdv = isNaN(kdvRaw) ? 20 : kdvRaw;

        if (miktar <= 0) { alert('Geçerli miktar girin!'); return; }
        if (fiyat <= 0) { alert('Geçerli fiyat girin!'); return; }
        if (iskonto < 0 || iskonto > 100) { alert('İskonto 0-100 arasında olmalı!'); return; }
        if (kdv < 0 || kdv > 100) { alert('KDV 0-100 arasında olmalı!'); return; }

        sepet.push({
            id: id,
            urun_adi: ad,
            miktar: miktar,
            birim_fiyat: fiyat,
            alis_fiyati: alis,
            iskonto: iskonto,
            kdv_orani: kdv
        });
        sepetGuncelle();
        document.getElementById('islemYapBtn').disabled = false;
        showToast('✅ ' + ad + ' sepete eklendi!', 'success');
    }

    // ============================================================
    // SEPET GÜNCELLE
    // ============================================================
    function sepetGuncelle() {
        var tbody = document.getElementById('sepetTbody');
        if (!tbody) return;

        var bos = document.getElementById('bos-sepet');
        if (bos) bos.remove();

        if (sepet.length === 0) {
            tbody.innerHTML = '<tr id="bos-sepet"><td colspan="9" class="text-center text-muted py-2">Sepet boş</td></tr>';
            document.getElementById('islemYapBtn').disabled = true;
            document.getElementById('araToplam').textContent = '0';
            document.getElementById('toplamIskonto').textContent = '0';
            document.getElementById('toplamKdv').textContent = '0';
            document.getElementById('genelToplam').textContent = '0';
            document.getElementById('netKarValue').textContent = '0';
            document.getElementById('alisFiyatCol').style.display = 'none';
            return;
        }

        var html = '';
        var araToplam = 0, toplamIskonto = 0, toplamKdv = 0, netKar = 0;

        sepet.forEach(function(item, index) {
            var satirToplam = item.miktar * item.birim_fiyat;
            var iskontoTutari = satirToplam * (item.iskonto / 100);
            var iskontoSonrasi = satirToplam - iskontoTutari;
            var kdvTutari = iskontoSonrasi * (item.kdv_orani / 100);
            var genelToplam = iskontoSonrasi + kdvTutari;

            var kar = (item.birim_fiyat - (item.alis_fiyati || 0)) * item.miktar;

            araToplam += satirToplam;
            toplamIskonto += iskontoTutari;
            toplamKdv += kdvTutari;
            netKar += kar;

            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td class="urun-adi-cell">${item.urun_adi}</td>
                    <td class="text-center">
                        <input type="text" class="edit-input" data-index="${index}" data-alan="miktar"
                               value="${item.miktar}" style="text-align:center; width:50px;">
                    </td>
                    <td class="text-end">
                        <input type="text" class="edit-input edit-input-fiyat" data-index="${index}" data-alan="birim_fiyat"
                               value="${item.birim_fiyat}" style="text-align:right; width:70px;">
                    </td>
                    <td class="text-end alis-fiyat-cell" style="display:none;">${item.alis_fiyati.toFixed(2)}</td>
                    <td class="text-center">
                        <input type="text" class="edit-input" data-index="${index}" data-alan="iskonto"
                               value="${item.iskonto}" style="text-align:center; width:45px;">
                    </td>
                    <td class="text-center">
                        <input type="text" class="edit-input" data-index="${index}" data-alan="kdv_orani"
                               value="${item.kdv_orani}" style="text-align:center; width:45px;">
                    </td>
                    <td class="text-end"><span class="sepet-satir-toplam">${genelToplam.toFixed(0)}</span></td>
                    <td class="text-center">
                        <button type="button" class="btn-danger-sm" onclick="sepetSil(${index})" style="padding: 0 4px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;

        document.getElementById('araToplam').textContent = araToplam.toFixed(0);
        document.getElementById('toplamIskonto').textContent = toplamIskonto.toFixed(0);
        document.getElementById('toplamKdv').textContent = toplamKdv.toFixed(0);
        document.getElementById('genelToplam').textContent = (araToplam - toplamIskonto + toplamKdv).toFixed(0);
        document.getElementById('netKarValue').textContent = netKar.toFixed(2);
        document.getElementById('netKarYarim').textContent = (netKar / 2).toFixed(2);

        var karRow = document.getElementById('netKarRow');
        if (karRow) {
            karRow.style.display = karGorunur ? 'table-row' : 'none';
        }

        var alisCol = document.getElementById('alisFiyatCol');
        if (alisCol) {
            alisCol.style.display = karGorunur ? '' : 'none';
        }
        document.querySelectorAll('.alis-fiyat-cell').forEach(function(cell) {
            cell.style.display = karGorunur ? '' : 'none';
        });

        odemeOzetGuncelle();
    }

    // ============================================================
    // ÖDEME DAĞILIMI
    // ============================================================
    function odemeTuruDegisti(selectEl) {
        var satir = selectEl.closest('.odeme-satiri');
        if (!satir) return;
        var hesapSelect = satir.querySelector('.odeme-hesap-select');
        if (!hesapSelect) return;

        if (selectEl.value === 'VERESİYE') {
            hesapSelect.value = '';
            hesapSelect.disabled = true;
        } else {
            hesapSelect.disabled = false;
        }
    }

    function odemeSatiriEkle() {
        odemeSatirSayaci++;
        var satirId = odemeSatirSayaci;

        var kanalOptions = ODEME_KANALLARI.map(function(k) {
            return '<option value="' + k + '"' + (k === 'NAKİT' ? ' selected' : '') + '>' + k + '</option>';
        }).join('');

        var hesapOptions = '<option value="">-- Kasa Seçin --</option>' + ODEME_HESAPLARI.map(function(h) {
            return '<option value="' + h.id + '">' + h.ad + '</option>';
        }).join('');

        var satir = document.createElement('div');
        satir.className = 'row g-1 mb-1 odeme-satiri';
        satir.dataset.satirId = satirId;
        satir.innerHTML =
            '<div class="col-4">' +
                '<select class="form-select-custom odeme-turu-select" style="font-size:11px;padding:2px 8px;height:28px;" onchange="odemeTuruDegisti(this)">' + kanalOptions + '</select>' +
            '</div>' +
            '<div class="col-4">' +
                '<select class="form-select-custom odeme-hesap-select" style="font-size:11px;padding:2px 8px;height:28px;">' + hesapOptions + '</select>' +
            '</div>' +
            '<div class="col-3">' +
                '<input type="number" class="form-control-custom odeme-tutar-input" step="0.01" min="0" value="0" style="font-size:11px;padding:2px 8px;height:28px;text-align:right;" oninput="odemeOzetGuncelle()">' +
            '</div>' +
            '<div class="col-1">' +
                '<button type="button" class="btn-danger-sm" style="height:28px;width:100%;" onclick="odemeSatiriSil(' + satirId + ')"><i class="fas fa-times"></i></button>' +
            '</div>';

        document.getElementById('odemeSatirlari').appendChild(satir);
        odemeOzetGuncelle();
    }

    function odemeSatiriSil(satirId) {
        var satir = document.querySelector('.odeme-satiri[data-satir-id="' + satirId + '"]');
        if (satir) satir.remove();
        odemeOzetGuncelle();
    }

    function odemeOzetGuncelle() {
        var genelToplam = parseFloat(document.getElementById('genelToplam').textContent.replace(/\./g, '').replace(',', '.')) || 0;
        var odenenToplam = 0;

        document.querySelectorAll('.odeme-tutar-input').forEach(function(input) {
            odenenToplam += parseFloat(input.value) || 0;
        });

        var kalan = genelToplam - odenenToplam;

        document.getElementById('odemeGenelToplamGoster').textContent = genelToplam.toFixed(2);
        document.getElementById('odemeOdenenGoster').textContent = odenenToplam.toFixed(2);

        var kalanEl = document.getElementById('odemeKalanGoster');
        kalanEl.textContent = kalan.toFixed(2);
        kalanEl.className = kalan > 0.01 ? 'text-warning' : (kalan < -0.01 ? 'text-danger' : 'text-success');
    }

    // ============================================================
    // SEPET DÜZENLE
    // ============================================================
    function sepetDuzenleAnlik(index, alan, deger) {
        if (index < 0 || index >= sepet.length) return;
        deger = deger.trim();
        if (deger === '' || isNaN(deger)) return;

        var val = parseFloat(deger);
        if (isNaN(val)) return;
        if (alan === 'iskonto' && (val < 0 || val > 100)) return;
        if (alan === 'kdv_orani' && (val < 0 || val > 100)) return;
        if (alan === 'miktar' && val < 0) return;
        if (alan === 'birim_fiyat' && val < 0) return;

        sepet[index][alan] = val;
        sepetToplamlariGuncelle();
    }

    function sepetDuzenle(index, alan, deger) {
        if (index < 0 || index >= sepet.length) return;

        deger = deger.trim();
        if (deger === '' || isNaN(deger)) {
            if (alan === 'miktar') deger = '1';
            else if (alan === 'birim_fiyat') deger = '0';
            else if (alan === 'iskonto') deger = '0';
            else if (alan === 'kdv_orani') deger = '20';
        }

        var val = parseFloat(deger);
        if (isNaN(val)) return;

        if (alan === 'miktar' && val < 0.01) { showToast('❌ Miktar 0.01\'den küçük olamaz!', 'error'); return; }
        if (alan === 'birim_fiyat' && val < 0) { showToast('❌ Fiyat negatif olamaz!', 'error'); return; }
        if (alan === 'iskonto' && (val < 0 || val > 100)) { showToast('❌ İskonto 0-100 arasında olmalı!', 'error'); return; }
        if (alan === 'kdv_orani' && (val < 0 || val > 100)) { showToast('❌ KDV 0-100 arasında olmalı!', 'error'); return; }

        sepet[index][alan] = val;
        sepetToplamlariGuncelle();
    }

    function sepetToplamlariGuncelle() {
        var araToplam = 0, toplamIskonto = 0, toplamKdv = 0, netKar = 0;

        sepet.forEach(function(item, index) {
            var satirToplam = item.miktar * item.birim_fiyat;
            var iskontoTutari = satirToplam * (item.iskonto / 100);
            var iskontoSonrasi = satirToplam - iskontoTutari;
            var kdvTutari = iskontoSonrasi * (item.kdv_orani / 100);
            var genelSatirToplam = iskontoSonrasi + kdvTutari;

            araToplam += satirToplam;
            toplamIskonto += iskontoTutari;
            toplamKdv += kdvTutari;
            netKar += (item.birim_fiyat - (item.alis_fiyati || 0)) * item.miktar;

            var satir = document.querySelector('#sepetTbody tr:nth-child(' + (index + 1) + ')');
            if (satir) {
                var toplamEl = satir.querySelector('.sepet-satir-toplam');
                if (toplamEl) toplamEl.textContent = genelSatirToplam.toFixed(0);
            }
        });

        document.getElementById('araToplam').textContent = araToplam.toFixed(0);
        document.getElementById('toplamIskonto').textContent = toplamIskonto.toFixed(0);
        document.getElementById('toplamKdv').textContent = toplamKdv.toFixed(0);
        document.getElementById('genelToplam').textContent = (araToplam - toplamIskonto + toplamKdv).toFixed(0);
        document.getElementById('netKarValue').textContent = netKar.toFixed(2);

        odemeOzetGuncelle();
    }

    function sepetSil(index) {
        if (index < 0 || index >= sepet.length) return;
        sepet.splice(index, 1);
        sepetGuncelle();
    }

    function sepetTemizle() {
        if (sepet.length === 0) return;
        if (!confirm('Sepeti temizlemek istediğinize emin misiniz?')) return;
        sepet = [];
        sepetGuncelle();
    }

    // ============================================================
    // ÜRÜN DETAY
    // ============================================================
    function dovizKarsiligiGoster(tutar, kaynakBirim) {
        var usdKurEl = document.getElementById('usd-kur');
        var eurKurEl = document.getElementById('eur-kur');
        if (!usdKurEl || !eurKurEl) return '';

        var usdKur = parseFloat((usdKurEl.textContent || '').replace(',', '.'));
        var eurKur = parseFloat((eurKurEl.textContent || '').replace(',', '.'));
        if (!usdKur || !eurKur || isNaN(usdKur) || isNaN(eurKur)) return '';

        var tlTutar;
        if (kaynakBirim === 'USD') {
            tlTutar = tutar * usdKur;
        } else if (kaynakBirim === 'EUR') {
            tlTutar = tutar * eurKur;
        } else {
            tlTutar = tutar;
        }

        var parcalar = [];
        if (kaynakBirim !== 'TL' && kaynakBirim !== 'TRY') {
            parcalar.push(tlTutar.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺');
        }
        if (kaynakBirim !== 'USD') {
            parcalar.push('$' + (tlTutar / usdKur).toFixed(2));
        }
        if (kaynakBirim !== 'EUR') {
            parcalar.push('€' + (tlTutar / eurKur).toFixed(2));
        }

        return parcalar.length
            ? ' <span style="font-size: 12px; color: var(--text-muted); font-weight: 400;">(≈ ' + parcalar.join(' / ') + ')</span>'
            : '';
    }

    function urunDetayGoster(id) {
        secilenUrunId = id;

        var modalElement = document.getElementById('urunDetayModal');
        if (!modalElement) {
            showToast('❌ Hata: Modal bulunamadı!', 'error');
            return;
        }

        document.getElementById('urunDetayLoading').style.display = 'block';
        document.getElementById('urunDetayIcerik').style.display = 'none';

        var modal = new bootstrap.Modal(modalElement);
        modal.show();

        fetch(API_BASE + '/api/urun_detay.php?id=' + id)
            .then(function(response) {
                if (!response.ok) throw new Error('API hatası: ' + response.status);
                return response.json();
            })
            .then(function(data) {
                document.getElementById('urunDetayLoading').style.display = 'none';
                document.getElementById('urunDetayIcerik').style.display = 'block';

                document.getElementById('urunDetayBaslik').textContent = data.urun_adi;

                var fiyatElement = document.getElementById('detay_satis_fiyati');
                if (fiyatElement) {
                    fiyatElement.innerHTML = data.satis_fiyati.toFixed(2) + ' <span class="doviz">' + data.satis_fiyati_doviz + '</span>' +
                        dovizKarsiligiGoster(data.satis_fiyati, data.satis_fiyati_doviz);
                }

                document.getElementById('detay_urun_kodu').textContent = data.urun_kodu;
                document.getElementById('detay_urun_adi').textContent = data.urun_adi;
                document.getElementById('detay_barkod').textContent = data.barkod;
                document.getElementById('detay_seri_no').textContent = data.seri_no;
                document.getElementById('detay_kategori').textContent = data.kategori;
                document.getElementById('detay_birim').textContent = data.birim;
                document.getElementById('detay_urun_tipi').textContent = data.urun_tipi;

                alisFiyatiDegeri = data.alis_fiyati;
                alisFiyatiDoviz = data.alis_fiyati_doviz;

                var alisElement = document.getElementById('detay_alis_fiyati');
                var btn = document.querySelector('.btn-alis-goster');
                var nokta = btn ? btn.querySelector('.nokta') : null;

                if (alisElement) {
                    alisElement.textContent = '•••••';
                    alisElement.className = 'detay-value alis-fiyat-gizli';
                }
                if (btn) {
                    btn.classList.remove('aktif');
                    btn.title = 'Alış fiyatını göster';
                }
                if (nokta) {
                    nokta.textContent = '•';
                }

                var stokElement = document.getElementById('detay_stok_miktari');
                if (stokElement) {
                    stokElement.textContent = data.stok_miktari.toFixed(2) + ' ' + data.birim;
                    if (data.stok_miktari <= 0) {
                        stokElement.style.color = 'var(--badge-danger-text, #d44a4a)';
                    } else if (data.stok_miktari <= data.min_stok) {
                        stokElement.style.color = 'var(--badge-warning-text, #d4c84a)';
                    } else {
                        stokElement.style.color = 'var(--badge-success-text, #4ad46a)';
                    }
                }

                document.getElementById('detay_min_stok').textContent = data.min_stok.toFixed(2) + ' ' + data.birim;
                document.getElementById('detay_max_stok').textContent = data.max_stok.toFixed(2) + ' ' + data.birim;
                document.getElementById('detay_created_at').textContent = data.created_at;
                document.getElementById('detay_aciklama').textContent = data.aciklama || 'Açıklama yok';
            })
            .catch(function(error) {
                document.getElementById('urunDetayLoading').style.display = 'none';
                document.getElementById('urunDetayIcerik').style.display = 'block';
                document.getElementById('detay_urun_adi').textContent = 'Ürün detayları yüklenemedi!';
                document.getElementById('detay_aciklama').textContent = 'Hata: ' + error.message;
                showToast('❌ Ürün detayları yüklenemedi: ' + error.message, 'error');
            });
    }

    function detaydanSepeteEkle() {
        if (!secilenUrunId) {
            showToast('❌ Ürün seçili değil!', 'error');
            return;
        }

        var urunAdi = document.getElementById('detay_urun_adi').textContent;

        var islemTuru = document.getElementById('islemTuru').value;

        var fiyatElement = document.getElementById('detay_satis_fiyati');
        var fiyatText = fiyatElement ? fiyatElement.textContent.trim() : '0';
        var fiyat = parseFloat(fiyatText.replace(' TL', '').replace(' USD', '').replace(' EUR', '')) || 0;

        // ALIŞ ise alış fiyatını kullan
        if (islemTuru === 'ALIS') {
            var alisElement = document.getElementById('detay_alis_fiyati');
            var alisText = alisElement ? alisElement.textContent.trim() : '0';
            var alis = parseFloat(alisText.replace(' TL', '').replace(' USD', '').replace(' EUR', '')) || 0;
            fiyat = alis;
        }

        var alis = alisFiyatiDegeri || 0;

        var miktar = parseInt(document.getElementById('eklenecekMiktar').value) || 1;
        var iskonto = parseFloat(document.getElementById('eklenecekIskonto').value) || 0;
        var kdvRaw = parseFloat(document.getElementById('eklenecekKdv').value); var kdv = isNaN(kdvRaw) ? 20 : kdvRaw;

        sepet.push({
            id: secilenUrunId,
            urun_adi: urunAdi,
            miktar: miktar,
            birim_fiyat: fiyat,
            alis_fiyati: alis,
            iskonto: iskonto,
            kdv_orani: kdv
        });

        sepetGuncelle();
        document.getElementById('islemYapBtn').disabled = false;

        var modal = bootstrap.Modal.getInstance(document.getElementById('urunDetayModal'));
        if (modal) modal.hide();

        showToast('✅ ' + urunAdi + ' sepete eklendi!', 'success');
    }

    // ============================================================
    // ALIŞ FİYATI TOGGLE
    // ============================================================
    function alisFiyatiToggle() {
        var element = document.getElementById('detay_alis_fiyati');
        var btn = document.querySelector('.btn-alis-goster');
        var nokta = btn ? btn.querySelector('.nokta') : null;

        if (!element || !btn) return;

        element.classList.toggle('goster');
        btn.classList.toggle('aktif');

        if (element.classList.contains('goster')) {
            element.innerHTML = alisFiyatiDegeri.toFixed(2) + ' ' + alisFiyatiDoviz + dovizKarsiligiGoster(alisFiyatiDegeri, alisFiyatiDoviz);
            if (nokta) nokta.textContent = '●';
            btn.title = 'Alış fiyatını gizle';
        } else {
            element.textContent = '•••••';
            if (nokta) nokta.textContent = '•';
            btn.title = 'Alış fiyatını göster';
        }
    }

    // ============================================================
    // İŞLEM TÜRÜ
    // ============================================================
    function islemDegistir(btn) {
        document.querySelectorAll('.islem-btn').forEach(function(b) {
            b.classList.remove('aktif', 'aktif-satis', 'aktif-alis', 'aktif-iade');
        });
        btn.classList.add('aktif');
        btn.classList.add('aktif-' + btn.dataset.value.toLowerCase());
        document.getElementById('islemTuru').value = btn.dataset.value;

        var textMap = { 'SATIS': 'SATIŞ', 'ALIS': 'ALIŞ', 'IADE': 'İADE' };
        document.getElementById('islemYapText').textContent = textMap[btn.dataset.value] || btn.dataset.value;
        document.getElementById('sepetBaslik').textContent = textMap[btn.dataset.value] || btn.dataset.value;

        // Makbuz seçiliyse numarayı güncelle (prefix değişebilir)
        if (currentBelge === 'MAKBUZ') {
            evrakNoGuncelle();
        }

        var islemTuru = btn.dataset.value;
        var duzenleBtn = document.getElementById('evrakNoDuzenleBtn');
        var noGoster = document.getElementById('evrakNoGoster');
        var noBox = document.getElementById('evrakNoBox');
        var evrakNoLabel = document.querySelector('.evrak-no-box .label');

        if (islemTuru === 'ALIS' && currentBelge !== 'FAT' && currentBelge !== 'EAR') {
            // ALIŞ + MAKBUZ durumunda tedarikçi no girişini zorunlu tut
            duzenleBtn.style.display = 'inline-block';
            duzenleBtn.title = 'Tedarikçi fatura numarasını girin (ZORUNLU)';
            noGoster.style.color = 'var(--badge-warning-text, #d4c84a)';
            noGoster.textContent = 'ZORUNLU GİR';
            noBox.style.borderColor = 'var(--badge-warning-text, #d4c84a)';
            noBox.style.backgroundColor = 'var(--badge-warning-bg, #1a1a0d)';
            evrakNoLabel.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:var(--badge-warning-text);"></i> TEDARİKÇİ NO';
            setTimeout(function() {
                var modal = new bootstrap.Modal(document.getElementById('evrakNoModal'));
                modal.show();
            }, 500);
        } else {
            duzenleBtn.style.display = 'inline-block';
            duzenleBtn.title = 'Numarayı değiştir (isteğe bağlı)';
            noGoster.style.color = 'var(--badge-success-text, #4ad46a)';
            noBox.style.borderColor = 'var(--border-color, #1a4a1a)';
            noBox.style.backgroundColor = 'var(--bg-card, #0d1a0d)';
            evrakNoLabel.innerHTML = '<i class="fas fa-hashtag"></i> Evrak No';
            evrakNoGuncelle();
        }
    }

    // ============================================================
    // MODAL ÜRÜN KAYDET
    // ============================================================
    function modalUrunKaydet() {
        var urun_kodu = document.getElementById('modal_urun_kodu').value.trim().toUpperCase();
        var urun_adi = document.getElementById('modal_urun_adi').value.trim().toUpperCase();
        var barkod = document.getElementById('modal_barkod').value.trim();
        var seri_no = document.getElementById('modal_seri_no').value.trim().toUpperCase();
        var kategori = document.getElementById('modal_kategori').value.trim().toUpperCase();
        var birim = document.getElementById('modal_birim').value;
        var urun_tipi = document.getElementById('modal_urun_tipi').value;
        var alis_fiyati = parseFloat(document.getElementById('modal_alis_fiyati').value) || 0;
        var alis_doviz = document.getElementById('modal_alis_doviz').value;
        var satis_fiyati = parseFloat(document.getElementById('modal_satis_fiyati').value) || 0;
        var satis_doviz = document.getElementById('modal_satis_doviz').value;
        var stok_miktari = parseFloat(document.getElementById('modal_stok_miktari').value) || 0;
        var min_stok = parseFloat(document.getElementById('modal_min_stok').value) || 0;
        var max_stok = parseFloat(document.getElementById('modal_max_stok').value) || 0;
        var aciklama = document.getElementById('modal_aciklama').value.trim().toUpperCase();

        if (!urun_kodu) { showToast('❌ Ürün kodu zorunludur!', 'error'); return; }
        if (!urun_adi) { showToast('❌ Ürün adı zorunludur!', 'error'); return; }

        var formData = new FormData();
        formData.append('csrf_token', CSRF_TOKEN);
        formData.append('urun_kodu', urun_kodu);
        formData.append('urun_adi', urun_adi);
        formData.append('barkod', barkod);
        formData.append('seri_no', seri_no);
        formData.append('kategori', kategori);
        formData.append('birim', birim);
        formData.append('urun_tipi', urun_tipi);
        formData.append('alis_fiyati', alis_fiyati);
        formData.append('alis_fiyati_doviz', alis_doviz);
        formData.append('satis_fiyati', satis_fiyati);
        formData.append('satis_fiyati_doviz', satis_doviz);
        formData.append('stok_miktari', stok_miktari);
        formData.append('min_stok', min_stok);
        formData.append('max_stok', max_stok);
        formData.append('aciklama', aciklama);

        fetch(API_BASE + '/api/stok_ekle_ajax.php', { method: 'POST', body: formData })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    var modal = bootstrap.Modal.getInstance(document.getElementById('yeniUrunModal'));
                    if (modal) modal.hide();

                    document.getElementById('modal_urun_kodu').value = '';
                    document.getElementById('modal_urun_adi').value = '';
                    document.getElementById('modal_barkod').value = '';
                    document.getElementById('modal_seri_no').value = '';
                    document.getElementById('modal_kategori').value = '';
                    document.getElementById('modal_aciklama').value = '';
                    document.getElementById('modal_stok_miktari').value = '0';
                    document.getElementById('modal_min_stok').value = '0';
                    document.getElementById('modal_max_stok').value = '0';
                    document.getElementById('modal_alis_fiyati').value = '0';
                    document.getElementById('modal_satis_fiyati').value = '0';

                    showToast('✅ Ürün başarıyla eklendi: ' + data.urun_adi, 'success');
                    document.getElementById('urunArama').value = data.urun_adi;
                    urunAra();
                } else {
                    showToast('❌ Hata: ' + (data.message || 'Ürün eklenemedi!'), 'error');
                }
            })
            .catch(function(error) {
                showToast('❌ Hata oluştu: ' + error, 'error');
            });
    }

    function modalBarkodOlustur() {
        var prefix = '869';
        var random = '';
        for (var i = 0; i < 9; i++) {
            random += Math.floor(Math.random() * 10);
        }
        var check = Math.floor(Math.random() * 10);
        document.getElementById('modal_barkod').value = prefix + random + check;
    }

    // ============================================================
    // FORM GÖNDERME
    // ============================================================
    document.getElementById('islemForm').addEventListener('submit', function(e) {
        if (sepet.length === 0) {
            e.preventDefault();
            alert('Sepet boş! Lütfen en az bir ürün ekleyin.');
            return false;
        }

        var cariSelect = document.getElementById('cariSelect');
        var cariId = cariSelect.value;

        if (!cariId || cariId === '' || cariId === 'null' || cariId === '0') {
            e.preventDefault();
            alert('Lütfen geçerli bir cari hesap seçin!');
            cariSelect.focus();
            return false;
        }

        var islemTuru = document.getElementById('islemTuru').value;
        if (islemTuru === 'ALIS') {
            var evrakNo = document.getElementById('evrakNoGoster').textContent;
            if (!evrakNo || evrakNo === '' || evrakNo === 'ZORUNLU GİR' || evrakNo === '-') {
                e.preventDefault();
                alert('Alış işlemi için tedarikçi fatura numarası girmek ZORUNLUDUR!');
                document.getElementById('evrakNoDuzenleBtn').click();
                return false;
            }
        }

        document.querySelectorAll('input[name^="urun_ids"]').forEach(function(el) { el.remove(); });
        document.querySelectorAll('input[name^="miktarlar"]').forEach(function(el) { el.remove(); });
        document.querySelectorAll('input[name^="fiyatlar"]').forEach(function(el) { el.remove(); });
        document.querySelectorAll('input[name^="iskontolar"]').forEach(function(el) { el.remove(); });
        document.querySelectorAll('input[name^="kdvler"]').forEach(function(el) { el.remove(); });

        var inputs = [
            ['islem_turu', document.getElementById('islemTuru').value],
            ['cari_id', cariId],
            ['belge_turu', document.getElementById('belgeTuru').value],
            ['aciklama', document.getElementById('aciklama').value]
        ];

        var evrakNoVal = document.getElementById('evrakNoGoster').textContent;
        if (evrakNoVal && evrakNoVal !== '-' && evrakNoVal !== '---' && evrakNoVal !== 'ZORUNLU GİR') {
            inputs.push(['evrak_no', evrakNoVal]);
        }

        var evrakTarihi = document.getElementById('evrakTarihi').value;
        if (evrakTarihi) {
            inputs.push(['evrak_tarihi', evrakTarihi]);
        }

        inputs.forEach(function(item) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = item[0];
            input.value = item[1];
            document.getElementById('islemForm').appendChild(input);
        });

        document.querySelectorAll('.odeme-satiri').forEach(function(satir) {
            var tutar = parseFloat(satir.querySelector('.odeme-tutar-input').value) || 0;
            if (tutar <= 0) return;

            var turu = satir.querySelector('.odeme-turu-select').value;
            var hesapId = satir.querySelector('.odeme-hesap-select').value;

            ['odeme_turu[]', 'odeme_hesap_id[]', 'odeme_tutar[]'].forEach(function(name, idx) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = [turu, hesapId, tutar][idx];
                document.getElementById('islemForm').appendChild(input);
            });
        });

        sepet.forEach(function(item) {
            var fields = ['urun_ids', 'miktarlar', 'fiyatlar', 'iskontolar', 'kdvler'];
            var values = [item.id, item.miktar, item.birim_fiyat, item.iskonto, item.kdv_orani];
            fields.forEach(function(name, idx) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = name + '[]';
                input.value = values[idx];
                document.getElementById('islemForm').appendChild(input);
            });
        });

        return true;
    });

    // ============================================================
    // PRİM POPUP
    // ============================================================
    var primSatisTutari = 0;
    var primReferansNo = '';
    var primFaturaId = '';
    var primMakbuzId = '';
    var primCariId = '';

    function primPopupKontrolEt() {
        var params = new URLSearchParams(window.location.search);
        if (params.get('prim_sor') !== '1') return;

        primSatisTutari = parseFloat(params.get('tutar')) || 0;
        primReferansNo = params.get('ref') || '';
        primFaturaId = params.get('fatura_id') || '';
        primMakbuzId = params.get('makbuz_id') || '';
        primCariId = params.get('cari_id') || '';

        document.getElementById('primSatisTutariGoster').textContent = primSatisTutari.toFixed(2) + ' ₺ (' + primReferansNo + ')';
        document.getElementById('primSoruAlani').style.display = 'block';
        document.getElementById('primDetayAlani').style.display = 'none';
        document.getElementById('primModalFooter').style.display = 'none';

        var modal = new bootstrap.Modal(document.getElementById('primModal'));
        modal.show();

        var temizUrl = window.location.pathname;
        window.history.replaceState({}, document.title, temizUrl);

        document.getElementById('primModal').addEventListener('hidden.bs.modal', function primModalKapaninca() {
            document.getElementById('primModal').removeEventListener('hidden.bs.modal', primModalKapaninca);
            if (primCariId) {
                window.location.href = API_BASE + '/cari_detay.php?id=' + primCariId;
            }
        });
    }

    function primEvet() {
        document.getElementById('primSoruAlani').style.display = 'none';
        document.getElementById('primDetayAlani').style.display = 'block';
        document.getElementById('primModalFooter').style.display = 'flex';
    }

    function primYontemDegisti() {
        var yontem = document.querySelector('input[name="primYontem"]:checked').value;
        document.getElementById('primSabitAlani').style.display = yontem === 'SABIT' ? 'block' : 'none';
        document.getElementById('primOranAlani').style.display = yontem === 'ORAN' ? 'block' : 'none';
        if (yontem === 'ORAN') primOranHesapla();
    }

    function primOranHesapla() {
        var oran = parseFloat(document.getElementById('primOranYuzde').value) || 0;
        var tutar = primSatisTutari * oran / 100;
        document.getElementById('primHesaplananTutar').textContent = tutar.toFixed(2);
    }

    function primKaydet() {
        var kisiId = document.getElementById('primKisi').value;
        if (!kisiId) {
            alert('Lütfen prim verilecek kişiyi seçin!');
            return;
        }

        var yontem = document.querySelector('input[name="primYontem"]:checked').value;
        var tutar, oran;
        if (yontem === 'SABIT') {
            tutar = parseFloat(document.getElementById('primTutarSabit').value) || 0;
            oran = null;
        } else {
            oran = parseFloat(document.getElementById('primOranYuzde').value) || 0;
            tutar = primSatisTutari * oran / 100;
        }

        if (tutar <= 0) {
            alert('Geçerli bir prim tutarı girin!');
            return;
        }

        fetch(API_BASE + '/api/prim_ekle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cari_id: kisiId,
                tutar: tutar,
                oran: oran,
                matrah: primSatisTutari,
                referans_no: primReferansNo,
                fatura_id: primFaturaId || null,
                makbuz_id: primMakbuzId || null,
                aciklama: document.getElementById('primAciklama').value,
                csrf_token: CSRF_TOKEN,
            }),
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var modal = bootstrap.Modal.getInstance(document.getElementById('primModal'));
                if (modal) modal.hide();
                if (data.success) {
                    showToast('✅ Prim kaydı oluşturuldu! (' + tutar.toFixed(2) + ' ₺) - Primler sayfasından ödeyebilirsiniz.', 'success');
                } else {
                    showToast('❌ Hata: ' + data.message, 'error');
                }
            })
            .catch(function(error) {
                showToast('❌ Hata: ' + error, 'error');
            });
    }

    // ============================================================
    // KAR GÖSTER/GİZLE
    // ============================================================
    function toggleKarGoster() {
        karGorunur = !karGorunur;
        var karRow = document.getElementById('netKarRow');
        var btn = document.getElementById('karToggleBtn');
        var alisCol = document.getElementById('alisFiyatCol');
        var alisCells = document.querySelectorAll('.alis-fiyat-cell');

        if (karRow) {
            karRow.style.display = karGorunur ? 'table-row' : 'none';
        }
        if (alisCol) {
            alisCol.style.display = karGorunur ? '' : 'none';
        }
        alisCells.forEach(function(cell) {
            cell.style.display = karGorunur ? '' : 'none';
        });
        if (btn) {
            btn.textContent = karGorunur ? '●' : '.';
            btn.title = karGorunur ? 'Karı Gizle' : 'Karı Göster';
        }

        if (typeof sepet !== 'undefined' && sepet.length > 0) {
            var netKar = 0;
            sepet.forEach(function(item) {
                var alis = parseFloat(item.alis_fiyati) || 0;
                netKar += (item.birim_fiyat - alis) * item.miktar;
            });
            document.getElementById('netKarValue').textContent = netKar.toFixed(2);
        } else {
            document.getElementById('netKarValue').textContent = '0.00';
        }
    }
</script>