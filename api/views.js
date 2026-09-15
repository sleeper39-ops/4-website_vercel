export default async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
  res.setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate');

  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  try {
    const resp = await fetch('https://hits.sh/ropvp2026.vercel.app.svg?t=' + Date.now());
    if (resp.ok) {
      const svg = await resp.text();
      const m = svg.match(/aria-label="hits:\s*(\d+)"/i) || svg.match(/>\s*([0-9,]+)\s*<\/text>/i);
      if (m && m[1]) {
        const views = parseInt(m[1].replace(/,/g, ''), 10);
        if (!isNaN(views) && views > 0) {
          return res.status(200).json({ views });
        }
      }
    }
  } catch (e) {
    // fallback
  }

  return res.status(200).json({ views: 50 });
}
