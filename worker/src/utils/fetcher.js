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

export function scoreLink(url, text) {
  let score = 5;
  const u = url.toLowerCase();
  const t = text.toLowerCase();
  if (/amazon\.com|ebay\.com|etsy\.com/.test(u)) score -= 3;
  if (/wikipedia\.org|britannica\.com|encyclopedia\.com/.test(u)) score -= 5;
  if (/coursera\.org|udemy\.com|edx\.org/.test(u)) score -= 3;
  if (/jstor\.org|academia\.edu|researchgate\.net/.test(u)) score -= 2;
  if (/\.gov|\.mil|\.edu\//.test(u)) score -= 1;
  if (t.length < 200) score -= 2;
  if (/esoteric|occult|hermetic|magic|alchemy|gnostic|kabbalah|tantra|sufi|mystic|shaman/i.test(t)) score += 3;
  if (/practitioner|ritual|initiation|tradition|teachings|wisdom|ancient|secret|sacred/i.test(t)) score += 2;
  if (/course|workshop|seminar|retreat|conference|festival/i.test(t)) score += 1;
  if (/blog|podcast|zine|newsletter|forum|community/i.test(u)) score += 2;
  return Math.max(0, Math.min(10, score));
}
