<?php
/**
 * public/todo_islem.php
 * Yapılacaklar (todos) işlemlerini (ekle, tamamla, geri al, sil) işleyen arka plan dosyası.
 */

require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(BASE_URL . '/yapilacaklar.php');

    if ($action === 'add') {
        $title = turkce_upper(temizle_text($_POST['title'] ?? ''));
        $description = turkce_upper(temizle_text($_POST['description'] ?? ''));
        $priority = temizle_text($_POST['priority'] ?? 'Orta');
        $due_date_str = temizle_text($_POST['due_date'] ?? '');

        if (empty($title)) {
            flash_set('Yapılacak iş başlığı boş olamaz!', 'danger');
            header('Location: ' . BASE_URL . '/yapilacaklar.php');
            exit;
        }

        // Öncelik kontrolü
        $allowed_priorities = ['Düşük', 'Orta', 'Yüksek'];
        if (!in_array($priority, $allowed_priorities)) {
            $priority = 'Orta';
        }

        // Tarih kontrolü
        $due_date = null;
        if (!empty($due_date_str)) {
            $parsed = parse_date($due_date_str);
            if ($parsed) {
                $due_date = $parsed->format('Y-m-d H:i:s');
            }
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO todos (title, description, priority, status, due_date, user_id, created_at)
                VALUES (?, ?, ?, \'Bekliyor\', ?, ?, CURRENT_TIMESTAMP)
            ');
            $stmt->execute([$title, $description, $priority, $due_date, $user['id']]);
            
            flash_set('Yeni yapılacak iş başarıyla eklendi.', 'success');
        } catch (PDOException $e) {
            flash_set('Hata oluştu: ' . $e->getMessage(), 'danger');
        }
    }
} else {
    // GET istekleri (tamamla, geri al, sil)
    require_csrf(BASE_URL . '/yapilacaklar.php');

    $id = safe_int($_GET['id'] ?? null);

    if ($id) {
        // İşin varlığını ve kullanıcıya aitliğini kontrol et
        $stmt = $pdo->prepare('SELECT * FROM todos WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user['id']]);
        $todo = $stmt->fetch();

        if (!$todo) {
            flash_set('Kayıt bulunamadı veya yetkiniz yok.', 'danger');
            header('Location: ' . BASE_URL . '/yapilacaklar.php');
            exit;
        }

        if ($action === 'complete') {
            try {
                $stmt = $pdo->prepare('UPDATE todos SET status = \'Tamamlandı\', completed_at = CURRENT_TIMESTAMP WHERE id = ?');
                $stmt->execute([$id]);
                flash_set('İş tamamlandı olarak işaretlendi.', 'success');
            } catch (PDOException $e) {
                flash_set('Hata oluştu: ' . $e->getMessage(), 'danger');
            }
        } elseif ($action === 'reopen') {
            try {
                $stmt = $pdo->prepare('UPDATE todos SET status = \'Bekliyor\', completed_at = NULL WHERE id = ?');
                $stmt->execute([$id]);
                flash_set('İş tekrar aktif hale getirildi.', 'success');
            } catch (PDOException $e) {
                flash_set('Hata oluştu: ' . $e->getMessage(), 'danger');
            }
        } elseif ($action === 'delete') {
            try {
                $stmt = $pdo->prepare('DELETE FROM todos WHERE id = ?');
                $stmt->execute([$id]);
                flash_set('Yapılacak iş silindi.', 'success');
            } catch (PDOException $e) {
                flash_set('Hata oluştu: ' . $e->getMessage(), 'danger');
            }
        }
    }
}

header('Location: ' . BASE_URL . '/yapilacaklar.php');
exit;
