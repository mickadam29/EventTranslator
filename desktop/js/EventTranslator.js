'use strict';

/* ============================================================
   EventTranslator — JS principal
   ============================================================ */

var ET = {
    currentEqLogicId: null,
    sourceEqLogicId: null,

    init: function () {
        ET._bindList();
        ET._bindDetail();
    },

    /* ---------- Liste gauche ---------- */
    _bindList: function () {
        $(document).on('click', '.eqLogicDisplayCard', function () {
            ET.loadEqLogic($(this).attr('data-eqLogic_id'));
        });

        $(document).on('click', '#bt_addEqLogic', function () {
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
        $(document).on('click', '#bt_saveEqLogic', function () { ET.saveAll(); });

        $(document).on('click', '#bt_removeEqLogic', function () {
            bootbox.confirm('{{Êtes-vous sûr de vouloir supprimer cet équipement ?}}', function (ok) {
                if (!ok) { return; }
                jeedom.eqLogic.remove({
                    id: ET.currentEqLogicId,
                    type: 'EventTranslator',
                    success: function () {
                        $('.eqLogicDisplayCard[data-eqLogic_id="' + ET.currentEqLogicId + '"]').remove();
                        $('#div_rightThumbnailList').hide();
                        ET.currentEqLogicId = null;
                        ET.sourceEqLogicId = null;
                    },
                    error: function (data) {
                        $.fn.showAlert({ message: data.message, level: 'danger' });
                    }
                });
            });
        });

        $(document).on('click', '#bt_addCmd', function () { ET._openCmdSelector(); });

        $(document).on('click', '.bt_removeCmd', function () {
            var $panel = $(this).closest('.et_cmd');
            bootbox.confirm('{{Supprimer cette commande ?}}', function (ok) {
                if (ok) { $panel.remove(); }
            });
        });

        $(document).on('click', '.bt_addMapping', function () {
            ET._addMappingRow($(this).closest('.panel-body').find('.et_mapping_body'), {});
        });

        $(document).on('click', '.bt_removeMapping', function () {
            $(this).closest('.et_mapping_row').remove();
        });

        $(document).on('change', '.et_mapping_type', function () {
            ET._switchTargetType($(this));
        });

        $(document).on('click', '.bt_selectActionCmd', function () {
            var $row = $(this).closest('.et_mapping_row');
            jeedom.cmd.getSelectModal({ cmd: { type: 'action' } }, function (result) {
                if (result && result.human) {
                    $row.find('.et_mapping_cmd_human').val(result.human);
                    $row.find('.et_mapping_cmd_id').val(result.cmd.id);
                }
            });
        });

        $(document).on('click', '.bt_selectScenario', function () {
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
                $('#in_virtualEqLogicId').val(cfg.virtual_eqLogic_id || '');
                $('#in_sourceEqLogicName').val(cfg.source_eqLogic_human || cfg.source_eqLogic_id || '');
                $('#in_virtualEqLogicName').val(cfg.virtual_eqLogic_human || cfg.virtual_eqLogic_id || '');

                // Charger les commandes depuis la réponse
                $('#div_cmdList').empty();
                if (Array.isArray(eq.cmd)) {
                    eq.cmd.forEach(function (cmd) {
                        // Filtrer : uniquement les commandes EventTranslatorCmd (type info avec source_cmd_id)
                        if (cmd.type === 'info' && cmd.configuration && cmd.configuration.source_cmd_id) {
                            ET._renderCmd(cmd);
                        }
                    });
                }

                $('.eqLogicDisplayCard').removeClass('active');
                $('.eqLogicDisplayCard[data-eqLogic_id="' + id + '"]').addClass('active');
                $('#div_rightThumbnailList').show();
            },
            error: function () {
                $.fn.showAlert({ message: '{{Erreur lors du chargement.}}', level: 'danger' });
            }
        });
    },

    /* ---------- Affichage d'un panneau commande ---------- */
    _renderCmd: function (cmd) {
        var $tmpl = $('#tmpl_cmd').clone().removeAttr('id').show();
        var srcCmdId = cmd.configuration ? cmd.configuration.source_cmd_id : '';
        var mappings = (cmd.configuration && cmd.configuration.mappings) ? cmd.configuration.mappings : [];

        $tmpl.attr('data-cmd_id', cmd.id || '');
        $tmpl.attr('data-source_cmd_id', srcCmdId);
        $tmpl.find('.et_cmd_name').val(cmd.name || '');

        // Affichage nom source (stocké ou récupéré)
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

        // Lignes de mapping
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
        jeedom.cmd.getSelectModal(
            { cmd: { type: 'info' }, eqLogic: { id: ET.sourceEqLogicId } },
            function (result) {
                if (!result || !result.human) { return; }
                var alreadyAdded = false;
                $('#div_cmdList .et_cmd').each(function () {
                    if ($(this).attr('data-source_cmd_id') == result.cmd.id) {
                        alreadyAdded = true;
                        return false;
                    }
                });
                if (alreadyAdded) {
                    $.fn.showAlert({ message: '{{Cette commande est déjà ajoutée.}}', level: 'warning' });
                    return;
                }
                var fakeCmd = {
                    id: '',
                    name: result.human.split(' > ').pop().replace(/[\[\]]/g, ''),
                    configuration: {
                        source_cmd_id: result.cmd.id,
                        source_cmd_human: result.human,
                        mappings: []
                    }
                };
                ET._renderCmd(fakeCmd);
            }
        );
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
                mappings: []
            };
            $cmd.find('.et_mapping_row').each(function () {
                var $row = $(this);
                var type = $row.find('.et_mapping_type').val();
                var source = $row.find('.et_mapping_source').val();
                if (source === '') { return; }
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
                // Mise à jour nom carte + rechargement complet
                var name = collected.eqLogicData.name;
                if (name) {
                    $('.eqLogicDisplayCard[data-eqLogic_id="' + ET.currentEqLogicId + '"] .name').text(name);
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
        var html = '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' + eq.id + '">'
            + '<i class="fas fa-exchange-alt fa-2x"></i><br>'
            + '<span class="name">' + (eq.name || '') + '</span>'
            + '</div>';
        $('#div_eqLogicList').append(html);
    }
};

$(function () { ET.init(); });
