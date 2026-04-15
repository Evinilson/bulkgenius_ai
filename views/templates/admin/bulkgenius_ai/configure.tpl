{*
 * BulkGenius AI - Interface Admin
 *}
<div class="ai-importer-wrap" data-action-url="{$action_url}">

  {* Mensagens *}
  {foreach from=$confirmations item=confirmation}
    <div class="alert alert-success">{$confirmation}</div>
  {/foreach}
  {foreach from=$errors item=error}
    <div class="alert alert-danger">{$error}</div>
  {/foreach}

  {* ── CONFIGURAÇÕES ─────────────────────────────────── *}
  <div class="panel panel-default">
    <div class="panel-heading">
      <i class="icon-cogs"></i> {l s='Configurações' mod='bulkgenius_ai'}
    </div>
    <div class="panel-body">
      <form method="POST" action="{$action_url}">
        <input type="hidden" name="submitConfig" value="1">

        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label>{l s='Provedor de IA' mod='bulkgenius_ai'}</label>
              <select name="ai_provider" id="ai_provider" class="form-control">
                <option value="openai" {if $ai_provider=='openai'}selected{/if}>OpenAI (Geral)</option>
                <option value="gemini" {if $ai_provider=='gemini'}selected{/if}>Google Gemini (Grátis/Rápido)</option>
                <option value="groq" {if $ai_provider=='groq'}selected{/if}>Groq (Ultra Rápido)</option>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label>{l s='Modelo IA' mod='bulkgenius_ai'}</label>
              <select name="ai_model" id="ai_model" class="form-control" data-current="{$ai_model}">
                {* Populado via JS *}
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label>{l s='Idioma dos produtos' mod='bulkgenius_ai'}</label>
              <select name="lang" class="form-control">
                <option value="pt" {if $lang=='pt'}selected{/if}>Português</option>
                <option value="en" {if $lang=='en'}selected{/if}>Inglês</option>
              </select>
            </div>
          </div>
        </div>

        {* Chaves API específicas *}
        <div id="provider-config">
          <div class="provider-fields" id="fields-openai" {if $ai_provider!='openai'}style="display:none"{/if}>
            <div class="form-group">
              <label>{l s='Chave API OpenAI' mod='bulkgenius_ai'} <span class="text-danger">*</span></label>
              <input type="password" name="api_key" class="form-control" value="" 
                     placeholder="{if $has_api_key}•••••••• (configurada){else}sk-...{/if}">
              <div class="help-block">{l s='Obtém a tua chave em platform.openai.com' mod='bulkgenius_ai'}</div>
              <button type="button" class="btn btn-info btn-test-connection" data-provider="openai" style="margin-top:10px">
                <i class="icon-refresh"></i> {l s='Testar Ligação OpenAI' mod='bulkgenius_ai'}
              </button>
            </div>
          </div>

          <div class="provider-fields" id="fields-gemini" {if $ai_provider!='gemini'}style="display:none"{/if}>
            <div class="form-group">
              <label>{l s='Chave API Gemini' mod='bulkgenius_ai'} <span class="text-danger">*</span></label>
              <input type="password" name="gemini_key" class="form-control" value="" 
                     placeholder="{if $has_gemini_key}•••••••• (configurada){else}AIza...{/if}">
              <div class="help-block">{l s='Obtém a tua chave em aistudio.google.com' mod='bulkgenius_ai'}</div>
              <button type="button" class="btn btn-info btn-test-connection" data-provider="gemini" style="margin-top:10px">
                <i class="icon-refresh"></i> {l s='Testar Ligação Gemini' mod='bulkgenius_ai'}
              </button>
            </div>
          </div>

          <div class="provider-fields" id="fields-groq" {if $ai_provider!='groq'}style="display:none"{/if}>
            <div class="form-group">
              <label>{l s='Chave API Groq' mod='bulkgenius_ai'} <span class="text-danger">*</span></label>
              <input type="password" name="groq_key" class="form-control" value="" 
                     placeholder="{if $has_groq_key}•••••••• (configurada){else}gsk_...{/if}">
              <div class="help-block">{l s='Obtém a tua chave em console.groq.com' mod='bulkgenius_ai'}</div>
              <button type="button" class="btn btn-info btn-test-connection" data-provider="groq" style="margin-top:10px">
                <i class="icon-refresh"></i> {l s='Testar Ligação Groq' mod='bulkgenius_ai'}
              </button>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>{l s='Categoria padrão' mod='bulkgenius_ai'}</label>
              <select name="id_category" class="form-control">
                {foreach from=$categories item=cat}
                  <option value="{$cat.id_category}" {if $id_category==$cat.id_category}selected{/if}>
                    {$cat.name|escape:'html':'UTF-8'}
                  </option>
                {/foreach}
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>{l s='Regra de IVA padrão' mod='bulkgenius_ai'}</label>
              <select name="id_tax_rule" class="form-control">
                {foreach from=$tax_rules item=tr}
                  <option value="{$tr.id_tax_rules_group}" {if $id_tax_rule==$tr.id_tax_rules_group}selected{/if}>
                    {$tr.name|escape:'html':'UTF-8'}
                  </option>
                {/foreach}
              </select>
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="icon-save"></i> {l s='Guardar Configurações' mod='bulkgenius_ai'}
        </button>
      </form>
    </div>
  </div>

  {* ── IMPORTAÇÃO ─────────────────────────────────────── *}
  <div class="panel panel-default">
    <div class="panel-heading">
      <i class="icon-upload"></i> {l s='Importar Produtos via Excel' mod='bulkgenius_ai'}
    </div>
    <div class="panel-body">

      {* Info colunas esperadas *}
      <div class="alert alert-info">
        <strong>{l s='Formato do Excel:' mod='bulkgenius_ai'}</strong>
        {l s='O ficheiro deve ter na 1ª linha os cabeçalhos:' mod='bulkgenius_ai'}
        <code>Nome</code>, <code>Referência</code>, <code>Preço</code>, <code>Descrição</code>
        <br>
        {l s='Também aceita em inglês: Name, Reference, Price, Description' mod='bulkgenius_ai'}
      </div>

      {* Upload *}
      <div id="upload-zone" class="ai-upload-zone">
        <i class="icon-file-excel-o upload-icon"></i>
        <p>{l s='Arrasta o ficheiro aqui ou clica para selecionar' mod='bulkgenius_ai'}</p>
        <input type="file" id="excel-file" accept=".xlsx,.xls,.csv" style="display:none">
        <button type="button" class="btn btn-default" id="btn-select-file">
          <i class="icon-folder-open"></i> {l s='Selecionar Ficheiro' mod='bulkgenius_ai'}
        </button>
      </div>

      {* Preview *}
      <div id="preview-section" style="display:none; margin-top:20px">
        <h4 id="preview-title"></h4>
        <div class="table-responsive">
          <table class="table table-bordered table-striped" id="preview-table">
            <thead><tr id="preview-headers"></tr></thead>
            <tbody id="preview-body"></tbody>
          </table>
        </div>
        <div class="ai-import-actions">
          <button type="button" id="btn-import" class="btn btn-success btn-lg">
            <i class="icon-magic"></i> {l s='Importar com IA' mod='bulkgenius_ai'}
          </button>
          <button type="button" id="btn-reset" class="btn btn-default">
            <i class="icon-refresh"></i> {l s='Cancelar' mod='bulkgenius_ai'}
          </button>
        </div>
      </div>

      {* Progresso *}
      <div id="progress-section" style="display:none; margin-top:20px">
        <div class="ai-progress-box">
          <div class="ai-spinner">
            <div class="spinner-dot"></div>
            <div class="spinner-dot"></div>
            <div class="spinner-dot"></div>
          </div>
          <p id="progress-text" class="progress-text">
            {l s='A processar produtos com IA...' mod='bulkgenius_ai'}
          </p>
          <div class="progress" style="height: 20px; margin-bottom: 20px;">
            <div id="progress-bar" class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%">
              0%
            </div>
          </div>
          <div class="progress-stats-mini">
             <span id="stat-processed">0</span> / <span id="stat-total">0</span> {l s='produtos processados' mod='bulkgenius_ai'}
          </div>
          <button type="button" id="btn-cancel-import" class="btn btn-danger-outline btn-sm" style="margin-top:15px">
            <i class="icon-remove"></i> {l s='Cancelar Importação' mod='bulkgenius_ai'}
          </button>
        </div>

        {* Lista de Logs em Tempo Real *}
        <div class="ai-realtime-logs" style="margin-top:30px">
          <h5 style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            {l s='Registo de Processamento' mod='bulkgenius_ai'}
            <small id="current-op-info" class="text-muted"></small>
          </h5>
          <div class="logs-container">
            <table class="table table-condensed" id="logs-table">
              <thead>
                <tr>
                  <th width="80">{l s='Estado' mod='bulkgenius_ai'}</th>
                  <th>{l s='Produto' mod='bulkgenius_ai'}</th>
                  <th>{l s='Resultado/Erro' mod='bulkgenius_ai'}</th>
                </tr>
              </thead>
              <tbody id="logs-body">
                {* Preenchido via JS *}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {* Resultados *}
      <div id="results-section" style="display:none; margin-top:20px"></div>
    </div>
  </div>

</div>

