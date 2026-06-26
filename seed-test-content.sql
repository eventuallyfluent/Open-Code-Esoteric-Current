-- ============================================================
-- Seed test content for The Esoteric Current
-- Run this in phpMyAdmin / Adminer / wp-admin SQL console
-- Replace `wp_` with your actual table prefix if different
-- ============================================================

-- 1) Research topics (for the agent pipeline)
INSERT IGNORE INTO `wp_ec_research_topics`
  (`title`, `research_goal`, `priority`, `run_frequency`, `status`, `next_run_at`)
VALUES
  ('Recent developments in Hermetic alchemy',      'Find new publications and discussions on hermetic alchemy practice',    'high',   'weekly',  'active', UTC_TIMESTAMP()),
  ('Kabbalah in contemporary academia',             'Track recent academic papers and conferences on Kabbalah studies',      'normal', 'weekly',  'active', UTC_TIMESTAMP()),
  ('Esoteric Christianity movements',               'Surface books, events, and interviews on esoteric Christianity',        'normal', 'weekly',  'active', UTC_TIMESTAMP()),
  ('Dzogchen and Tantra cross-pollination',         'Identify teachers, retreats, and publications bridging these traditions', 'low',   'monthly', 'active', UTC_TIMESTAMP()),
  ('Ceremonial magic grimoire republications',       'Track new editions and translations of historical grimoires',           'normal', 'weekly',  'active', UTC_TIMESTAMP());

-- 2) Findings (content that appears on the homepage card grid)
INSERT IGNORE INTO `wp_ec_findings`
  (`id`, `finding_type`, `title`, `url`, `source_url`, `excerpt`, `relevance_score`, `confidence_score`, `classification`, `status`, `created_at`)
VALUES
  (1001, 'book',
   'The Kybalion: Centenary Edition',
   'https://example.com/kybalion-centenary',
   'https://publisher.example.com/hermetic',
   'A new annotated edition of the foundational Hermetic text, including commentary from contemporary practitioners and scholars.',
   92.00, 88.00, 'hermeticism,text', 'awaiting_review', UTC_TIMESTAMP()),

  (1002, 'research-paper',
   'Alchemical Symbolism in Early Modern Scientific Manuscripts',
   'https://example.com/alchemical-symbolism-paper',
   'https://journal.example.com/early-modern',
   'New research examining the influence of alchemical diagrams on the development of early modern scientific notation.',
   85.00, 82.00, 'alchemy,history', 'awaiting_review', UTC_TIMESTAMP()),

  (1003, 'interview',
   'A Conversation with Rabbi David Aaron on Contemporary Kabbalah',
   'https://example.com/rabbi-aaron-interview',
   'https://podcast.example.com/esoteric-pod',
   'An in-depth discussion on how Kabbalistic thought is being reinterpreted for the 21st century.',
   78.00, 75.00, 'kabbalah,contemporary', 'awaiting_review', UTC_TIMESTAMP()),

  (1004, 'event',
   'International Conference on Gnostic Studies 2026',
   'https://example.com/gnostic-conference-2026',
   'https://conference.example.com/gnostic',
   'Annual gathering of scholars and practitioners exploring Gnostic traditions from antiquity to modern revivals.',
   88.00, 85.00, 'gnosticism,conference', 'awaiting_review', UTC_TIMESTAMP()),

  (1005, 'podcast',
   'The Alchemy of Sound: Interview with John D. Smith',
   'https://example.com/alchemy-of-sound',
   'https://podcast.example.com/occult-pod',
   'Explores the relationship between harmonic resonance, alchemical principle, and contemplative practice.',
   72.00, 70.00, 'alchemy,music,mysticism', 'awaiting_review', UTC_TIMESTAMP()),

  (1006, 'news-article',
   'New Translation of Corpus Hermeticum Published by Oxford Press',
   'https://example.com/corpus-hermeticum-oxford',
   'https://news.example.com/religious-studies',
   'Oxford University Press releases a critical edition of the Corpus Hermeticum with facing translation and extensive commentary.',
   95.00, 90.00, 'hermeticism,text,translation', 'awaiting_review', UTC_TIMESTAMP()),

  (1007, 'organization',
   'The Theosophical Society Centennial Archive Goes Online',
   'https://example.com/theosophical-archive',
   'https://archive.example.com/theosophy',
   'Digitized collection of lectures, correspondence, and rare publications from the Theosophical Society''s first century.',
   80.00, 85.00, 'theosophy,archive', 'awaiting_review', UTC_TIMESTAMP()),

  (1008, 'development',
   'Sufi Mysticism in Modern Poetry: A Rising Trend',
   'https://example.com/sufi-poetry-trend',
   'https://arts.example.com/contemporary',
   'A growing number of contemporary poets are drawing on Sufi mystical imagery and Rumi-inspired forms.',
   65.00, 72.00, 'sufism,literature', 'awaiting_review', UTC_TIMESTAMP()),

  (1009, 'resource',
   'Online Archive of Rosicrucian Manifestos',
   'https://example.com/rosicrucian-manifestos',
   'https://archive.example.com/rosicrucian',
   'A comprehensive digital collection of the Fama Fraternitatis, Confessio Fraternitatis, and related Rosicrucian texts.',
   90.00, 92.00, 'rosicrucianism,text,archive', 'awaiting_review', UTC_TIMESTAMP()),

  (1010, 'video',
   'Lecture Series: Neoplatonism and the Western Esoteric Tradition',
   'https://example.com/neoplatonism-lectures',
   'https://video.example.com/academic',
   'Twelve-part lecture series tracing Neoplatonic influence from Plotinus through Renaissance magic to modern esotericism.',
   82.00, 80.00, 'neoplatonism,lecture', 'awaiting_review', UTC_TIMESTAMP()),

  (1011, 'person',
   'Profile: Dr. Helena Petrovna Blavatsky Collection at the British Library',
   'https://example.com/blavatsky-profile',
   'https://library.example.com/collections',
   'The British Library announces a newly catalogued collection of Blavatsky''s personal correspondence and working notes.',
   75.00, 78.00, 'theosophy,person,archive', 'awaiting_review', UTC_TIMESTAMP()),

  (1012, 'book',
   'Practical Alchemy: A Modern Practitioner''s Guide',
   'https://example.com/practical-alchemy-guide',
   'https://publisher.example.com/esoteric',
   'A hands-on guide to laboratory alchemical practice, bridging medieval texts with contemporary chemical understanding.',
   70.00, 65.00, 'alchemy,practice', 'awaiting_review', UTC_TIMESTAMP());

-- 3) Editorial queue entries (workflow_state = 'published' to appear on frontend)
INSERT IGNORE INTO `wp_ec_editorial_queue`
  (`source_type`, `source_id`, `workflow_state`, `topic_id`, `created_at`, `updated_at`)
VALUES
  ('finding', 1001, 'published', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
  ('finding', 1002, 'published', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
  ('finding', 1003, 'published', 2, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
  ('finding', 1004, 'published', 3, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
  ('finding', 1005, 'published', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
  ('finding', 1006, 'published', 2, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
  ('finding', 1007, 'published', 5, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
  ('finding', 1008, 'published', 4, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
  ('finding', 1009, 'published', 5, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
  ('finding', 1010, 'published', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
  ('finding', 1011, 'published', 3, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
  ('finding', 1012, 'published', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());

-- 4) Update findings status to match
UPDATE `wp_ec_findings` SET `status` = 'approved' WHERE `id` BETWEEN 1001 AND 1012;
