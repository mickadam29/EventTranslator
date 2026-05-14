<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'EventTranslator');
$eqLogics = eqLogic::byType('EventTranslator');
?>

<style>
.et_card {
    display: inline-block;
    width: 110px;
    min-height: 115px;
    text-align: center;
    border: 1px solid rgba(0,0,0,.125);
    border-radius: 4px;
    padding: 10px 5px;
    margin: 5px;
    vertical-align: top;
    overflow: hidden;
    word-break: break-all;
    background: rgba(255,255,255,.7);
}
.et_card:hover {
    border-color: #aaa;
    background: rgba(255,255,255,.9);
}
.et_card .et_icon {
    width: 60px;
    height: 60px;
    object-fit: contain;
    display: block;
    margin: 0 auto 5px;
}
.et_card.opacity05 { opacity: 0.5; }
</style>

<div class="row row-overflow">

    <!-- PAGE LISTE -->
    <div class="col-xs-12 eqLogicThumbnailDisplay">

        <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
            <div class="cursor logoPrimary" id="bt_addEqLogic">
                <i class="fas fa-plus-circle"></i>
                <br><span>{{Ajouter}}</span>
            </div>
            <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
                <i class="fas fa-wrench"></i>
                <br><span>{{Configuration}}</span>
            </div>
        </div>

        <legend><i class="fas fa-clone"></i> {{Mes équipements}}</legend>
        <div class="input-group" style="margin:5px;">
            <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">
            <div class="input-group-btn">
                <a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>
            </div>
        </div>
        <div class="eqLogicThumbnailContainer" id="div_eqLogicList">
            <?php if (count($eqLogics) == 0): ?>
            <span class="text-muted" id="span_noEqLogic" style="margin:10px;display:block;">
                {{Aucun équipement, cliquer sur "Ajouter" pour commencer}}
            </span>
            <?php else: ?>
            <?php foreach ($eqLogics as $eqLogic):
                $obj = $eqLogic->getObject();
                $label = $obj !== null
                    ? htmlspecialchars($obj->getName()) . ' / ' . htmlspecialchars($eqLogic->getName())
                    : htmlspecialchars($eqLogic->getName());
            ?>
            <div class="et_card cursor <?= ($eqLogic->getIsEnable() == 0) ? 'opacity05' : '' ?>"
                 data-eqLogic_id="<?= $eqLogic->getId() ?>">
                <img class="et_icon" src="<?= htmlspecialchars($eqLogic->getImage()) ?>"
                     onerror="this.onerror=null;this.src='core/img/no-image-plugin.png';" />
                <span class="name"><?= $label ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div><!-- /.eqLogicThumbnailDisplay -->

    <!-- PAGE DÉTAIL -->
    <div class="col-xs-12 eqLogic" style="display:none;">

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
                <a href="#" class="eqLogicAction" data-action="returnToThumbnailDisplay">
                    <i class="fas fa-arrow-circle-left"></i>
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
                                <?php foreach (jeeObject::buildTree(null, false) as $object): ?>
                                <option value="<?= $object->getId() ?>">
                                    <?= str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . htmlspecialchars($object->getName()) ?>
                                </option>
                                <?php endforeach; ?>
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

        </div><!-- /.tab-content -->
    </div><!-- /.eqLogic -->

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
