<?php
/**
 * Flask tarafında /fatura/duzenle/<id> aynı fatura_olustur.html şablonunu
 * mevcut fatura verisiyle dolduruyordu. Burada da aynı mantık: fatura_olustur.php
 * zaten ?id= parametresini destekliyor, o yüzden buraya direkt yönlendiriyoruz.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/faturalar.php');
    exit;
}

header('Location: ' . BASE_URL . '/fatura_olustur.php?id=' . $id);
exit;
