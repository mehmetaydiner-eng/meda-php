<?php
/**
 * includes/pagination.php
 * Liste sayfalarında (Cariler, Stok, Faturalar, Makbuzlar, Teklifler,
 * Teknik Servis) tekrar tekrar kullanılan basit sayfalama yardımcıları.
 *
 * Kullanım deseni (bir liste sayfasında):
 *   require_once __DIR__ . '/../includes/pagination.php';
 *   $sayfa = get_current_page();
 *   $perPage = 30;
 *   $toplamKayit = (int)$pdo->query('SELECT COUNT(*) FROM cariler')->fetchColumn();
 *   $toplamSayfa = (int)ceil($toplamKayit / $perPage);
 *   $stmt = $pdo->prepare('SELECT * FROM cariler ORDER BY unvan LIMIT ? OFFSET ?');
 *   $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
 *   $stmt->bindValue(2, pagination_offset($sayfa, $perPage), PDO::PARAM_INT);
 *   $stmt->execute();
 *   $cariler = $stmt->fetchAll();
 *   ...
 *   <?= render_pagination($sayfa, $toplamSayfa, BASE_URL . '/cariler.php') ?>
 */

/** URL'den mevcut sayfa numarasını okur (1'den başlar, negatif/0 verilirse 1'e sabitlenir) */
function get_current_page(): int
{
    $page = safe_int($_GET['sayfa'] ?? 1, 1);
    return $page < 1 ? 1 : $page;
}

/** Sayfa numarasına ve sayfa başına kayıt sayısına göre SQL OFFSET hesaplar */
function pagination_offset(int $page, int $perPage): int
{
    return ($page - 1) * $perPage;
}

/**
 * Sayfalama kontrollerini (« Önceki  1 2 [3] 4 5  Sonraki »)  HTML olarak
 * üretir. Tek bir sayfa varsa (ya da hiç kayıt yoksa) boş string döner.
 *
 * @param int    $currentPage Şu anki sayfa (1'den başlar)
 * @param int    $totalPages  Toplam sayfa sayısı
 * @param string $baseUrl     Sorgu string'i olmadan sayfa URL'si (örn. BASE_URL.'/cariler.php')
 * @param array  $extraQuery  Sayfa numarası dışında korunması gereken diğer GET
 *                             parametreleri (örn. arama/filtre değerleri)
 */
function render_pagination(int $currentPage, int $totalPages, string $baseUrl, array $extraQuery = []): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $buildUrl = function (int $page) use ($baseUrl, $extraQuery): string {
        $query = array_merge($extraQuery, ['sayfa' => $page]);
        return $baseUrl . '?' . http_build_query($query);
    };

    $html = '<nav aria-label="Sayfalama"><ul class="pagination-custom">';

    // ÖNCEKİ
    if ($currentPage > 1) {
        $html .= '<li><a href="' . e($buildUrl($currentPage - 1)) . '">&laquo; Önceki</a></li>';
    } else {
        $html .= '<li class="disabled"><span>&laquo; Önceki</span></li>';
    }

    // Sayfa numaraları - mevcut sayfanın etrafında küçük bir pencere göster,
    // uçlara da her zaman 1 ve son sayfayı ekle (çok sayfalı listelerde
    // yüzlerce numarayı tek tek basmamak için)
    $pencere = 2;
    $baslangic = max(1, $currentPage - $pencere);
    $bitis = min($totalPages, $currentPage + $pencere);

    if ($baslangic > 1) {
        $html .= '<li><a href="' . e($buildUrl(1)) . '">1</a></li>';
        if ($baslangic > 2) {
            $html .= '<li class="disabled"><span>&hellip;</span></li>';
        }
    }

    for ($i = $baslangic; $i <= $bitis; $i++) {
        $aktifSinif = $i === $currentPage ? ' class="active"' : '';
        $html .= "<li{$aktifSinif}><a href=\"" . e($buildUrl($i)) . "\">{$i}</a></li>";
    }

    if ($bitis < $totalPages) {
        if ($bitis < $totalPages - 1) {
            $html .= '<li class="disabled"><span>&hellip;</span></li>';
        }
        $html .= '<li><a href="' . e($buildUrl($totalPages)) . '">' . $totalPages . '</a></li>';
    }

    // SONRAKİ
    if ($currentPage < $totalPages) {
        $html .= '<li><a href="' . e($buildUrl($currentPage + 1)) . '">Sonraki &raquo;</a></li>';
    } else {
        $html .= '<li class="disabled"><span>Sonraki &raquo;</span></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

/** "X - Y arası, toplam Z kayıt" özet metni üretir */
function render_pagination_ozet(int $currentPage, int $perPage, int $totalRecords): string
{
    if ($totalRecords === 0) {
        return '';
    }
    $baslangic = pagination_offset($currentPage, $perPage) + 1;
    $bitis = min($totalRecords, $baslangic + $perPage - 1);
    return '<div class="pagination-ozet">' . $baslangic . ' - ' . $bitis . ' arası gösteriliyor, toplam ' . $totalRecords . ' kayıt</div>';
}
