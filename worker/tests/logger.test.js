import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { createLogger } from '../src/utils/logger.js';

describe('createLogger', () => {
  it('info level outputs info and above', () => {
    const log = createLogger('info');
    let output = [];
    const origLog = console.log;
    const origWarn = console.warn;
    const origError = console.error;
    console.log = (...args) => output.push(['log', ...args]);
    console.warn = (...args) => output.push(['warn', ...args]);
    console.error = (...args) => output.push(['error', ...args]);

    log.info('test-info');
    log.warn('test-warn');
    log.error('test-error');
    log.debug('test-debug');

    console.log = origLog;
    console.warn = origWarn;
    console.error = origError;

    assert.equal(output.length, 3);
  });
});
