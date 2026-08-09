<?php
require_once __DIR__ . '/../includes/auth.php';

require_login();

logout_user_session();

// logout_user_session() session_destroy() çağırdığı için yeni flash mesaj
// göstermek adına session'ı tekrar başlatıyoruz.
session_start();
flash_set('Çıkış yapıldı.', 'info');

header('Location: ' . BASE_URL . '/login.php');
exit;
