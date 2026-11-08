 <?php
  require_once __DIR__ . '/../config.php';
  require_once __DIR__ . '/../vendor/autoload.php';

  const PRO_PRICE = 18000;
  const PRO_DURATION_DAYS = 30;

  function midtrans_configure(): void {
      if (MIDTRANS_SERVER_KEY === '') {
          throw new RuntimeException('Midtrans Server Key belum dikonfigurasi.');
      }

      \Midtrans\Config::$serverKey = MIDTRANS_SERVER_KEY;
      \Midtrans\Config::$isProduction = MIDTRANS_IS_PRODUCTION;
      \Midtrans\Config::$isSanitized = true;
      \Midtrans\Config::$is3ds = true;
  }