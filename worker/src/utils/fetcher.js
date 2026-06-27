export async function fetchPage(url, log) {
  try {
    const res = await fetch(url, {
      signal: AbortSignal.timeout(10000),
      headers: { 'User-Agent': 'Mozilla/5.0 (compatible; EsotericCurrentBot/1.0)' },
    });
    if (!res.ok) return null;
    const html = await res.text();
    const text = extractText(html).slice(0, 4000);
    if (text.length < 100) return null;
    return { url: res.url, text };
  } catch {
    return null;
  }
}

function extractText(html) {
  let text = html
    .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&[^;]+;/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
  return text;
}

const BLOCKED_DOMAINS = [
  'amazon.com', 'ebay.com', 'etsy.com',
  'wikipedia.org', 'britannica.com', 'encyclopedia.com',
  'coursera.org', 'udemy.com', 'edx.org',
  'jstor.org', 'academia.edu', 'researchgate.net',
  'oup.com', 'cambridge.org', 'springer.com', 'tandfonline.com',
  'sagepub.com', 'wiley.com', 'elsevier.com', 'sciencedirect.com',
  'degruyter.com', 'brill.com', 'mit.edu', 'harvard.edu',
  'stanford.edu', 'ox.ac.uk', 'cam.ac.uk',
  'wikipedia.org', 'britannica.com',
  'goodreads.com', 'amazon.com',
  'youtube.com', 'vimeo.com',
  'instagram.com', 'facebook.com', 'twitter.com', 'x.com',
  'reddit.com', 'quora.com', 'medium.com',
  'archive.org', 'scribd.com', 'issuu.com',
];

export function scoreLink(url, text) {
  let score = 4;
  const u = url.toLowerCase();
  const t = text.toLowerCase();
  for (const domain of BLOCKED_DOMAINS) {
    if (u.includes(domain)) score -= 4;
  }
  if (/\.gov|\.mil/.test(u)) score -= 3;
  if (/\.edu\//.test(u)) score -= 2;
  if (t.length < 300) score -= 2;
  if (/esoteric|occult|hermetic|magic|alchemy|gnostic|kabbalah|tantra|sufi|mystic|shaman|wicca|pagan|ceremonial/i.test(t)) score += 3;
  if (/practitioner|ritual|initiation|tradition|teachings|wisdom|ancient|secret|sacred|grimoire|initiate|adept/i.test(t)) score += 2;
  if (/blog|podcast|zine|newsletter|forum|community|personal/i.test(u)) score += 3;
  if (/course|workshop|seminar|retreat|conference|festival/i.test(t)) score += 1;
  return Math.max(0, Math.min(10, score));
}
