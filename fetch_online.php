<?php
/**
 * RO-2026 :: ตัวดึงสถิติจากฐานข้อมูล + เช็คสถานะเซิร์ฟเวอร์ -> data/server.json
 *
 * รันด้วย PHP ของ Laragon:
 *   & "C:\...\1-start_database_run_laragon.exe\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" fetch_online.php
 *
 * สคริปต์นี้ใช้สิทธิ์ READ ONLY เท่านั้น (SELECT + เช็คพอร์ต) เขียนเฉพาะ data/server.json
 */

// ---------- ค่าการเชื่อมต่อฐานข้อมูล (ตรงกับ DB จริง: ro2026) ----------
$DB_HOST = '127.0.0.1';
$DB_PORT = '3306';
$DB_USER = 'ro2026';
$DB_PASS = 'ro2026';
$DB_NAME = 'ro2026';

// ---------- พอร์ตเซิร์ฟเวอร์เกม (ตาม conf: login 6900, char 6121, map 5121) ----------
$GAME = [
    'login' => [ '127.0.0.1', 6900 ],
    'char'  => [ '127.0.0.1', 6121 ],
    'map'   => [ '127.0.0.1', 5121 ],
];

date_default_timezone_set('Asia/Bangkok');

$outFile = __DIR__ . '/data/server.json';

// เช็คว่าพอร์ตเปิด (เซิร์ฟเวอร์กำลังฟังอยู่) หรือไม่
function check_port($host, $port) {
    $fp = @fsockopen($host, $port, $errno, $errstr, 1.0);
    if (!$fp) return false;
    fclose($fp);
    return true;
}

// สถานะเซิร์ฟเวอร์: online = ครบทั้ง Login + Char + Map
$server = [];
foreach ($GAME as $key => $ep) {
    $server[$key] = check_port($ep[0], $ep[1]);
}
$server['online'] = $server['login'] && $server['char'] && $server['map'];

// เชื่อมต่อ DB (ถ้าไม่ติด = เหลือแค่ข้อมูลสถานะ + ค่าเก่า)
$dbOk = false;
$pdo = null;
try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );
    $dbOk = true;
} catch (Exception $e) {
    $dbOk = false;
}

$stats = [ 'online' => 0, 'accounts' => 0, 'characters' => 0, 'guilds' => 0, 'maps' => [], 'db' => $dbOk, 'server' => $server ];

if ($dbOk && $pdo) {
    function q($pdo, $sql) {
        $s = $pdo->prepare($sql);
        $s->execute();
        $row = $s->fetch(PDO::FETCH_NUM);
        return $row ? (int)$row[0] : 0;
    }

    // `char` เป็นคำสงวนของ MySQL ต้องคร่อม backtick
    $stats['online']     = q($pdo, 'SELECT COUNT(*) FROM `char` WHERE online = 1');
    $stats['accounts']   = q($pdo, 'SELECT COUNT(*) FROM login');
    $stats['characters'] = q($pdo, 'SELECT COUNT(*) FROM `char`');
    $stats['guilds']     = q($pdo, 'SELECT COUNT(*) FROM guild');

    // คนออนไลน์แยกตามแผนที่ (สูงสุด 8 แผนที่)
    $st = $pdo->prepare(
        'SELECT last_map AS m, COUNT(*) AS c FROM `char` WHERE online = 1 GROUP BY last_map ORDER BY c DESC, m ASC LIMIT 8'
    );
    $st->execute();
    $st->bindColumn('m', $map);
    $st->bindColumn('c', $cnt);
    while ($st->fetch(PDO::FETCH_ASSOC) && $map !== '') {
        $stats['maps'][] = ['map' => $map, 'count' => (int)$cnt];
    }
}

$stats['updated_at'] = date('c'); // ISO 8601

$bytes = file_put_contents($outFile, json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
if ($bytes === false) {
    fwrite(STDERR, "Cannot write {$outFile}" . PHP_EOL);
    exit(1);
}

echo "OK -> {$outFile}" . PHP_EOL;
echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;