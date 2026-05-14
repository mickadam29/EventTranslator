<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'EventTranslator');
$eqLogics = eqLogic::byType('EventTranslator');
?>

<div class="row row-overflow">

    <!-- VUE LISTE -->
    <div id="div_listView" class="col-xs-12">

        <div class="eqLogicThumbnailContainer" style="margin-bottom:10px;">
            <div class="eqLogicThumbnailDisplay logoPrimary cursor"
                 id="bt_addEqLogic" title="{{Ajouter un équipement}}">
                <i class="fas fa-plus-circle"></i>
                <br><span>{{Ajouter}}</span>
            </div>
            <div class="eqLogicThumbnailDisplay logoSecondary eqLogicAction cursor"
                 data-action="gotoPluginConf" title="{{Configuration}}">
                <i class="fas fa-wrench"></i>
                <br><span>{{Configuration}}</span>
            </div>
        </div>

        <div class="input-group" style="max-width:350px; margin-bottom:15px;">
            <span class="input-group-addon"><i class="fas fa-search"></i></span>
            <input type="text" id="in_searchEqLogic" class="form-control"
                   placeholder="{{Rechercher un équipement...}}" />
            <span class="input-group-btn">
                <button class="btn btn-default" type="button" id="bt_resetSearch" title="{{Effacer}}">
                    <i class="fas fa-times"></i>
                </button>
            </span>
        </div>

        <div class="eqLogicThumbnailContainer" id="div_eqLogicList">
            <?php foreach ($eqLogics as $eqLogic) {
                $iconUrl = $eqLogic->getConfiguration('source_icon_url');
                if (empty($iconUrl)) {
                    $srcType = $eqLogic->getConfiguration('source_eqType');
                    $srcPlugin = $srcType ? plugin::byId($srcType) : null;
                    $iconUrl = is_object($srcPlugin) ? $srcPlugin->getPathImgIcon() : 'core/img/no-image-plugin.png';
                }
            ?>
            <div class="eqLogicThumbnailDisplay cursor <?= ($eqLogic->getIsEnable() == 0) ? 'opacity05' : '' ?>"
                 data-eqLogic_id="<?= $eqLogic->getId() ?>">
                <img src="<?= $iconUrl ?>" style="width:48px;height:48px;" />
                <br>
                <span class="name"><?= $eqLogic->getName() ?></span>
                <?php if ($eqLogic->getObject() !== null): ?>
                <br><small class="text-muted"><?= $eqLogic->getObject()->getName() ?></small>
                <?php endif; ?>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- VUE DETAIL -->
    <div id="div_detailView" class="col-xs-12" style="display:none;">
        <div id="div_eqLogicDetail">

            <input type="hidden" id="in_eqLogicId" value="" />
            <input type="hidden" id="in_sourceEqLogicId" value="" />

            <div class="pull-right" style="margin-bottom:5px;">
                <a class="btn btn-sm btn-success roundedLeft" id="bt_saveEqLogic">
                    <i class="fas fa-check-circle"></i> {{Sauvegarder}}
                </a><a class="btn btn-sm btn-danger roundedRight" id="bt_removeEqLogic">
                    <i class="fas fa-minus-circle"></i> {{Supprimer}}
                </a>
            </div>

            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation">
                    <a href="#" id="bt_backToList">
                        <i class="fas fa-arrow-left"></i> {{Retour}}
                    </a>
                </li>
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

                <div role="tabpanel" class="tab-pane active" id="tab_general">
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom}}</label>
                            <div class="col-sm-5">
                                <input type="text" id="in_eqLogicName" class="form-control"
                                       placeholder="{{Nom de l'équipement}}" />
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
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="tab_cmds">
                    <a class="btn btn-default btn-sm" id="bt_addCmd">
                        <i class="fas fa-plus-circle"></i> {{Ajouter une commande}}
                    </a>
                    <br><br>
                    <div id="div_cmdList"></div>
                </div>

            </div>
        </div>
    </div>

</div>

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
                    <label>{{Nom}}</label>
                    <input type="text" class="et_cmd_name form-control input-sm"
                           placeholder="{{Nom de la commande}}" />
                </div>
                <div class="col-sm-2">
                    <label>{{Type}}</label>
                    <select class="et_cmd_subtype form-control input-sm">
                        <option value="string">{{Texte}}</option>
                        <option value="numeric">{{Numérique}}</option>
                        <option value="binary">{{Binaire}}</option>
                    </select>
                </div>
                <div class="col-sm-3 text-right">
                    <a class="btn btn-xs btn-danger bt_removeCmd">
                        <i class="fas fa-times"></i> {{Supprimer}}
                    </a>
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
            <div style="margin-top:12px; margin-bottom:15px; display:inline-flex; gap:6px;">
                <a class="btn btn-xs btn-default bt_addMapping">
                    <i class="fas fa-plus"></i> {{Ajouter une règle}}
                </a>
                <a class="btn btn-xs btn-success bt_learnMapping">
                    <i class="fas fa-headphones"></i> {{Apprendre}}
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Template ligne de mapping -->
<table id="tmpl_mapping" style="display:none;"><tbody>
    <tr class="et_mapping_row">
        <td>
            <input type="text" class="form-control input-sm et_mapping_source"
                   placeholder="{{Valeur source}}" />
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
                <input type="text" class="form-control input-sm et_mapping_value"
                       placeholder="{{Valeur traduite}}" />
            </div>
            <div class="et_target_cmd" style="display:none;">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control et_mapping_cmd_human" readonly
                           placeholder="{{Objet > Équipement > Commande}}" />
                    <input type="hidden" class="et_mapping_cmd_id" value="" />
                    <input type="hidden" class="et_mapping_cmd_options" value="" />
                    <span class="input-group-btn">
                        <a class="btn btn-default bt_selectActionCmd"><i class="fas fa-search"></i></a>
                    </span>
                </div>
            </div>
            <div class="et_target_scenario" style="display:none;">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control et_mapping_scenario_human" readonly
                           placeholder="{{Scénario}}" />
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
