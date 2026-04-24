/**
 * BulkGenius AI - Product Page Button Placement (Robust Version with MutationObserver)
 */
(function ($) {
    'use strict';

    $(function () {
        let currentFieldType = ''; // 'Resumo', 'Descrição' or 'SEO'
        let currentTargetId = '';  // ID do textarea ou input associado
        let currentSourceText = '';
        const fieldConfigs = {
            'Resumo': {
                idMatcher: function (id) {
                    return /(^|_)description_short(_|$)/.test(id);
                },
                requestType: 'summary'
            },
            'Descrição': {
                idMatcher: function (id) {
                    return /(^|_)description(_|$)/.test(id) &&
                        !/(^|_)description_short(_|$)/.test(id) &&
                        !/(^|_)meta_description(_|$)/.test(id);
                },
                requestType: 'description'
            },
            'Descrição Meta': {
                idMatcher: function (id) {
                    return /(^|_)meta_description(_|$)/.test(id);
                },
                requestType: 'meta_description'
            }
        };

        function showFeedback(message) {
            $('#bg-ai-feedback').text(message).show();
        }

        function clearFeedback() {
            $('#bg-ai-feedback').hide().text('');
        }

        function resetGenerateState() {
            $('#bg-ai-loading').hide();
            $('#bg-ai-result-text').show();
            $('#btn-bg-ai-generate').prop('disabled', false);
        }

        function getFieldConfig(type) {
            return fieldConfigs[type] || fieldConfigs['Descrição'];
        }

        function getMatchingFields(type, $scope) {
            const config = getFieldConfig(type);
            const $root = $scope && $scope.length ? $scope : $(document);

            return $root.find('textarea[id], input[id]').filter(function () {
                const id = this.id || '';
                return config.idMatcher(id);
            });
        }

        function getVisibleField(type) {
            const $candidates = getMatchingFields(type);

            const $visible = $candidates.filter(function () {
                return $(this).is(':visible');
            }).first();

            if ($visible.length) {
                return $visible;
            }

            return $candidates.first();
        }

        function getActiveTranslationField($scope, type) {
            const $root = $scope && $scope.length ? $scope : $(document);
            const $activePane = $root
                .find('.translationsFields .tab-pane.show.active, .translationsFields .tab-pane.active')
                .first();

            if ($activePane.length) {
                const $fieldInPane = getMatchingFields(type, $activePane).first();
                if ($fieldInPane.length) {
                    return $fieldInPane;
                }
            }

            return $();
        }

        function resolveTargetInput($btn, type) {
            let inputId = $btn.prev('label').attr('for');

            if (inputId) {
                return inputId;
            }

            const $container = $btn.closest('.form-group, .row, fieldset, .form-item, .mb-3, .product-tab, .tab-pane, section, .card');
            if ($container.length) {
                const $activeTranslationField = getActiveTranslationField($container, type);
                if ($activeTranslationField.length) {
                    return $activeTranslationField.attr('id');
                }

                const $scopedField = getMatchingFields(type, $container).first();

                if ($scopedField.length) {
                    return $scopedField.attr('id');
                }
            }

            const $globalActiveTranslationField = getActiveTranslationField($(document), type);
            if ($globalActiveTranslationField.length) {
                return $globalActiveTranslationField.attr('id');
            }

            const $globalField = getVisibleField(type);
            return $globalField.attr('id') || '';
        }

        function getEditorText(inputId, type) {
            if (typeof tinymce === 'undefined' || !inputId || (type !== 'Resumo' && type !== 'Descrição')) {
                return '';
            }

            let editor = tinymce.get(inputId);

            if (!editor) {
                const config = getFieldConfig(type);
                editor = tinymce.editors.find(function (instance) {
                    if (!instance || !instance.id) {
                        return false;
                    }

                    const element = instance.getElement && instance.getElement();
                    return config.idMatcher(instance.id) && (!element || $(element).is(':visible'));
                });
            }

            if (!editor) {
                return '';
            }

            return editor.getContent({ format: 'text' }) || '';
        }

        function getCurrentFieldText(inputId, type) {
            let currentText = getEditorText(inputId, type);

            if (!currentText && inputId) {
                currentText = $('#' + inputId).val() || '';
            }

            if (!currentText) {
                const $fallbackField = getVisibleField(type);
                currentText = $fallbackField.val() || '';
            }

            if (currentText && type !== 'Descrição') {
                currentText = currentText.replace(/<[^>]*>?/gm, '');
            }

            return $.trim(currentText);
        }

        function injectAiButtons() {
            const $btnTemplate = $('#btn-bg-ai-open');
            if (!$btnTemplate.length) return;

            const targets = [
                { containerId: 'product_description_description_short', fieldType: 'Resumo' },
                { containerId: 'product_description_description', fieldType: 'Descrição' },
                { containerId: 'product_seo_meta_description', fieldType: 'Descrição Meta' },
            ];

            targets.forEach(function (target) {
                const $container = $('#' + target.containerId);
                if (!$container.length) return;

                const $anchor = $container.prev('h2, h3, h4, label').last();
                if (!$anchor.length) return;

                if ($anchor.next('.bg-ai-btn-injected').length) return;

                const $newBtn = $btnTemplate.clone(true);
                $newBtn.removeAttr('id')
                       .addClass('bg-ai-btn-injected')
                       .attr('data-field-type', target.fieldType)
                       .attr('style', '');

                $anchor.after($newBtn);
            });
        }

        // Lógica de abertura do Modal e Carregamento de Dados
        $(document).on('click', '.bg-ai-btn-injected', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const type = $btn.attr('data-field-type');
            currentFieldType = type;

            const inputId = resolveTargetInput($btn, type);
            currentTargetId = inputId;

            // Título do Modal
            $('#bg-ai-modal-title').text('Otimizar ' + type);

            // Garantir que o modal está no body para evitar conflitos de z-index ou display
            const $modal = $('#bg-ai-modal');
            if (!$modal.parent().is('body')) {
                $modal.appendTo('body');
            }

            const currentText = getCurrentFieldText(inputId, type);
            currentSourceText = currentText;
            const preview = currentText.substring(0, 150);
            $('#bg-ai-current-text').text(preview + (preview.length >= 150 ? '...' : ''));
            
            // Reset do estado do modal
            clearFeedback();
            $('#bg-ai-result-text').empty();
            $('#btn-bg-ai-apply').prop('disabled', true);
            
            $('#bg-ai-modal').modal('show');
        });

        // Lógica de Geração por IA
        $('#btn-bg-ai-generate').on('click', function () {
            const $btn = $(this);

            // Apanhar o nome do produto — a div ativa não tem a classe d-none
            const productName = $('#product_header_name')
                .find('.js-locale-input:not(.d-none) input')
                .first().val() || '';

            // Extrair id_product da URL como fallback para o backend ir buscar à BD
            const urlParams = new URLSearchParams(window.location.search);
            const productId = urlParams.get('id_product') || '';

            if (typeof AI_IMPORTER === 'undefined' || !AI_IMPORTER.actionUrl || !AI_IMPORTER.token) {
                showFeedback('A configuração AJAX do módulo não está disponível nesta página. Recarregue a página e confirme que o módulo injecta AI_IMPORTER no AdminProducts.');
                return;
            }
            
            clearFeedback();
            $('#bg-ai-loading').show();
            $('#bg-ai-result-text').hide();
            $btn.prop('disabled', true);

            $.ajax({
                url: AI_IMPORTER.actionUrl,
                type: 'POST',
                dataType: 'json',
                timeout: 30000,
                data: {
                    action: 'regenerate_product',
                    token: AI_IMPORTER.token,
                    name: productName,
                    id_product: productId,
                    current_content: currentSourceText,
                    type: getFieldConfig(currentFieldType).requestType,
                    // Ler o ISO da língua ativa a partir do botão de língua do nome do produto
                    // O botão mostra "PT", "ES", etc. — convertemos para lowercase
                    lang_code: (
                        $('#product_header_name .js-locale-btn').text().trim().toLowerCase() ||
                        $('button.js-locale-btn').first().text().trim().toLowerCase() ||
                        'pt'
                    )
                },
                success: function (response) {
                    if (response.success) {
                        let result = '';
                        if (currentFieldType === 'Resumo') result = response.content.description_short;
                        else if (currentFieldType === 'Descrição') result = response.content.description;
                        else result = response.content.meta_description;

                        // Usar .html() para 'Resumo' e 'Descrição' (conteúdo HTML), .text() para os restantes
                        if (currentFieldType === 'Descrição' || currentFieldType === 'Resumo') {
                            $('#bg-ai-result-text').html(result).show();
                        } else {
                            $('#bg-ai-result-text').text(result).show();
                        }
                        $('#btn-bg-ai-apply').prop('disabled', false);
                    } else {
                        $('#bg-ai-result-text').text('');
                        showFeedback(response.message || 'Ocorreu um erro ao gerar a sugestão.');
                    }
                },
                error: function (xhr, textStatus) {
                    $('#bg-ai-result-text').text('');

                    if (textStatus === 'timeout') {
                        showFeedback('A geração demorou demasiado tempo e expirou.');
                        return;
                    }

                    const response = xhr && xhr.responseJSON;
                    if (response && response.message) {
                        showFeedback(response.message);
                        return;
                    }

                    showFeedback('Erro na ligação ao servidor.');
                },
                complete: function () {
                    resetGenerateState();
                }
            });
        });

        // Lógica para Aplicar a Sugestão
        $('#btn-bg-ai-apply').on('click', function () {
            const result = (currentFieldType === 'Descrição' || currentFieldType === 'Resumo')
                ? $('#bg-ai-result-text').html()
                : $('#bg-ai-result-text').text();

            // Sincronizar com TinyMCE
            if (typeof tinymce !== 'undefined' && (currentFieldType === 'Resumo' || currentFieldType === 'Descrição')) {
                const editor = tinymce.get(currentTargetId) || tinymce.editors.find(e => e.id && e.id.indexOf(currentTargetId) !== -1);
                if (editor) {
                    editor.setContent(result);
                }
            }
            
            // Sincronizar com o input/textarea nativo
            if (currentTargetId) {
                const $input = $('#' + currentTargetId);
                $input.val(result).trigger('change');
                
                // Disparar evento de input para que frameworks como Vue (PS8) detetem a mudança
                const el = document.getElementById(currentTargetId);
                if (el) {
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
            
            $('#bg-ai-modal').modal('hide');
        });

        // MutObserver e Inicialização
        const targetNode = document.getElementById('main-div') || document.body;
        const observer = new MutationObserver(function() {
            if (window.bgAiTimeout) clearTimeout(window.bgAiTimeout);
            window.bgAiTimeout = setTimeout(injectAiButtons, 100);
        });
        observer.observe(targetNode, { childList: true, subtree: true });
        injectAiButtons();
    });


})(jQuery);
