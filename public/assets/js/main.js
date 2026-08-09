// static/js/main.js

// Hamburger menü - base.html'de zaten var, burada tekrar etmeye gerek yok
// Ancak diğer sayfalar için gerekli fonksiyonlar burada

// ========== TÜRKÇE BÜYÜK HARF DÖNÜŞTÜRÜCÜ ==========
function turkceBuyukHarf(text) {
    if (!text) return text;
    
    const turkceKucuk = 'abcçdefgğhıijklmnoöprsştuüvyz';
    const turkceBuyuk = 'ABCÇDEFGĞHIİJKLMNOÖPRSŞTUÜVYZ';
    
    let result = '';
    for (let i = 0; i < text.length; i++) {
        const char = text[i];
        const index = turkceKucuk.indexOf(char.toLowerCase());
        if (index !== -1) {
            result += turkceBuyuk[index];
        } else {
            result += char.toUpperCase();
        }
    }
    return result;
}

// ========== FORM GÖNDERİMİNDE BÜYÜK HARF ==========
document.addEventListener('DOMContentLoaded', function() {
    // Email ve password hariç tüm inputları büyük harfe çevir
    const inputs = document.querySelectorAll('input:not([type="email"]):not([type="password"]), textarea');
    
    inputs.forEach(function(input) {
        input.addEventListener('input', function() {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            const yeniDeger = turkceBuyukHarf(this.value);
            if (this.value !== yeniDeger) {
                this.value = yeniDeger;
                this.setSelectionRange(start, end);
            }
        });
        
        input.addEventListener('paste', function() {
            setTimeout(() => {
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = turkceBuyukHarf(this.value);
                this.setSelectionRange(start, end);
            }, 10);
        });
        
        if (input.value) {
            input.value = turkceBuyukHarf(input.value);
        }
    });
    
    // Form gönderilirken büyük harfe çevir
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            const inputs = this.querySelectorAll('input:not([type="email"]):not([type="password"]), textarea');
            inputs.forEach(function(input) {
                input.value = turkceBuyukHarf(input.value);
            });
        });
    });
});// static/js/main.js - En sona EKLEYİN veya GÜNCELLEYİN

// ========== TÜRKÇE I/İ DÜZELTMESİ - TÜM INPUT ALANLARI ==========
document.addEventListener('DOMContentLoaded', function() {
    
    // Türkçe I/İ düzeltme fonksiyonu
    function turkceDuzenle(text) {
        if (!text) return text;
        // İ -> I (Türkçe büyük İ -> normal I)
        text = text.replace(/İ/g, 'I');
        // i -> ı (Türkçe küçük i -> normal ı)
        text = text.replace(/i/g, 'ı');
        // Tümünü büyük harf yap
        return text.toUpperCase();
    }
    
    // Email ve password hariç tüm input alanlarını bul
    const inputs = document.querySelectorAll('input:not([type="email"]):not([type="password"]), textarea, select');
    
    inputs.forEach(function(input) {
        
        // Yazarken düzelt
        input.addEventListener('input', function() {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            
            const yeniDeger = turkceDuzenle(this.value);
            if (this.value !== yeniDeger) {
                this.value = yeniDeger;
                this.setSelectionRange(start, end);
            }
        });
        
        // Yapıştırma işleminde düzelt
        input.addEventListener('paste', function() {
            setTimeout(() => {
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = turkceDuzenle(this.value);
                this.setSelectionRange(start, end);
            }, 10);
        });
        
        // Başlangıç değerini düzelt
        if (input.value) {
            input.value = turkceDuzenle(input.value);
        }
    });
    
    // Form gönderilirken tüm alanları düzelt
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            const inputs = this.querySelectorAll('input:not([type="email"]):not([type="password"]), textarea');
            inputs.forEach(function(input) {
                input.value = turkceDuzenle(input.value);
            });
        });
    });
});

// static/js/main.js - TAM DOSYA (Kesin Çözüm)

// ========== TÜRKÇE BÜYÜK HARF DÖNÜŞTÜRÜCÜ (İ KORUNUR) ==========
function turkceBuyukHarf(text) {
    if (!text) return text;
    
    const turkceKucuk = 'abcçdefgğhıijklmnoöprsştuüvyz';
    const turkceBuyuk = 'ABCÇDEFGĞHIİJKLMNOÖPRSŞTUÜVYZ';
    
    let result = '';
    for (let i = 0; i < text.length; i++) {
        const char = text[i];
        const index = turkceKucuk.indexOf(char.toLowerCase());
        if (index !== -1) {
            result += turkceBuyuk[index];
        } else {
            result += char.toUpperCase();
        }
    }
    return result;
}

// ========== TÜM SAYFA İÇİN BÜYÜK HARF DÖNÜŞÜMÜ (İ KORUNUR) ==========
document.addEventListener('DOMContentLoaded', function() {
    
    // Email ve password hariç tüm input alanlarını bul
    const inputs = document.querySelectorAll('input:not([type="email"]):not([type="password"]), textarea');
    
    inputs.forEach(function(input) {
        
        // Kullanıcı adı alanı özel (İ -> I dönüşümü)
        if (input.name === 'username' || input.id === 'username-input') {
            input.addEventListener('input', function() {
                const start = this.selectionStart;
                const end = this.selectionEnd;
                let value = this.value;
                // SADECE username alanında İ -> I
                value = value.replace(/İ/g, 'I');
                value = value.replace(/i/g, 'ı');
                value = value.toUpperCase();
                if (this.value !== value) {
                    this.value = value;
                    this.setSelectionRange(start, end);
                }
            });
            
            input.addEventListener('paste', function() {
                setTimeout(() => {
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    let value = this.value;
                    value = value.replace(/İ/g, 'I');
                    value = value.replace(/i/g, 'ı');
                    value = value.toUpperCase();
                    this.value = value;
                    this.setSelectionRange(start, end);
                }, 10);
            });
            
            if (input.value) {
                let value = input.value;
                value = value.replace(/İ/g, 'I');
                value = value.replace(/i/g, 'ı');
                value = value.toUpperCase();
                input.value = value;
            }
        } else {
            // Diğer TÜM alanlar - İ KORUNUR, sadece büyük harf yapılır
            input.addEventListener('input', function() {
                const start = this.selectionStart;
                const end = this.selectionEnd;
                const yeniDeger = turkceBuyukHarf(this.value);
                if (this.value !== yeniDeger) {
                    this.value = yeniDeger;
                    this.setSelectionRange(start, end);
                }
            });
            
            input.addEventListener('paste', function() {
                setTimeout(() => {
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    this.value = turkceBuyukHarf(this.value);
                    this.setSelectionRange(start, end);
                }, 10);
            });
            
            if (input.value) {
                input.value = turkceBuyukHarf(input.value);
            }
        }
    });
    
    // Form gönderilirken
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            const inputs = this.querySelectorAll('input:not([type="email"]):not([type="password"]), textarea');
            inputs.forEach(function(input) {
                if (input.name === 'username' || input.id === 'username-input') {
                    let value = input.value;
                    value = value.replace(/İ/g, 'I');
                    value = value.replace(/i/g, 'ı');
                    value = value.toUpperCase();
                    input.value = value;
                } else {
                    input.value = turkceBuyukHarf(input.value);
                }
            });
        });
    });
});

// ========== HAMBURGER MENÜ ==========
document.getElementById('hamburger').addEventListener('click', function() {
    document.getElementById('navMenu').classList.toggle('open');
});

// ========== ALERT'LERİ OTOMATİK KAPAT ==========
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        new bootstrap.Alert(alert).close();
    });
}, 5000);

// ========== YAPILACAKLAR BADGE ==========
{% if current_user.is_authenticated %}
fetch('/api/todo_count')
    .then(response => response.json())
    .then(data => {
        const badge = document.getElementById('todo-badge');
        if (badge) {
            badge.textContent = data.count || 0;
        }
    })
    .catch(() => {});
{% endif %}

// static/js/main.js - En sona EKLEYİN

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

// ========== SAYFA YÜKLENDİĞİNDE TEMAYI YÜKLE ==========
document.addEventListener('DOMContentLoaded', function() {
    var savedTheme = localStorage.getItem('theme') || 'dark';
    setTheme(savedTheme);
});
// ========== static/js/main.js - SONA EKLEYİN ==========

// ===== HIZLI İŞLEM - GLOBAL DEĞİŞKENLER =====
var sepet = [];
var selectedUrun = null;
var toplamKdvOrani = 20;

// ===== SEPETE EKLE =====
function sepeteEkle() {
    var select = document.getElementById('urunListesi');
    if (!select) return;
    
    var selected = select.options[select.selectedIndex];
    if (!selected || !selected.value) {
        alert('Lütfen bir ürün seçin!');
        return;
    }
    
    var miktar = parseFloat(document.getElementById('eklenecekMiktar').value) || 1;
    var fiyat = parseFloat(document.getElementById('eklenecekFiyat').value) || 0;
    var iskonto = parseFloat(document.getElementById('eklenecekIskonto').value) || 0;
    var kdvOrani = parseFloat(document.getElementById('eklenecekKdv').value) || 20;
    
    if (miktar <= 0 || fiyat <= 0) {
        alert('Geçerli miktar ve fiyat girin!');
        return;
    }
    
    var item = {
        id: selected.value,
        urun_adi: selected.dataset.ad || 'Bilinmeyen',
        miktar: miktar,
        birim_fiyat: fiyat,
        iskonto: iskonto,
        kdv_orani: kdvOrani
    };
    
    sepet.push(item);
    sepetGuncelle();
    
    document.getElementById('eklenecekMiktar').value = 1;
    document.getElementById('eklenecekIskonto').value = 0;
    document.getElementById('eklenecekKdv').value = 20;
    document.getElementById('urunListesi').value = '';
    document.getElementById('urunArama').value = '';
    document.getElementById('urunListesi').innerHTML = '<option value="">Seçin...</option>';
    
    var btn = document.getElementById('islemYapBtn');
    if (btn) btn.disabled = false;
}

// ===== SEPET GÜNCELLE =====
/* function sepetGuncelle() {
    var tbody = document.getElementById('sepetTbody');
    if (!tbody) return;
    
    var bos = document.getElementById('bos-sepet');
    if (bos) bos.remove();
    
    if (sepet.length === 0) {
        tbody.innerHTML = `
            <tr id="bos-sepet">
                <td colspan="8" class="text-center text-muted py-3">
                    <i class="fas fa-shopping-cart fa-2x d-block mb-2"></i>
                    Sepet boş. Ürün ekleyin.
                </td>
            </tr>
        `;
        var btn = document.getElementById('islemYapBtn');
        if (btn) btn.disabled = true;
        return;
    } */
    
    var html = '';
    var araToplam = 0;
    var toplamIskonto = 0;
    var toplamKdv = 0;
    
    sepet.forEach(function(item, index) {
        var satirToplam = item.miktar * item.birim_fiyat;
        var iskontoTutari = satirToplam * (item.iskonto / 100);
        var iskontoSonrasi = satirToplam - iskontoTutari;
        var kdvTutari = iskontoSonrasi * (item.kdv_orani / 100);
        var genelToplam = iskontoSonrasi + kdvTutari;
        
        araToplam += satirToplam;
        toplamIskonto += iskontoTutari;
        toplamKdv += kdvTutari;
        
        html += `
            <tr>
                <td>${index + 1}</td>
                <td>${item.urun_adi}</td>
                <td class="text-center">${item.miktar.toFixed(2)}</td>
                <td class="text-end">${item.birim_fiyat.toFixed(2)}</td>
                <td class="text-center">${item.iskonto.toFixed(0)}%</td>
                <td class="text-center">${item.kdv_orani.toFixed(0)}%</td>
                <td class="text-end">${genelToplam.toFixed(2)}</td>
                <td class="text-center">
                    <button class="btn btn-danger btn-sm" onclick="sepetSil(${index})" style="padding: 2px 6px; font-size: 10px;">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    document.getElementById('araToplam').textContent = araToplam.toFixed(2);
    document.getElementById('toplamIskonto').textContent = toplamIskonto.toFixed(2);
    document.getElementById('toplamKdv').textContent = toplamKdv.toFixed(2);
    document.getElementById('genelToplam').textContent = (araToplam - toplamIskonto + toplamKdv).toFixed(2);
}

// ===== SEPET SİL =====
function sepetSil(index) {
    sepet.splice(index, 1);
    sepetGuncelle();
}

// ===== SEPET TEMİZLE =====
function sepetTemizle() {
    if (sepet.length === 0) return;
    if (!confirm('Sepeti temizlemek istediğinize emin misiniz?')) return;
    sepet = [];
    sepetGuncelle();
}

// ===== İŞLEM TÜRÜ DEĞİŞTİR =====
function islemDegistir(btn) {
    var btns = document.querySelectorAll('.islem-btn');
    btns.forEach(function(b) { 
        b.classList.remove('aktif', 'aktif-satis', 'aktif-alis', 'aktif-iade'); 
    });
    btn.classList.add('aktif');
    btn.classList.add('aktif-' + btn.dataset.value.toLowerCase());
    document.getElementById('islemTuru').value = btn.dataset.value;
    
    var islemText = document.getElementById('islemYapText');
    var textMap = { 'SATIS': 'SATIŞ', 'ALIS': 'ALIŞ', 'IADE': 'İADE' };
    islemText.textContent = textMap[btn.dataset.value] || btn.dataset.value;
    
    var baslik = document.getElementById('sepetBaslik');
    if (baslik) baslik.textContent = textMap[btn.dataset.value] || btn.dataset.value;
}

// ===== BELGE TÜRÜ DEĞİŞTİR =====
function belgeDegistir(btn) {
    document.querySelectorAll('.belge-btn').forEach(function(b) { b.classList.remove('aktif'); });
    btn.classList.add('aktif');
    document.getElementById('belgeTuru').value = btn.dataset.value;
}

// ===== ÖDEME KANALI SEÇ =====
function odemeSec(btn) {
    document.querySelectorAll('.odeme-btn').forEach(function(b) { b.classList.remove('aktif'); });
    btn.classList.add('aktif');
    document.getElementById('odemeKanali').value = btn.dataset.value;
}

// ===== ÜRÜN SEÇ (Listeden) =====
function urunSecFromList(select) {
    var selected = select.options[select.selectedIndex];
    if (selected && selected.value) {
        document.getElementById('eklenecekFiyat').value = selected.dataset.fiyat || 0;
        document.getElementById('eklenecekKdv').value = selected.dataset.kdv || 20;
    }
}

// ===== ÜRÜN ARAMA =====
// ===== ÜRÜN ARAMA (JavaScript ile filtreleme - opsiyonel) =====
function urunAra() {
    var q = document.getElementById('urunArama').value.trim();
    var sonucDiv = document.getElementById('urunListesiSonuc');
    
    if (q.length < 1) {
        sonucDiv.innerHTML = `<div class="bos-mesaj"><i class="fas fa-box"></i>Arama yaparak ürünleri listeleyin</div>`;
        return;
    }
    
    sonucDiv.innerHTML = `<div class="bos-mesaj"><i class="fas fa-spinner fa-spin"></i> Aranıyor...</div>`;
    
    fetch('/hizli-islem/urun-ara?q=' + encodeURIComponent(q))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (!data || data.length === 0) {
                sonucDiv.innerHTML = `<div class="bos-mesaj"><i class="fas fa-box"></i>"<strong>${q}</strong>" için ürün bulunamadı</div>`;
                return;
            }
            
            var html = '';
            data.forEach(function(urun) {
                var stok = parseFloat(urun.stok_miktari) || 0;
                var fiyat = parseFloat(urun.satis_fiyati) || 0;
                var stokClass = stok <= 0 ? 'dusuk' : 'normal';
                
                html += `
                    <div class="urun-item" data-id="${urun.id}">
                        <div>
                            <div class="urun-adi">${urun.urun_adi || 'Bilinmeyen'}</div>
                            <div class="urun-detay">Kod: ${urun.urun_kodu || '-'} | Barkod: ${urun.barkod || '-'}</div>
                        </div>
                        <div class="text-end">
                            <div class="urun-fiyat">${fiyat.toFixed(2)} ₺</div>
                            <div class="urun-stok ${stokClass}">Stok: ${stok} ${urun.birim || 'ADET'}</div>
                        </div>
                        <div>
                            <button class="btn-sepete-ekle" onclick="sepeteDirektEkle(${urun.id}, '${urun.urun_adi.replace(/'/g, "\\'")}', ${fiyat})">
                                <i class="fas fa-cart-plus"></i> SEPETE EKLE
                            </button>
                        </div>
                    </div>
                `;
            });
            
            sonucDiv.innerHTML = html;
        })
        .catch(function(error) {
            sonucDiv.innerHTML = `<div class="bos-mesaj" style="color:#d44a4a;"><i class="fas fa-exclamation-triangle"></i>Hata: ${error.message}</div>`;
        });
}

// ===== CARİ ARAMA (JavaScript ile filtreleme) =====
function cariAra(q) {
    var select = document.getElementById('cariSelect');
    var search = q.toLowerCase().trim();
    
    select.innerHTML = '<option value="">Seçin...</option>';
    
    if (search.length === 0) {
        // Tüm carileri göster
        allCariler.forEach(function(cari) {
            var option = document.createElement('option');
            option.value = cari.id;
            option.text = cari.unvan;
            select.appendChild(option);
        });
        return;
    }
    
    var found = false;
    allCariler.forEach(function(cari) {
        // unvan, vergi_no, telefon, email içinde ara (her yerde)
        if (cari.unvan.toLowerCase().includes(search) || 
            (cari.vergi_no && cari.vergi_no.toLowerCase().includes(search)) ||
            (cari.telefon && cari.telefon.toLowerCase().includes(search)) ||
            (cari.email && cari.email.toLowerCase().includes(search))) {
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

// ===== CARİ LİSTESİNİ YÜKLE =====
function loadCariler() {
    fetch('/api/cari-ara?q=&tur=TÜMÜ')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            allCariler = data;
            cariAra('');
        })
        .catch(function(error) {
            console.log('Cari listesi yüklenemedi:', error);
        });
}

// ===== MODAL CARİ KAYDET =====
function modalCariKaydet() {
    var unvan = document.getElementById('modal_cari_unvan').value.trim().toUpperCase();
    if (!unvan) {
        alert('Ünvan zorunludur!');
        return;
    }
    
    var formData = new FormData();
    formData.append('unvan', unvan);
    formData.append('cari_turu', document.getElementById('modal_cari_turu').value);
    formData.append('vergi_no', document.getElementById('modal_cari_vergi_no').value.trim().toUpperCase());
    formData.append('vergi_dairesi', document.getElementById('modal_cari_vd').value.trim().toUpperCase());
    formData.append('telefon', document.getElementById('modal_cari_tel').value.trim());
    formData.append('email', document.getElementById('modal_cari_email').value.trim().toLowerCase());
    formData.append('yetkili', document.getElementById('modal_cari_yetkili').value.trim().toUpperCase());
    formData.append('adres', document.getElementById('modal_cari_adres').value.trim().toUpperCase());
    formData.append('aciklama', document.getElementById('modal_cari_aciklama').value.trim().toUpperCase());
    
    fetch('/cari/ekle/ajax', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            var select = document.querySelector('select[name="cari_id"]');
            if (select) {
                var option = document.createElement('option');
                option.value = data.cari_id;
                option.text = data.unvan + ' - ' + (data.vergi_no || 'VERGİ NO YOK');
                select.appendChild(option);
                select.value = data.cari_id;
            }
            
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
            
            alert('Cari başarıyla eklendi!');
        } else {
            alert('Hata: ' + (data.message || 'Cari eklenemedi!'));
        }
    })
    .catch(function(error) {
        alert('Hata oluştu: ' + error);
    });
}

// ===== FORM GÖNDERME (Sepet verilerini ekle) =====
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('islemForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (sepet.length === 0) {
                e.preventDefault();
                alert('Sepet boş! Lütfen en az bir ürün ekleyin.');
                return false;
            }
            
            sepet.forEach(function(item) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'urun_ids[]';
                input.value = item.id;
                form.appendChild(input);
                
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'miktarlar[]';
                input.value = item.miktar;
                form.appendChild(input);
                
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'fiyatlar[]';
                input.value = item.birim_fiyat;
                form.appendChild(input);
                
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'iskontolar[]';
                input.value = item.iskonto;
                form.appendChild(input);
                
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'kdvler[]';
                input.value = item.kdv_orani;
                form.appendChild(input);
            });
            
            return true;
        });
    }
    
    // Varsayılan KDV oranını %20 olarak ayarla
    var kdvInput = document.getElementById('eklenecekKdv');
    if (kdvInput) kdvInput.value = 20;
    
    // Enter tuşu ile arama
    var aramaInput = document.getElementById('urunArama');
    if (aramaInput) {
        aramaInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                urunAra();
            }
        });
    }
});

// ===== FORM GÖNDERME =====
document.getElementById('islemForm').addEventListener('submit', function(e) {
    if (sepet.length === 0) {
        e.preventDefault();
        alert('Sepet boş! Lütfen en az bir ürün ekleyin.');
        return false;
    }
    
    // Sepet verilerini forma ekle
    sepet.forEach(function(item) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'urun_ids[]';
        input.value = item.id;
        this.appendChild(input);
        
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'miktarlar[]';
        input.value = item.miktar;
        this.appendChild(input);
        
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'fiyatlar[]';
        input.value = item.birim_fiyat;
        this.appendChild(input);
        
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'iskontolar[]';
        input.value = item.iskonto;
        this.appendChild(input);
        
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'kdvler[]';
        input.value = item.kdv_orani;
        this.appendChild(input);
    });
    
    return true;
});
