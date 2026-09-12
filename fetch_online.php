<?php
/**
 * RO-2026 :: ตัวดึงสถิติจากฐานข้อมูล -> data/server.json (สำหรับเว็บ static)
 *
 * รันด้วย PHP ของ Laragon:
 *   & "C:\...\1-start_database_run_laragon.exe\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" fetch_online.php
 *
 * สคริปต์นี้ใช้สิทธิ์ READ ONLY เท่านั้น (SELECT) และเขียนเฉพาะ data/server.json
 */

// ---------- ค่าการเชื่อมต่อฐานข้อมูล (ตรงกับ DB จริง: ro2026) ----------
$DB_HOST = '127.0.0.1';
$DB_PORT = '3306';
$DB_USER = 'ro2026';
$DB_PASS = 'ro2026';
$DB_NAME = 'ro2026';

$outFile = __DIR__ . '/data/server.json';

// เชื่อมต่อ PDO MySQL
try {
	$pdo = new PDO(
		"mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
		$DB_USER,
		$DB_PASS,
		[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
	);
} catch (Exception $e) {
	fwrite(STDERR, "DB connection failed: " . $e->getMessage() . PHP_EOL);
	exit(1);
}

function q($pdo, $sql) {
	$s = $pdo->prepare($sql);
	$s->execute();
	$row = $s->fetch(PDO::FETCH_NUM);
	return $row ? (int)$row[0] : 0;
}

// `char` เป็นคำสงวนของ MySQL ต้องคร่อม backtick
$stats = [
	'online'     => q($pdo, 'SELECT COUNT(*) FROM `char` WHERE online = 1'),
	'accounts'   => q($pdo, 'SELECT COUNT(*) FROM login'),
	'characters' => q($pdo, 'SELECT COUNT(*) FROM `char`'),
	'guilds'     => q($pdo, 'SELECT COUNT(*) FROM guild'),
];

// คนออนไลน์แยกตามแผนที่ (สูงสุด 8 แผนที่, เรียงตามจำนวนมากสุดก่อน)
$stats['maps'] = [];
$st = $pdo->prepare(
	'SELECT last_map AS m, COUNT(*) AS c FROM `char` WHERE online = 1 GROUP BY last_map ORDER BY c DESC, m ASC LIMIT 8'
);
$st->execute();
$st->bindColumn('m', $map);
$st->bindColumn('c', $cnt);
while ($st->fetch(PDO::FETCH_ASSOC) && $map !== '') {
	$stats['maps'][] = ['map' => $map, 'count' => (int)$cnt];
}

$stats['updated_at'] = date('c'); // ISO 8601 (UTC offset ท้องถิ่น)

$bytes = file_put_contents($outFile, json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
if ($bytes === false) {
	fwrite(STDERR, "Cannot write {$outFile}" . PHP_EOL);
	exit(1);
}

echo "OK -> {$outFile}" . PHP_EOL;
echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;