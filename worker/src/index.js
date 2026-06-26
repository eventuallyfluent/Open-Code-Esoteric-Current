import { loadConfig } from './config.js';
import { createLogger } from './utils/logger.js';
import { createDeepSeekClient } from './deepseek.js';
import { createWordPressClient } from './wordpress.js';
import { createResearchAgent } from './agents/research.js';
import { createCollectorAgent } from './agents/collector.js';
import { createSynthesizerAgent } from './agents/synthesizer.js';

const ESOTERIC_CATEGORIES = [
  'Hermeticism', 'Alchemy', 'Astrology', 'Ceremonial Magic',
  'Kabbalah', 'Gnosticism', 'Dzogchen', 'Shamanism', 'Tantra',
  'Sufism', 'Mysticism', 'Esoteric Christianity', 'Theosophy',
  'Rosicrucianism', 'Neoplatonism',
];

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
      { title: 'Test Topic', category: 'Hermeticism', reason: 'Dry run test', confidence: 0.5 },
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
  const synthesizer = createSynthesizerAgent(deepseek);

  async function processRun(run) {
    const topic = run.topic;
    log.info('process-topic', { title: topic.title, runUuid: run.run_uuid });

    const briefing = await collector.deepDive(topic);
    if (!briefing) {
      log.warn('collect-failed', { title: topic.title });
      if (!config.dryRun) {
        await wp.sendCallback({ run_uuid: run.run_uuid, status: 'failed', error: { code: 'collect_failed', message: 'Agent could not compile briefing' } });
      }
      return null;
    }
    log.info('collect-complete', { title: topic.title, keyPoints: briefing.key_points?.length ?? 0 });

    const article = await synthesizer.synthesize(briefing);
    if (!article) {
      log.warn('synthesize-failed', { title: topic.title });
      if (!config.dryRun) {
        await wp.sendCallback({ run_uuid: run.run_uuid, status: 'failed', error: { code: 'synthesize_failed', message: 'Agent could not generate article' } });
      }
      return null;
    }
    log.info('synthesize-complete', { title: article.title, wordCount: article.content?.length ?? 0 });

    if (!config.dryRun) {
      const submitResult = await wp.submitArticle({
        title: article.title,
        content: article.content,
        excerpt: article.excerpt,
        tags: article.tags,
        source_category: topic.category || topic.category_name,
        research_notes: JSON.stringify(briefing),
      });
      log.info('article-submitted', { title: article.title, postId: submitResult.post_id });

      await wp.sendCallback({
        run_uuid: run.run_uuid,
        status: 'completed',
        findings: [
          { title: article.title, content: article.content, excerpt: article.excerpt, tags: article.tags, post_id: submitResult.post_id, source: briefing.sources }
        ],
      });
      log.info('run-completed', { runUuid: run.run_uuid });
    }

    return { title: article.title, content: article.content, excerpt: article.excerpt, tags: article.tags, _briefing: briefing };
  }

  const results = await Promise.all(claimedRuns.map(processRun));
  const articles = results.filter(Boolean);

  log.info('worker-complete', { topicsClaimed: claimedRuns.length, articlesProduced: articles.length });
}

main().catch(err => {
  console.error(JSON.stringify({ l: 'error', t: new Date().toISOString(), m: err.message, stack: err.stack }));
  process.exit(1);
});
