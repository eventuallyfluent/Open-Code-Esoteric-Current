import { loadConfig } from './config.js';
import { createLogger } from './utils/logger.js';
import { createDeepSeekClient } from './deepseek.js';
import { createWordPressClient } from './wordpress.js';
import { createResearchAgent } from './agents/research.js';
import { createCollectorAgent } from './agents/collector.js';
import { createSynthesizerAgent } from './agents/synthesizer.js';

const ESOTERIC_CATEGORIES = [
  'Hermeticism',
  'Alchemy',
  'Astrology',
  'Ceremonial Magic',
  'Kabbalah',
  'Gnosticism',
  'Dzogchen',
  'Shamanism',
  'Tantra',
  'Sufism',
  'Mysticism',
  'Esoteric Christianity',
  'Theosophy',
  'Rosicrucianism',
  'Neoplatonism',
];

async function main() {
  const config = loadConfig();
  const log = createLogger(config.logLevel);

  log.info('worker-start', { dryRun: config.dryRun, model: config.deepseekModel });

  // Check WordPress connectivity
  const wp = createWordPressClient(config, log);
  const deepseek = createDeepSeekClient(config, log);

  if (!config.dryRun) {
    try {
      await wp.health();
      log.info('wp-health-ok');
    } catch (err) {
      log.error('wp-health-failed', err.message);
      process.exit(1);
    }
  }

  // Phase 1: Research
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

  // Phase 2: Claim topics with WordPress
  let claimResult;
  if (!config.dryRun && topics.length > 0) {
    claimResult = await wp.claim(topics);
    log.info('topics-claimed', { count: topics.length, claimed: claimResult?.claimed?.length ?? 0 });
  }

  // Phase 3: Collect + Synthesize for each topic
  const collector = createCollectorAgent(deepseek);
  const synthesizer = createSynthesizerAgent(deepseek);

  const articles = [];
  const topicsToProcess = config.dryRun ? topics : (claimResult?.claimed ?? topics);

  for (const topic of topicsToProcess) {
    log.info('process-topic', { title: topic.title });

    // Collect
    const briefing = await collector.deepDive(topic);
    if (!briefing) {
      log.warn('collect-failed', { title: topic.title });
      continue;
    }
    log.info('collect-complete', { title: topic.title, keyPoints: briefing.key_points?.length ?? 0 });

    // Synthesize
    const article = await synthesizer.synthesize(briefing);
    if (!article) {
      log.warn('synthesize-failed', { title: topic.title });
      continue;
    }
    log.info('synthesize-complete', { title: article.title, wordCount: article.content?.length ?? 0 });

    articles.push({ ...article, _briefing: briefing });

    // Submit to WordPress
    if (!config.dryRun) {
      await wp.submitArticle({
        title: article.title,
        content: article.content,
        excerpt: article.excerpt,
        tags: article.tags,
        source_category: topic.category,
        research_notes: JSON.stringify(briefing),
      });
      log.info('article-submitted', { title: article.title });
    }
  }

  log.info('worker-complete', { articlesProduced: articles.length });
}

main().catch(err => {
  console.error(JSON.stringify({ l: 'error', t: new Date().toISOString(), m: err.message, stack: err.stack }));
  process.exit(1);
});
