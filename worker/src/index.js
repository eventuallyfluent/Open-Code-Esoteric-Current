import { loadConfig } from './config.js';
import { createLogger } from './utils/logger.js';
import { createDeepSeekClient } from './deepseek.js';
import { createWordPressClient } from './wordpress.js';
import { createResearchAgent } from './agents/research.js';
import { createCollectorAgent } from './agents/collector.js';

const ESOTERIC_CATEGORIES = [
  'Hermeticism', 'Alchemy', 'Astrology', 'Ceremonial Magic',
  'Kabbalah', 'Gnosticism', 'Dzogchen', 'Shamanism', 'Tantra',
  'Sufism', 'Mysticism', 'Esoteric Christianity', 'Theosophy',
  'Rosicrucianism', 'Neoplatonism',
];

const BLOCKED_DOMAINS = new Set([
  'wikipedia.org',
  'archive.org',
  'encyclopedia.com',
  'britannica.com',
]);

async function main() {
  const config = loadConfig();
  const log = createLogger(config.logLevel);

  log.info('worker-start', { dryRun: config.dryRun, model: config.deepseekModel });

  const wp = createWordPressClient(config, log);
  const deepseek = createDeepSeekClient(config, log);

  if (!config.dryRun) {
    try {
      const health = await wp.health();
      log.info('wp-health-ok', { version: health.version });
    } catch (err) {
      log.error('wp-health-failed', err.message);
      process.exit(1);
    }
  }

  log.info('phase-research-start');
  const research = createResearchAgent(deepseek);
  let topics;
  if (config.dryRun) {
    topics = [
      { title: 'New book on Hermetic astrology', category: 'Hermeticism', reason: 'Recently published title from Inner Traditions', confidence: 0.5 },
    ];
    log.info('dry-run: using mock topics');
  } else {
    topics = await research.discoverTopics(ESOTERIC_CATEGORIES);
    log.info('phase-research-complete', { count: topics.length });
  }

  let claimedRuns = [];
  if (!config.dryRun && topics.length > 0) {
    const claimResult = await wp.claim(topics);
    if (claimResult.claimed && claimResult.runs?.length > 0) {
      claimedRuns = claimResult.runs;
      log.info('topics-claimed', { count: claimedRuns.length });
      claimedRuns.forEach(r => log.info('claimed-topic', { topic: r.topic.title, runUuid: r.run_uuid }));
    } else {
      log.info('no-topics-to-claim', { message: claimResult.message });
    }
  } else if (config.dryRun) {
    claimedRuns = topics.map(t => ({ topic: t, run_uuid: 'dry-run' }));
  }

  const collector = createCollectorAgent(deepseek);

  function filterSources(sources, topicTitle, log) {
    if (!sources || !Array.isArray(sources)) return [];
    const before = sources.length;
    const filtered = sources.filter(src => {
      if (!src.url) return false;
      let hostname;
      try {
        hostname = new URL(src.url).hostname;
      } catch {
        return false;
      }
      const blocked = [...BLOCKED_DOMAINS].some(domain =>
        hostname === domain || hostname.endsWith('.' + domain)
      );
      if (blocked) {
        log.debug('source-blocked', { url: src.url, title: src.title, domain: hostname, topic: topicTitle });
      }
      return !blocked;
    });
    const removed = before - filtered.length;
    if (removed > 0) {
      log.info('sources-filtered', { topic: topicTitle, before, after: filtered.length, removed });
    }
    return filtered;
  }

  async function processRun(run) {
    const topic = run.topic;
    log.info('process-topic', { title: topic.title, runUuid: run.run_uuid });

    const briefing = await collector.deepDive(topic);
    if (!briefing || !briefing.sources || briefing.sources.length === 0) {
      log.warn('no-sources-found', { title: topic.title });
      if (!config.dryRun) {
        await wp.sendCallback({ run_uuid: run.run_uuid, status: 'completed', findings: [] });
      }
      return null;
    }
    log.info('collect-complete', { title: topic.title, sources: briefing.sources.length });

    const sources = filterSources(briefing.sources, topic.title, log);
    if (sources.length === 0) {
      log.warn('all-sources-filtered', { title: topic.title });
      if (!config.dryRun) {
        await wp.sendCallback({ run_uuid: run.run_uuid, status: 'completed', findings: [] });
      }
      return null;
    }

    if (!config.dryRun) {
      const findings = sources.map(src => ({
        title: briefing.title || src.title || topic.title,
        excerpt: src.relevance || '',
        url: src.url,
        source_url: src.url,
        finding_type: topic.category?.toLowerCase().replace(/\s+/g, '-') || 'resource',
        source_domain: new URL(src.url).hostname,
      }));

      await wp.sendCallback({
        run_uuid: run.run_uuid,
        status: 'completed',
        category: topic.category,
        findings,
      });
      log.info('run-completed', { runUuid: run.run_uuid, findingsCount: findings.length });
    }

    return { topic: topic.title, sourceCount: sources.length };
  }

  const results = await Promise.all(claimedRuns.map(processRun));
  const completed = results.filter(Boolean);

  log.info('worker-complete', { topicsClaimed: claimedRuns.length, runsCompleted: completed.length });
}

main().catch(err => {
  console.error(JSON.stringify({ l: 'error', t: new Date().toISOString(), m: err.message, stack: err.stack }));
  process.exit(1);
});
