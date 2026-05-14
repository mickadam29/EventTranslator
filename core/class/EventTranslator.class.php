<?php

class EventTranslator extends eqLogic {

    public static function rebuildAllListeners() {
        foreach (listener::byClass('EventTranslator') as $l) {
            $l->remove();
        }
        $watchedCmds = [];
        foreach (eqLogic::byType('EventTranslator') as $eqLogic) {
            if (!$eqLogic->getIsEnable()) {
                continue;
            }
            foreach ($eqLogic->getCmd('info') as $cmd) {
                $sourceCmdId = $cmd->getConfiguration('source_cmd_id');
                if (empty($sourceCmdId) || in_array($sourceCmdId, $watchedCmds)) {
                    continue;
                }
                $watchedCmds[] = $sourceCmdId;
                $listener = new listener();
                $listener->setClass('EventTranslator');
                $listener->setFunction('cmdChange');
                $listener->addEvent($sourceCmdId);
                $listener->save();
            }
        }
    }

    public static function cmdChange($options) {
        $sourceCmdId = $options['event_id'];
        $value       = $options['value'];
        foreach (eqLogic::byType('EventTranslator') as $eqLogic) {
            if (!$eqLogic->getIsEnable()) {
                continue;
            }
            foreach ($eqLogic->getCmd('info') as $cmd) {
                if ($cmd->getConfiguration('source_cmd_id') != $sourceCmdId) {
                    continue;
                }
                $mappings = $cmd->getConfiguration('mappings', []);
                foreach ($mappings as $mapping) {
                    if (!isset($mapping['source']) || (string)$mapping['source'] !== (string)$value) {
                        continue;
                    }
                    $type = $mapping['type'] ?? 'value';
                    switch ($type) {
                        case 'value':
                            $cmd->setConfiguration('repeatEventManagement', 'always');
                            $cmd->event($mapping['value'] ?? '');
                            break;
                        case 'cmd':
                            if (!empty($mapping['cmd_id'])) {
                                $actionCmd = cmd::byId($mapping['cmd_id']);
                                if (is_object($actionCmd)) {
                                    $cmdOptions = [];
                                    if (!empty($mapping['cmd_options'])) {
                                        $subType = $actionCmd->getSubType();
                                        $cmdOptions[$subType] = $mapping['cmd_options'];
                                    }
                                    $actionCmd->execCmd($cmdOptions);
                                }
                            }
                            break;
                        case 'scenario':
                            if (!empty($mapping['scenario_id'])) {
                                $scenario = scenario::byId($mapping['scenario_id']);
                                if (is_object($scenario)) {
                                    $scenario->launch('EventTranslator');
                                }
                            }
                            break;
                    }
                }
            }
        }
    }

    public function getImage() {
        $iconUrl = $this->getConfiguration('source_icon_url');
        if (!empty($iconUrl)) {
            return $iconUrl;
        }
        $srcType = $this->getConfiguration('source_eqType');
        if ($srcType) {
            $plugin = plugin::byId($srcType);
            if (is_object($plugin)) {
                return $plugin->getPathImgIcon();
            }
        }
        return parent::getImage();
    }

    public static function sourceIsUsed($_sourceId, $_excludeId = null) {
        foreach (eqLogic::byType('EventTranslator') as $eqLogic) {
            if ($_excludeId !== null && $eqLogic->getId() == $_excludeId) {
                continue;
            }
            if ($eqLogic->getConfiguration('source_eqLogic_id') == $_sourceId) {
                return true;
            }
        }
        return false;
    }

    public static function createFromSource($_sourceEqLogicId) {
        if (self::sourceIsUsed($_sourceEqLogicId)) {
            throw new Exception(__('Cet équipement source est déjà utilisé dans EventTranslator.', __FILE__));
        }
        $source = eqLogic::byId($_sourceEqLogicId);
        if (!is_object($source)) {
            throw new Exception(__('Équipement source introuvable.', __FILE__));
        }
        if ($source->getEqType_name() === 'EventTranslator') {
            throw new Exception(__('Un équipement EventTranslator ne peut pas être utilisé comme source.', __FILE__));
        }

        $eqLogic = new EventTranslator();
        $eqLogic->setName($source->getName() . '_et');
        $eqLogic->setLogicalId('ET_' . $_sourceEqLogicId);
        $eqLogic->setEqType_name('EventTranslator');
        $eqLogic->setIsEnable(1);
        $eqLogic->setIsVisible(1);
        $eqLogic->setObject_id($source->getObject_id());
        $eqLogic->setConfiguration('source_eqLogic_id', (string)$_sourceEqLogicId);
        $eqLogic->setConfiguration('source_eqLogic_human', $source->getHumanName());
        $eqLogic->setConfiguration('source_eqType', $source->getEqType_name());
        $srcPlugin = plugin::byId($source->getEqType_name());
        $eqLogic->setConfiguration('source_icon_url', is_object($srcPlugin) ? $srcPlugin->getPathImgIcon() : 'core/img/no-image-plugin.png');
        $eqLogic->save();

        return $eqLogic;
    }

    public function saveWithCmds($_eqLogicData, $_cmdsData) {
        if (isset($_eqLogicData['name']) && $_eqLogicData['name'] !== '') {
            $this->setName($_eqLogicData['name']);
        }
        if (array_key_exists('object_id', $_eqLogicData)) {
            $this->setObject_id($_eqLogicData['object_id']);
        }
        if (isset($_eqLogicData['isEnable'])) {
            $this->setIsEnable($_eqLogicData['isEnable']);
        }
        if (isset($_eqLogicData['isVisible'])) {
            $this->setIsVisible($_eqLogicData['isVisible']);
        }
        $this->save();

        $keptCmdIds = [];

        foreach ($_cmdsData as $cmdData) {
            $sourceCmdId = $cmdData['source_cmd_id'] ?? '';
            if (empty($sourceCmdId)) {
                continue;
            }
            $sourceCmd = cmd::byId($sourceCmdId);
            if (!is_object($sourceCmd)) {
                continue;
            }

            $cmd = null;
            if (!empty($cmdData['id'])) {
                $cmd = cmd::byId($cmdData['id']);
                if (is_object($cmd) && $cmd->getEqLogic_id() != $this->getId()) {
                    $cmd = null;
                }
            }
            $subtype = !empty($cmdData['subtype']) ? $cmdData['subtype'] : $sourceCmd->getSubType();

            if (!is_object($cmd)) {
                $cmd = new EventTranslatorCmd();
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId('ET_src_' . $sourceCmdId);
                $cmd->setType('info');
                $cmd->setConfiguration('source_cmd_id', (string)$sourceCmdId);
            }
            $cmd->setSubType($subtype);

            $cmdName = !empty($cmdData['name']) ? $cmdData['name'] : $sourceCmd->getName();
            $cmd->setName($cmdName);
            $cmd->setConfiguration('source_cmd_human', $sourceCmd->getHumanName());
            $cmd->setConfiguration('mappings', $cmdData['mappings'] ?? []);
            $cmd->setConfiguration('repeatEventManagement', 'always');
            $cmd->save();

            $keptCmdIds[] = $cmd->getId();
        }

        foreach ($this->getCmd('info') as $existing) {
            if (!in_array($existing->getId(), $keptCmdIds)) {
                $existing->remove();
            }
        }

        EventTranslator::rebuildAllListeners();
    }

    public function postSave() {
        EventTranslator::rebuildAllListeners();
    }

    public function postRemove() {
        EventTranslator::rebuildAllListeners();
    }
}

class EventTranslatorCmd extends cmd {

    public function execute($_options = []) {
        return $this->getValue();
    }
}
