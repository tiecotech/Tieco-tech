// Renders the Reel to numbered JPEG frames by driving Edge over the DevTools protocol.
// usage: node render-frames.mjs <outDir> [fps] [startFrame] [endFrame]
import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const EDGE = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const [outDir, fpsArg, fromArg, toArg] = process.argv.slice(2);
const fps = +(fpsArg || 30);
mkdirSync(outDir, { recursive: true });

const port = 9333;
const edge = spawn(EDGE, [
  '--headless=new', '--disable-gpu', '--hide-scrollbars', `--remote-debugging-port=${port}`,
  `--user-data-dir=${process.env.TEMP}\\edge-reel-cdp`, '--no-first-run', '--window-size=1080,1920', 'about:blank',
], { stdio: 'ignore' });

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
let targets;
for (let i = 0; i < 50; i++) {
  try { targets = await (await fetch(`http://127.0.0.1:${port}/json`)).json(); if (targets.some((t) => t.type === 'page')) break; } catch {}
  await sleep(300);
}
const page = targets.find((t) => t.type === 'page');
const ws = new WebSocket(page.webSocketDebuggerUrl);
await new Promise((r) => (ws.onopen = r));

let id = 0; const pending = new Map();
ws.onmessage = (m) => { const d = JSON.parse(m.data); if (d.id && pending.has(d.id)) { pending.get(d.id)(d); pending.delete(d.id); } };
const send = (method, params = {}) => new Promise((res, rej) => {
  const n = ++id; pending.set(n, (d) => (d.error ? rej(new Error(method + ': ' + JSON.stringify(d.error))) : res(d.result)));
  ws.send(JSON.stringify({ id: n, method, params }));
});
const evaluate = async (expression) => (await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true })).result.value;

await send('Page.enable');
await send('Emulation.setDeviceMetricsOverride', { width: 1080, height: 1920, deviceScaleFactor: 1, mobile: false });
await send('Page.navigate', { url: 'http://localhost:8765/reel.html?cap=1' });
await sleep(1500);
// wait for fonts and every image to be decoded before the first frame
await evaluate(`(async () => { await document.fonts.ready; await Promise.all([...document.images].map((i) => i.decode().catch(() => {}))); return true; })()`);
const total = await evaluate('window.TOTAL');
const frames = Math.round((total / 1000) * fps);
const from = +(fromArg || 0), to = Math.min(+(toArg || frames), frames);
console.log(`total ${total} ms -> ${frames} frames at ${fps} fps, rendering ${from}..${to - 1}`);

const t0 = Date.now();
for (let f = from; f < to; f++) {
  await evaluate(`window.seek(${((f * 1000) / fps).toFixed(3)})`);
  const shot = await send('Page.captureScreenshot', { format: 'jpeg', quality: 94, captureBeyondViewport: false });
  writeFileSync(join(outDir, `f${String(f).padStart(4, '0')}.jpg`), Buffer.from(shot.data, 'base64'));
  if (f % 60 === 0) console.log(`frame ${f}/${frames}  ${((Date.now() - t0) / 1000).toFixed(0)}s`);
}
console.log(`done in ${((Date.now() - t0) / 1000).toFixed(0)}s`);
ws.close(); edge.kill();
process.exit(0);
