<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/cid_rules.php';
require_once __DIR__ . '/../app/generate_pdf.php';
require_once __DIR__ . '/../app/protected.php';

bootstrap();

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $tipo       = strtoupper(trim($_POST['tipo'] ?? ''));
        $nome       = strtoupper(trim($_POST['nome'] ?? ''));
        $cid        = trim($_POST['cid'] ?? '');
        $dataEmiss  = trim($_POST['data_emissao'] ?? date('Y-m-d'));
        $zeroFill   = (int)($_POST['zero_fill'] ?? 3);

        $dataNasc    = trim($_POST['data_nascimento'] ?? '');
        $idade       = isset($_POST['idade']) ? (int)$_POST['idade'] : null;
        $endereco    = trim($_POST['endereco'] ?? '');
        $is_menor    = isset($_POST['is_menor']) ? 1 : 0;
        $responsavel = trim($_POST['responsavel'] ?? '');

        if (!in_array($tipo, ['IDOSO', 'PCD'], true)) throw new RuntimeException('Tipo inválido.');
        if ($nome === '') throw new RuntimeException('Nome obrigatório.');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataEmiss)) throw new RuntimeException('Data de emissão inválida.');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataNasc)) {
            throw new RuntimeException('Data de nascimento obrigatória.');
        }

        $dtNasc = new DateTime($dataNasc);
        $dtEmis = new DateTime($dataEmiss);
        $calcIdade = (int)$dtNasc->diff($dtEmis)->y;
        $idade = $calcIdade;

        if ($idade < 0 || $idade > 130) throw new RuntimeException('Idade calculada inválida.');
        if ($endereco === '') throw new RuntimeException('Endereço é obrigatório.');

        $ano = (int)substr($dataEmiss, 0, 4);

        if ($tipo === 'IDOSO') {
            $validadeAnos = 5;
            $cid = '';
            $is_menor = 0;
            $responsavel = null;
        } else {
            if ($cid === '') throw new RuntimeException('CID é obrigatório para PCD.');
            $validadeAnos = validadePorCID($cid);
            $is_menor = $idade < 18 ? 1 : 0;
            if ($is_menor === 1 && $responsavel === '') {
                throw new RuntimeException('Nome do responsável é obrigatório para menor de idade.');
            }
            if ($is_menor === 0) $responsavel = null;
        }

        $numero = nextNumber($tipo, $ano);
        $numeroFmtNum    = $zeroFill > 0 ? str_pad((string)$numero, $zeroFill, '0', STR_PAD_LEFT) : (string)$numero;
        $numeroFormatado = $numeroFmtNum . '/' . $ano;

        $dtIni = new DateTime($dataEmiss);
        $dtFim = (clone $dtIni)->modify('+' . $validadeAnos . ' years');
        $inicioBR = $dtIni->format('d/m/Y');
        $fimBR    = $dtFim->format('d/m/Y');

        $pdfPath = gerarPDF($tipo, $nome, $numeroFormatado, $inicioBR, $fimBR, $cid ?: null);
        if (!is_file($pdfPath)) throw new RuntimeException('Falha ao gerar o PDF.');

        $stmt = db()->prepare("
            INSERT INTO records
                (tipo, numero, ano, numero_formatado, nome, cid, validade_anos, data_emissao, data_validade, pdf_path,
                 idade, endereco, is_menor, responsavel, data_nascimento)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $tipo, $numero, $ano, $numeroFormatado, $nome, $cid ?: null, $validadeAnos,
            $dtIni->format('Y-m-d'), $dtFim->format('Y-m-d'), $pdfPath,
            $idade, $endereco, $is_menor, $responsavel, $dtNasc->format('Y-m-d')
        ]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($pdfPath) . '"');
        header('Content-Length: ' . filesize($pdfPath));
        readfile($pdfPath);
        exit;

    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Emissão de Credencial - SEMTRANSP</title>
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
      padding: 24px;
      min-height: 100vh;
    }

    .container {
      width: 100%;
      max-width: 1000px;
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
      gap: 20px;
      border: 2px solid var(--yellow);
    }

    .logo {
      width: 70px;
      height: auto;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }

    .header-content h1 {
      font-size: 26px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 4px;
    }

    .header-content .subtitle {
      color: var(--muted);
      font-size: 14px;
    }

    nav {
      background: var(--card);
      padding: 14px 20px;
      border-radius: 12px;
      box-shadow: var(--shadow);
      margin-bottom: 24px;
      display: flex;
      gap: 8px;
      border: 1px solid var(--line);
    }

    nav a {
      color: var(--yellow-dark);
      text-decoration: none;
      padding: 8px 14px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.2s;
    }

    nav a:hover {
      background: var(--yellow-light);
    }

    .error-box {
      background: #fee2e2;
      border: 2px solid #f87171;
      color: #991b1b;
      padding: 16px 20px;
      border-radius: 12px;
      margin-bottom: 24px;
      font-weight: 600;
      box-shadow: var(--shadow);
    }

    .form-card {
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 20px;
      padding: 32px;
      box-shadow: var(--shadow-lg);
      margin-bottom: 24px;
    }

    .section-title {
      font-size: 18px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--line);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .section-title::before {
      content: '';
      width: 4px;
      height: 20px;
      background: linear-gradient(135deg, var(--yellow) 0%, var(--yellow-dark) 100%);
      border-radius: 2px;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-bottom: 24px;
    }

    .grid-full {
      grid-column: 1 / -1;
    }

    .field-group {
      display: flex;
      flex-direction: column;
    }

    label {
      font-weight: 600;
      font-size: 14px;
      color: var(--text);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .required {
      color: #dc2626;
      font-weight: 700;
    }

    input[type="text"],
    input[type="date"],
    input[type="number"],
    select,
    textarea {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid var(--line);
      border-radius: 12px;
      background: #fff;
      font-size: 14px;
      color: var(--text);
      transition: all 0.2s;
      font-family: inherit;
    }

    input:focus,
    select:focus,
    textarea:focus {
      outline: none;
      border-color: var(--yellow);
      box-shadow: 0 0 0 3px var(--yellow-light);
    }

    input:read-only {
      background: #f9fafb;
      color: var(--muted);
      cursor: not-allowed;
    }

    textarea {
      min-height: 90px;
      resize: vertical;
      font-family: inherit;
    }

    .hint {
      color: var(--muted);
      font-size: 12px;
      margin-top: 6px;
    }

    .checkbox-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 16px;
      background: var(--yellow-light);
      border: 2px solid var(--yellow);
      border-radius: 12px;
      margin-bottom: 16px;
    }

    .checkbox-row input[type="checkbox"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
      accent-color: var(--yellow-dark);
    }

    .checkbox-row label {
      margin: 0;
      cursor: pointer;
      flex: 1;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      padding: 4px 10px;
      border-radius: 999px;
      background: var(--yellow);
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
    }

    .actions {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      padding-top: 24px;
      border-top: 2px solid var(--line);
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 14px 28px;
      border-radius: 12px;
      border: 2px solid;
      font-weight: 700;
      font-size: 15px;
      text-decoration: none;
      transition: all 0.2s;
      cursor: pointer;
      box-shadow: var(--shadow);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--yellow) 0%, var(--yellow-dark) 100%);
      border-color: var(--yellow-dark);
      color: #fff;
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

    .hidden {
      display: none;
    }

    @media (max-width: 768px) {
      .grid {
        grid-template-columns: 1fr;
      }

      header {
        flex-direction: column;
        align-items: flex-start;
      }

      .actions {
        flex-direction: column-reverse;
      }

      .btn {
        width: 100%;
      }

      .form-card {
        padding: 24px 20px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <img src="assets/semtransp.png" alt="Brasão SEMTRANSP" class="logo">
      <div class="header-content">
        <h1>Emissão de Credencial</h1>
        <p class="subtitle">Preencha os dados para gerar a credencial de estacionamento</p>
      </div>
    </header>

    <nav>
      <a href="index.php">← Voltar</a>
      <a href="list.php">Listar Credenciais</a>
    </nav>

    <?php if ($erro): ?>
      <div class="error-box">
        <strong>Erro:</strong> <?= htmlspecialchars($erro) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="form-card">
      <div class="section-title">Dados do Beneficiário</div>

      <div class="grid">
        <div class="field-group">
          <label for="tipo">Tipo da Credencial <span class="required">*</span></label>
          <select name="tipo" id="tipo" required>
            <option value="">Selecione o tipo</option>
            <option value="IDOSO">IDOSO (validade 5 anos)</option>
            <option value="PCD">PCD (validade conforme CID)</option>
          </select>
        </div>

        <div class="field-group">
          <label for="nome">Nome Completo <span class="required">*</span></label>
          <input type="text" id="nome" name="nome" placeholder="Digite o nome completo" required>
        </div>

        <div class="field-group">
          <label for="data_nascimento">Data de Nascimento <span class="required">*</span></label>
          <input type="date" id="data_nascimento" name="data_nascimento" required>
          <div class="hint">Será usada para calcular a idade automaticamente</div>
        </div>

        <div class="field-group">
          <label for="data_emissao">Data de Emissão <span class="required">*</span></label>
          <input type="date" id="data_emissao" name="data_emissao" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="field-group pcd-only hidden">
          <label for="cid">Código CID <span class="required">*</span></label>
          <input type="text" id="cid" name="cid" placeholder="Ex: H54.0">
          <div class="hint">Obrigatório para credenciais PCD</div>
        </div>

        <div class="field-group">
          <label for="zero_fill">Formato do Número</label>
          <select name="zero_fill" id="zero_fill">
            <option value="3">Com zeros à esquerda (ex: 046/2026)</option>
            <option value="0">Sem zeros (ex: 46/2026)</option>
          </select>
        </div>

        <div class="field-group">
          <label for="idade">Idade (calculada automaticamente)</label>
          <input type="number" id="idade" name="idade" placeholder="—" readonly>
          <div class="hint">Idade na data de emissão</div>
        </div>

        <div class="field-group grid-full">
          <label for="endereco">Endereço Completo <span class="required">*</span></label>
          <textarea id="endereco" name="endereco" placeholder="Rua, número, bairro, cidade/UF, CEP" required></textarea>
        </div>
      </div>

      <div class="pcd-only hidden">
        <div class="section-title">Informações Adicionais (PCD)</div>
        
        <div class="checkbox-row">
          <input type="checkbox" id="is_menor" name="is_menor" value="1">
          <label for="is_menor">Beneficiário é menor de idade (menos de 18 anos)</label>
          <span class="badge">Apenas PCD</span>
        </div>

        <div id="responsavel-field" class="field-group hidden">
          <label for="responsavel">Nome do Responsável Legal <span class="required">*</span></label>
          <input type="text" id="responsavel" name="responsavel" placeholder="Digite o nome do responsável">
          <div class="hint">Obrigatório para menores de idade</div>
        </div>
      </div>

      <div class="actions">
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Emitir Credencial</button>
      </div>
    </form>
  </div>

  <script>
    const tipoSelect = document.getElementById('tipo');
    const pcdFields = document.querySelectorAll('.pcd-only');
    const isMenorCheck = document.getElementById('is_menor');
    const responsavelField = document.getElementById('responsavel-field');
    const responsavelInput = document.getElementById('responsavel');
    const cidInput = document.getElementById('cid');
    
    const nascInput = document.getElementById('data_nascimento');
    const emisInput = document.getElementById('data_emissao');
    const idadeInput = document.getElementById('idade');

    function togglePcdFields() {
      const isPCD = tipoSelect.value === 'PCD';
      pcdFields.forEach(el => {
        if (isPCD) {
          el.classList.remove('hidden');
        } else {
          el.classList.add('hidden');
        }
      });

      if (cidInput) {
        if (isPCD) {
          cidInput.setAttribute('required', 'required');
        } else {
          cidInput.removeAttribute('required');
          cidInput.value = '';
        }
      }

      if (!isPCD && isMenorCheck) {
        isMenorCheck.checked = false;
        toggleResponsavel();
      }

      calculateAge();
    }

    function toggleResponsavel() {
      const show = isMenorCheck && isMenorCheck.checked;
      if (responsavelField) {
        if (show) {
          responsavelField.classList.remove('hidden');
          if (responsavelInput) responsavelInput.setAttribute('required', 'required');
        } else {
          responsavelField.classList.add('hidden');
          if (responsavelInput) {
            responsavelInput.removeAttribute('required');
            responsavelInput.value = '';
          }
        }
      }
    }

    function calculateAge() {
      const nasc = nascInput.value;
      const emis = emisInput.value;
      
      if (!nasc || !emis) {
        idadeInput.value = '';
        return;
      }

      const birthDate = new Date(nasc);
      const emitDate = new Date(emis);
      
      let age = emitDate.getFullYear() - birthDate.getFullYear();
      const monthDiff = emitDate.getMonth() - birthDate.getMonth();
      
      if (monthDiff < 0 || (monthDiff === 0 && emitDate.getDate() < birthDate.getDate())) {
        age--;
      }

      idadeInput.value = age >= 0 ? age : '';

      if (tipoSelect.value === 'PCD' && age < 18 && age >= 0) {
        if (isMenorCheck) isMenorCheck.checked = true;
        toggleResponsavel();
      } else if (tipoSelect.value === 'PCD' && age >= 18) {
        if (isMenorCheck) isMenorCheck.checked = false;
        toggleResponsavel();
      }
    }

    tipoSelect.addEventListener('change', togglePcdFields);
    if (isMenorCheck) isMenorCheck.addEventListener('change', toggleResponsavel);
    nascInput.addEventListener('change', calculateAge);
    emisInput.addEventListener('change', calculateAge);

    togglePcdFields();
    calculateAge();
  </script>
</body>
</html>