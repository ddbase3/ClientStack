// Run explicitly with node; not loaded by ILIAS/PHP class-map discovery.
// Requires php-cli and Chromium; uses no npm dependencies.
import { createServer } from 'node:http';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join, resolve } from 'node:path';
import { spawn, execFileSync } from 'node:child_process';
import { tmpdir } from 'node:os';
import { mkdtempSync, rmSync } from 'node:fs';

const testDir = dirname(fileURLToPath(import.meta.url));
const root = resolve(testDir, '../..');
const renderPhp = `
$view = new class {
  public array $_ = ['userSearchEndpoint' => '/api', 'userSearchScope' => 'groups',
    'modularSelectModuleUrl' => '/assets/modularselect/index.js', 'csrf' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
    'roleOptions' => [['id' => 4, 'label' => 'Role four']]];
  public function render() {
    $t = static fn(string $key, string $fallback) => $fallback;
    $editorPrefix = 'rules[demo]';
    $editorRows = [['kind' => 'user', 'value' => '12', 'config' => '{}']];
    $availableGroups = ['development']; $criterionOptions = [];
    $allowRoles = true; $allowGroups = true;
    ob_start();
    require '${root}/tpl/Display/AuthorizationAudienceEditor.php';
    return ob_get_clean();
  }
};
echo '<form id="the-form">' . $view->render() . '</form>';
`;
const rendered = execFileSync('php', ['-r', renderPhp], {encoding:'utf8'});
const editorScript = readFileSync(join(root, 'tpl/Display/AuthorizationAudienceScript.php'), 'utf8');
const testScript = String.raw`<script>
(async () => {
  const results = [];
  const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
  const until = async (check, message) => {
    for (let i = 0; i < 70; i++) {
      if (check()) return;
      await sleep(50);
    }
    throw new Error('Timed out: ' + message);
  };
  const check = (state, description) => {
    if (!state) throw new Error(description);
    results.push('ok: ' + description);
  };
  try {
    window.BASE3AudienceEditor.bindAll(document);
    const form = document.getElementById('the-form');
    const editor = form.querySelector('[data-audience-editor]');
    const row = form.querySelector('[data-audience-row]');
    const number = row.querySelector('[data-audience-number]');
    await until(() => !!row.querySelector('.mselect-root'), 'enhancement');
    check(number.type === 'hidden' && !number.disabled, 'canonical value field enabled and hidden');
    check(!row.querySelector('[data-audience-user-wrap]').hidden, 'user picker visible');
    await until(() => row.querySelector('.mselect-chip')?.textContent.includes('Resolved User'), 'initial selected ID resolved');
    check(number.value === '12', 'existing ID preserved');
    check(new FormData(form).get('rules[demo][0][value]') === '12', 'existing value submitted');
    const input = row.querySelector('.mselect-input');
    input.focus();
    input.value = 'anna';
    input.dispatchEvent(new Event('input', {bubbles:true}));
    await until(() => row.querySelector('.mselect-option')?.textContent.includes('Anna'), 'async search');
    row.querySelector('.mselect-option').click();
    check(number.value === '21', 'new user ID replaces old ID');
    check(new FormData(form).get('rules[demo][0][value]') === '21', 'original POST field unchanged');
    const kind = row.querySelector('[data-audience-kind]');
    kind.value = 'role';
    kind.dispatchEvent(new Event('change', {bubbles:true}));
    check(number.disabled && row.querySelector('[data-audience-user-wrap]').hidden, 'switch to role disables user');
    check(new FormData(form).get('rules[demo][0][value]') === null, 'user ID not submitted as role');
    kind.value = 'user';
    kind.dispatchEvent(new Event('change', {bubbles:true}));
    check(number.value === '' && number.type === 'hidden', 'switch back to user requires new selection');
    const add = form.querySelector('[data-audience-add]');
    add.click();
    const newRow = form.querySelectorAll('[data-audience-list] > [data-audience-row]')[1];
    const newKind = newRow.querySelector('[data-audience-kind]');
    newKind.value = 'user';
    newKind.dispatchEvent(new Event('change', {bubbles:true}));
    await until(() => form.querySelectorAll('[data-audience-list] > [data-audience-row] .mselect-root').length === 2, 'new user row enhanced');
    check(form.querySelectorAll('[data-audience-list] > [data-audience-row]').length === 2, 'new row added');
    form.querySelectorAll('[data-audience-remove]')[1].click();
    check(form.querySelectorAll('[data-audience-list] > [data-audience-row]').length === 1, 'row removed and cleaned');
    window.BASE3AudienceEditor.unbindAll(form);
    check(!form.querySelector('.mselect-root'), 'AJAX/tab unload destroys widgets');
    document.body.dataset.smoke = 'PASS';
  } catch(error) {
    results.push('FAIL: ' + (error?.stack || error));
    document.body.dataset.smoke = 'FAIL';
  }
  document.getElementById('results').textContent = results.join('\n');
})();
</script>`;
const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><link rel="stylesheet" href="/assets/modularselect/styles/modularselect.css"><style>.base3-authorization-editor [hidden]{display:none!important;}</style></head><body><main class="base3-authorization-editor">${rendered}</main>${editorScript}<pre id="results">PENDING</pre>${testScript}</body></html>`;
const requests = [];
const server = createServer(async (req, res) => {
  if (req.url === '/') { res.setHeader('Content-Type', 'text/html; charset=utf-8'); res.end(html); return; }
  if (req.url.startsWith('/assets/modularselect/')) {
    const path = req.url.slice('/assets/modularselect/'.length);
    if (!/^[a-zA-Z0-9_./-]+$/.test(path) || path.includes('..')) {res.writeHead(404).end(); return;}
    try {
      const content = readFileSync(join(root, 'assets/modularselect', path));
      res.setHeader('Content-Type', path.endsWith('.css') ? 'text/css' : 'text/javascript');
      res.end(content);
    } catch (_) { res.writeHead(404).end(); }
    return;
  }
  if (req.url === '/api' && req.method === 'POST') {
    let body = '';
    for await (const chunk of req) body += chunk;
    const data = new URLSearchParams(body);
    requests.push(Object.fromEntries(data));
    res.setHeader('Content-Type', 'application/json');
    if (data.get('csrf') !== 'a'.repeat(64) || data.get('scope') !== 'groups') {res.writeHead(403).end('{}'); return;}
    if (data.get('action') === 'resolve') {res.end(JSON.stringify({item: {value:'12', label:'Resolved User (login) [ID 12]'}})); return;}
    res.end(JSON.stringify({items: [{value:'21', label:'Anna User (anna) [ID 21]'}], hasMore:false}));
    return;
  }
  res.writeHead(404).end();
});
const userData = mkdtempSync(join(tmpdir(), 'base3-audience-chromium-'));
let browser = null;
try {
  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  const address = server.address();
  const chromium = process.env.CHROMIUM || 'chromium';
  const args = ['--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu', '--disable-background-networking', '--virtual-time-budget=12000', `--user-data-dir=${userData}`, '--dump-dom', `http://127.0.0.1:${address.port}/`];
  const child = spawn(chromium, args, {stdio:['ignore','pipe','pipe']});
  browser = child;
  let stdout = ''; let stderr = '';
  child.stdout.on('data', (data) => { stdout += data; });
  child.stderr.on('data', (data) => { stderr += data; });
  const result = await new Promise((resolve, reject) => {
    const timeout = setTimeout(() => {child.kill('SIGKILL'); reject(new Error('Chromium timeout'));}, 18000);
    child.on('error', reject);
    child.on('exit', (code) => {clearTimeout(timeout); resolve(code);});
  });
  const details = /<pre id="results">([\s\S]*?)<\/pre>/.exec(stdout)?.[1] ?? stderr.slice(-1000);
  const ok = result === 0 && stdout.includes('data-smoke="PASS"') && requests.some((r)=>r.action==='search' && r.query==='anna') && requests.some((r)=>r.action==='resolve' && r.id==='12');
  console.log(details.replaceAll('&gt;','>').replaceAll('&quot;','"'));
  if (!ok) throw new Error('Audience browser smoke failed; browser exit: ' + result);
  console.log('Browser integration smoke PASSED; real POST search and resolve were issued.');
} finally {
  browser?.kill('SIGKILL');
  browser?.stdout?.destroy();
  browser?.stderr?.destroy();
  server.closeAllConnections();
  server.close();
  rmSync(userData, {recursive:true,force:true});
}
