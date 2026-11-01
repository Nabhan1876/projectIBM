<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Doc2DB — Minimarket</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: { DEFAULT: '#1d4ed8', light: '#3b82f6', dark: '#1e3a8a' }
          }
        }
      }
    }
  </script>
  <style>
    /* custom minimal overrides */
    [x-cloak] { display: none; }
    .step-active  { @apply bg-brand text-white; }
    .step-done    { @apply bg-green-600 text-white; }
    .step-idle    { @apply bg-gray-200 text-gray-500; }
    #drop-zone.dragover { border-color: #3b82f6; background-color: #eff6ff; }
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    input[type=number]::-webkit-inner-spin-button { opacity: 1; }
    /* table sticky header */
    .tbl-wrap { max-height: 420px; overflow-y: auto; }
    .tbl-wrap thead th { position: sticky; top: 0; z-index: 1; background: #f8fafc; }
  </style>
</head>

<body class="bg-gray-50 min-h-screen font-sans text-gray-800 text-sm">

<!-- ══════════════════════════════════════════════════════════
     TOPBAR
══════════════════════════════════════════════════════════ -->
<header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between shadow-sm sticky top-0 z-50">
  <div class="flex items-center gap-3">
    <span class="text-brand text-xl font-bold tracking-tight">Doc<span class="text-gray-400 font-light">2</span>DB</span>
    <span class="text-gray-300 text-lg">|</span>
    <span class="text-gray-500 text-xs uppercase tracking-widest">Minimarket AI Input</span>
  </div>
  <a href="dashboard.php"
     class="text-xs text-brand hover:underline flex items-center gap-1">
    &#9783; Riwayat Dokumen
  </a>
</header>

<!-- ══════════════════════════════════════════════════════════
     STEP INDICATOR
══════════════════════════════════════════════════════════ -->
<div class="max-w-3xl mx-auto mt-6 px-4">
  <ol id="step-bar" class="flex items-center gap-0 select-none">
    <!-- steps injected by JS -->
  </ol>
</div>

<!-- ══════════════════════════════════════════════════════════
     MAIN CONTENT
══════════════════════════════════════════════════════════ -->
<main class="max-w-3xl mx-auto px-4 pb-16">

  <!-- ─── STEP 1: Upload ─────────────────────────────────── -->
  <section id="step-upload" class="step-section mt-6">
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h2 class="font-semibold text-base mb-1">Langkah 1 — Unggah Dokumen</h2>
      <p class="text-gray-500 text-xs mb-5">Nota/faktur supplier, surat jalan, atau daftar barang masuk (JPG, PNG, PDF, TXT). Maks 10 MB.</p>

      <!-- Drop zone -->
      <div id="drop-zone"
           class="border-2 border-dashed border-gray-300 rounded-lg p-10 text-center cursor-pointer transition-colors hover:border-brand hover:bg-blue-50"
           onclick="document.getElementById('file-input').click()"
           ondragover="event.preventDefault(); this.classList.add('dragover')"
           ondragleave="this.classList.remove('dragover')"
           ondrop="handleDrop(event)">
        <svg class="mx-auto mb-3 text-gray-300 w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12V4m0 0L8 8m4-4l4 4"/>
        </svg>
        <p class="text-gray-500">Seret & lepas file di sini, atau <span class="text-brand font-medium">klik untuk memilih</span></p>
        <p class="text-gray-400 text-xs mt-1">JPG · PNG · PDF · TXT</p>
      </div>
      <input id="file-input" type="file" class="hidden"
             accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt"
             onchange="handleFileSelect(this.files[0])" />

      <!-- Preview area -->
      <div id="preview-area" class="hidden mt-5 border border-gray-200 rounded-lg overflow-hidden">
        <div class="bg-gray-50 px-4 py-2 flex items-center justify-between border-b border-gray-200">
          <span id="preview-filename" class="text-xs font-medium text-gray-600 truncate max-w-xs"></span>
          <span id="preview-filesize" class="text-xs text-gray-400 ml-2 shrink-0"></span>
        </div>
        <div id="preview-body" class="flex items-center justify-center min-h-40 p-4 bg-white">
          <!-- image / text / pdf placeholder -->
        </div>
      </div>

      <!-- Upload button -->
      <div class="mt-5 flex items-center gap-3">
        <button id="btn-upload"
                class="hidden px-5 py-2 bg-brand text-white rounded font-medium text-sm hover:bg-brand-dark transition disabled:opacity-50"
                onclick="doUpload()">
          Unggah &amp; Lanjutkan →
        </button>
        <span id="upload-status" class="text-xs text-gray-400"></span>
      </div>
    </div>
  </section>

  <!-- ─── STEP 2: Extracting ─────────────────────────────── -->
  <section id="step-extract" class="step-section hidden mt-6">
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h2 class="font-semibold text-base mb-1">Langkah 2 — Ekstraksi AI</h2>
      <p class="text-gray-500 text-xs mb-5">Sistem membaca dan menganalisis dokumen untuk mengekstrak data barang, harga, dan supplier.</p>

      <!-- Raw text preview -->
      <div class="mb-4">
        <label class="block text-xs font-medium text-gray-500 mb-1">Pratinjau Teks Dokumen (500 karakter pertama)</label>
        <pre id="raw-preview" class="bg-gray-50 border border-gray-200 rounded p-3 text-xs text-gray-600 whitespace-pre-wrap overflow-auto max-h-32"></pre>
      </div>

      <!-- Extracting loader -->
      <div id="extract-loader" class="flex items-center gap-3 py-4">
        <svg class="spin w-5 h-5 text-brand" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
        <span class="text-sm text-gray-600">Menganalisis dokumen...</span>
      </div>

      <button id="btn-extract" class="hidden px-5 py-2 bg-brand text-white rounded font-medium text-sm hover:bg-brand-dark transition"
              onclick="doExtract()">
        Mulai Ekstraksi →
      </button>
    </div>
  </section>

  <!-- ─── STEP 3: Validation ─────────────────────────────── -->
  <section id="step-validate" class="step-section hidden mt-6">
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h2 class="font-semibold text-base mb-1">Langkah 3 — Validasi &amp; Koreksi</h2>
      <p class="text-gray-500 text-xs mb-4">Periksa dan perbaiki data yang diekstrak sebelum disimpan ke database.</p>

      <!-- Supplier info -->
      <div class="grid grid-cols-2 gap-3 mb-5">
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Nama Supplier</label>
          <input id="v-supplier-name" type="text"
                 class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand"
                 placeholder="Nama supplier" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Telepon Supplier</label>
          <input id="v-supplier-phone" type="text"
                 class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand"
                 placeholder="Nomor telepon (opsional)" />
        </div>
      </div>

      <!-- Items table (editable) -->
      <div class="tbl-wrap border border-gray-200 rounded-lg">
        <table class="w-full text-xs">
          <thead>
            <tr class="text-gray-500 uppercase text-left text-xs">
              <th class="px-3 py-2 w-6">#</th>
              <th class="px-3 py-2">Nama Barang</th>
              <th class="px-3 py-2 w-20 text-right">Qty</th>
              <th class="px-3 py-2 w-28 text-right">Harga Beli (Rp)</th>
              <th class="px-3 py-2 w-28 text-right">Total (Rp)</th>
              <th class="px-3 py-2 w-24">Catatan</th>
              <th class="px-3 py-2 w-8"></th>
            </tr>
          </thead>
          <tbody id="items-tbody">
            <!-- rows injected by JS -->
          </tbody>
        </table>
      </div>

      <!-- Add row + total -->
      <div class="mt-3 flex items-center justify-between">
        <button onclick="addItemRow()"
                class="text-xs text-brand hover:underline flex items-center gap-1">
          <span class="text-lg leading-none">+</span> Tambah Baris
        </button>
        <div class="text-right">
          <span class="text-xs text-gray-500">Total Nilai :</span>
          <span id="grand-total" class="text-sm font-semibold text-gray-800 ml-1">Rp 0</span>
        </div>
      </div>

      <!-- Action -->
      <div class="mt-6 flex items-center gap-3">
        <button id="btn-save"
                class="px-6 py-2 bg-green-600 text-white rounded font-medium text-sm hover:bg-green-700 transition disabled:opacity-50"
                onclick="doSave()">
          &#10003; Simpan ke Database
        </button>
        <span id="save-status" class="text-xs text-gray-400"></span>
      </div>
    </div>
  </section>

  <!-- ─── STEP 4: Success ────────────────────────────────── -->
  <section id="step-success" class="step-section hidden mt-6">
    <div class="bg-white border border-gray-200 rounded-lg p-10 text-center">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-green-100 mb-4">
        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
      <h2 class="text-lg font-semibold text-gray-800 mb-1">Data Berhasil Disimpan!</h2>
      <p id="success-message" class="text-gray-500 text-sm mb-6"></p>
      <div class="flex justify-center gap-3">
        <button onclick="resetFlow()"
                class="px-5 py-2 border border-gray-300 rounded text-sm hover:bg-gray-50">
          &#8635; Proses Dokumen Baru
        </button>
        <a href="dashboard.php"
           class="px-5 py-2 bg-brand text-white rounded text-sm hover:bg-brand-dark">
          Lihat Riwayat →
        </a>
      </div>
    </div>
  </section>

</main>

<!-- ══════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════ -->
<script>
// ─── State ────────────────────────────────────────────────
const state = {
  file        : null,
  documentId  : null,
  supplier    : { name: '', phone: '' },
  items       : [],
  currentStep : 1,
};

// ─── Step definitions ─────────────────────────────────────
const STEPS = [
  { id: 1, label: 'Unggah'    },
  { id: 2, label: 'Ekstraksi' },
  { id: 3, label: 'Validasi'  },
  { id: 4, label: 'Selesai'   },
];

const SECTION_IDS = {
  1: 'step-upload',
  2: 'step-extract',
  3: 'step-validate',
  4: 'step-success',
};

// ─── Init ─────────────────────────────────────────────────
(function init() {
  renderStepBar();
  setStep(1);
})();

function renderStepBar() {
  const bar = document.getElementById('step-bar');
  bar.innerHTML = STEPS.map((s, i) => {
    const isLast = i === STEPS.length - 1;
    return `
      <li class="flex items-center flex-1 ${isLast ? '' : 'flex-1'}">
        <div id="step-dot-${s.id}"
             class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold shrink-0 step-idle transition-colors">
          ${s.id}
        </div>
        <span id="step-label-${s.id}" class="ml-2 text-xs text-gray-500 hidden sm:inline whitespace-nowrap">${s.label}</span>
        ${!isLast ? '<div class="flex-1 h-px bg-gray-200 mx-2"></div>' : ''}
      </li>`;
  }).join('');
}

function setStep(n) {
  state.currentStep = n;

  // Show/hide sections
  Object.entries(SECTION_IDS).forEach(([stepNum, sectionId]) => {
    document.getElementById(sectionId).classList.toggle('hidden', parseInt(stepNum) !== n);
  });

  // Update step dots
  STEPS.forEach(s => {
    const dot   = document.getElementById(`step-dot-${s.id}`);
    const label = document.getElementById(`step-label-${s.id}`);
    dot.className = dot.className.replace(/step-(active|done|idle)/g, '');
    if (s.id < n)      { dot.classList.add('step-done'); dot.textContent = '✓'; }
    else if (s.id === n) { dot.classList.add('step-active'); dot.textContent = s.id; }
    else                { dot.classList.add('step-idle');  dot.textContent = s.id; }
    label.className = label.className.replace(/text-\w+-\d+/g, '');
    label.classList.add(s.id <= n ? 'text-gray-800' : 'text-gray-400');
    label.classList.add('ml-2','text-xs','hidden','sm:inline','whitespace-nowrap');
  });
}

// ─── Step 1: Upload ───────────────────────────────────────
function handleDrop(e) {
  e.preventDefault();
  document.getElementById('drop-zone').classList.remove('dragover');
  const file = e.dataTransfer.files[0];
  if (file) handleFileSelect(file);
}

function handleFileSelect(file) {
  if (!file) return;
  state.file = file;

  // Show preview
  const previewArea = document.getElementById('preview-area');
  const previewBody = document.getElementById('preview-body');
  document.getElementById('preview-filename').textContent = file.name;
  document.getElementById('preview-filesize').textContent = formatBytes(file.size);
  previewArea.classList.remove('hidden');

  const mime = file.type;

  if (mime.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = e => {
      previewBody.innerHTML =
        `<img src="${e.target.result}" class="max-h-64 max-w-full object-contain rounded" alt="Preview" />`;
    };
    reader.readAsDataURL(file);
  } else if (mime === 'text/plain') {
    const reader = new FileReader();
    reader.onload = e => {
      previewBody.innerHTML =
        `<pre class="text-xs text-gray-600 whitespace-pre-wrap overflow-auto max-h-48 w-full text-left">${escHtml(e.target.result.slice(0, 1000))}</pre>`;
    };
    reader.readAsText(file);
  } else if (mime === 'application/pdf') {
    previewBody.innerHTML =
      `<div class="text-center text-gray-400">
         <svg class="mx-auto w-10 h-10 mb-2" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24">
           <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9l-5-5H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
         </svg>
         <p class="text-xs font-medium">${escHtml(file.name)}</p>
         <p class="text-xs text-gray-400 mt-1">PDF — ${formatBytes(file.size)}</p>
       </div>`;
  }

  document.getElementById('btn-upload').classList.remove('hidden');
  document.getElementById('upload-status').textContent = '';
}

async function doUpload() {
  const btn = document.getElementById('btn-upload');
  const statusEl = document.getElementById('upload-status');

  btn.disabled = true;
  btn.textContent = 'Mengunggah...';
  statusEl.textContent = '';

  const formData = new FormData();
  formData.append('document', state.file);

  try {
    const res  = await fetch('api/upload.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (!data.success) {
      throw new Error(data.message || 'Upload gagal.');
    }

    state.documentId = data.document_id;
    setStep(2);
    showExtractStep(data);

  } catch (err) {
    statusEl.textContent = '⚠ ' + err.message;
    statusEl.className = 'text-xs text-red-500';
    btn.disabled   = false;
    btn.textContent = 'Unggah & Lanjutkan →';
  }
}

// ─── Step 2: Extract ──────────────────────────────────────
function showExtractStep(uploadData) {
  document.getElementById('raw-preview').textContent = '(Memproses...)';
  document.getElementById('extract-loader').classList.remove('hidden');
  document.getElementById('btn-extract').classList.add('hidden');
  // Auto-trigger extraction after short UX delay
  setTimeout(doExtract, 800);
}

async function doExtract() {
  document.getElementById('extract-loader').classList.remove('hidden');
  document.getElementById('btn-extract').classList.add('hidden');

  try {
    const res  = await fetch('api/extract.php', {
      method : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body   : JSON.stringify({ document_id: state.documentId }),
    });
    const data = await res.json();

    if (!data.success) throw new Error(data.message);

    document.getElementById('raw-preview').textContent = data.raw_preview || '(tidak ada teks)';
    state.supplier = data.supplier || { name: '', phone: '' };
    state.items    = data.items    || [];

    setStep(3);
    renderValidationForm();

  } catch (err) {
    document.getElementById('extract-loader').classList.add('hidden');
    document.getElementById('btn-extract').classList.remove('hidden');
    document.getElementById('btn-extract').textContent = 'Coba Lagi';
    alert('Ekstraksi gagal: ' + err.message);
  }
}

// ─── Step 3: Validate ─────────────────────────────────────
function renderValidationForm() {
  document.getElementById('v-supplier-name').value  = state.supplier.name  || '';
  document.getElementById('v-supplier-phone').value = state.supplier.phone || '';

  const tbody = document.getElementById('items-tbody');
  tbody.innerHTML = '';

  if (state.items.length === 0) {
    addItemRow(); // satu baris kosong jika tidak ada hasil ekstraksi
  } else {
    state.items.forEach(item => addItemRow(item));
  }

  recalcTotal();
}

function addItemRow(item = {}) {
  const tbody = document.getElementById('items-tbody');
  const rowCount = tbody.querySelectorAll('tr').length + 1;
  const tr = document.createElement('tr');
  tr.className = 'border-t border-gray-100 hover:bg-gray-50';

  const confidence = item.confidence === 'high'
    ? '<span class="inline-block w-2 h-2 rounded-full bg-green-400 mr-1 align-middle" title="Keyakinan Tinggi"></span>'
    : '<span class="inline-block w-2 h-2 rounded-full bg-yellow-400 mr-1 align-middle" title="Perlu Verifikasi"></span>';

  tr.innerHTML = `
    <td class="px-3 py-1.5 text-gray-400">${rowCount}</td>
    <td class="px-3 py-1.5">
      ${item.product_name ? confidence : ''}
      <input type="text" value="${escHtml(item.product_name || '')}"
             placeholder="Nama barang"
             class="w-full border-0 border-b border-gray-200 focus:outline-none focus:border-brand text-xs py-0.5 bg-transparent"
             onchange="recalcTotal()" />
    </td>
    <td class="px-3 py-1.5 text-right">
      <input type="number" min="1" value="${item.quantity || 1}"
             class="w-16 border border-gray-200 rounded px-1 py-0.5 text-xs text-right focus:outline-none focus:ring-1 focus:ring-brand"
             oninput="recalcRow(this); recalcTotal()" />
    </td>
    <td class="px-3 py-1.5 text-right">
      <input type="number" min="0" step="100" value="${item.buy_price || 0}"
             class="w-24 border border-gray-200 rounded px-1 py-0.5 text-xs text-right focus:outline-none focus:ring-1 focus:ring-brand"
             oninput="recalcRow(this); recalcTotal()" />
    </td>
    <td class="px-3 py-1.5 text-right row-total text-gray-700 font-medium">
      ${formatRupiah((item.quantity || 1) * (item.buy_price || 0))}
    </td>
    <td class="px-3 py-1.5">
      <input type="text" value="${escHtml(item.note || '')}"
             placeholder="—"
             class="w-full border-0 border-b border-gray-200 focus:outline-none focus:border-brand text-xs py-0.5 bg-transparent" />
    </td>
    <td class="px-3 py-1.5 text-center">
      <button onclick="removeRow(this)" class="text-gray-300 hover:text-red-400 text-base leading-none" title="Hapus baris">×</button>
    </td>
  `;
  tbody.appendChild(tr);
}

function removeRow(btn) {
  const tbody = document.getElementById('items-tbody');
  if (tbody.querySelectorAll('tr').length <= 1) return; // minimal 1 baris
  btn.closest('tr').remove();
  renumberRows();
  recalcTotal();
}

function renumberRows() {
  document.querySelectorAll('#items-tbody tr').forEach((tr, i) => {
    tr.cells[0].textContent = i + 1;
  });
}

function recalcRow(inputEl) {
  const tr     = inputEl.closest('tr');
  const inputs = tr.querySelectorAll('input[type=number]');
  const qty    = parseFloat(inputs[0].value) || 0;
  const price  = parseFloat(inputs[1].value) || 0;
  tr.querySelector('.row-total').textContent = formatRupiah(qty * price);
}

function recalcTotal() {
  let total = 0;
  document.querySelectorAll('#items-tbody tr').forEach(tr => {
    const inputs = tr.querySelectorAll('input[type=number]');
    if (inputs.length < 2) return;
    total += (parseFloat(inputs[0].value) || 0) * (parseFloat(inputs[1].value) || 0);
  });
  document.getElementById('grand-total').textContent = formatRupiah(total);
}

// ─── Step 4: Save ─────────────────────────────────────────
async function doSave() {
  const btn      = document.getElementById('btn-save');
  const statusEl = document.getElementById('save-status');

  // Collect data from form
  const supplier = {
    name : document.getElementById('v-supplier-name').value.trim(),
    phone: document.getElementById('v-supplier-phone').value.trim(),
  };

  const items = [];
  document.querySelectorAll('#items-tbody tr').forEach(tr => {
    const textInputs   = tr.querySelectorAll('input[type=text]');
    const numberInputs = tr.querySelectorAll('input[type=number]');
    const name  = textInputs[0]?.value.trim();
    const qty   = parseFloat(numberInputs[0]?.value) || 0;
    const price = parseFloat(numberInputs[1]?.value) || 0;
    const note  = textInputs[1]?.value.trim() || '';
    if (name) items.push({ product_name: name, quantity: qty, buy_price: price, note });
  });

  if (!supplier.name) {
    statusEl.textContent = '⚠ Nama supplier wajib diisi.';
    statusEl.className = 'text-xs text-red-500';
    return;
  }
  if (items.length === 0) {
    statusEl.textContent = '⚠ Minimal satu item harus diisi.';
    statusEl.className = 'text-xs text-red-500';
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Menyimpan...';
  statusEl.textContent = '';

  try {
    const res  = await fetch('api/save.php', {
      method : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body   : JSON.stringify({ document_id: state.documentId, supplier, items }),
    });
    const data = await res.json();

    if (!data.success) throw new Error(data.message);

    document.getElementById('success-message').textContent = data.message;
    setStep(4);

  } catch (err) {
    statusEl.textContent = '⚠ ' + err.message;
    statusEl.className = 'text-xs text-red-500';
    btn.disabled   = false;
    btn.textContent = '✓ Simpan ke Database';
  }
}

// ─── Reset ────────────────────────────────────────────────
function resetFlow() {
  state.file       = null;
  state.documentId = null;
  state.supplier   = { name: '', phone: '' };
  state.items      = [];

  document.getElementById('file-input').value = '';
  document.getElementById('preview-area').classList.add('hidden');
  document.getElementById('btn-upload').classList.add('hidden');
  document.getElementById('btn-upload').disabled = false;
  document.getElementById('btn-upload').textContent = 'Unggah & Lanjutkan →';
  document.getElementById('upload-status').textContent = '';

  setStep(1);
}

// ─── Utils ────────────────────────────────────────────────
function formatBytes(b) {
  if (b < 1024) return b + ' B';
  if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
  return (b / 1048576).toFixed(1) + ' MB';
}

function formatRupiah(n) {
  return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}
</script>

</body>
</html>
