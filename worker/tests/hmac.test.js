import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { signRequest, sign } from '../src/utils/hmac.js';

describe('HMAC signing', () => {
  it('signRequest produces deterministic output for same inputs', () => {
    const secret = 'test-secret';
    const a = signRequest('POST', '/ec/v1/health', '{}', secret);
    const b = signRequest('POST', '/ec/v1/health', '{}', secret);
    assert.notStrictEqual(a.signature, b.signature, 'nonces differ, so signatures differ');
    assert.equal(typeof a.nonce, 'string');
    assert.equal(typeof a.timestamp, 'number');
    assert.equal(typeof a.signature, 'string');
    assert.equal(a.signature.length, 64);
  });

  it('sign produces payload-based HMAC', () => {
    const secret = 'test-secret';
    const payload = { foo: 'bar' };
    const result = sign(payload, secret);
    assert.equal(typeof result.signature, 'string');
    assert.equal(result.signature.length, 64);
  });

  it('different payloads produce different signatures', () => {
    const secret = 'test-secret';
    const a = sign({ foo: 'bar' }, secret);
    const b = sign({ foo: 'baz' }, secret);
    assert.notStrictEqual(a.signature, b.signature);
  });

  it('signRequest with different methods differ', () => {
    const secret = 'test-secret';
    const a = signRequest('POST', '/ec/v1/claim', '{}', secret);
    const b = signRequest('GET', '/ec/v1/claim', '{}', secret);
    assert.notStrictEqual(a.signature, b.signature);
  });

  it('signRequest with different paths differ', () => {
    const secret = 'test-secret';
    const a = signRequest('POST', '/ec/v1/claim', '{}', secret);
    const b = signRequest('POST', '/ec/v1/article', '{}', secret);
    assert.notStrictEqual(a.signature, b.signature);
  });
});
