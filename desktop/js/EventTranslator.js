'use strict';

var ET = {
    currentEqLogicId: null,
    sourceEqLogicId: null,

    _learning: {
        interval:        null,
        countdown:       0,
        $btn:            null,
        $body:           null,
        cmdId:           null,
        lastValue:       null,
        lastCollectDate: null
    },

    init: function () {
        $(document).off('.ET');
        ET._bindList();
        ET._bindDetail();
    },

    /* ---------- Liste ---------- */
    _bindList: function () {
        $(document).on('click.ET', '#div_eqLogicList [data-eqLogic_id]', function () {
            ET.loadEqLogic($(this).attr('data-eqLogic_id'));
        });

        $(document).on('input.ET', '#in_searchEqlogic', function () {
            var search = $(this).val().toLowerCase();
            $('#div_eqLogicList [data-eqLogic_id]').each(function () {
                $(this).toggle($(this).find('.name').text().toLowerCase().indexOf(search) !== -1);
            });
        });

        $(document).on('click.ET', '#bt_resetSearch', function () {
            $('#in_searchEqlogic').val('').trigger('input');
        });

        /* Appelé par plugin.template.js via data-action="returnToThumbnailDisplay" */
        $(document).on('click.ET', '.eqLogicAction[data-action="returnToThumbnailDisplay"]', function () {
            ET._stopLearning();
            ET.currentEqLogicId = null;
        });

        $(document).on('click.ET', '#bt_addEqLogic', function () {
            jeedom.eqLogic.getSelectModal({}, function (result) {
                if (!result || !result.id) { return; }
                $.ajax({
                    type: 'POST',
                    url: 'plugins/EventTranslator/core/ajax/EventTranslator.ajax.php',
                    data: { action: 'createFromSource', source_eqLogic_id: result.id },
                    dataType: 'json',
                    success: function (data) {
                        if (data.state !== 'ok') {
                            $.fn.showAlert({ message: data.result, level: 'danger' });
                            return;
                        }
                        var eq = data.result;
                        $('#span_noEqLogic').remove();
                        ET._appendCard(eq);
                        ET.loadEqLogic(eq.id);
                    },
                    error: function () {
                        $.fn.showAlert({ message: '{{Erreur lors de la création.}}', level: 'danger' });
                    }
                });
            });
        });
    },

    /* ---------- Panneau détail ---------- */
    _bindDetail: function () {
        $(document).on('click.ET', '#bt_saveEqLogic', function () { ET.saveAll(); });

        $(document).on('click.ET', '#bt_removeEqLogic', function () {
            bootbox.confirm('{{Êtes-vous sûr de vouloir supprimer cet équipement ?}}', function (ok) {
                if (!ok) { return; }
                $.ajax({
                    type: 'POST',
                    url: 'plugins/EventTranslator/core/ajax/EventTranslator.ajax.php',
                    data: { action: 'remove', eqLogic_id: ET.currentEqLogicId },
                    dataType: 'json',
                    success: function (data) {
                        if (data.state !== 'ok') {
                            $.fn.showAlert({ message: data.result, level: 'danger' });
                            return;
                        }
                        $('.et_card[data-eqLogic_id="' + ET.currentEqLogicId + '"]').remove();
                        ET.currentEqLogicId = null;
                        $('.eqLogic').hide();
                        $('.eqLogicThumbnailDisplay').show();
                    },
                    error: function () {
                        $.fn.showAlert({ message: '{{Erreur lors de la suppression.}}', level: 'danger' });
                    }
                });
            });
        });

        $(document).on('click.ET', '#bt_addCmd', function () { ET._openCmdSelector(); });

        $(document).on('click.ET', '.bt_removeCmd', function () {
            var $panel = $(this).closest('.et_cmd');
            bootbox.confirm('{{Supprimer cette commande ?}}', function (ok) {
                if (ok) { $panel.remove(); }
            });
        });

        $(document).on('click.ET', '.bt_addMapping', function () {
            ET._addMappingRow($(this).closest('.panel-body').find('.et_mapping_body'), {});
        });

        $(document).on('click.ET', '.bt_learnMapping', function () {
            var $btn = $(this);
            var $panel = $btn.closest('.et_cmd');
            var sourceCmdId = $panel.attr('data-source_cmd_id');
            if (!sourceCmdId) {
                $.fn.showAlert({ message: '{{Aucune commande source définie.}}', level: 'warning' });
                return;
            }
            if (ET._learning.interval !== null) {
                ET._stopLearning();
                return;
            }
            ET._startLearning($btn, sourceCmdId, $panel.find('.et_mapping_body'));
        });

        $(document).on('click.ET', '.bt_removeMapping', function () {
            $(this).closest('.et_mapping_row').remove();
        });

        $(document).on('change.ET', '.et_mapping_type', function () {
            ET._switchTargetType($(this));
        });

        $(document).on('click.ET', '.bt_selectActionCmd', function () {
            var $row = $(this).closest('.et_mapping_row');
            jeedom.cmd.getSelectModal({ cmd: { type: 'action' } }, function (result) {
                if (result && result.human) {
                    $row.find('.et_mapping_cmd_human').val(result.human);
                    $row.find('.et_mapping_cmd_id').val(result.cmd.id);
                }
            });
        });

        $(document).on('click.ET', '.bt_selectScenario', function () {
            var $row = $(this).closest('.et_mapping_row');
            jeedom.scenario.getSelectModal({}, function (result) {
                if (result && result.human) {
                    $row.find('.et_mapping_scenario_human').val(result.human);
                    $row.find('.et_mapping_scenario_id').val(result.id);
                }
            });
        });
    },

    /* ---------- Chargement équipement ---------- */
    loadEqLogic: function (id) {
        $.ajax({
            type: 'POST',
            url: 'core/ajax/eqLogic.ajax.php',
            data: { action: 'get', type: 'EventTranslator', id: id },
            dataType: 'json',
            success: function (data) {
                if (data.state !== 'ok') {
                    $.fn.showAlert({ message: data.result, level: 'danger' });
                    return;
                }
                var eq = data.result;
                ET.currentEqLogicId = eq.id;
                ET.sourceEqLogicId  = eq.configuration ? eq.configuration.source_eqLogic_id : '';

                $('#in_eqLogicId').val(eq.id);
                $('#in_eqLogicName').val(eq.name || '');
                $('#sel_objectId').val(eq.object_id || '');
                $('#cb_isEnable').prop('checked', parseInt(eq.isEnable) === 1);
                $('#cb_isVisible').prop('checked', parseInt(eq.isVisible) === 1);

                var cfg = eq.configuration || {};
                $('#in_sourceEqLogicId').val(cfg.source_eqLogic_id || '');
                $('#in_sourceEqLogicName').val(cfg.source_eqLogic_human || cfg.source_eqLogic_id || '');

                $('#div_cmdList').empty();
                if (Array.isArray(eq.cmd)) {
                    eq.cmd.forEach(function (cmd) {
                        if (cmd.type === 'info' && cmd.configuration && cmd.configuration.source_cmd_id) {
                            ET._renderCmd(cmd);
                        }
                    });
                }

                $('#div_eqLogicList [data-eqLogic_id]').removeClass('active');
                $('#div_eqLogicList [data-eqLogic_id="' + id + '"]').addClass('active');

                /* Basculer vers la vue détail (standard Jeedom) */
                $('.eqLogicThumbnailDisplay').hide();
                $('.eqLogic').show();
                /* Réinitialiser sur le premier onglet */
                $('.eqLogic .nav-tabs li').removeClass('active');
                $('.eqLogic .nav-tabs li:nth-child(2)').addClass('active');
                $('.eqLogic .tab-pane').removeClass('active');
                $('#tab_general').addClass('active');
            },
            error: function () {
                $.fn.showAlert({ message: '{{Erreur lors du chargement.}}', level: 'danger' });
            }
        });
    },

    /* ---------- Affichage d'un panneau commande ---------- */
    _renderCmd: function (cmd) {
        var $tmpl = $('#tmpl_cmd').clone().removeAttr('id').show();
        var $panel = $tmpl.find('.et_cmd');
        var srcCmdId = cmd.configuration ? cmd.configuration.source_cmd_id : '';
        var mappings = (cmd.configuration && cmd.configuration.mappings) ? cmd.configuration.mappings : [];

        $panel.attr('data-cmd_id', cmd.id || '');
        $panel.attr('data-source_cmd_id', srcCmdId);
        $tmpl.find('.et_cmd_name').val(cmd.name || '');
        $tmpl.find('.et_cmd_subtype').val(cmd.subType || 'string');

        var humanStored = cmd.configuration ? cmd.configuration.source_cmd_human : '';
        if (humanStored) {
            $tmpl.find('.et_cmd_source_human').text(humanStored);
        } else if (srcCmdId) {
            var $humanSpan = $tmpl.find('.et_cmd_source_human');
            jeedom.cmd.getHumanCmdName({
                id: srcCmdId,
                success: function (name) { $humanSpan.text(name); }
            });
        }

        var $tbody = $tmpl.find('.et_mapping_body');
        mappings.forEach(function (mapping) { ET._addMappingRow($tbody, mapping); });

        $('#div_cmdList').append($tmpl);
    },

    /* ---------- Sélecteur de commande source ---------- */
    _openCmdSelector: function () {
        if (!ET.sourceEqLogicId) {
            $.fn.showAlert({ message: '{{Aucun équipement source défini.}}', level: 'warning' });
            return;
        }
        $.ajax({
            type: 'POST',
            url: 'plugins/EventTranslator/core/ajax/EventTranslator.ajax.php',
            data: { action: 'sourceCmds', eqLogic_id: ET.sourceEqLogicId },
            dataType: 'json',
            success: function (data) {
                if (data.state !== 'ok') { return; }
                var cmds = data.result || [];
                if (cmds.length === 0) {
                    $.fn.showAlert({ message: '{{Aucune commande info sur l\'équipement source.}}', level: 'warning' });
                    return;
                }
                var options = cmds.map(function (c) {
                    return '<option value="' + c.id + '">' + $('<div>').text(c.name).html() + '</option>';
                }).join('');
                bootbox.dialog({
                    title: '{{Sélectionner une commande source}}',
                    message: '<select class="form-control" id="sel_srcCmd">' + options + '</select>',
                    buttons: {
                        cancel: { label: '{{Annuler}}', className: 'btn-default' },
                        ok: {
                            label: '{{Valider}}',
                            className: 'btn-success',
                            callback: function () {
                                var cmdId = $('#sel_srcCmd').val();
                                var cmd = cmds.find(function (c) { return String(c.id) === String(cmdId); });
                                if (!cmd) { return; }
                                var already = false;
                                $('#div_cmdList .et_cmd').each(function () {
                                    if ($(this).attr('data-source_cmd_id') == cmdId) { already = true; return false; }
                                });
                                if (already) {
                                    $.fn.showAlert({ message: '{{Cette commande est déjà ajoutée.}}', level: 'warning' });
                                    return;
                                }
                                var sourceName = $('#in_sourceEqLogicName').val();
                                ET._renderCmd({
                                    id: '',
                                    name: cmd.name,
                                    subType: 'string',
                                    configuration: {
                                        source_cmd_id: cmdId,
                                        source_cmd_human: '[' + sourceName + '][' + cmd.name + ']',
                                        mappings: []
                                    }
                                });
                            }
                        }
                    }
                });
            },
            error: function () {
                $.fn.showAlert({ message: '{{Erreur lors du chargement des commandes.}}', level: 'danger' });
            }
        });
    },

    /* ---------- Mode apprentissage ---------- */
    _startLearning: function ($btn, cmdId, $body) {
        var L = ET._learning;
        $.ajax({
            type: 'POST',
            url: 'plugins/EventTranslator/core/ajax/EventTranslator.ajax.php',
            data: { action: 'getCmdValue', cmd_id: cmdId },
            dataType: 'json',
            success: function (data) {
                if (data.state !== 'ok') {
                    $.fn.showAlert({ message: '{{Impossible de lire la commande source.}}', level: 'danger' });
                    return;
                }
                L.$btn            = $btn;
                L.$body           = $body;
                L.cmdId           = cmdId;
                L.lastValue       = data.result.value;
                L.lastCollectDate = data.result.collectDate;
                L.countdown       = 30;
                $btn.removeClass('btn-success').addClass('btn-danger')
                    .html('<i class="fas fa-stop"></i> {{Terminer}} (' + L.countdown + 's)');
                L.interval = setInterval(function () { ET._pollLearning(); }, 1000);
            },
            error: function () {
                $.fn.showAlert({ message: '{{Erreur lors de la lecture de la commande.}}', level: 'danger' });
            }
        });
    },

    _pollLearning: function () {
        var L = ET._learning;
        L.countdown--;
        L.$btn.html('<i class="fas fa-stop"></i> {{Terminer}} (' + L.countdown + 's)');
        if (L.countdown <= 0) {
            ET._stopLearning();
            return;
        }
        $.ajax({
            type: 'POST',
            url: 'plugins/EventTranslator/core/ajax/EventTranslator.ajax.php',
            data: { action: 'getCmdValue', cmd_id: L.cmdId },
            dataType: 'json',
            success: function (data) {
                if (data.state !== 'ok') { return; }
                var newVal         = data.result.value;
                var newCollectDate = data.result.collectDate;
                var changed = (newVal !== '' && newVal !== L.lastValue) ||
                              (newCollectDate !== L.lastCollectDate && newVal !== '');
                if (changed) {
                    L.lastValue       = newVal;
                    L.lastCollectDate = newCollectDate;
                    L.countdown       = 30;
                    var alreadyKnown = false;
                    L.$body.find('.et_mapping_source').each(function () {
                        if ($(this).val() === newVal) { alreadyKnown = true; return false; }
                    });
                    if (!alreadyKnown) {
                        ET._addMappingRow(L.$body, { source: newVal });
                    }
                }
            }
        });
    },

    _stopLearning: function () {
        var L = ET._learning;
        if (L.interval !== null) {
            clearInterval(L.interval);
            L.interval = null;
        }
        if (L.$btn) {
            L.$btn.removeClass('btn-danger').addClass('btn-success')
                .html('<i class="fas fa-headphones"></i> {{Apprendre}}');
            L.$btn = null;
        }
        L.$body = L.cmdId = L.lastValue = L.lastCollectDate = null;
        L.countdown = 0;
    },

    /* ---------- Ligne de mapping ---------- */
    _addMappingRow: function ($tbody, mapping) {
        var $row = $('#tmpl_mapping').find('tr').clone();
        var type = mapping.type || 'value';

        $row.find('.et_mapping_source').val(mapping.source || '');
        $row.find('.et_mapping_type').val(type);

        if (type === 'value') {
            $row.find('.et_mapping_value').val(mapping.value || '');
        } else if (type === 'cmd') {
            $row.find('.et_mapping_cmd_human').val(mapping.cmd_human || '');
            $row.find('.et_mapping_cmd_id').val(mapping.cmd_id || '');
            $row.find('.et_mapping_cmd_options').val(mapping.cmd_options || '');
        } else if (type === 'scenario') {
            $row.find('.et_mapping_scenario_human').val(mapping.scenario_human || '');
            $row.find('.et_mapping_scenario_id').val(mapping.scenario_id || '');
        }

        ET._switchTargetType($row.find('.et_mapping_type'));
        $tbody.append($row);
    },

    _switchTargetType: function ($select) {
        var type = $select.val();
        var $row = $select.closest('.et_mapping_row');
        $row.find('.et_target_value').toggle(type === 'value');
        $row.find('.et_target_cmd').toggle(type === 'cmd');
        $row.find('.et_target_scenario').toggle(type === 'scenario');
    },

    /* ---------- Collecte des données ---------- */
    _collectData: function () {
        var eqLogicData = {
            name: $('#in_eqLogicName').val(),
            object_id: $('#sel_objectId').val(),
            isEnable:  $('#cb_isEnable').is(':checked') ? 1 : 0,
            isVisible: $('#cb_isVisible').is(':checked') ? 1 : 0
        };

        var cmdsData = [];
        $('#div_cmdList .et_cmd').each(function () {
            var $cmd = $(this);
            var cmdData = {
                id: $cmd.attr('data-cmd_id') || '',
                name: $cmd.find('.et_cmd_name').val(),
                source_cmd_id: $cmd.attr('data-source_cmd_id'),
                subtype: $cmd.find('.et_cmd_subtype').val() || 'string',
                mappings: []
            };
            $cmd.find('.et_mapping_row').each(function () {
                var $row = $(this);
                var type = $row.find('.et_mapping_type').val();
                var source = $row.find('.et_mapping_source').val();
                if (source === '') { return; }
                if (type === 'cmd' && !$row.find('.et_mapping_cmd_id').val()) {
                    $row.remove();
                    return;
                }
                if (type === 'scenario' && !$row.find('.et_mapping_scenario_id').val()) {
                    $row.remove();
                    return;
                }
                var mapping = { source: source, type: type };
                if (type === 'value') {
                    mapping.value = $row.find('.et_mapping_value').val();
                } else if (type === 'cmd') {
                    mapping.cmd_id      = $row.find('.et_mapping_cmd_id').val();
                    mapping.cmd_human   = $row.find('.et_mapping_cmd_human').val();
                    mapping.cmd_options = $row.find('.et_mapping_cmd_options').val();
                } else if (type === 'scenario') {
                    mapping.scenario_id    = $row.find('.et_mapping_scenario_id').val();
                    mapping.scenario_human = $row.find('.et_mapping_scenario_human').val();
                }
                cmdData.mappings.push(mapping);
            });
            if (cmdData.source_cmd_id) { cmdsData.push(cmdData); }
        });

        return { eqLogicData: eqLogicData, cmdsData: cmdsData };
    },

    /* ---------- Sauvegarde ---------- */
    saveAll: function () {
        var collected = ET._collectData();
        $.ajax({
            type: 'POST',
            url: 'plugins/EventTranslator/core/ajax/EventTranslator.ajax.php',
            data: {
                action:      'saveAll',
                eqLogic_id:  ET.currentEqLogicId,
                eqLogic:     JSON.stringify(collected.eqLogicData),
                cmds:        JSON.stringify(collected.cmdsData)
            },
            dataType: 'json',
            success: function (data) {
                if (data.state !== 'ok') {
                    $.fn.showAlert({ message: data.result, level: 'danger' });
                    return;
                }
                $.fn.showAlert({ message: '{{Sauvegardé avec succès.}}', level: 'success' });
                var name = collected.eqLogicData.name;
                if (name) {
                    $('.et_card[data-eqLogic_id="' + ET.currentEqLogicId + '"] .name').text(name);
                }
                ET.loadEqLogic(ET.currentEqLogicId);
            },
            error: function () {
                $.fn.showAlert({ message: '{{Erreur lors de la sauvegarde.}}', level: 'danger' });
            }
        });
    },

    /* ---------- Ajouter une carte dans la liste ---------- */
    _appendCard: function (eq) {
        var cfg = eq.configuration || {};
        var iconSrc = cfg.source_icon_url || 'core/img/no-image-plugin.png';
        var html = '<div class="et_card cursor" data-eqLogic_id="' + eq.id + '">'
            + '<img class="et_icon" src="' + iconSrc + '" onerror="this.onerror=null;this.src=\'core/img/no-image-plugin.png\';" />'
            + '<span class="name">' + (eq.name || '') + '</span>'
            + '</div>';
        $('#div_eqLogicList').append(html);
    }
};

$(function () { ET.init(); });
