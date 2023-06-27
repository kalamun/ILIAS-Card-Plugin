<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

/**
 * Exporter class for the Card Plugin
 * @author Roberto Pasini <bonjour@kalamun.net>
 */
class ilCardImporter extends ilPageComponentPluginImporter
{
    public function init() : void
    {
    }

    /**
     * Import xml representation
     * @param string $a_entity
     * @param string $a_id
     * @param string $a_xml
     * @param ilImportMapping $a_mapping
     */
    public function importXmlRepresentation(
        string $a_entity,
        string $a_id,
        string $a_xml,
        ilImportMapping $a_mapping
    ) : void {
        global $DIC;

        /** @var ilComponentFactory $component_factory */
        $component_factory = $DIC["component.factory"];

        /** @var ilCardPlugin $plugin */
        $plugin = $component_factory->getPlugin("pctpc");

        $new_id = self::getPCMapping($a_id, $a_mapping);

        $properties = self::getPCProperties($new_id);
        $version = self::getPCVersion($new_id);

        // write the mapped file id to the properties
        if ($old_file_id = $properties['page_file']) {
            $new_file_id = $a_mapping->getMapping("Modules/File", 'file', $old_file_id);
            $properties['page_file'] = $new_file_id;
        }

        // save the data from the imported xml and write its id to the properties
        if ($additional_data_id = $properties['additional_data_id']) {
            $data = html_entity_decode(substr($a_xml, 6, -7));
            $id = $plugin->saveData($data);
            $properties['additional_data_id'] = $id;
        }

        self::setPCProperties($new_id, $properties);
        self::setPCVersion($new_id, $version);
    }
}