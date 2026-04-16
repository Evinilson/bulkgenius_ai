<div id="bulkgenius-ai-button-wrapper" style="display:none !important;">
    <button type="button" id="btn-bg-ai-open" class="btn btn-primary btn-sm bg-ai-btn-injected" style="display:none !important;">
        <i class="icon-magic"></i> {l s='Otimizar com IA' mod='bulkgenius_ai'}
    </button>
</div>

<!-- Modal de Optimização por IA -->
<div class="modal fade" id="bg-ai-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="icon-magic"></i> <span id="bg-ai-modal-title">{l s='Otimizar Conteúdo' mod='bulkgenius_ai'}</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="bg-ai-feedback" class="alert alert-danger bg-ai-feedback" style="display:none;"></div>
                <div class="bg-ai-layout">
                    <div class="bg-ai-section">
                        <div class="card bg-light border-0 bg-ai-current-card">
                            <div class="card-body">
                                <h6 class="card-title text-muted small">{l s='Conteúdo Atual (ou Base)' mod='bulkgenius_ai'}</h6>
                                <div id="bg-ai-current-text" class="bg-ai-preview-box bg-ai-current-box"></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-ai-section bg-ai-divider">
                        <div class="bg-ai-divider-line"></div>
                        <span class="bg-ai-divider-badge">
                            <i class="icon-arrow-down text-primary animate-pulse"></i>
                        </span>
                    </div>

                    <div class="bg-ai-section">
                        <div class="card bg-ai-result-card border-primary">
                            <div class="card-body">
                                <h6 class="card-title text-primary small">{l s='Sugestão da IA' mod='bulkgenius_ai'}</h6>
                                <div id="bg-ai-loading" class="text-center py-4" style="display:none;">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2 small text-muted">{l s='A pensar...' mod='bulkgenius_ai'}</p>
                                </div>
                                <div id="bg-ai-result-text" class="bg-ai-preview-box bg-ai-result-box result-active" contenteditable="true"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{l s='Cancelar' mod='bulkgenius_ai'}</button>
                <button type="button" id="btn-bg-ai-generate" class="btn btn-primary">
                    <i class="icon-refresh"></i> {l s='Gerar Sugestão' mod='bulkgenius_ai'}
                </button>
                <button type="button" id="btn-bg-ai-apply" class="btn btn-success" disabled>
                    <i class="icon-check"></i> {l s='Aplicar Alterações' mod='bulkgenius_ai'}
                </button>
            </div>
        </div>
    </div>
</div>
