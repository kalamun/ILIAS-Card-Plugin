<?php

/**
 * Class ilCardImporter
 * @author Oskar Truffer <ot@studer-raimann.ch>
 */
class ilCardImporter extends ilPageComponentPluginImporter /* ilXmlImporter */
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
        global $DIC;

        /** @var ilComponentFactory $component_factory */
        // $component_factory = $DIC["component.factory"]; // ILIAS 8

        $new_id = self::getPCMapping($a_id, $a_mapping);

        $properties = self::getPCProperties($new_id);
        $version = self::getPCVersion($new_id);

        if ($old_file_id = $properties['ref_id']) {
            $new_file_id = false;
            foreach($a_mapping->getAllMappings() as $component) {
                $new_file_id = $component['refs'][$properties['ref_id']];
                if (!empty($new_file_id)) break;
            }
            if (!empty($new_file_id)) {
                $properties['ref_id'] = $new_file_id;
            }
        }

        if ($old_file_id = $properties['thumbnail']) {
            $new_file_id = $a_mapping->getMapping("Modules/File", 'file', $old_file_id);
            $properties['thumbnail'] = $new_file_id;
        }

        self::setPCProperties($new_id, $properties);
        self::setPCVersion($new_id, $version);
    }
}