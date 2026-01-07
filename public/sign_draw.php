<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/protected.php';
bootstrap();

$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo "ID inválido."; exit; }

$stmt = $pdo->prepare("SELECT id,tipo,numero_formatado,nome,data_emissao,data_validade FROM records WHERE id=?");
$stmt->execute([$id]);
$rec = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$rec) { http_response_code(404); echo "Registro não encontrado."; exit; }

$redirectTo = isset($_GET['back']) && $_GET['back']==='pending' ? 'pending.php' : 'list.php';
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Assinar Credencial #<?= (int)$rec['id'] ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{--bg:#f6f7fb;--card:#fff;--text:#111;--muted:#6b7280;--line:#e5e7eb;--yellow:#dcbb14;--yellow-800:#927d0b}
    *{box-sizing:border-box} body{font-family:Arial,sans-serif;background:var(--bg);color:var(--text);margin:0}
    .container{max-width:1000px;margin:0 auto;padding:24px}
    .nav{display:flex;gap:10px;margin-bottom:16px}
    .nav a{display:inline-block;padding:8px 12px;border:1px solid var(--line);border-radius:10px;background:#fff;text-decoration:none;color:#111}
    .nav a.primary{background:var(--yellow);border-color:var(--yellow);font-weight:700}
    .card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:16px}
    .meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px}
    .meta div{background:#fffdf3;border:1px dashed #f1e7a8;border-radius:10px;padding:10px}
    .label{font-size:12px;color:#7a6b00}.value{font-weight:700;margin-top:2px}
    .toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:10px 0}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:12px;border:1px solid var(--yellow);background:var(--yellow);font-weight:700;cursor:pointer}
    .btn.outline{background:#fff;color:#111}
    .canvas-wrap{border:2px dashed #e8e5c7;border-radius:12px;background:#fff;position:relative;overflow:hidden}
    /* linha-guia */
    .guide{position:absolute;left:0;right:0;height:1px;background:repeating-linear-gradient(90deg,#bbb 0 10px,transparent 10px 20px);pointer-events:none}
    .small{font-size:12px;color:var(--muted)}
  </style>
</head>
<body>
<div class="container">

  <!-- NAV: inclui Início -->
  <div class="nav">
    <a href="index.php">Início</a>
    <a href="pending.php">Assinaturas pendentes</a>
    <a href="list.php" class="primary">Credenciais emitidas</a>
  </div>

  <h1>Assinar Credencial</h1>

  <div class="card" style="margin:12px 0">
    <div class="meta">
      <div><div class="label">Registro</div><div class="value"><?= htmlspecialchars($rec['numero_formatado']) ?></div></div>
      <div><div class="label">Tipo</div><div class="value"><?= htmlspecialchars($rec['tipo']) ?></div></div>
      <div><div class="label">Nome</div><div class="value"><?= htmlspecialchars($rec['nome']) ?></div></div>
      <div><div class="label">Emissão</div><div class="value"><?= date('d/m/Y', strtotime($rec['data_emissao'])) ?></div></div>
      <div><div class="label">Validade</div><div class="value"><?= date('d/m/Y', strtotime($rec['data_validade'])) ?></div></div>
    </div>
  </div>

  <!-- IMPORTANTE: mandamos redirect_to para o finalize decidir para onde voltar -->
  <form id="signForm" method="post" action="sign_store.php">
    <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>">
    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectTo) ?>">
    <input type="hidden" name="dataUrl" id="dataUrl">

    <div class="card">
      <div class="toolbar">
        <label>Assinante:
          <select name="by" required>
            <option value="RENANN">RENANN</option>
            <option value="KARINE">KARINE</option>
          </select>
        </label>
        <label><input type="checkbox" id="thin"> traço fino</label>

        <!-- Linha-guia ON/OFF + altura -->
        <label><input type="checkbox" id="guide" checked> linha-guia</label>
        <label class="small">altura da linha
          <input type="range" id="guideY" min="5" max="95" value="75">
          <span id="guidePct" class="small">75%</span>
        </label>

        <button type="button" class="btn outline" id="clearBtn">Limpar</button>
      </div>

      <div class="canvas-wrap">
        <div id="guideLine" class="guide" style="top:75%"></div>
        <canvas id="pad" width="940" height="260"></canvas>
      </div>

      <div class="toolbar" style="justify-content:flex-end">
        <button type="button" class="btn" id="saveBtn">Salvar assinatura</button>
      </div>
      <div class="small">Assine com o mouse (ou dedo, no touch). Depois clique em “Salvar assinatura”.</div>
    </div>
  </form>
</div>

<script>
(function(){
  const canvas = document.getElementById('pad');
  const ctx = canvas.getContext('2d');
  const thin = document.getElementById('thin');
  const clearBtn = document.getElementById('clearBtn');
  const saveBtn = document.getElementById('saveBtn');
  const out = document.getElementById('dataUrl');

  const guideChk = document.getElementById('guide');
  const guideY = document.getElementById('guideY');
  const guidePct = document.getElementById('guidePct');
  const guideLine = document.getElementById('guideLine');

  let drawing=false,last=null;

  function setStyle(){ ctx.lineCap='round'; ctx.lineJoin='round'; ctx.strokeStyle='#111'; ctx.lineWidth=thin.checked?2:3.5; }
  function pos(e){ const r=canvas.getBoundingClientRect(); return {x:(e.touches?e.touches[0].clientX:e.clientX)-r.left, y:(e.touches?e.touches[0].clientY:e.clientY)-r.top}; }
  function start(e){ drawing=true; last=pos(e); e.preventDefault(); }
  function move(e){ if(!drawing) return; const p=pos(e); setStyle(); ctx.beginPath(); ctx.moveTo(last.x,last.y); ctx.lineTo(p.x,p.y); ctx.stroke(); last=p; e.preventDefault(); }
  function end(e){ drawing=false; e.preventDefault(); }

  canvas.addEventListener('mousedown',start); canvas.addEventListener('mousemove',move);
  canvas.addEventListener('mouseup',end); canvas.addEventListener('mouseleave',end);
  canvas.addEventListener('touchstart',start,{passive:false}); canvas.addEventListener('touchmove',move,{passive:false});
  canvas.addEventListener('touchend',end,{passive:false});

  clearBtn.addEventListener('click',()=>ctx.clearRect(0,0,canvas.width,canvas.height));
  saveBtn.addEventListener('click',()=>{
    const pixels = ctx.getImageData(0,0,canvas.width,canvas.height).data;
    let hasInk=false; for(let i=3;i<pixels.length;i+=4){ if(pixels[i]!==0){ hasInk=true; break; } }
    if(!hasInk){ alert('Assine no quadro antes de salvar.'); return; }
    out.value = canvas.toDataURL('image/png');
    document.getElementById('signForm').submit(); // -> sign_store.php -> sign_finalize.php
  });

  // linha-guia
  function updateGuide(){
    guideLine.style.display = guideChk.checked ? 'block' : 'none';
    guideLine.style.top = guideY.value + '%';
    guidePct.textContent = guideY.value + '%';
  }
  guideChk.addEventListener('change',updateGuide);
  guideY.addEventListener('input',updateGuide);
  updateGuide();
})();
</script>
</body>
</html>
