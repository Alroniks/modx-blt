<?php

// @todo: create somehow default basic class in builder, that can be included into the package. But need to be sure, that versions will be respected

if (!$object->xpdo && !$object->xpdo instanceof modX) {
    return true;
}

switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
}
