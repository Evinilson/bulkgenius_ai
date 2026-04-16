/**
 * AI Product Importer - Admin JS
 * Gere upload de Excel, preview e importação AJAX
 */
(function ($) {
  'use strict';

  $(function () {
    const wrapEl = document.querySelector('.ai-importer-wrap');
    const actionUrl =
      (window.AI_IMPORTER && window.AI_IMPORTER.actionUrl) ||
      (wrapEl && wrapEl.getAttribute('data-action-url')) ||
      '';
    let selectedFile = null;
    let importingRows = [];
    let isImporting = false;
    let stopImportFlag = false;
    let stats = { success: 0, error: 0 };

    // ── Configuração de Provedores ─────────────────────────
    const providerModels = {
      openai: [
        { id: 'gpt-4o-mini', name: 'gpt-4o-mini (Recomendado)' },
        { id: 'gpt-4o', name: 'gpt-4o (Alta Qualidade)' },
        { id: 'gpt-4-turbo', name: 'gpt-4-turbo' },
        { id: 'gpt-3.5-turbo', name: 'gpt-3.5-turbo' }
      ],
      gemini: [
        { id: 'gemini-1.5-flash', name: 'gemini-1.5-flash (Mais rápido/Grátis)' },
        { id: 'gemini-1.5-pro', name: 'gemini-1.5-pro (Mais inteligente)' }
      ],
      groq: [
        { id: 'groq/compound', name: 'Groq Compound' },
        { id: 'groq/compound-mini', name: 'Groq Compound Mini' },
        { id: 'llama-3.1-8b-instant', name: 'Llama 3.1 8B (Instantâneo)' },
        { id: 'llama-3.1-70b-versatile', name: 'Llama 3.1 70B (Versátil)' },
        { id: 'mixtral-8x7b-32768', name: 'Mixtral 8x7B' }
      ]
    };

    const aiProvider = document.getElementById('ai_provider');
    const aiModel = document.getElementById('ai_model');

    if (aiProvider && aiModel) {
      aiProvider.addEventListener('change', function () {
        const selected = this.value;

        // Mostrar/Ocultar campos de chaves
        $('.provider-fields').hide();
        $(`#fields-${selected}`).show();

        // Atualizar lista de modelos
        updateModelOptions(selected);
      });

      // Inicializar no carregamento
      updateModelOptions(aiProvider.value, aiModel.getAttribute('data-current'));
    }

    function updateModelOptions(provider, currentModel) {
      if (!aiModel || !providerModels[provider]) return;

      const models = providerModels[provider];
      aiModel.innerHTML = models.map(m =>
        `<option value="${m.id}" ${m.id === currentModel ? 'selected' : ''}>${m.name}</option>`
      ).join('');
    }

    // ── Drag & Drop ────────────────────────────────────────
    const uploadZone = document.getElementById('upload-zone');
    if (uploadZone) {
      uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('drag-over');
      });

      uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('drag-over');
      });

      uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file) handleFileSelected(file);
      });

      uploadZone.addEventListener('click', (e) => {
        // Se o clique veio do próprio input, não fazemos nada (evita loop)
        if (e.target.id === 'excel-file') return;
        document.getElementById('excel-file').click();
      });
    }

    const selectFileBtn = document.getElementById('btn-select-file');
    if (selectFileBtn) {
      selectFileBtn.addEventListener('click', (e) => {
        e.stopPropagation(); // Evita que o clique suba para o uploadZone
        document.getElementById('excel-file').click();
      });
    }

    const excelFileInput = document.getElementById('excel-file');
    if (excelFileInput) {
      excelFileInput.addEventListener('change', function () {
        if (this.files[0]) handleFileSelected(this.files[0]);
      });
    }

    // ── Ficheiro Selecionado ───────────────────────────────
    function handleFileSelected(file) {
      const ext = file.name.split('.').pop().toLowerCase();
      if (!['xlsx', 'xls', 'csv'].includes(ext)) {
        showAlert('danger', 'Formato inválido. Use .xlsx, .xls ou .csv');
        return;
      }
      selectedFile = file;
      requestPreview(file);
    }

    // ── Preview ────────────────────────────────────────────
    function requestPreview(file) {
      if (!actionUrl) {
        showAlert('danger', 'URL de ação não encontrada. Recarregue a página.');
        return;
      }
      const fd = new FormData();
      fd.append('action', 'preview');
      fd.append('excel_file', file);
      fd.append('ajax', '1');
      fd.append('token', window.AI_IMPORTER && window.AI_IMPORTER.token || '');

      showLoading('A ler o ficheiro...');

      $.ajax({
        url: actionUrl,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function (res) {
          hideLoading();
          if (res.success) {
            importingRows = res.rows;
            renderPreview(res.rows.slice(0, 5), res.total, file.name);
          } else {
            showAlert('danger', res.message || 'Erro ao ler ficheiro.');
          }
        },
        error: function (xhr) {
          hideLoading();
          let msg = 'Erro de comunicação com o servidor.';
          if (xhr.status === 413) msg = 'Ficheiro demasiado grande para o servidor.';
          showAlert('danger', msg);
        }
      });
    }

    function renderPreview(rows, total, filename) {
      if (!rows || rows.length === 0) {
        showAlert('danger', 'Nenhum dado encontrado no ficheiro.');
        return;
      }

      const headers = Object.keys(rows[0]);
      const thead = document.getElementById('preview-headers');
      const tbody = document.getElementById('preview-body');

      if (thead) {
        thead.innerHTML = headers.map(h => `<th>${capitalize(h.replace('_', ' '))}</th>`).join('');
      }
      if (tbody) {
        tbody.innerHTML = rows.map(row =>
          `<tr>${headers.map(h => `<td>${escHtml(row[h] || '')}</td>`).join('')}</tr>`
        ).join('');
      }

      const previewTitle = document.getElementById('preview-title');
      if (previewTitle) {
        previewTitle.innerHTML =
          `<i class="icon-eye"></i> Pré-visualização: <strong>${escHtml(filename)}</strong> — 
           <span class="text-primary">${total} produto(s) encontrado(s)</span>
           ${total > 5 ? `<small class="text-muted"> (mostrando os primeiros 5)</small>` : ''}`;
      }

      if (uploadZone) uploadZone.style.display = 'none';
      const previewSection = document.getElementById('preview-section');
      if (previewSection) previewSection.style.display = 'block';
      const resultsSection = document.getElementById('results-section');
      if (resultsSection) resultsSection.style.display = 'none';
    }

    // ── Importar ────────────────────────────────────────────
    const btnImport = document.getElementById('btn-import');
    if (btnImport) {
      btnImport.addEventListener('click', function () {
        if (!importingRows || importingRows.length === 0) return;
        
        startImportProcess();
      });
    }

    const btnCancelImport = document.getElementById('btn-cancel-import');
    if (btnCancelImport) {
      btnCancelImport.addEventListener('click', function () {
        if (confirm('Tens a certeza que queres cancelar a importação?')) {
          stopImportFlag = true;
          setProgressText('A cancelar... Por favor aguarde.');
          $(this).prop('disabled', true);
        }
      });
    }

    async function startImportProcess() {
      isImporting = true;
      stopImportFlag = false;
      stats = { success: 0, error: 0 };

      const previewSection = document.getElementById('preview-section');
      const progressSection = document.getElementById('progress-section');
      const resultsSection = document.getElementById('results-section');

      if (previewSection) previewSection.style.display = 'none';
      if (progressSection) progressSection.style.display = 'block';
      if (resultsSection) resultsSection.style.display = 'none';

      // Reset logs
      const logsBody = document.getElementById('logs-body');
      if (logsBody) logsBody.innerHTML = '';
      
      updateProgressBar(0, importingRows.length);
      
      // Beforeunload protection
      window.addEventListener('beforeunload', preventExiting);

      await runImportLoop();
    }

    async function runImportLoop() {
      const CONCURRENCY = 3; // Número de produtos a processar em simultâneo
      const total = importingRows.length;
      let nextIndex = 0;
      let doneCount = 0;

      async function worker() {
        while (true) {
          if (stopImportFlag) {
            addLog('warning', 'Interrompido', 'A importação foi cancelada pelo utilizador.');
            break;
          }

          const index = nextIndex++;
          if (index >= total) break;

          const product = importingRows[index];

          try {
            const result = await processSingleProduct(product);
            if (result.success) {
              stats.success++;
              addLog('success', product.name, 'Produto criado com sucesso! ID: ' + result.id, result.id);
            } else {
              stats.error++;
              addLog('danger', product.name, result.message || 'Erro desconhecido.');
            }
          } catch (e) {
            stats.error++;
            addLog('danger', product.name, 'Erro de comunicação: ' + e.message);
          }

          doneCount++;
          updateProgressBar(doneCount, total, product.name);
        }
      }

      // Lança N workers em paralelo
      const workers = [];
      for (let i = 0; i < Math.min(CONCURRENCY, total); i++) {
        workers.push(worker());
      }
      await Promise.all(workers);

      finishImport();
    }

    function processSingleProduct(product) {
      return new Promise((resolve, reject) => {
        const fd = new FormData();
        fd.append('action', 'import_single');
        fd.append('ajax', '1');
        fd.append('token', window.AI_IMPORTER && window.AI_IMPORTER.token || '');
        
        // Passamos os campos individualmente ou como array
        for(let key in product) {
          fd.append(`product[${key}]`, product[key]);
        }

        $.ajax({
          url: actionUrl,
          method: 'POST',
          data: fd,
          processData: false,
          contentType: false,
          success: resolve,
          error: (xhr) => reject(new Error('Status: ' + xhr.status))
        });
      });
    }

    function updateProgressBar(current, total, name = '') {
      const pct = total > 0 ? Math.round((current / total) * 100) : 0;
      const bar = document.getElementById('progress-bar');
      const text = document.getElementById('progress-text');
      const proc = document.getElementById('stat-processed');
      const tot = document.getElementById('stat-total');

      if (bar) {
        bar.style.width = pct + '%';
        bar.textContent = pct + '%';
      }
      if (text && name) {
        text.innerHTML = `A processar: <strong>${escHtml(name)}</strong>`;
      }
      if (proc) proc.textContent = current;
      if (tot) tot.textContent = total;
    }

    function addLog(type, name, message, productId = null) {
      const logsBody = document.getElementById('logs-body');
      if (!logsBody) return;

      const icon = type === 'success' ? 'icon-check' : (type === 'danger' ? 'icon-remove' : 'icon-warning');
      const row = document.createElement('tr');
      row.className = 'logs-row-new'; // Adiciona animação de entrada
      
      let actionHtml = '';
      if (productId) {
          actionHtml = `<a href="index.php?controller=AdminProducts&id_product=${productId}&updateproduct" 
                           target="_blank" class="btn btn-default btn-xs btn-view-product" style="margin-left:10px">
                           <i class="icon-external-link"></i> Ver produto
                        </a>`;
      }

      row.innerHTML = `
        <td><span class="log-status ${type}"><i class="${icon}"></i></span></td>
        <td><strong>${escHtml(name)}</strong></td>
        <td>
            <small>${escHtml(message)}</small>
            ${actionHtml}
        </td>
      `;
      
      logsBody.insertBefore(row, logsBody.firstChild);
    }

    function finishImport() {
      isImporting = false;
      window.removeEventListener('beforeunload', preventExiting);
      
      updateProgressBar(importingRows.length, importingRows.length);
      setProgressText('Concluído!');

      // Parar animação da barra e spinner
      $('#progress-bar').removeClass('active progress-bar-striped').addClass('progress-bar-success');
      $('.ai-spinner').addClass('hidden');
      
      const btnCancel = document.getElementById('btn-cancel-import');
      if (btnCancel) btnCancel.style.display = 'none';

      // Esconder a caixa de progresso após 2 segundos, mantendo apenas logs e resultados
      setTimeout(() => {
        $('.ai-progress-box').slideUp();
      }, 2000);

      // Mostrar resumo final
      setTimeout(() => {
        renderResults({
          success: true,
          created: stats.success,
          errors: stats.error,
          results: [], // Não precisamos mais deste array grande aqui
          error_details: [] 
        });
      }, 1000);
    }

    function preventExiting(e) {
      e.preventDefault();
      e.returnValue = '';
    }

    function renderResults(res) {
      const section = document.getElementById('results-section');
      if (!section) return;

      let errorsHtml = '';
      if (res.error_details && res.error_details.length > 0) {
        errorsHtml = `
          <div style="margin-top:16px">
            <h5>Erros (${res.errors}):</h5>
            <table class="table table-sm table-bordered">
              <thead><tr><th>Linha</th><th>Produto</th><th>Erro</th></tr></thead>
              <tbody>
                ${res.error_details.map(e =>
                  `<tr class="danger"><td>${e.row}</td><td>${escHtml(e.name)}</td><td>${escHtml(e.error)}</td></tr>`
                ).join('')}
              </tbody>
            </table>
          </div>`;
      }

      let createdHtml = '';
      if (res.results && res.results.length > 0) {
        createdHtml = `
          <div style="margin-top:16px">
            <h5>Produtos criados:</h5>
            <table class="table table-sm table-bordered">
              <thead><tr><th>#</th><th>Produto</th><th>ID</th><th></th></tr></thead>
              <tbody>
                ${res.results.map(r =>
                  `<tr class="success">
                    <td>${r.row}</td>
                    <td>${escHtml(r.name)}</td>
                    <td>${r.id}</td>
                    <td><a href="index.php?controller=AdminProducts&id_product=${r.id}&updateproduct" 
                           target="_blank" class="result-product-link">Ver produto →</a></td>
                  </tr>`
                ).join('')}
              </tbody>
            </table>
          </div>`;
      }

      section.innerHTML = `
        <div class="ai-results-box">
          <div class="result-stats">
            <div class="stat-badge success">
              <span class="stat-num">${res.created}</span>
              <span class="stat-label">Criados</span>
            </div>
            <div class="stat-badge danger">
              <span class="stat-num">${res.errors}</span>
              <span class="stat-label">Erros</span>
            </div>
          </div>
          ${createdHtml}
          ${errorsHtml}
          <button type="button" class="btn btn-default" onclick="location.reload()">
            <i class="icon-refresh"></i> Nova Importação
          </button>
        </div>`;

      section.style.display = 'block';
    }

    // ── Reset ───────────────────────────────────────────────
    const btnReset = document.getElementById('btn-reset');
    if (btnReset) {
      btnReset.addEventListener('click', resetUpload);
    }

    function resetUpload() {
      selectedFile = null;
      if (excelFileInput) excelFileInput.value = '';
      if (uploadZone) uploadZone.style.display = 'block';
      const previewSection = document.getElementById('preview-section');
      const progressSection = document.getElementById('progress-section');
      if (previewSection) previewSection.style.display = 'none';
      if (progressSection) progressSection.style.display = 'none';
    }

    // ── Helpers ─────────────────────────────────────────────
    function showLoading(text) {
      const section = document.getElementById('progress-section');
      if (section) section.style.display = 'block';
      setProgressText(text);
    }

    function hideLoading() {
      const section = document.getElementById('progress-section');
      if (section) section.style.display = 'none';
    }

    function setProgressText(text) {
      const el = document.getElementById('progress-text');
      if (el) el.textContent = text;
    }

    function showAlert(type, message) {
      const wrap = document.querySelector('.ai-importer-wrap');
      if (!wrap) return;
      const div = document.createElement('div');
      div.className = `alert alert-${type} alert-dismissible`;
      div.innerHTML = `<button type="button" class="close" data-dismiss="alert">&times;</button>${escHtml(message)}`;
      wrap.insertBefore(div, wrap.firstChild);
    }

    // ── Testar Ligação ──────────────────────────────────────
    $('.btn-test-connection').on('click', function (e) {
      e.preventDefault();
      const btn = $(this);
      const provider = btn.data('provider');
      const icon = btn.find('i');
      
      // Bloquear botão e mostrar spinner
      btn.prop('disabled', true);
      icon.attr('class', 'icon-refresh icon-spin');
      
      const fd = new FormData();
      fd.append('action', 'test_connection');
      // Passamos os dados atuais do form para testar sem precisar guardar primeiro
      fd.append('ai_provider', provider);
      fd.append('api_key', $('input[name="api_key"]').val());
      fd.append('gemini_key', $('input[name="gemini_key"]').val());
      fd.append('groq_key', $('input[name="groq_key"]').val());
      fd.append('ai_model', aiModel.value);
      fd.append('ajax', '1');
      fd.append('token', window.AI_IMPORTER && window.AI_IMPORTER.token || '');

      $.ajax({
        url: actionUrl,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function (res) {
          btn.prop('disabled', false);
          if (res.success) {
            icon.attr('class', 'icon-check text-success');
            btn.removeClass('btn-info').addClass('btn-success');
            setTimeout(() => {
                icon.attr('class', 'icon-refresh');
                btn.removeClass('btn-success').addClass('btn-info');
            }, 3000);
          } else {
            icon.attr('class', 'icon-remove text-danger');
            btn.removeClass('btn-info').addClass('btn-danger');
            showAlert('danger', 'Erro no teste: ' + res.message);
          }
        },
        error: function () {
          btn.prop('disabled', false);
          icon.attr('class', 'icon-warning text-warning');
          showAlert('danger', 'Erro de comunicação ao testar API.');
        }
      });
    });

    function capitalize(str) {
      return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function escHtml(str) {
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }
  });

})(jQuery);
