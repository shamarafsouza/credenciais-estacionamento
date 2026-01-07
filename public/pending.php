<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/protected.php';
bootstrap();

$pdo = db();

$rows = $pdo->query("
  SELECT id, tipo, numero_formatado, nome, data_emissao, data_validade, status
  FROM records
  WHERE (status IS NULL OR UPPER(status) <> 'ASSINADO')
  ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

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
  <title>Assinaturas Pendentes - SEMTRANSP</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root {
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
    
    /* Header */
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
    
    /* Navigation */
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

    /* Alert Box */
    .alert-box {
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      border: 2px solid var(--yellow);
      padding: 20px 24px;
      border-radius: 16px;
      margin-bottom: 24px;
      box-shadow: var(--shadow);
    }

    .alert-content h3 {
      font-size: 16px;
      font-weight: 700;
      margin-bottom: 4px;
      color: var(--text);
    }

    .alert-content p {
      font-size: 14px;
      color: var(--muted);
      margin: 0;
    }

    /* Stats */
    .stats {
      background: var(--card);
      padding: 20px 28px;
      border-radius: 16px;
      box-shadow: var(--shadow);
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border: 2px solid var(--yellow);
    }

    .stats-item {
      text-align: center;
    }

    .stats-number {
      font-size: 36px;
      font-weight: 800;
      color: var(--yellow-dark);
      display: block;
    }

    .stats-label {
      font-size: 13px;
      color: var(--muted);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* Table Card */
    .table-card {
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 20px;
      box-shadow: var(--shadow-lg);
      overflow: hidden;
    }
    
    .table-wrap {
      overflow-x: auto;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    
    thead th {
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      text-align: left;
      font-weight: 700;
      font-size: 13px;
      padding: 16px;
      color: var(--text);
      border-bottom: 2px solid var(--yellow);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    tbody td {
      padding: 16px;
      font-size: 14px;
      vertical-align: middle;
      border-bottom: 1px solid var(--line);
    }
    
    tbody tr:hover {
      background: var(--yellow-light);
    }
    
    .mono {
      font-family: 'Courier New', Courier, monospace;
      font-weight: 600;
    }
    
    /* Badges */
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
    
    /* Buttons */
    .actions {
      display: flex;
      gap: 8px;
    }
    
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 10px 16px;
      border-radius: 10px;
      border: 2px solid;
      text-decoration: none;
      font-weight: 700;
      font-size: 13px;
      transition: all 0.2s;
      white-space: nowrap;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, var(--ok) 0%, #059669 100%);
      border-color: #059669;
      color: #fff;
      box-shadow: var(--shadow);
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }
    
    .btn-secondary {
      background: #fff;
      border-color: var(--line);
      color: var(--text);
    }
    
    .btn-secondary:hover {
      background: #f9fafb;
      border-color: var(--muted);
    }
    
    /* Empty State */
    .empty-state {
      padding: 60px 40px;
      text-align: center;
      background: var(--card);
      border-radius: 20px;
      box-shadow: var(--shadow-lg);
      border: 2px dashed var(--line);
    }
    
    .empty-state-title {
      font-size: 20px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 8px;
    }
    
    .empty-state-text {
      color: var(--muted);
      font-size: 14px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
      }
      
      .stats {
        flex-direction: column;
        gap: 20px;
      }
      
      .table-wrap {
        overflow-x: auto;
      }
      
      .actions {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
        justify-content: center;
      }

      .alert-box {
        text-align: center;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1>Assinaturas Pendentes</h1>
      <img src="assets/semtransp.png" alt="Brasão SEMTRANSP" class="logo">
    </header>

    <nav>
      <a href="index.php">← Voltar ao Início</a>
      <a href="list.php">Ver Todas as Credenciais</a>
    </nav>

    <div class="alert-box">
      <div class="alert-content">
        <h3>Atenção</h3>
        <p>As credenciais abaixo aguardam assinatura digital para finalização e liberação ao beneficiário.</p>
      </div>
    </div>

    <div class="stats">
      <div class="stats-item">
        <span class="stats-number"><?php echo isset($rows) ? count($rows) : 0; ?></span>
        <span class="stats-label">Pendentes</span>
      </div>
    </div>

    <?php if (!isset($rows) || count($rows) === 0): ?>
      <div class="empty-state">
        <div class="empty-state-title">Nenhuma assinatura pendente</div>
        <div class="empty-state-text">Todas as credenciais emitidas foram assinadas.</div>
      </div>
    <?php else: ?>
      <div class="table-card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Tipo</th>
                <th>Número</th>
                <th>Nome</th>
                <th>Emissão</th>
                <th>Validade</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r):
                [$label, $cls] = badgeStatusValidade($r['data_validade']);
              ?>
                <tr>
                  <td class="mono"><?= (int)$r['id'] ?></td>
                  <td><span class="badge badge-tipo"><?= htmlspecialchars($r['tipo']) ?></span></td>
                  <td class="mono"><?= htmlspecialchars($r['numero_formatado']) ?></td>
                  <td><?= htmlspecialchars($r['nome']) ?></td>
                  <td class="mono"><?= date('d/m/Y', strtotime($r['data_emissao'])) ?></td>
                  <td>
                    <div class="mono" style="margin-bottom: 6px;">
                      <?= date('d/m/Y', strtotime($r['data_validade'])) ?>
                    </div>
                    <span class="badge <?= $cls ?>"><?= $label ?></span>
                  </td>
                  <td>
                    <div class="actions">
                      <a class="btn btn-primary" href="sign_draw.php?id=<?= (int)$r['id'] ?>">Assinar</a>
                      <a class="btn btn-secondary" href="download.php?id=<?= (int)$r['id'] ?>">PDF</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>