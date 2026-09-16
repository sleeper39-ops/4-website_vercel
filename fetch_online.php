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

// ตรวจสอบยอดผู้เข้าชมสะสมจาก hits.sh
$oldRaw = is_file($outFile) ? @file_get_contents($outFile) : false;
$old = $oldRaw !== false ? json_decode($oldRaw, true) : null;
$curViews = (isset($old['views']) && is_numeric($old['views'])) ? (int)$old['views'] : 50;
$ctx = stream_context_create(['http' => ['timeout' => 2]]);
$hitSvg = @file_get_contents('https://hits.sh/ropvp2026.vercel.app.svg', false, $ctx);
if ($hitSvg && preg_match('/aria-label="hits:\s*([0-9,]+)"/i', $hitSvg, $hm)) {
    $parsed = (int)str_replace(',', '', $hm[1]);
    $curViews = max($curViews, $parsed);
}
if ($curViews > 999999) { $curViews = (($curViews - 1) % 999999) + 1; }
$stats['views'] = $curViews;

// ตรวจว่าข้อมูลจริงเปลี่ยน (ไม่นับ updated_at) หรือไม่
function stable_dump($a) {
    if (!is_array($a)) return $a;
    ksort($a);
    foreach ($a as $k => $v) $a[$k] = stable_dump($v);
    return json_encode($a, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

$dataChanged = false;
if (is_array($old)) {
    $newComp = $stats;
    $oldComp = $old;
    unset($newComp['updated_at'], $oldComp['updated_at']);
    if (stable_dump($newComp) !== stable_dump($oldComp)) {
        $dataChanged = true;
    }
} else {
    $dataChanged = true; // ยังไม่มีไฟล์เดิม = มีข้อมูลใหม่ชัวร์
}

// ---------- แกลอรีรูปภาพ: สแกนโฟลเดอร์ gallery/ แล้วเขียน data/gallery.json ----------
$galleryDir = __DIR__ . '/gallery';
$thumbsDir = $galleryDir . '/thumbs';
$galleryFile = __DIR__ . '/data/gallery.json';
$gallery = [];
if (is_dir($galleryDir)) {
    @mkdir($thumbsDir, 0777, true);
    $exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    $files = scandir($galleryDir);
    foreach ($files as $f) {
        if ($f[0] === '.') continue;                  // ข้าม .gitkeep, .hidden
        $full = $galleryDir . '/' . $f;
        if (!is_file($full)) continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, $exts, true)) continue;
        $name = pathinfo($f, PATHINFO_FILENAME);
        // สร้าง thumbnail (กว้าง max 520px) เพื่อให้เว็บโหลดเร็ว
        $thumb = $thumbsDir . '/' . $name . '.jpg';
        $needThumb = !is_file($thumb) || filemtime($thumb) < filemtime($full);
        $mt = @filemtime($full) ?: time();
        $thumbUrl = is_file($thumb) ? 'gallery/thumbs/' . $name . '.jpg' : 'gallery/' . $f;
        if ($needThumb && function_exists('imagecreatefromstring')) {
            $src = @imagecreatefromstring(file_get_contents($full));
            if ($src) {
                $w = imagesx($src); $h = imagesy($src);
                $tw = min(520, $w);
                $th = max(1, (int)round($h * $tw / $w));
                $dst = imagecreatetruecolor($tw, $th);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
                imagejpeg($dst, $thumb, 82);
                imagedestroy($dst); imagedestroy($src);
                $thumbUrl = 'gallery/thumbs/' . $name . '.jpg';
            }
        }
        $gallery[] = [
            'src'   => 'gallery/' . $f,
            'thumb' => $thumbUrl,
            'name'  => $name,
            'm'     => $mt, // mtime ของรูปต้นฉบับ: ใช้เป็น cache-buster + ตรวจจับรูปที่ถูกแทนที่ (ชื่อเดิม)
        ];
    }
    usort($gallery, function ($a, $b) { return strcmp($a['src'], $b['src']); });
}
if ($gallery) {
    $newGalleryJson = json_encode($gallery, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $oldGalleryRaw = @file_get_contents($galleryFile);
    if ($oldGalleryRaw !== $newGalleryJson) {
        file_put_contents($galleryFile, $newGalleryJson);
        $dataChanged = true; // มีรูปใหม่/รูปหาย -> ให้ push
    }
} elseif (is_file($galleryFile)) {
    @unlink($galleryFile); // ไม่มีรูปในโฟลเดอร์ -> ลบไฟล์ลิสต์ทิ้ง
    $dataChanged = true;
}

// ตรวจว่าถึงรอบ heartbeat (ทุก 10 นาที) เพื่อ freshen timestamp หรือไม่
$needHeartbeat = false;
if (is_file($outFile)) {
    if ((time() - filemtime($outFile)) >= 600) {
        $needHeartbeat = true;
    }
} else {
    $dataChanged = true;
}

if ($dataChanged || $needHeartbeat) {
    $stats['updated_at'] = date('c');
    $bytes = file_put_contents($outFile, json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    if ($bytes === false) {
        fwrite(STDERR, "Cannot write {$outFile}" . PHP_EOL);
        exit(1);
    }
    @file_put_contents(__DIR__ . '/data/flag_changed', $stats['updated_at'] . PHP_EOL);
} else {
    if (is_array($old) && isset($old['updated_at'])) {
        $stats['updated_at'] = $old['updated_at'];
    }
}

echo ($dataChanged ? "DATA_CHANGED -> " : ($needHeartbeat ? "DATA_HEARTBEAT -> " : "DATA_SAME    -> ")) . "{$outFile}" . PHP_EOL;
echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;