<?php
// detail.php — Rincian satu dokumen beserta semua stock_entries-nya
require_once __DIR__ . '/config/database.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) { header('Location: dashboard.php'); exit; }

$docStmt = $db->prepare('SELECT * FROM documents WHERE id = :id LIMIT 1');
$docStmt->execute([':id' => $id]);
$doc = $docStmt->fetch();

if (!$doc) { header('Location: dashboard.php'); exit; }

$entries = $db->prepare(
    'SELECT se.*, s.name AS supplier_name
     FROM stock_entries se
     JOIN suppliers s ON s.id = se.supplier_id
     WHERE se.document_id = :id
     ORDER BY se.id ASC'
);
$entries->execute([':id' => $id]);
$items = $entries->fetchAll();

function formatRp(float $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

$totalValue = array_sum(array_column($items, 'total_price'));
$statusMap  = ['uploaded'=>'Uploaded','extracted'=>'Extracted','saved'=>'Saved','error'=>'Error'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Detail Dokumen #<?= $id ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen font-sans text-sm text-gray-800">

<header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center gap-3 shadow-sm">
  <a href="index.php" class="text-brand font-bold text-xl tracking-tight">Doc<span class="text-gray-400 font-light">2</span>DB</a>
  <span class="text-gray-300">/</span>
  <a href="dashboard.php" class="text-gray-500 hover:text-brand text-xs">Riwayat</a>
  <span class="text-gray-300">/</span>
  <span class="text-gray-600 text-xs">#<?= $id ?></span>
</header>

<div class="max-w-4xl mx-auto px-4 py-8">

  <!-- Document info -->
  <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <div class="flex items-start justify-between">
      <div>
        <h1 class="font-semibold text-base text-gray-800 mb-1">
          <?= htmlspecialchars($doc['original_name']) ?>
        </h1>
        <p class="text-xs text-gray-400">
          ID: #<?= $doc['id'] ?> &middot;
          <?= htmlspecialchars($doc['file_type']) ?> &middot;
          <?= number_format($doc['file_size'] / 1024, 1) ?> KB &middot;
          Diunggah <?= date('d M Y, H:i', strtotime($doc['uploaded_at'])) ?>
        </p>
      </div>
      <span class="px-3 py-1 rounded text-xs font-medium
        <?= match($doc['status']) {
          'saved'     => 'bg-green-100 text-green-700',
          'extracted' => 'bg-yellow-100 text-yellow-700',
          'error'     => 'bg-red-100 text-red-700',
          default     => 'bg-blue-100 text-blue-700',
        } ?>">
        <?= $statusMap[$doc['status']] ?? $doc['status'] ?>
      </span>
    </div>

    <?php if ($doc['file_type'] !== 'application/pdf'): ?>
    <div class="mt-4">
      <?php $filePath = 'uploads/' . htmlspecialchars($doc['stored_name']); ?>
      <?php if (str_starts_with($doc['file_type'], 'image/')): ?>
      <img src="<?= $filePath ?>" class="max-h-72 rounded border border-gray-200" alt="Dokumen" />
      <?php elseif ($doc['file_type'] === 'text/plain'): ?>
      <pre class="bg-gray-50 border border-gray-200 rounded p-4 text-xs text-gray-600 overflow-auto max-h-48"><?=
        htmlspecialchars(file_get_contents(__DIR__ . '/uploads/' . $doc['stored_name']))
      ?></pre>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Stock entries -->
  <h2 class="text-sm font-semibold text-gray-700 mb-3">
    Daftar Barang Masuk
    <span class="ml-2 text-xs font-normal text-gray-400"><?= count($items) ?> item</span>
  </h2>

  <?php if (empty($items)): ?>
  <div class="bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">
    Tidak ada data stock entry untuk dokumen ini.
  </div>
  <?php else: ?>
  <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <div style="overflow-x:auto">
    <table class="w-full text-xs">
      <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase tracking-wide">
        <tr>
          <th class="px-4 py-2 text-left w-6">#</th>
          <th class="px-4 py-2 text-left">Nama Barang</th>
          <th class="px-4 py-2 text-right">Qty</th>
          <th class="px-4 py-2 text-right">Harga Beli</th>
          <th class="px-4 py-2 text-right">Total</th>
          <th class="px-4 py-2 text-left">Supplier</th>
          <th class="px-4 py-2 text-left">Catatan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $e): ?>
        <tr class="border-t border-gray-100">
          <td class="px-4 py-2 text-gray-400"><?= $i + 1 ?></td>
          <td class="px-4 py-2 font-medium"><?= htmlspecialchars($e['product_name']) ?></td>
          <td class="px-4 py-2 text-right"><?= number_format($e['quantity']) ?></td>
          <td class="px-4 py-2 text-right"><?= formatRp((float)$e['buy_price']) ?></td>
          <td class="px-4 py-2 text-right font-medium"><?= formatRp((float)$e['total_price']) ?></td>
          <td class="px-4 py-2"><?= htmlspecialchars($e['supplier_name']) ?></td>
          <td class="px-4 py-2 text-gray-500"><?= htmlspecialchars($e['note'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        <!-- Total row -->
        <tr class="border-t-2 border-gray-300 bg-gray-50 font-semibold">
          <td colspan="4" class="px-4 py-2 text-right text-gray-600">TOTAL NILAI</td>
          <td class="px-4 py-2 text-right text-gray-800"><?= formatRp($totalValue) ?></td>
          <td colspan="2"></td>
        </tr>
      </tbody>
    </table>
    </div>
  </div>
  <?php endif; ?>

  <div class="mt-6">
    <a href="dashboard.php" class="text-xs text-brand hover:underline">← Kembali ke Riwayat</a>
  </div>
</div>
</body>
</html>
