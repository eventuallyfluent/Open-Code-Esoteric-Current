const levels = { error: 0, warn: 1, info: 2, debug: 3 };

export function createLogger(level = 'info') {
  const threshold = levels[level] ?? levels.info;
  return {
    error: (...args) => threshold >= 0 && console.error(JSON.stringify({ l: 'error', t: new Date().toISOString(), m: args.join(' ') })),
    warn:  (...args) => threshold >= 1 && console.warn( JSON.stringify({ l: 'warn',  t: new Date().toISOString(), m: args.join(' ') })),
    info:  (...args) => threshold >= 2 && console.log(  JSON.stringify({ l: 'info',  t: new Date().toISOString(), m: args.join(' ') })),
    debug: (...args) => threshold >= 3 && console.log(  JSON.stringify({ l: 'debug', t: new Date().toISOString(), m: args.join(' ') })),
  };
}
