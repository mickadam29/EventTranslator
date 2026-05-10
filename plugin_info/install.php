<?php
function EventTranslator_install() {
}

function EventTranslator_update() {
}

function EventTranslator_remove() {
    foreach (eqLogic::byType('EventTranslator') as $eqLogic) {
        $eqLogic->remove();
    }
    foreach (listener::byClass('EventTranslator') as $listener) {
        $listener->remove();
    }
}
