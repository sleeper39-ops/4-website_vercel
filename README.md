# RO-2026 Website (ropvp2026.vercel.app)

เว็บสถิติเซิร์ฟเวอร์แบบ static (หน้าเดียว) โฮสต์บน **Vercel** โดเมน: **https://ropvp2026.vercel.app**
GitHub repo: **`sleeper39-ops/4-website_vercel`** — ใช้ไฟล์ `index.html` เพียงไฟล์เดียว + ข้อมูลสถิติใน `data/server.json`

## โครงสร้างไฟล์

| ไฟล์ | หน้าที่ |
|---|---|
| `index.html` | หน้าเว็บทั้งหมด (ภาษาไทย, สถิติ, วิธีเล่น, วิธีสมัคร, ดาวน์โหลด, FAQ) |
| `data/server.json` | ไฟล์สถิติที่เว็บอ่าน (คนออนไลน์, แอคเคานต์, ตัวละคร, กิลด์) |
| `fetch_online.php` | สคริปต์ในเครื่อง — อ่าน MariaDB แล้วเขียน `data/server.json` (READ ONLY) |
| `update_site.bat` | รัน `fetch_online.php` แล้ว commit+push ขึ้น GitHub ให้อัตโนมัติ |

## สิ่งจำเป็น

- **Git** — ติดตั้งแล้วพร้อมใช้ผ่าน **GitHub Desktop** (ถ้ายังไม่มี: https://github.com/apps/desktop)
- บัญชี **GitHub** และ **Vercel**

## ขั้นตอนครั้งแรก (ใช้ GitHub Desktop ได้เลย)

1. เปิด **GitHub Desktop** → File → **Add Local Repository** → เลือกโฟลเดอร์นี้:
   `C:\Users\WD_Black\Documents\RO_OFFLINE_2026\4-website_vercel`
2. ตอบให้สร้าง local repo → ที่แถวบนกด **Publish repository** → ตั้งชื่อ repo = **`4-website_vercel`**
   และเลือก **Public** (สำคัญ! ถ้า Private เว็บสาธารณะ+raw จะเปิดไม่ได้) แล้วกด Publish — ไฟล์ทั้งหมดจะถูก push ขึ้น GitHub
3. เปิด https://vercel.com → สมัคร/login ด้วย GitHub → **Add New... → Project** → เลือก repo `4-website_vercel`
   - ตั้งชื่อโปรเจกต์เป็น **`ropvp2026`** จะได้โดเมน = **https://ropvp2026.vercel.app**
   - Framework Preset: **Other** (ไม่ต้องมี build command, Output Directory ว่าง)
   - กด **Deploy**

> ถ้าใช้ขั้นตอน CLI แทน: `git init` → `git remote add origin https://github.com/<user>/ropvp2026.git` → `git push -u origin main`

## ระบบเรียลไทม์เป็นยังไง (สำคัญ)

เว็บเป็น static บน Vercel ต่อ MySQL โดยตรงไม่ได้ เลยใช้กลไก "สแนปช็อต + poll":

1. สคริปต์ในเครื่อง `fetch_online.php` อ่านจาก **MariaDB (ro2026)** → เขียน `data/server.json`
2. เว็บโหลดหน้านี้แล้ว **ดึงข้อมูลจาก GitHub raw ทุก 20 วินาที** จาก
   `https://raw.githubusercontent.com/sleeper39-ops/4-website_vercel/main/data/server.json`
   — หน้าจะแสดงตัวเลขสด ๆ แบบเรียลไทม์ (หน่วงสุดท้าย ~1-2 นาที นับจากที่ `update_site.bat` push)
3. ทุกครั้งที่ push ขึ้น GitHub → ไฟล์ `server.json` ใน raw ถูกอัปเดต → เว็บเห็นค่าใหม่เอง **โดยไม่ต้อง deploy Vercel ใหม่**

> เว็บใน `index.html` อ่านจาก `raw.githubusercontent.com/sleeper39-ops/4-website_vercel/main|master/data/server.json`
> แล้วล้มมาใช้ `data/server.json` ในเครื่องเป็นตัวสำรอง — ไม่ต้องแก้ลิงก์เองแล้ว (แก้แล้ว)

## อัปเดตสถิติคนออนไลน์

รัน `update_site.bat` **(แนะนำให้ตั้ง Task Scheduler รันทุก 1 นาที)** เพื่อให้ตัวเลขสดตลอด:
- เปิด **Task Scheduler** → Create Basic Task → ตั้ง "Repeat" ทุก 1 นาที → เปิด `C:\...\4-website_vercel\update_site.bat`
- สคริปต์จะ: อ่าน DB → เขียน `server.json` → `git commit` + `git push` → เว็บ poll เห็นค่าใหม่ภายใน ~20 วิ

## ตั้งค่าที่ต้องแก้

- **ลิงก์ดาวน์โหลดตัวเกม**: เปิด `index.html` แล้วแก้บรรทัด
  `<a class="btn" target="_blank" rel="noopener" href="#">ดาวน์โหลดตัวเกม</a>` — ใส่ลิงก์จริงแทน `#`
- **ค่าเชื่อมต่อ DB**: ถ้าเปลี่ยน user/พาส ให้แก้ใน `fetch_online.php` (ปัจจุบัน `ro2026` / `ro2026`, DB `ro2026`, port 3306)

## หมายเหตุความปลอดภัย

- `fetch_online.php` ใช้สิทธิ์ **SELECT เท่านั้น** รันเฉพาะในเครื่อง — ไม่ถูกอัปขึ้น Vercel/GitHub เพื่อความปลอดภัย
- ข้อมูลสถิติที่เผยแพร่ออกมาเป็นตัวเลขรวมเท่านั้น (ไม่เปิด username รายบุคคลในหน้าเว็บ)