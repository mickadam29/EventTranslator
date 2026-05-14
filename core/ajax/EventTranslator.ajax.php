<?php
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
    }

    $action = init('action');

    switch ($action) {
        case 'createFromSource':
            $sourceId = init('source_eqLogic_id');
            if (empty($sourceId)) {
                throw new Exception(__('ID équipement source manquant.', __FILE__));
            }
            $eqLogic = EventTranslator::createFromSource($sourceId);
            ajax::success(utils::o2a($eqLogic));
            break;

        case 'sourceCmds':
            $eqLogicId = init('eqLogic_id');
            $source = eqLogic::byId($eqLogicId);
            if (!is_object($source)) {
                throw new Exception(__('Équipement source introuvable.', __FILE__));
            }
            if ($source->getEqType_name() === 'EventTranslator') {
                throw new Exception(__('Un équipement EventTranslator ne peut pas être utilisé comme source.', __FILE__));
            }
            $cmds = [];
            foreach ($source->getCmd('info') as $cmd) {
                $cmds[] = ['id' => $cmd->getId(), 'name' => $cmd->getName()];
            }
            ajax::success([
                'eqLogic' => ['id' => $source->getId(), 'name' => $source->getHumanName()],
                'cmds'    => $cmds,
            ]);
            break;

        case 'remove':
            $eqLogicId = init('eqLogic_id');
            if (empty($eqLogicId)) {
                throw new Exception(__('ID équipement manquant.', __FILE__));
            }
            $eqLogic = eqLogic::byId($eqLogicId);
            if (!is_object($eqLogic) || $eqLogic->getEqType_name() !== 'EventTranslator') {
                throw new Exception(__('Équipement EventTranslator introuvable.', __FILE__));
            }
            $eqLogic->remove();
            ajax::success();
            break;

        case 'saveAll':
            $eqLogicId = init('eqLogic_id');
            if (empty($eqLogicId)) {
                throw new Exception(__('ID équipement manquant.', __FILE__));
            }
            $eqLogic = eqLogic::byId($eqLogicId);
            if (!is_object($eqLogic) || $eqLogic->getEqType_name() !== 'EventTranslator') {
                throw new Exception(__('Équipement EventTranslator introuvable.', __FILE__));
            }
            $eqLogicData = json_decode(init('eqLogic'), true) ?: [];
            $cmdsData    = json_decode(init('cmds'), true) ?: [];
            $eqLogic->saveWithCmds($eqLogicData, $cmdsData);
            ajax::success();
            break;

        case 'getCmdValue':
            $cmdId = init('cmd_id');
            $cmd = cmd::byId($cmdId);
            if (!is_object($cmd)) {
                throw new Exception(__('Commande introuvable.', __FILE__));
            }
            $value = ($cmd->getType() === 'info') ? $cmd->execCmd() : null;
            ajax::success([
                'value'       => (string)($value ?? ''),
                'collectDate' => $cmd->getCollectDate(),
            ]);
            break;

        default:
            throw new Exception(__('Action non reconnue : ', __FILE__) . $action);
    }
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
