<?php
// dashboard.php — Riwayat Dokumen & Stok Masuk
// -----------------------------------------------------------
require_once __DIR__ . '/config/database.php';

$db = getDB();

// ── Pagination ──────────────────────────────────────────────
$perPage  = 10;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;

// ── Filter ──────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$filterSearch = trim($_GET['q'] ?? '');

$where  = [];
$params = [];

if (in_array($filterStatus, ['uploaded','extracted','saved','error'], true)) {
    $where[]            = 'status = :status';
    $params[':status']  = $filterStatus;
}
if ($filterSearch !== '') {
    $where[]           = 'original_name LIKE :q';
    $params[':q']      = '%' . $filterSearch . '%';
}

$whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ── Count total ─────────────────────────────────────────────
$totalRows = (int) $db->prepare("SELECT COUNT(*) FROM documents {$whereClause}")
                       ->execute($params) ? 1 : 0;

$countStmt = $db->prepare("SELECT COUNT(*) FROM documents {$whereClause}");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);

// ── Fetch documents ─────────────────────────────────────────
$docStmt = $db->prepare(
    "SELECT d.*,
            COUNT(se.id)       AS item_count,
            SUM(se.total_price) AS total_value
     FROM documents d
     LEFT JOIN stock_entries se ON se.document_id = d.id
     {$whereClause}
     GROUP BY d.id
     ORDER BY d.uploaded_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) {
    $docStmt->bindValue($k, $v);
}
$docStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$docStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$docStmt->execute();
$documents = $docStmt->fetchAll();

// ── Stats cards ──────────────────────────────────────────────
$stats = $db->query(
    "SELECT
       COUNT(*) AS total_docs,
       SUM(status = 'saved') AS saved_docs,
       (SELECT COUNT(*) FROM products) AS total_products,
       (SELECT COALESCE(SUM(total_price),0) FROM stock_entries) AS total_value
     FROM documents"
)->fetch();

// ── Status badge helper ──────────────────────────────────────
function statusBadge(string $status): string {
    return match($status) {
        'uploaded'   => '<span class="px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">Uploaded</span>',
        'extracted'  => '<span class="px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700">Extracted</span>',
        'saved'      => '<span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Saved</span>',
        'error'      => '<span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-700">Error</span>',
        default      => '<span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-500">' . htmlspecialchars($status) . '</span>',
    };
}

function formatRp(float $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — Doc2DB Minimarket</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: { extend: { colors: { brand: { DEFAULT: '#1d4ed8', dark: '#1e3a8a' } } } }
    }
  </script>
</head>
<body class="bg-gray-50 min-h-screen font-sans text-sm text-gray-800">

<!-- TOPBAR -->
<header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between shadow-sm sticky top-0 z-50">
  <div class="flex items-center gap-3">
    <a href="index.php" class="text-brand text-xl font-bold tracking-tight hover:opacity-80">Doc<span class="text-gray-400 font-light">2</span>DB</a>
    <span class="text-gray-300 text-lg">|</span>
    <span class="text-gray-500 text-xs uppercase tracking-widest">Riwayat Dokumen</span>
  </div>
  <a href="index.php"
     class="px-4 py-1.5 bg-brand text-white rounded text-xs font-medium hover:bg-brand-dark transition">
    + Unggah Dokumen Baru
  </a>
</header>

<div class="max-w-5xl mx-auto px-4 py-8">

  <!-- ── STATS CARDS ──────────────────────────────────────── -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <?php
    $cards = [
      ['label' => 'Total Dokumen',  'value' => number_format($stats['total_docs']),     'color' => 'text-brand'],
      ['label' => 'Tersimpan',      'value' => number_format($stats['saved_docs']),     'color' => 'text-green-600'],
      ['label' => 'Total Produk',   'value' => number_format($stats['total_products']), 'color' => 'text-purple-600'],
      ['label' => 'Nilai Stok Masuk','value' => formatRp((float)$stats['total_value']), 'color' => 'text-orange-600'],
    ];
    foreach ($cards as $c): ?>
    <div class="bg-white border border-gray-200 rounded-lg px-5 py-4">
      <p class="text-xs text-gray-400 uppercase tracking-wide"><?= $c['label'] ?></p>
      <p class="text-xl font-bold <?= $c['color'] ?> mt-1"><?= $c['value'] ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── FILTER BAR ───────────────────────────────────────── -->
  <form method="GET" class="flex flex-wrap gap-2 mb-4 items-end">
    <div>
      <label class="block text-xs text-gray-500 mb-1">Cari nama file</label>
      <input type="text" name="q" value="<?= htmlspecialchars($filterSearch) ?>"
             placeholder="Ketik nama file..."
             class="border border-gray-300 rounded px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-brand w-48" />
    </div>
    <div>
      <label class="block text-xs text-gray-500 mb-1">Status</label>
      <select name="status" class="border border-gray-300 rounded px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-brand">
        <option value="">Semua Status</option>
        <?php foreach (['uploaded','extracted','saved','error'] as $s): ?>
        <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>>
          <?= ucfirst($s) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="px-4 py-1.5 bg-brand text-white rounded text-xs hover:bg-brand-dark transition">Filter</button>
    <?php if ($filterStatus || $filterSearch): ?>
    <a href="dashboard.php" class="px-4 py-1.5 border border-gray-300 rounded text-xs hover:bg-gray-50">Reset</a>
    <?php endif; ?>
    <span class="ml-auto text-xs text-gray-400 self-center"><?= $totalRows ?> dokumen ditemukan</span>
  </form>

  <!-- ── DOCUMENTS TABLE ──────────────────────────────────── -->
  <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <div style="overflow-x:auto">
    <table class="w-full text-xs">
      <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase tracking-wide">
        <tr>
          <th class="px-4 py-2 text-left w-6">#</th>
          <th class="px-4 py-2 text-left">Nama File</th>
          <th class="px-4 py-2 text-center">Tipe</th>
          <th class="px-4 py-2 text-center">Status</th>
          <th class="px-4 py-2 text-right">Item</th>
          <th class="px-4 py-2 text-right">Nilai Total</th>
          <th class="px-4 py-2 text-left">Diunggah</th>
          <th class="px-4 py-2 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($documents)): ?>
        <tr>
          <td colspan="8" class="text-center py-12 text-gray-400">
            Belum ada dokumen.
            <a href="index.php" class="text-brand hover:underline ml-1">Unggah sekarang →</a>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($documents as $i => $doc): ?>
        <tr class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
          <td class="px-4 py-2 text-gray-400"><?= $offset + $i + 1 ?></td>
          <td class="px-4 py-2">
            <span class="font-medium text-gray-700 truncate block max-w-xs">
              <?= htmlspecialchars($doc['original_name']) ?>
            </span>
            <span class="text-gray-400"><?= number_format($doc['file_size'] / 1024, 1) ?> KB</span>
          </td>
          <td class="px-4 py-2 text-center">
            <?php
            $mt = $doc['file_type'];
            if (str_starts_with($mt, 'image/')) echo '🖼';
            elseif ($mt === 'application/pdf') echo '📄';
            else echo '📝';
            ?>
            <span class="text-gray-400 ml-1">
              <?= strtoupper(pathinfo($doc['stored_name'], PATHINFO_EXTENSION)) ?>
            </span>
          </td>
          <td class="px-4 py-2 text-center"><?= statusBadge($doc['status']) ?></td>
          <td class="px-4 py-2 text-right"><?= (int)$doc['item_count'] ?></td>
          <td class="px-4 py-2 text-right font-medium"><?= $doc['total_value'] ? formatRp((float)$doc['total_value']) : '—' ?></td>
          <td class="px-4 py-2 text-gray-500">
            <?= date('d/m/Y H:i', strtotime($doc['uploaded_at'])) ?>
          </td>
          <td class="px-4 py-2 text-center">
            <a href="detail.php?id=<?= $doc['id'] ?>"
               class="text-brand hover:underline text-xs">Detail</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- ── PAGINATION ───────────────────────────────────────── -->
  <?php if ($totalPages > 1): ?>
  <nav class="mt-4 flex justify-center gap-1">
    <?php for ($p = 1; $p <= $totalPages; $p++):
      $qStr = http_build_query(['page' => $p, 'status' => $filterStatus, 'q' => $filterSearch]);
    ?>
    <a href="?<?= $qStr ?>"
       class="px-3 py-1 rounded text-xs border <?= $p === $page
         ? 'bg-brand text-white border-brand'
         : 'border-gray-300 text-gray-600 hover:bg-gray-50' ?>">
      <?= $p ?>
    </a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>

  <!-- ── RECENT STOCK ENTRIES ─────────────────────────────── -->
  <h3 class="text-sm font-semibold text-gray-700 mt-10 mb-3">Barang Masuk Terkini (20 terakhir)</h3>
  <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <div style="overflow-x:auto">
    <table class="w-full text-xs">
      <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase tracking-wide">
        <tr>
          <th class="px-4 py-2 text-left">Nama Barang</th>
          <th class="px-4 py-2 text-right">Qty</th>
          <th class="px-4 py-2 text-right">Harga Beli</th>
          <th class="px-4 py-2 text-right">Total</th>
          <th class="px-4 py-2 text-left">Supplier</th>
          <th class="px-4 py-2 text-left">Dari Dokumen</th>
          <th class="px-4 py-2 text-left">Waktu</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $entries = $db->query(
            "SELECT se.*, s.name AS supplier_name, d.original_name
             FROM stock_entries se
             JOIN suppliers s ON s.id = se.supplier_id
             JOIN documents d ON d.id = se.document_id
             ORDER BY se.created_at DESC
             LIMIT 20"
        )->fetchAll();

        if (empty($entries)): ?>
        <tr>
          <td colspan="7" class="text-center py-8 text-gray-400">Belum ada data stok masuk.</td>
        </tr>
        <?php else: ?>
        <?php foreach ($entries as $e): ?>
        <tr class="border-t border-gray-100 hover:bg-gray-50">
          <td class="px-4 py-2 font-medium"><?= htmlspecialchars($e['product_name']) ?></td>
          <td class="px-4 py-2 text-right"><?= number_format($e['quantity']) ?></td>
          <td class="px-4 py-2 text-right"><?= formatRp((float)$e['buy_price']) ?></td>
          <td class="px-4 py-2 text-right font-medium"><?= formatRp((float)$e['total_price']) ?></td>
          <td class="px-4 py-2 text-gray-600"><?= htmlspecialchars($e['supplier_name']) ?></td>
          <td class="px-4 py-2 text-gray-500 max-w-xs truncate"><?= htmlspecialchars($e['original_name']) ?></td>
          <td class="px-4 py-2 text-gray-400"><?= date('d/m H:i', strtotime($e['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>

</div>
</body>
</html>
