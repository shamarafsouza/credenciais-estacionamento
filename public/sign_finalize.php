<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/protected.php';
bootstrap();

// Composer
require_once __DIR__ . '/../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

/**
 * Classe FPDI com suporte a rotação de imagens (assinatura)
 */
class PdfWithRotate extends \setasign\Fpdi\Fpdi {
    protected $angle = 0;

    public function Rotate($angle, $x = -1, $y = -1) {
        if ($x == -1) $x = $this->x;
        if ($y == -1) $y = $this->y;
        if ($this->angle != 0) $this->_out('Q');
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI/180;
            $c = cos($angle); $s = sin($angle);
            $cx = $x * $this->k; $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.5F %.5F cm 1 0 0 1 %.5F %.5F cm',
                $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy
            ));
        }
    }

    public function _endpage() {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

    public function RotatedImage($file, $x, $y, $w, $h = 0, $angle = 0, $type = 'PNG') {
        $this->Rotate($angle, $x, $y);
        $this->Image($file, $x, $y, $w, $h, $type);
        $this->Rotate(0);
    }
}

/**
 * Resolve um caminho salvo no DB para um caminho absoluto no disco.
 * Aceita relativos e absolutos; se vier um caminho “quebrado” (ex.: em Downloads),
 * reconstrói a partir de /data/... dentro do projeto.
 */
function toAbsPath(string $maybePath): string {
    if ($maybePath === '') return $maybePath;

    // Normaliza separadores para análise
    $raw = $maybePath;
    $p   = str_replace('\\', '/', $raw);

    // 1) Se já é absoluto E existe, usa direto
    $isAbs = preg_match('~^[a-zA-Z]:/|^/~', $p) === 1;
    if ($isAbs && is_file($raw)) {
        return $raw;
    }

    // 2) Se é absoluto mas NÃO existe, tenta reconstruir a partir de "data/"
    if ($isAbs && !is_file($raw)) {
        $pos = strpos($p, 'data/');
        if ($pos !== false) {
            // pega tudo após "data/"
            $tail = substr($p, $pos + 5); // ex.: generated/idoso_007-2025.pdf
            $rebuild = dataPath($tail);   // <raiz>/data/generated/idoso_007-2025.pdf
            if (is_file($rebuild)) return $rebuild;
        }
    }

    // 3) Tenta relativo à raiz do projeto
    $proj = rootPath() . DIRECTORY_SEPARATOR . ltrim($maybePath, DIRECTORY_SEPARATOR);
    if (is_file($proj)) return $proj;

    // 4) Tenta relativo à pasta /data
    $inData = dataPath(ltrim($maybePath, DIRECTORY_SEPARATOR));
    if (is_file($inData)) return $inData;

    // 5) Fallback: assume que deveria estar em /data/generated/...
    return dataPath('generated' . DIRECTORY_SEPARATOR . ltrim($maybePath, DIRECTORY_SEPARATOR));
}

// -----------------------------
// Entrada e registro
// -----------------------------
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if ($id <= 0) {
    http_response_code(400);
    echo "ID inválido.";
    exit;
}

// opcional: quem assinou e para onde voltar
$byReq       = isset($_POST['by']) ? (string)$_POST['by'] : (isset($_GET['by']) ? (string)$_GET['by'] : '');
$redirectTo  = isset($_POST['redirect_to']) ? (string)$_POST['redirect_to'] : (isset($_GET['redirect_to']) ? (string)$_GET['redirect_to'] : 'list.php');
$redirectTo  = in_array($redirectTo, ['pending.php','list.php','index.php'], true) ? $redirectTo : 'list.php';

$stmt = $pdo->prepare("
  SELECT id, tipo, numero_formatado, nome, data_emissao, data_validade,
         status, signed_by, signed_at, signature_image_path, pdf_path
    FROM records
   WHERE id = :id
  LIMIT 1
");
$stmt->execute([':id' => $id]);
$rec = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rec) {
    http_response_code(404);
    echo "Registro não encontrado.";
    exit;
}

$pdfRel = (string)($rec['pdf_path'] ?? '');
$pdfSrc = toAbsPath($pdfRel);

$sigRel  = (string)($rec['signature_image_path'] ?? '');
$sigPath = $sigRel
    ? rootPath() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $sigRel)
    : '';

if ($sigPath === '' || !is_file($sigPath)) {
    http_response_code(400);
    echo "Erro: Assinatura ainda não foi enviada/salva.";
    echo "<br>signature_image_path: " . htmlspecialchars($sigRel);
    echo '<br><a href="javascript:history.back()">Voltar</a>';
    exit;
}

if (!is_file($pdfSrc)) {
    http_response_code(400);
    echo "PDF de origem não encontrado.<pre>";
    echo "\n- pdf_path (DB): " . $pdfRel;
    echo "\n- Tentou abrir   : " . $pdfSrc;
    echo "\n- Existe?        : " . (is_file($pdfSrc) ? 'SIM' : 'NÃO');
    echo "\n- Raiz projeto   : " . rootPath();
    echo "\n- Pasta /data    : " . dataPath();
    echo "</pre><a href=\"javascript:history.back()\">Voltar</a>";
    exit;
}

// -----------------------------
// Saída
// -----------------------------
$outDir = dataPath('signed_pdfs');
if (!is_dir($outDir) && !mkdir($outDir, 0777, true) && !is_dir($outDir)) {
    throw new RuntimeException('Falha ao criar pasta de PDFs assinados.');
}

$baseName = pathinfo($pdfSrc, PATHINFO_FILENAME); // ex: idoso_007-2025
$outAbs   = $outDir . DIRECTORY_SEPARATOR . $baseName . '_signed.pdf';
$outRel   = 'data/signed_pdfs/' . basename($outAbs);

// -----------------------------
// Parâmetros de ajuste (mm / graus)
// -----------------------------
// Página onde deve assinar (1 = primeira página)
$TARGET_PAGE       = 1;

// ↑ Aumente/diminua para mudar o tamanho da assinatura
$SIG_W_MM          = 150.0; // largura da assinatura (mm)

// → mexe no X (direita/esquerda)
$OFFSET_RIGHT_MM   = 80.0;  // distância da borda direita (mm)

// ↑ mexe no Y (alto/baixo) – medido a partir do RODAPÉ
$OFFSET_BOTTOM_MM  = 34.0;  // distância da borda inferior (mm)

// Rotação em graus (ex.: 0, 10, -10, 90, -90)
$SIG_ANGLE_DEG     = 0;

// -----------------------------
// FPDI
// -----------------------------
$pdf = new PdfWithRotate();
$pdf->SetAutoPageBreak(false);
$pageCount = $pdf->setSourceFile($pdfSrc);

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $tplId = $pdf->importPage($pageNo);
    $size  = $pdf->getTemplateSize($tplId); // ['width','height','orientation']

    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
    $pdf->useTemplate($tplId);

    // Assina na página alvo
    if ($pageNo === $TARGET_PAGE) {
        // Proporção do PNG para calcular altura correta
        $wpx = $hpx = 0;
        if (function_exists('getimagesize')) {
            $inf = @getimagesize($sigPath);
            if (is_array($inf) && isset($inf[0], $inf[1]) && $inf[0] > 0) {
                $wpx = (float)$inf[0]; $hpx = (float)$inf[1];
            }
        }
        $ratio = ($wpx > 0 && $hpx > 0) ? ($hpx / $wpx) : 1.0;
        $sigW  = $SIG_W_MM;
        $sigH  = $sigW * $ratio;

        // Coordenadas (canto superior esquerdo da imagem)
        $x = $size['width']  - $OFFSET_RIGHT_MM - $sigW;  // move esquerda/direita
        $y = $size['height'] - $OFFSET_BOTTOM_MM - $sigH; // move alto/baixo (medido do rodapé)

        // Desenha assinatura (requer GD habilitado para PNG)
        $pdf->RotatedImage($sigPath, $x, $y, $sigW, $sigH, $SIG_ANGLE_DEG, 'PNG');
    }
}

// Salva em disco
$pdf->Output($outAbs, 'F');

// Atualiza DB: status, quem assinou (se informado) e caminho do PDF assinado
$bindings = [
    ':p'  => $outRel,
    ':id' => $id,
];
$sql = "
  UPDATE records
     SET status = 'ASSINADO',
         signed_at = datetime('now'),
         pdf_path = :p
";

$byToSave = strtoupper(trim($byReq));
if ($byToSave !== '') {
    $sql .= ", signed_by = :by";
    $bindings[':by'] = $byToSave;
}
$sql .= " WHERE id = :id";

$pdo->prepare($sql)->execute($bindings);

// Redireciona de volta sem baixar, com flag de ok para mostrar toast
header('Location: ' . $redirectTo . '?ok=1');
exit;
