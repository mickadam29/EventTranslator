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
        $value = $options['value'];
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
                            $virtualCmdId = $cmd->getConfiguration('virtual_cmd_id');
                            if (!empty($virtualCmdId)) {
                                $virtualCmd = cmd::byId($virtualCmdId);
                                if (is_object($virtualCmd)) {
                                    $virtualCmd->event($mapping['value'] ?? '');
                                }
                            }
                            break;
                        case 'cmd':
                            if (!empty($mapping['cmd_id'])) {
                                $actionCmd = cmd::byId($mapping['cmd_id']);
                                if (is_object($actionCmd)) {
                                    $cmdOptions = [];
                                    if (isset($mapping['cmd_options']) && $mapping['cmd_options'] !== '') {
                                        $cmdOptions['value'] = $mapping['cmd_options'];
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
        self::loadVirtualClass();

        $virtual = new virtualEqLogic();
        $virtual->setName($source->getName() . '_virt');
        $virtual->setEqType_name('virtual');
        $virtual->setIsEnable(1);
        $virtual->setIsVisible(1);
        $virtual->setObject_id($source->getObject_id());
        $virtual->save();

        $eqLogic = new EventTranslator();
        $eqLogic->setName($source->getName());
        $eqLogic->setEqType_name('EventTranslator');
        $eqLogic->setIsEnable(1);
        $eqLogic->setIsVisible(1);
        $eqLogic->setObject_id($source->getObject_id());
        $eqLogic->setConfiguration('source_eqLogic_id', $_sourceEqLogicId);
        $eqLogic->setConfiguration('virtual_eqLogic_id', $virtual->getId());
        $eqLogic->save();

        return $eqLogic;
    }

    public function saveWithCmds($_eqLogicData, $_cmdsData) {
        self::loadVirtualClass();

        $this->setName($_eqLogicData['name'] ?? $this->getName());
        if (isset($_eqLogicData['object_id'])) {
            $this->setObject_id($_eqLogicData['object_id']);
        }
        if (isset($_eqLogicData['isEnable'])) {
            $this->setIsEnable($_eqLogicData['isEnable']);
        }
        if (isset($_eqLogicData['isVisible'])) {
            $this->setIsVisible($_eqLogicData['isVisible']);
        }
        $this->save();

        $virtualEqLogicId = $this->getConfiguration('virtual_eqLogic_id');
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
            }
            if (!is_object($cmd)) {
                $cmd = new EventTranslatorCmd();
                $cmd->setEqLogic_id($this->getId());
                $cmd->setType('info');
                $cmd->setSubType($sourceCmd->getSubType());
                $cmd->setConfiguration('source_cmd_id', $sourceCmdId);
            }
            $cmd->setName($cmdData['name'] ?? $sourceCmd->getName());
            $mappings = $cmdData['mappings'] ?? [];
            $cmd->setConfiguration('mappings', $mappings);
            $cmd->save();

            $virtualCmdId = $cmd->getConfiguration('virtual_cmd_id');
            $virtualCmd = null;
            if (!empty($virtualCmdId)) {
                $virtualCmd = cmd::byId($virtualCmdId);
            }
            if (!is_object($virtualCmd)) {
                $virtualCmd = new virtualCmd();
                $virtualCmd->setEqLogic_id($virtualEqLogicId);
                $virtualCmd->setType('info');
                $virtualCmd->setSubType($sourceCmd->getSubType());
            }
            $virtualCmd->setName($cmdData['name'] ?? $sourceCmd->getName());
            $virtualCmd->save();

            $cmd->setConfiguration('virtual_cmd_id', $virtualCmd->getId());
            $cmd->save();

            $keptCmdIds[] = $cmd->getId();
        }

        foreach ($this->getCmd('info') as $cmd) {
            if (!in_array($cmd->getId(), $keptCmdIds)) {
                $cmd->remove();
            }
        }

        EventTranslator::rebuildAllListeners();

        return utils::o2a($this);
    }

    public function postSave() {
        EventTranslator::rebuildAllListeners();
    }

    public function preRemove() {
        $virtualId = $this->getConfiguration('virtual_eqLogic_id');
        if (!empty($virtualId)) {
            $virtual = eqLogic::byId($virtualId);
            if (is_object($virtual)) {
                $virtual->remove();
            }
        }
    }

    public function postRemove() {
        EventTranslator::rebuildAllListeners();
    }

    private static function loadVirtualClass() {
        if (!class_exists('virtualEqLogic')) {
            include_file('core', 'virtual', 'class', 'virtual');
        }
        if (!class_exists('virtualEqLogic')) {
            throw new Exception(__('Le plugin Virtuel doit être installé et activé.', __FILE__));
        }
    }
}

class EventTranslatorCmd extends cmd {

    public function preRemove() {
        $virtualCmdId = $this->getConfiguration('virtual_cmd_id');
        if (!empty($virtualCmdId)) {
            $virtualCmd = cmd::byId($virtualCmdId);
            if (is_object($virtualCmd)) {
                $virtualCmd->remove();
            }
        }
    }

    public function execute($_options = []) {
        return $this->getValue();
    }
}
