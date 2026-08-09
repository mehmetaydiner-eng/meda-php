<?php
/**
 * api/genel_ara.php
 * Tüm modüllerde arama yapar: cari, ürün, fatura, makbuz, sipariş
 * Canlı arama için JSON döndürür.
 */

// Doğru yol: public/api/ -> meda-php/includes/ -> ../../includes/auth.php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode(['success' => false, 'message' => 'En az 2 karakter girin']);
        exit;
    }

    $search = '%' . $q . '%';
    $results = [];

    // ===== 1. CARİLER =====
    $stmt = $pdo->prepare("
        SELECT id, unvan, telefon, vergi_no, cari_turu
        FROM cariler
        WHERE unvan LIKE ? OR telefon LIKE ? OR vergi_no LIKE ?
        LIMIT 10
    ");
    $stmt->execute([$search, $search, $search]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'type' => 'cari',
            'type_label' => 'Cari',
            'id' => $row['id'],
            'title' => $row['unvan'] . ' (' . ($row['telefon'] ?: 'tel yok') . ')',
            'link' => BASE_URL . '/cari_detay.php?id=' . $row['id'],
            'extra' => $row['cari_turu']
        ];
    }

    // ===== 2. ÜRÜNLER =====
    $stmt = $pdo->prepare("
        SELECT id, urun_adi, urun_kodu, barkod, seri_no
        FROM urunler
        WHERE urun_adi LIKE ? OR urun_kodu LIKE ? OR barkod LIKE ? OR seri_no LIKE ?
        LIMIT 10
    ");
    $stmt->execute([$search, $search, $search, $search]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $label = $row['urun_adi'];
        if ($row['urun_kodu']) $label .= ' (' . $row['urun_kodu'] . ')';
        $results[] = [
            'type' => 'urun',
            'type_label' => 'Ürün',
            'id' => $row['id'],
            'title' => $label,
            'link' => BASE_URL . '/stok_duzenle.php?id=' . $row['id'],
            'extra' => $row['barkod'] ? 'Barkod: ' . $row['barkod'] : ''
        ];
    }

    // ===== 3. FATURALAR =====
    $stmt = $pdo->prepare("
        SELECT f.id, f.fatura_no, f.fatura_turu, c.unvan AS cari_unvan
        FROM faturalar f
        LEFT JOIN cariler c ON c.id = f.cari_id
        WHERE f.fatura_no LIKE ?
        LIMIT 10
    ");
    $stmt->execute([$search]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'type' => 'fatura',
            'type_label' => 'Fatura',
            'id' => $row['id'],
            'title' => $row['fatura_no'] . ' - ' . ($row['cari_unvan'] ?: 'Cari yok'),
            'link' => BASE_URL . '/fatura_olustur.php?id=' . $row['id'],
            'extra' => $row['fatura_turu']
        ];
    }

    // ===== 4. MAKBUZLAR =====
    $stmt = $pdo->prepare("
        SELECT m.id, m.makbuz_no, m.makbuz_turu, c.unvan AS cari_unvan
        FROM makbuzlar m
        LEFT JOIN cariler c ON c.id = m.cari_id
        WHERE m.makbuz_no LIKE ?
        LIMIT 10
    ");
    $stmt->execute([$search]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'type' => 'makbuz',
            'type_label' => 'Makbuz',
            'id' => $row['id'],
            'title' => $row['makbuz_no'] . ' - ' . ($row['cari_unvan'] ?: 'Cari yok'),
            'link' => BASE_URL . '/makbuz_detay.php?id=' . $row['id'],
            'extra' => $row['makbuz_turu']
        ];
    }

    // ===== 5. SİPARİŞLER =====
    $stmt = $pdo->prepare("
        SELECT s.id, s.siparis_no, s.durum, c.unvan AS cari_unvan
        FROM siparisler s
        LEFT JOIN cariler c ON c.id = s.cari_id
        WHERE s.siparis_no LIKE ?
        LIMIT 10
    ");
    $stmt->execute([$search]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'type' => 'siparis',
            'type_label' => 'Sipariş',
            'id' => $row['id'],
            'title' => $row['siparis_no'] . ' - ' . ($row['cari_unvan'] ?: 'Cari yok'),
            'link' => BASE_URL . '/siparis_olustur.php?id=' . $row['id'],
            'extra' => $row['durum']
        ];
    }

    echo json_encode([
        'success' => true,
        'results' => $results,
        'query' => $q
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sunucu hatası: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}