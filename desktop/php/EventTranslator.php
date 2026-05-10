<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'EventTranslator');
$eqLogics = eqLogic::byType('EventTranslator');
?>

<div class="row row-overflow">

    <!-- Panneau gauche : liste des équipements -->
    <div class="col-xs-12 col-sm-3" id="div_leftThumbnailList">
        <legend>
            <i class="fas fa-exchange-alt"></i> {{EventTranslator}}
            <a class="btn btn-default btn-xs pull-right" id="bt_addEqLogic" title="{{Ajouter}}">
                <i class="fas fa-plus-circle"></i> {{Ajouter}}
            </a>
            <br><small class="text-muted">{{Mes équipements}}</small>
        </legend>
        <div id="div_eqLogicList">
            <?php foreach ($eqLogics as $eqLogic) { ?>
            <?php
            $srcType = $eqLogic->getConfiguration('source_eqType');
            $iconUrl = $srcType ? 'plugins/' . $srcType . '/plugin_info/' . $srcType . '.png' : '';
            ?>
            <div class="eqLogicDisplayCard cursor <?= ($eqLogic->getIsEnable() == 0) ? 'opacity05' : '' ?>"
                 data-eqLogic_id="<?= $eqLogic->getId() ?>">
                <?php if ($iconUrl): ?>
                <img src="<?= $iconUrl ?>" style="width:32px;height:32px;"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block';" />
                <i class="fas fa-exchange-alt fa-2x" style="display:none;"></i>
                <?php else: ?>
                <i class="fas fa-exchange-alt fa-2x"></i>
                <?php endif; ?>
                <br>
                <span class="name"><?= $eqLogic->getName() ?></span><br>
                <span class="hiddenAsCard"><?= ($eqLogic->getObject() != null) ? $eqLogic->getObject()->getName() : '' ?></span>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- Panneau droit : détail de l'équipement -->
    <div class="col-xs-12 col-sm-9" id="div_rightThumbnailList" style="display:none;">
        <div id="div_eqLogicDetail">

            <!-- Champs cachés -->
            <input type="hidden" id="in_eqLogicId" value="" />
            <input type="hidden" id="in_sourceEqLogicId" value="" />
            <input type="hidden" id="in_virtualEqLogicId" value="" />

            <!-- Barre d'actions + onglets sur la même ligne -->
            <div class="input-group pull-right" style="display:inline-flex;margin-bottom:5px;">
                <span class="input-group-btn">
                    <a class="btn btn-sm btn-success roundedLeft" id="bt_saveEqLogic">
                        <i class="fas fa-check-circle"></i> {{Sauvegarder}}
                    </a><a class="btn btn-sm btn-danger roundedRight" id="bt_removeEqLogic">
                        <i class="fas fa-minus-circle"></i> {{Supprimer}}
                    </a>
                </span>
            </div>

            <!-- Onglets -->
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#tab_general" role="tab" data-toggle="tab">
                        <i class="fas fa-home"></i> {{Général}}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tab_cmds" role="tab" data-toggle="tab">
                        <i class="fas fa-list-alt"></i> {{Commandes}}
                    </a>
                </li>
            </ul>

            <div class="tab-content" style="padding-top:15px;">

                <!-- Onglet Général -->
                <div role="tabpanel" class="tab-pane active" id="tab_general">
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom}}</label>
                            <div class="col-sm-5">
                                <input type="text" id="in_eqLogicName" class="form-control" placeholder="{{Nom de l'équipement}}" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Objet parent}}</label>
                            <div class="col-sm-4">
                                <select id="sel_objectId" class="form-control">
                                    <option value="">{{Aucun}}</option>
                                    <?php foreach (jeeObject::all() as $object) { ?>
                                    <option value="<?= $object->getId() ?>"><?= $object->getName() ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Activer}}</label>
                            <div class="col-sm-1">
                                <input type="checkbox" id="cb_isEnable" />
                            </div>
                            <label class="col-sm-2 control-label">{{Visible}}</label>
                            <div class="col-sm-1">
                                <input type="checkbox" id="cb_isVisible" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Équipement source}}</label>
                            <div class="col-sm-5">
                                <input type="text" id="in_sourceEqLogicName" class="form-control" readonly />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Équipement virtuel}}</label>
                            <div class="col-sm-5">
                                <input type="text" id="in_virtualEqLogicName" class="form-control" readonly />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onglet Commandes -->
                <div role="tabpanel" class="tab-pane" id="tab_cmds">
                    <a class="btn btn-default btn-sm" id="bt_addCmd">
                        <i class="fas fa-plus-circle"></i> {{Ajouter une commande}}
                    </a>
                    <br><br>
                    <div id="div_cmdList"></div>
                </div>

            </div><!-- /.tab-content -->
        </div><!-- /#div_eqLogicDetail -->
    </div><!-- /#div_rightThumbnailList -->

</div><!-- /.row -->

<!-- Template commande -->
<div id="tmpl_cmd" style="display:none;">
    <div class="et_cmd panel panel-default" data-cmd_id="" data-source_cmd_id="">
        <div class="panel-heading">
            <div class="row">
                <div class="col-sm-4">
                    <strong>{{Source :}}</strong>
                    <span class="et_cmd_source_human text-muted">-</span>
                </div>
                <div class="col-sm-3">
                    <label>{{Nom virtuel}}</label>
                    <input type="text" class="et_cmd_name form-control input-sm" placeholder="{{Nom de la commande virtuelle}}" />
                </div>
                <div class="col-sm-2">
                    <label>{{Type virtuel}}</label>
                    <select class="et_cmd_subtype form-control input-sm">
                        <option value="string">{{Texte}}</option>
                        <option value="numeric">{{Numérique}}</option>
                        <option value="binary">{{Binaire}}</option>
                    </select>
                </div>
                <div class="col-sm-4 text-right">
                    <a class="btn btn-xs btn-danger bt_removeCmd"><i class="fas fa-times"></i> {{Supprimer}}</a>
                </div>
            </div>
        </div>
        <div class="panel-body">
            <table class="table table-condensed et_mapping_table">
                <thead>
                    <tr>
                        <th style="width:25%">{{Valeur source}}</th>
                        <th style="width:20%">{{Type d'action}}</th>
                        <th style="width:45%">{{Cible}}</th>
                        <th style="width:10%"></th>
                    </tr>
                </thead>
                <tbody class="et_mapping_body"></tbody>
            </table>
            <a class="btn btn-xs btn-default bt_addMapping">
                <i class="fas fa-plus"></i> {{Ajouter une règle}}
            </a>
        </div>
    </div>
</div>

<!-- Template ligne de mapping -->
<table id="tmpl_mapping" style="display:none;"><tbody>
    <tr class="et_mapping_row">
        <td>
            <input type="text" class="form-control input-sm et_mapping_source" placeholder="{{Valeur source}}" />
        </td>
        <td>
            <select class="form-control input-sm et_mapping_type">
                <option value="value">{{Valeur}}</option>
                <option value="cmd">{{Commande}}</option>
                <option value="scenario">{{Scénario}}</option>
            </select>
        </td>
        <td>
            <div class="et_target_value">
                <input type="text" class="form-control input-sm et_mapping_value" placeholder="{{Valeur traduite}}" />
            </div>
            <div class="et_target_cmd" style="display:none;">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control et_mapping_cmd_human" readonly placeholder="{{Objet > Équipement > Commande}}" />
                    <input type="hidden" class="et_mapping_cmd_id" value="" />
                    <input type="hidden" class="et_mapping_cmd_options" value="" />
                    <span class="input-group-btn">
                        <a class="btn btn-default bt_selectActionCmd"><i class="fas fa-search"></i></a>
                    </span>
                </div>
            </div>
            <div class="et_target_scenario" style="display:none;">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control et_mapping_scenario_human" readonly placeholder="{{Scénario}}" />
                    <input type="hidden" class="et_mapping_scenario_id" value="" />
                    <span class="input-group-btn">
                        <a class="btn btn-default bt_selectScenario"><i class="fas fa-search"></i></a>
                    </span>
                </div>
            </div>
        </td>
        <td class="text-right">
            <a class="btn btn-xs btn-danger bt_removeMapping"><i class="fas fa-times"></i></a>
        </td>
    </tr>
</tbody></table>

<?php
include_file('desktop', 'EventTranslator', 'js', 'EventTranslator');
include_file('core', 'plugin.template', 'js');
?>
