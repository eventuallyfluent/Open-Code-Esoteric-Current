import { loadConfig } from './config.js';
import { createLogger } from './utils/logger.js';
import { createDeepSeekClient } from './deepseek.js';
import { createWordPressClient } from './wordpress.js';
import { fetchPage, scoreLink } from './utils/fetcher.js';

const CATEGORIES = [
  'Hermeticism', 'Alchemy', 'Ceremonial Magic', 'Kabbalah',
  'Gnosticism', 'Dzogchen', 'Tantra', 'Sufism', 'Mysticism',
  'Esoteric Christianity', 'Theosophy', 'Occultism',
  'Chaos Magic', 'Shamanism', 'Astrology',
];

const MAX_SITES = 3;
const MAX_PAGES_PER_SITE = 3;
const MAX_FINDINGS = 8;

async function findRelevantSites(deepseek, category, log) {
  log.info('searching-sites', { category });
  const res = await deepseek.chat([
    { role: 'system', content: `Search the web for real, currently active websites with genuinely interesting content about ${category} in esoteric/occult studies. 

For each website, I will actually visit it to verify it exists. Only suggest websites you believe are real.

Return JSON array: [{ url, name, why_interesting }]. Max 5 results.` },
    { role: 'user', content: `Search the web for the most interesting, active websites with real content about ${category}. Focus on practitioner sites, specialist publishers, niche blogs, and actual organizations — NOT mainstream academic publishers.` },
  ], { temperature: 0.5, maxTokens: 4096 });

  const text = res.choices?.[0]?.message?.content || '[]';
  let candidates;
  try { candidates = JSON.parse(text.replace(/```(?:json)?\n?/g, '').trim()); }
  catch { return []; }

  const verified = [];
  for (const c of (candidates || [])) {
    if (!c.url) continue;
    const page = await fetchPage(c.url, log);
    if (!page) {
      log.debug('site-unreachable', { url: c.url, category });
      continue;
    }
    const score = scoreLink(page.url, page.text);
    log.info('site-checked', { url: page.url, score, textLen: page.text.length, category });
    if (score >= 3) {
      verified.push({ url: page.url, name: c.name || '', text: page.text, score, why: c.why_interesting || '' });
    }
    if (verified.length >= MAX_SITES) break;
  }
  return verified;
}

async function diveSite(deepseek, site, log) {
  log.info('diving-site', { url: site.url });

  const res = await deepseek.chat([
    { role: 'system', content: `You are looking at the homepage text of ${site.url} about ${site.name || 'an esoteric site'}. 

Based on this REAL content, suggest specific sub-pages on this same domain to visit for the most interesting content. 

Return JSON array: [{ path: string, reason: string }]. Paths should be relative (e.g. /articles/, /blog/, /courses/, /about/). Max 3.` },
    { role: 'user', content: `Homepage content: ${site.text.slice(0, 3000)}\n\nWhat pages on this site should I visit for the most interesting esoteric content?` },
  ], { temperature: 0.3, maxTokens: 2048 });

  const text = res.choices?.[0]?.message?.content || '[]';
  let suggestions;
  try { suggestions = JSON.parse(text.replace(/```(?:json)?\n?/g, '').trim()); }
  catch { suggestions = []; }

  const pages = [{ url: site.url, text: site.text }];
  const base = new URL(site.url);
  for (const s of (suggestions || [])) {
    if (pages.length >= MAX_PAGES_PER_SITE) break;
    try {
      const pageUrl = new URL(s.path, base.origin).href;
      if (new URL(pageUrl).hostname !== base.hostname) continue;
      const page = await fetchPage(pageUrl, log);
      if (page && page.text.length > 200) {
        pages.push({ url: page.url, text: page.text });
        log.info('page-fetched', { url: page.url, len: page.text.length });
      }
    } catch {}
  }

  const evalRes = await deepseek.chat([
    { role: 'system', content: `You are a curator of esoteric content. Below is real content from ${site.name || 'a website'} about esoteric topics.

Pick the most genuinely interesting FINDINGS from this content — specific articles, pages, courses, or resources that are noteworthy. Exclude generic/low-effort content.

Return JSON array of objects:
- title: string (descriptive title)
- excerpt: string (1-2 sentences, why it's interesting)
- url: string (the specific page URL)
- resource_type: string (article|book|course|paper|event|teacher|organization|podcast)
- reason_interesting: string

Max 3 findings. Only include things you can see evidence of in the actual content provided.` },
    { role: 'user', content: pages.map((p, i) => `--- Page ${i + 1}: ${p.url} ---\n${p.text.slice(0, 2500)}`).join('\n\n') },
  ], { temperature: 0.3, maxTokens: 4096 });

  const evalText = evalRes.choices?.[0]?.message?.content || '[]';
  try { return JSON.parse(evalText.replace(/```(?:json)?\n?/g, '').trim()); }
  catch { return []; }
}

async function main() {
  const config = loadConfig();
  const log = createLogger(config.logLevel);

  log.info('worker-start', { model: config.deepseekModel, dryRun: config.dryRun });

  const deepseek = createDeepSeekClient(config, log);
  const wp = createWordPressClient(config, log);

  if (!config.dryRun) {
    const health = await wp.health();
    log.info('wp-health-ok', { version: health.version });
  }

  for (const category of CATEGORIES) {
    log.info('phase-discover', { category });
    const sites = await findRelevantSites(deepseek, category, log);
    if (sites.length === 0) {
      log.warn('no-sites-found', { category });
      continue;
    }

    const allFindings = [];
    for (const site of sites) {
      const findings = await diveSite(deepseek, site, log);
      for (const f of findings) {
        allFindings.push({
          title: f.title || site.name,
          excerpt: f.excerpt || f.reason_interesting || '',
          url: f.url || site.url,
          source_url: f.url || site.url,
          finding_type: (f.resource_type || 'article').toLowerCase(),
          source_domain: new URL(f.url || site.url).hostname,
        });
      }
    }

    if (allFindings.length === 0) {
      log.warn('no-findings', { category });
      continue;
    }

    log.info('findings-ready', { category, count: allFindings.length });

    if (!config.dryRun) {
      try {
        const claimResult = await wp.claim([{
          title: `${category} — ${sites[0].name || 'discovered resources'}`,
          category,
          reason: `Found ${allFindings.length} real resources from verified websites`,
          confidence: 0.7,
        }]);
        const run = claimResult.runs?.[0];
        if (run) {
          await wp.sendCallback({
            run_uuid: run.run_uuid,
            status: 'completed',
            category,
            findings: allFindings.slice(0, MAX_FINDINGS),
          });
          log.info('category-done', { category, findingsCount: Math.min(allFindings.length, MAX_FINDINGS) });
        }
      } catch (err) {
        log.error('category-failed', { category, error: err.message });
      }
    } else {
      log.info('dry-run-findings', { category, count: allFindings.length, samples: allFindings.slice(0, 3).map(f => f.url) });
    }
  }

  log.info('worker-complete');
}

main().catch(err => {
  console.error(JSON.stringify({ l: 'error', t: new Date().toISOString(), m: err.message }));
  process.exit(1);
});
