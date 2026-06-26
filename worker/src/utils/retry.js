export async function withRetry(fn, { maxRetries = 3, baseMs = 1000, log = console } = {}) {
  for (let attempt = 0; attempt <= maxRetries; attempt++) {
    try {
      return await fn();
    } catch (err) {
      if (attempt === maxRetries) throw err;
      const wait = baseMs * Math.pow(2, attempt) + Math.random() * 1000;
      log.warn(`retry ${attempt + 1}/${maxRetries} after ${Math.round(wait)}ms: ${err.message}`);
      await new Promise(r => setTimeout(r, wait));
    }
  }
}
