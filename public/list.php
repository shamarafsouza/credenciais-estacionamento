<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/protected.php';
bootstrap();

$pdo   = db();
$tipoF = isset($_GET['tipo']) ? strtoupper(trim((string)$_GET['tipo'])) : '';
$q     = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$statusF = isset($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : '';
$ok = isset($_GET['ok']) ? (int)$_GET['ok'] : 0;

$sql = "SELECT id, tipo, numero_formatado, nome, data_emissao, data_validade,
               status, signed_by, signed_at
        FROM records";
$clauses = [];
$params  = [];

if ($tipoF === 'IDOSO' || $tipoF === 'PCD') {
    $clauses[] = "tipo = :tipo";
    $params[':tipo'] = $tipoF;
}
if ($q !== '') {
    $clauses[] = "(numero_formatado LIKE :q OR nome LIKE :q)";
    $params[':q'] = '%'.$q.'%';
}
if ($statusF === 'ASSINADO') {
    $clauses[] = "UPPER(COALESCE(status,'')) = 'ASSINADO'";
} elseif ($statusF === 'PENDENTE') {
    $clauses[] = "(status IS NULL OR UPPER(status) <> 'ASSINADO')";
}
if ($clauses) {
    $sql .= ' WHERE ' . implode(' AND ', $clauses);
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function badgeStatusValidade(string $ymd): array {
  $hoje = new DateTime('today');
  $val  = DateTime::createFromFormat('Y-m-d', $ymd) ?: new DateTime($ymd);
  if ($val < $hoje) return ['Vencida', 'danger'];
  $dias = (int)$hoje->diff($val)->format('%r%a');
  if ($dias <= 30) return ["Vence em {$dias}d", 'warn'];
  return ['Válida', 'ok'];
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Credenciais Emitidas - SEMTRANSP</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{
      --bg: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      --card: #ffffff;
      --text: #111827;
      --muted: #6b7280;
      --line: #f3f4f6;
      --yellow: #fbbf24;
      --yellow-dark: #d97706;
      --yellow-light: #fef3c7;
      --ok: #10b981;
      --warn: #f59e0b;
      --danger: #ef4444;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      padding: 24px;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
    }
    
    header {
      background: var(--card);
      padding: 24px 32px;
      border-radius: 20px;
      box-shadow: var(--shadow-lg);
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border: 2px solid var(--yellow);
    }
    
    header h1 {
      font-size: 28px;
      font-weight: 700;
      color: var(--text);
    }
    
    .logo {
      width: 80px;
      height: auto;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }
    
    nav {
      background: var(--card);
      padding: 16px 24px;
      border-radius: 16px;
      box-shadow: var(--shadow);
      margin-bottom: 24px;
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      border: 1px solid var(--line);
    }
    
    nav a {
      color: var(--yellow-dark);
      text-decoration: none;
      padding: 8px 16px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.2s;
      border: 2px solid transparent;
    }
    
    nav a:hover {
      background: var(--yellow-light);
      border-color: var(--yellow);
      transform: translateY(-2px);
    }
    
    .toolbar {
      background: var(--card);
      padding: 24px;
      border-radius: 16px;
      box-shadow: var(--shadow-lg);
      margin-bottom: 24px;
      border: 1px solid var(--line);
    }
    
    .toolbar form {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;
    }
    
    .toolbar select,
    .toolbar input[type="text"] {
      padding: 12px 16px;
      border: 2px solid var(--line);
      border-radius: 12px;
      font-size: 14px;
      background: #fff;
      color: var(--text);
      font-weight: 500;
      transition: all 0.2s;
      min-width: 180px;
    }
    
    .toolbar select:focus,
    .toolbar input[type="text"]:focus {
      outline: none;
      border-color: var(--yellow);
      box-shadow: 0 0 0 3px var(--yellow-light);
    }
    
    .toolbar input[type="text"] {
      flex: 1;
      min-width: 250px;
    }
    
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 20px;
      border-radius: 12px;
      border: none;
      background: linear-gradient(135deg, var(--yellow) 0%, var(--yellow-dark) 100%);
      color: #fff;
      text-decoration: none;
      font-weight: 700;
      font-size: 14px;
      box-shadow: var(--shadow);
      transition: all 0.2s;
      cursor: pointer;
      white-space: nowrap;
    }
    
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }
    
    .btn-outline {
      background: #fff;
      color: var(--yellow-dark);
      border: 2px solid var(--yellow);
    }
    
    .btn-outline:hover {
      background: var(--yellow-light);
    }
    
    .btn-pdf {
      background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
      color: #fff;
      padding: 10px 16px;
      font-size: 13px;
    }
    
    .btn-sign {
      background: linear-gradient(135deg, var(--ok) 0%, #059669 100%);
      padding: 10px 16px;
      font-size: 13px;
    }
    
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
      gap: 20px;
      margin-bottom: 24px;
    }
    
    .credential-card {
      background: var(--card);
      border-radius: 16px;
      box-shadow: var(--shadow);
      border: 2px solid var(--line);
      overflow: hidden;
      transition: all 0.3s;
    }
    
    .credential-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-lg);
      border-color: var(--yellow);
    }
    
    .card-header {
      background: linear-gradient(135deg, var(--yellow-light) 0%, #fef3c7 100%);
      padding: 16px 20px;
      border-bottom: 2px solid var(--yellow);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .card-number {
      font-family: 'Courier New', monospace;
      font-size: 18px;
      font-weight: 800;
      color: var(--yellow-dark);
    }
    
    .card-body {
      padding: 20px;
    }
    
    .card-name {
      font-size: 16px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 12px;
    }
    
    .card-info {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 16px;
      font-size: 13px;
    }
    
    .info-label {
      color: var(--muted);
      font-weight: 600;
      margin-bottom: 4px;
    }
    
    .info-value {
      color: var(--text);
      font-weight: 500;
      font-family: 'Courier New', monospace;
    }
    
    .card-footer {
      padding: 16px 20px;
      background: #fafafa;
      border-top: 1px solid var(--line);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
    }
    
    .card-actions {
      display: flex;
      gap: 8px;
    }
    
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      border: 2px solid;
      white-space: nowrap;
    }
    
    .badge-tipo {
      background: var(--yellow-light);
      border-color: var(--yellow);
      color: var(--yellow-dark);
    }
    
    .badge.ok {
      background: #d1fae5;
      border-color: #6ee7b7;
      color: #065f46;
    }
    
    .badge.warn {
      background: #fef3c7;
      border-color: #fde68a;
      color: #92400e;
    }
    
    .badge.danger {
      background: #fee2e2;
      border-color: #fca5a5;
      color: #991b1b;
    }
    
    .badge.signed {
      background: #d1fae5;
      border-color: #6ee7b7;
      color: #065f46;
    }
    
    .badge.pending {
      background: #fed7aa;
      border-color: #fdba74;
      color: #9a3412;
    }
    
    .badges-group {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }
    
    .signature-info {
      font-size: 11px;
      color: var(--muted);
      margin-top: 4px;
    }
    
    .stats {
      background: var(--card);
      padding: 16px 24px;
      border-radius: 16px;
      box-shadow: var(--shadow);
      text-align: center;
      margin-bottom: 24px;
      border: 2px solid var(--yellow);
    }
    
    .stats-number {
      font-size: 32px;
      font-weight: 800;
      color: var(--yellow-dark);
      margin-bottom: 4px;
    }
    
    .stats-label {
      font-size: 14px;
      color: var(--muted);
      font-weight: 600;
    }
    
    .empty-state {
      background: var(--card);
      padding: 60px 40px;
      border-radius: 16px;
      text-align: center;
      box-shadow: var(--shadow);
      border: 2px dashed var(--line);
    }
    
    .empty-state-text {
      color: var(--muted);
      font-size: 16px;
      font-weight: 500;
    }
    
    .toast {
      position: fixed;
      right: 24px;
      bottom: 24px;
      background: var(--ok);
      color: #fff;
      padding: 16px 24px;
      border-radius: 12px;
      box-shadow: var(--shadow-lg);
      opacity: 0;
      transform: translateY(8px);
      transition: all 0.3s;
      z-index: 9999;
      font-weight: 600;
    }
    
    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }
    
    @media (max-width: 768px) {
      .cards-grid {
        grid-template-columns: 1fr;
      }
      
      header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
      }
      
      .toolbar form {
        flex-direction: column;
        align-items: stretch;
      }
      
      .toolbar select,
      .toolbar input[type="text"] {
        width: 100%;
      }
      
      .card-info {
        grid-template-columns: 1fr;
      }
      
      .card-footer {
        flex-direction: column;
        align-items: stretch;
      }
      
      .card-actions {
        justify-content: stretch;
      }
      
      .card-actions .btn {
        flex: 1;
        justify-content: center;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1>Credenciais Emitidas</h1>
      <img src="assets/semtransp.png" alt="SEMTRANSP" class="logo">
    </header>

    <nav>
      <a href="index.php">← Início</a>
      <a href="create.php">Nova Emissão</a>
      <a href="pending.php">Pendentes</a>
    </nav>

    <div class="toolbar">
      <form method="get" action="">
        <select name="tipo">
          <option value="">Todos os tipos</option>
          <option value="IDOSO" <?= $tipoF==='IDOSO'?'selected':'' ?>>IDOSO</option>
          <option value="PCD" <?= $tipoF==='PCD'?'selected':'' ?>>PCD</option>
        </select>

        <select name="status">
          <option value="">Todos os status</option>
          <option value="PENDENTE" <?= $statusF==='PENDENTE'?'selected':'' ?>>Pendentes</option>
          <option value="ASSINADO" <?= $statusF==='ASSINADO'?'selected':'' ?>>Finalizados</option>
        </select>

        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por número ou nome">
        
        <button class="btn" type="submit">Filtrar</button>
        <a class="btn btn-outline" href="list.php">Limpar</a>
      </form>
    </div>

    <div class="stats">
      <div class="stats-number"><?= count($rows) ?></div>
      <div class="stats-label">Total de Credenciais</div>
    </div>

    <?php if (!$rows): ?>
      <div class="empty-state">
        <div class="empty-state-text">Nenhuma credencial encontrada</div>
      </div>
    <?php else: ?>
      <div class="cards-grid">
        <?php foreach ($rows as $r):
          [$label,$cls] = badgeStatusValidade($r['data_validade']);
          $emissaoBR  = date('d/m/Y', strtotime($r['data_emissao']));
          $validadeBR = date('d/m/Y', strtotime($r['data_validade']));
          $isSigned   = strtoupper((string)($r['status'] ?? '')) === 'ASSINADO';
          $signedBy   = $r['signed_by'] ? strtoupper($r['signed_by']) : null;
          $signedAtBR = $r['signed_at'] ? date('d/m/Y H:i', strtotime($r['signed_at'])) : null;
          $stText = $isSigned ? 'Finalizado' : 'Pendente';
          $stCls  = $isSigned ? 'signed' : 'pending';
        ?>
        <div class="credential-card">
          <div class="card-header">
            <span class="badge badge-tipo"><?= htmlspecialchars($r['tipo']) ?></span>
            <span class="card-number"><?= htmlspecialchars($r['numero_formatado']) ?></span>
          </div>
          <div class="card-body">
            <div class="card-name"><?= htmlspecialchars($r['nome']) ?></div>
            <div class="card-info">
              <div>
                <div class="info-label">Emissão</div>
                <div class="info-value"><?= $emissaoBR ?></div>
              </div>
              <div>
                <div class="info-label">Validade</div>
                <div class="info-value"><?= $validadeBR ?></div>
              </div>
            </div>
            <div class="badges-group">
              <span class="badge <?= $cls ?>"><?= $label ?></span>
              <span class="badge <?= $stCls ?>"><?= $stText ?></span>
            </div>
            <?php if ($isSigned): ?>
              <div class="signature-info">
                ASSINADO<?= $signedBy ? ' por '.htmlspecialchars($signedBy) : '' ?><?= $signedAtBR ? ' em '.$signedAtBR : '' ?>
              </div>
            <?php else: ?>
              <div class="signature-info">Aguardando assinatura</div>
            <?php endif; ?>
          </div>
          <div class="card-footer">
            <div class="card-actions">
              <a class="btn btn-pdf" href="download.php?id=<?= (int)$r['id'] ?>">PDF</a>
              <?php if (!$isSigned): ?>
                <a class="btn btn-sign" href="sign_draw.php?id=<?= (int)$r['id'] ?>">Assinar</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($ok === 1): ?>
    <div id="toast" class="toast">Assinatura salva com sucesso</div>
    <script>
      const t = document.getElementById('toast');
      if(t) {
        requestAnimationFrame(()=> t.classList.add('show'));
        setTimeout(()=> t.classList.remove('show'), 3000);
      }
    </script>
  <?php endif; ?>
</body>
</html>