<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Sistema de Credenciais - SEMTRANSP</title>
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

    html, body {
      height: 100%;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      min-height: 100vh;
    }

    .container {
      width: 100%;
      max-width: 1100px;
    }

    /* Header */
    .header {
      background: var(--card);
      padding: 40px 32px;
      border-radius: 24px;
      box-shadow: var(--shadow-lg);
      margin-bottom: 32px;
      text-align: center;
      border: 3px solid var(--yellow);
      position: relative;
      overflow: hidden;
    }

    .header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, var(--yellow) 0%, var(--yellow-dark) 100%);
    }

    .logo {
      width: 140px;
      height: auto;
      display: block;
      margin: 0 auto 24px;
      filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
    }

    h1 {
      font-size: 32px;
      font-weight: 700;
      margin-bottom: 12px;
      color: var(--text);
    }

    .subtitle {
      color: var(--muted);
      font-size: 15px;
      line-height: 1.6;
      max-width: 700px;
      margin: 0 auto;
      font-weight: 500;
    }

    /* Intro Text */
    .intro {
      background: var(--card);
      padding: 18px 24px;
      border-radius: 16px;
      margin-bottom: 24px;
      box-shadow: var(--shadow);
      border-left: 4px solid var(--yellow);
    }

    .intro p {
      font-size: 15px;
      font-weight: 600;
      color: var(--text);
      margin: 0;
    }

    /* Cards Grid */
    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 24px;
    }

    .card {
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 20px;
      padding: 32px 28px;
      box-shadow: var(--shadow);
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--yellow) 0%, var(--yellow-dark) 100%);
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.3s;
    }

    .card:hover {
      transform: translateY(-6px);
      box-shadow: var(--shadow-lg);
      border-color: var(--yellow);
    }

    .card:hover::before {
      transform: scaleX(1);
    }

    .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
      padding-bottom: 16px;
      border-bottom: 2px solid var(--line);
    }

    .card h3 {
      font-size: 20px;
      font-weight: 700;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .card p {
      color: var(--muted);
      font-size: 14px;
      line-height: 1.6;
      margin-bottom: 24px;
    }

    /* Badge */
    .badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 6px 14px;
      border-radius: 999px;
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      border: 2px solid var(--yellow);
      color: var(--yellow-dark);
      font-size: 15px;
      font-weight: 800;
      min-width: 36px;
      box-shadow: var(--shadow);
    }

    /* Button */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 14px 20px;
      border-radius: 12px;
      border: none;
      background: linear-gradient(135deg, var(--yellow) 0%, var(--yellow-dark) 100%);
      color: #fff;
      text-decoration: none;
      font-weight: 700;
      font-size: 15px;
      box-shadow: var(--shadow);
      transition: all 0.2s;
      cursor: pointer;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .btn:active {
      transform: translateY(0);
    }

    .btn-arrow {
      margin-left: auto;
      transition: transform 0.2s;
    }

    .btn:hover .btn-arrow {
      transform: translateX(4px);
    }

    /* Footer */
    .footer {
      text-align: center;
      margin-top: 32px;
      padding: 20px;
      color: var(--muted);
      font-size: 13px;
      background: var(--card);
      border-radius: 12px;
      box-shadow: var(--shadow);
      border: 1px solid var(--line);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .cards {
        grid-template-columns: 1fr;
      }

      h1 {
        font-size: 26px;
      }

      .logo {
        width: 110px;
      }

      .header {
        padding: 32px 24px;
      }

      .card {
        padding: 24px 20px;
      }

      .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
      }
    }

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .header {
      animation: fadeInUp 0.6s ease-out;
    }

    .intro {
      animation: fadeInUp 0.6s ease-out 0.1s backwards;
    }

    .card:nth-child(1) {
      animation: fadeInUp 0.6s ease-out 0.2s backwards;
    }

    .card:nth-child(2) {
      animation: fadeInUp 0.6s ease-out 0.3s backwards;
    }

    .card:nth-child(3) {
      animation: fadeInUp 0.6s ease-out 0.4s backwards;
    }
  </style>
</head>
<body>
  <div class="container">
    <header class="header">
      <img src="assets/semtransp.png" alt="Brasão SEMTRANSP" class="logo">
      <h1>Sistema de Credenciais</h1>
      <p class="subtitle">
        Secretaria Municipal de Trânsito, Transporte, Mobilidade Urbana e Segurança Pública de Baixo Guandu
      </p>
    </header>

    <div class="intro">
      <p>Escolha uma opção para gerenciar as credenciais de estacionamento:</p>
    </div>

    <div class="cards">
      <div class="card">
        <div class="card-header">
          <h3>Emitir Nova Credencial</h3>
        </div>
        <p>Gerar PDF da credencial e registrar automaticamente no banco de dados do sistema.</p>
        <a class="btn" href="create.php">
          <span>Acessar</span>
          <span class="btn-arrow">→</span>
        </a>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Credenciais Emitidas</h3>
        </div>
        <p>Visualize todas as credenciais emitidas, faça download dos PDFs e gerencie o status de cada registro.</p>
        <a class="btn" href="list.php">
          <span>Acessar</span>
          <span class="btn-arrow">→</span>
        </a>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Assinaturas Pendentes</h3>
          <span class="badge">7</span>
        </div>
        <p>Credenciais aguardando assinatura digital do secretário para finalização e liberação.</p>
        <a class="btn" href="pending.php">
          <span>Acessar</span>
          <span class="btn-arrow">→</span>
        </a>
      </div>
    </div>

    <div class="footer">
      Sistema de Gestão de Credenciais | Prefeitura Municipal de Baixo Guandu - ES
    </div>
  </div>
</body>
</html>