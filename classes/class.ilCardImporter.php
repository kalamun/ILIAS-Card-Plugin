<?php

/**
 * Class ilCardImporter
 * @author Oskar Truffer <ot@studer-raimann.ch>
 */
class ilCardImporter extends ilXmlImporter
{
    /**
     * Import xml representation
     * @param string        entity
     * @param string        target release
     * @param string        id
     * @return    string        xml string
     */
    public function importXmlRepresentation(
        /* string */ $a_entity,
        /* string */ $a_id,
        /* string */ $a_xml,
        /* ilImportMapping */ $a_mapping
    ) /* : void */ {
      return false;
        $xml = simplexml_load_string($a_xml);
        $pl = new ilCardPlugin();
        $entity = new ilObjCard();
        $entity->setTitle((string) $xml->title . " " . $pl->txt("copy"));
        $entity->setDescription((string) $xml->description);
        $entity->setOnline((string) $xml->online);
        $entity->setImportId($a_id);
        $entity->create();
        $new_id = $entity->getId();
        $a_mapping->addMapping("Plugins/TestObjectRepository", "xtst", $a_id, $new_id);
    }
}