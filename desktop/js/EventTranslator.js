'use strict';

/* ============================================================
   EventTranslator — JS principal
   ============================================================ */

var ET = {
    currentEqLogicId: null,
    sourceEqLogicId: null,

    /* ---------- Initialisation ---------- */
    init: function () {
        ET._bindList();
        ET._bindDetail();
    },

    /* ---------- Liaison liste gauche ---------- */
    _bindList: function () {
        // Clic sur une carte équipement
        $(document).on('click', '.eqLogicDisplayCard', function () {
            var id = $(this).attr('data-eqLogic_id');
            ET.loadEqLogic(id);
        });

        // Bouton Ajouter
        $('#bt_addEqLogic').on('click', function () {
            jeedom.eqLogic.getSelectModal({}, function (result) {
                if (!result || !result.id) {
                    return;
                }
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
                        ET._appendCard(data.result);
                        ET.loadEqLogic(data.result.id);
                    },
                    error: function () {
                        $.fn.showAlert({ message: '{{Erreur lors de la création.}}', level: 'danger' });
                    }
                });
            });
        });
    },

    /* ---------- Liaison panneau détail ---------- */
    _bindDetail: function () {
        // Sauvegarde
        $('#bt_saveEqLogic').on('click', function () {
            ET.saveAll();
        });

        // Suppression équipement
        $('#bt_removeEqLogic').on('click', function () {
            bootbox.confirm('{{Êtes-vous sûr de vouloir supprimer cet équipement ?}}', function (result) {
                if (!result) {
                    return;
                }
                jeedom.eqLogic.remove({
                    id: ET.currentEqLogicId,
                    success: function () {
                        $('.eqLogicDisplayCard[data-eqLogic_id="' + ET.currentEqLogicId + '"]').remove();
                        $('#div_rightThumbnailList').hide();
                        ET.currentEqLogicId = null;
                    },
                    error: function (data) {
                        $.fn.showAlert({ message: data.result, level: 'danger' });
                    }
                });
            });
        });

        // Configuration avancée
        $('#bt_configureEqLogic').on('click', function () {
            jeedom.eqLogic.configure({ id: ET.currentEqLogicId });
        });

        // Ajouter une commande
        $('#bt_addCmd').on('click', function () {
            ET._openCmdSelector();
        });

        // Supprimer une commande (délégation)
        $(document).on('click', '.bt_removeCmd', function () {
            var $panel = $(this).closest('.et_cmd');
            bootbox.confirm('{{Supprimer cette commande ?}}', function (result) {
                if (!result) {
                    return;
                }
                $panel.remove();
            });
        });

        // Ajouter une règle de mapping
        $(document).on('click', '.bt_addMapping', function () {
            var $tbody = $(this).closest('.panel-body').find('.et_mapping_body');
            ET._addMappingRow($tbody, {});
        });

        // Supprimer une règle
        $(document).on('click', '.bt_removeMapping', function () {
            $(this).closest('.et_mapping_row').remove();
        });

        // Changement de type d'action
        $(document).on('change', '.et_mapping_type', function () {
            ET._switchTargetType($(this));
        });

        // Sélection commande action
        $(document).on('click', '.bt_selectActionCmd', function () {
            var $row = $(this).closest('.et_mapping_row');
            jeedom.cmd.getSelectModal({ cmd: { type: 'action' } }, function (result) {
                if (result && result.human) {
                    $row.find('.et_mapping_cmd_human').val(result.human);
                    $row.find('.et_mapping_cmd_id').val(result.cmd.id);
                }
            });
        });

        // Sélection scénario
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

    /* ---------- Chargement d'un équipement ---------- */
    loadEqLogic: function (id) {
        $.ajax({
            type: 'POST',
            url: 'core/ajax/eqLogic.ajax.php',
            data: { action: 'get', id: id },
            dataType: 'json',
            success: function (data) {
                if (data.state !== 'ok') {
                    $.fn.showAlert({ message: data.result, level: 'danger' });
                    return;
                }
                var eq = data.result;
                ET.currentEqLogicId = eq.id;
                ET.sourceEqLogicId = eq.configuration ? eq.configuration.source_eqLogic_id : '';

                // Remplir général
                $('#in_eqLogicId').val(eq.id);
                $('#in_eqLogicName').val(eq.name);
                $('#sel_objectId').val(eq.object_id || '');
                $('#cb_isEnable').prop('checked', eq.isEnable == 1);
                $('#cb_isVisible').prop('checked', eq.isVisible == 1);
                $('#in_sourceEqLogicId').val(eq.configuration ? eq.configuration.source_eqLogic_id : '');
                $('#in_virtualEqLogicId').val(eq.configuration ? eq.configuration.virtual_eqLogic_id : '');

                // Afficher nom source
                var srcId = eq.configuration ? eq.configuration.source_eqLogic_id : '';
                if (srcId) {
                    jeedom.eqLogic.get({
                        id: srcId,
                        success: function (srcData) {
                            if (srcData.state === 'ok') {
                                var src = srcData.result;
                                var human = (src.object_name ? src.object_name + ' > ' : '') + src.name;
                                $('#in_sourceEqLogicName').val(human);
                            }
                        }
                    });
                }

                // Afficher nom virtuel
                var virtId = eq.configuration ? eq.configuration.virtual_eqLogic_id : '';
                if (virtId) {
                    jeedom.eqLogic.get({
                        id: virtId,
                        success: function (virtData) {
                            if (virtData.state === 'ok') {
                                $('#in_virtualEqLogicName').val(virtData.result.name);
                            }
                        }
                    });
                }

                // Charger les commandes
                ET.loadCmds(id);

                // Marquer la carte active
                $('.eqLogicDisplayCard').removeClass('active');
                $('.eqLogicDisplayCard[data-eqLogic_id="' + id + '"]').addClass('active');

                $('#div_rightThumbnailList').show();
            }
        });
    },

    /* ---------- Chargement des commandes ---------- */
    loadCmds: function (eqLogicId) {
        $.ajax({
            type: 'POST',
            url: 'plugins/EventTranslator/core/ajax/EventTranslator.ajax.php',
            data: { action: 'getCmds', eqLogic_id: eqLogicId },
            dataType: 'json',
            success: function (data) {
                if (data.state !== 'ok') {
                    return;
                }
                $('#div_cmdList').empty();
                $.each(data.result, function (i, cmd) {
                    ET._renderCmd(cmd);
                });
            }
        });
    },

    /* ---------- Affichage d'un panneau commande ---------- */
    _renderCmd: function (cmd) {
        var $tmpl = $('#tmpl_cmd').clone().removeAttr('id').show();
        $tmpl.attr('data-cmd_id', cmd.id || '');
        $tmpl.attr('data-source_cmd_id', cmd.configuration ? cmd.configuration.source_cmd_id : '');
        $tmpl.find('.et_cmd_name').val(cmd.name || '');

        // Afficher le nom humain de la commande source
        var srcCmdId = cmd.configuration ? cmd.configuration.source_cmd_id : '';
        if (srcCmdId) {
            jeedom.cmd.get({
                id: srcCmdId,
                success: function (res) {
                    if (res.state === 'ok') {
                        var c = res.result;
                        var human = (c.eqLogic ? (c.eqLogic.object_name ? c.eqLogic.object_name + ' > ' : '') + c.eqLogic.name + ' > ' : '') + c.name;
                        $tmpl.find('.et_cmd_source_human').text(human);
                    }
                }
            });
        }

        // Ajouter les règles de mapping
        var mappings = (cmd.configuration && cmd.configuration.mappings) ? cmd.configuration.mappings : [];
        var $tbody = $tmpl.find('.et_mapping_body');
        $.each(mappings, function (i, mapping) {
            ET._addMappingRow($tbody, mapping);
        });

        $('#div_cmdList').append($tmpl);
    },

    /* ---------- Ajouter une ligne de mapping ---------- */
    _addMappingRow: function ($tbody, mapping) {
        var $row = $('#tmpl_mapping').find('tr').clone();
        $row.find('.et_mapping_source').val(mapping.source || '');

        var type = mapping.type || 'value';
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

    /* ---------- Afficher/masquer le bon champ cible ---------- */
    _switchTargetType: function ($select) {
        var type = $select.val();
        var $row = $select.closest('.et_mapping_row');
        $row.find('.et_target_value').toggle(type === 'value');
        $row.find('.et_target_cmd').toggle(type === 'cmd');
        $row.find('.et_target_scenario').toggle(type === 'scenario');
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
                if (!result || !result.human) {
                    return;
                }
                // Vérifier que ce cmd n'est pas déjà ajouté
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
                var cmd = {
                    id: '',
                    name: result.cmd.name || result.human.split(' > ').pop(),
                    configuration: {
                        source_cmd_id: result.cmd.id,
                        mappings: []
                    }
                };
                cmd.configuration.cmd_human = result.human;
                ET._renderCmd(cmd);
                // Mettre à jour le nom de la source sur le panneau
                $('#div_cmdList .et_cmd').last().find('.et_cmd_source_human').text(result.human);
                $('#div_cmdList .et_cmd').last().attr('data-source_cmd_id', result.cmd.id);
            }
        );
    },

    /* ---------- Collecte des données à sauvegarder ---------- */
    _collectData: function () {
        var eqLogicData = {
            name: $('#in_eqLogicName').val(),
            object_id: $('#sel_objectId').val(),
            isEnable: $('#cb_isEnable').is(':checked') ? 1 : 0,
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
                var mapping = {
                    source: $row.find('.et_mapping_source').val(),
                    type: type
                };
                if (type === 'value') {
                    mapping.value = $row.find('.et_mapping_value').val();
                } else if (type === 'cmd') {
                    mapping.cmd_id = $row.find('.et_mapping_cmd_id').val();
                    mapping.cmd_human = $row.find('.et_mapping_cmd_human').val();
                    mapping.cmd_options = $row.find('.et_mapping_cmd_options').val();
                } else if (type === 'scenario') {
                    mapping.scenario_id = $row.find('.et_mapping_scenario_id').val();
                    mapping.scenario_human = $row.find('.et_mapping_scenario_human').val();
                }
                if (mapping.source !== '') {
                    cmdData.mappings.push(mapping);
                }
            });
            if (cmdData.source_cmd_id) {
                cmdsData.push(cmdData);
            }
        });

        return { eqLogicData: eqLogicData, cmdsData: cmdsData };
    },

    /* ---------- Sauvegarde globale ---------- */
    saveAll: function () {
        var collected = ET._collectData();
        $.ajax({
            type: 'POST',
            url: 'plugins/EventTranslator/core/ajax/EventTranslator.ajax.php',
            data: {
                action: 'saveAll',
                eqLogic_id: ET.currentEqLogicId,
                eqLogic: JSON.stringify(collected.eqLogicData),
                cmds: JSON.stringify(collected.cmdsData)
            },
            dataType: 'json',
            success: function (data) {
                if (data.state !== 'ok') {
                    $.fn.showAlert({ message: data.result, level: 'danger' });
                    return;
                }
                $.fn.showAlert({ message: '{{Sauvegardé avec succès.}}', level: 'success' });
                // Mettre à jour le nom dans la carte
                var name = collected.eqLogicData.name;
                $('.eqLogicDisplayCard[data-eqLogic_id="' + ET.currentEqLogicId + '"] .name').text(name);
                // Recharger les commandes pour obtenir les IDs mis à jour
                ET.loadCmds(ET.currentEqLogicId);
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
            + '<span class="name">' + eq.name + '</span>'
            + '</div>';
        $('#div_eqLogicList').append(html);
    }
};

$(function () {
    ET.init();
});
